<?php

namespace App\Controllers;

use App\Models\InstallmentModel;
use App\Models\BookingModel;

class Installments extends BaseController
{
    protected BookingModel $bookingModel;

    public function __construct()
    {
        $this->bookingModel = new BookingModel();
    }

    // ─────────────────────────────────────────────────────────────
    // USER
    // ─────────────────────────────────────────────────────────────

    public function schedule(int $bookingId)
    {
        $db = \Config\Database::connect();

        $booking = $db->table('bookings b')
            ->select('b.*, r.room_name')
            ->join('rooms r', 'r.id = b.room_id')
            ->where('b.id', $bookingId)
            ->where('b.user_id', session()->get('userId'))
            ->where('b.deleted_at', null)
            ->get()
            ->getRowArray();

        if (! $booking) {
            return redirect()->to('/user/bookings')->with('error', 'Booking tidak ditemukan.');
        }

        $schedule = InstallmentModel::getScheduleForBooking($bookingId);

        return view('user/installments', [
            'title'    => 'Jadwal Cicilan',
            'booking'  => $booking,
            'schedule' => $schedule,
        ]);
    }

    public function upload(int $scheduleId)
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(403);
        }

        $db = \Config\Database::connect();

        $schedule = $db->table('payment_schedules ps')
            ->select('ps.*, b.user_id')
            ->join('bookings b', 'b.id = ps.booking_id')
            ->where('ps.id', $scheduleId)
            ->where('b.user_id', session()->get('userId'))
            ->get()
            ->getRowArray();

        if (! $schedule) {
            return $this->jsonResponse('error', 'Jadwal tidak ditemukan.', [], 404);
        }

        if ($schedule['status'] === 'paid') {
            return $this->jsonResponse('error', 'Bulan ini sudah lunas.');
        }

        $file = $this->request->getFile('payment_proof');
        if (! $file || ! $file->isValid()) {
            return $this->jsonResponse('error', 'File tidak valid.');
        }

        $allowed = ['image/jpeg', 'image/jpg', 'image/png'];
        if (! in_array($file->getMimeType(), $allowed)) {
            return $this->jsonResponse('error', 'Hanya JPG dan PNG yang diperbolehkan.');
        }

        if ($file->getSize() / 1024 > 2048) {
            return $this->jsonResponse('error', 'Maksimum 2 MB.');
        }

        $dest = FCPATH . 'uploads/installments';
        if (! is_dir($dest)) {
            mkdir($dest, 0775, true);
        }

        $newName = $file->getRandomName();
        $file->move($dest, $newName);

        $existing = $db->table('installment_payments')
            ->where('payment_schedule_id', $scheduleId)
            ->get()->getRowArray();

        if ($existing) {
            $oldPath = $dest . '/' . $existing['payment_proof'];
            if (file_exists($oldPath)) @unlink($oldPath);

            $db->table('installment_payments')
                ->where('payment_schedule_id', $scheduleId)
                ->update([
                    'payment_proof' => $newName,
                    'amount_paid'   => $schedule['amount'],
                    'status'        => 'pending',
                    'verified_at'   => null,
                    'updated_at'    => date('Y-m-d H:i:s'),
                ]);
        } else {
            $db->table('installment_payments')->insert([
                'payment_schedule_id' => $scheduleId,
                'payment_proof'       => $newName,
                'amount_paid'         => $schedule['amount'],
                'status'              => 'pending',
                'created_at'          => date('Y-m-d H:i:s'),
                'updated_at'          => date('Y-m-d H:i:s'),
            ]);
        }

        return $this->jsonResponse('success', 'Bukti cicilan bulan ke-' . $schedule['month_number'] . ' berhasil diunggah!');
    }

    public function verify(int $installmentId)
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(403);
        }

        $status  = $this->request->getPost('status');
        $allowed = ['verified', 'rejected'];
        if (! in_array($status, $allowed)) {
            return $this->jsonResponse('error', 'Status tidak valid.');
        }

        $db      = \Config\Database::connect();
        $payment = $db->table('installment_payments')
            ->where('id', $installmentId)
            ->get()->getRowArray();

        if (! $payment) {
            return $this->jsonResponse('error', 'Data tidak ditemukan.', [], 404);
        }

        $db->table('installment_payments')
            ->where('id', $installmentId)
            ->update([
                'status'      => $status,
                'verified_at' => $status === 'verified' ? date('Y-m-d H:i:s') : null,
                'updated_at'  => date('Y-m-d H:i:s'),
            ]);

        if ($status === 'verified') {
            $db->table('payment_schedules')
                ->where('id', $payment['payment_schedule_id'])
                ->update([
                    'status'     => 'paid',
                    'paid_at'    => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
        } else {
            // Jika ditolak, kembalikan payment_schedule ke unpaid
            // agar penyewa bisa upload ulang
            $db->table('payment_schedules')
                ->where('id', $payment['payment_schedule_id'])
                ->where('status', 'paid') // jangan overwrite overdue
                ->update([
                    'status'     => 'unpaid',
                    'paid_at'    => null,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
        }

        return $this->jsonResponse('success', "Cicilan berhasil di-{$status}.");
    }

    // ─────────────────────────────────────────────────────────────
    // ADMIN
    // ─────────────────────────────────────────────────────────────

    public function adminIndex()
    {
        return view('admin/installments/index', ['title' => 'Manajemen Cicilan']);
    }

    /**
     * FIX: Tambah ip.payment_proof ke SELECT.
     * Tambah aksi per status:
     *   pending  → [Lihat Bukti] [Verifikasi] [Tolak]
     *   verified → [Lihat Bukti]  (sudah selesai)
     *   rejected → [Lihat Bukti] [Verifikasi Ulang]  (jika admin salah tolak)
     */
    public function adminData()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(403);
        }

        $params = $this->request->getPost();
        $search = $params['search']['value'] ?? '';
        $start  = (int)($params['start'] ?? 0);
        $length = (int)($params['length'] ?? 10);

        $db      = \Config\Database::connect();
        $builder = $db->table('installment_payments ip')
            ->select('ip.id, ip.payment_proof, ip.status, ip.verified_at, ip.amount_paid,
                      ip.notes,
                      u.username, r.room_name,
                      ps.id as schedule_id, ps.month_number, ps.due_date')
            ->join('payment_schedules ps', 'ps.id = ip.payment_schedule_id')
            ->join('bookings b',           'b.id  = ps.booking_id')
            ->join('users u',              'u.id  = b.user_id')
            ->join('rooms r',              'r.id  = b.room_id');

        if ($search !== '') {
            $builder->groupStart()
                ->like('u.username',  $search)
                ->orLike('r.room_name', $search)
                ->orLike('ip.status', $search)
                ->groupEnd();
        }

        $total    = $db->table('installment_payments')->countAllResults();
        $filtered = (clone $builder)->countAllResults(false);
        $data     = $builder->orderBy('ip.id', 'desc')->limit($length, $start)->get()->getResultArray();

        foreach ($data as &$row) {
            // ── Badge status ──────────────────────────────────────────
            $badge = match ($row['status']) {
                'pending'  => '<span class="badge bg-warning text-dark">Pending</span>',
                'verified' => '<span class="badge bg-success">Terverifikasi</span>',
                'rejected' => '<span class="badge bg-danger">Ditolak</span>',
                default    => '<span class="badge bg-secondary">' . esc($row['status']) . '</span>',
            };

            // ── Kolom Aksi berdasarkan status ─────────────────────────
            //
            // Sebelumnya: verified & rejected → '' (kosong total, tidak berguna)
            // Fix: semua status mendapat tombol "Lihat Bukti" untuk melihat foto
            //      cicilan yang diunggah penyewa.

            // Tombol Lihat Bukti — tersedia untuk semua status
            $proofUrl   = base_url('uploads/installments/' . $row['payment_proof']);
            $btnProof   = '
                <button class="btn btn-sm btn-info btn-view-proof"
                        data-src="' . $proofUrl . '"
                        data-label="' . esc($row['username']) . ' — ' . esc($row['room_name']) . ' Bln ' . $row['month_number'] . '"
                        title="Lihat bukti cicilan">
                    <i class="bi bi-eye"></i>
                </button>';

            $actionBtns = $btnProof . ' ';

            if ($row['status'] === 'pending') {
                $actionBtns .= '
                <button class="btn btn-sm btn-success btn-inst-verify"
                        data-id="' . $row['id'] . '" data-action="verified"
                        title="Verifikasi cicilan ini">
                    <i class="bi bi-check-lg me-1"></i>OK
                </button>
                <button class="btn btn-sm btn-danger btn-inst-verify ms-1"
                        data-id="' . $row['id'] . '" data-action="rejected"
                        title="Tolak cicilan ini">
                    <i class="bi bi-x-lg me-1"></i>Tolak
                </button>';

            } elseif ($row['status'] === 'rejected') {
                // Admin bisa verifikasi ulang jika sebelumnya salah tolak
                $actionBtns .= '
                <button class="btn btn-sm btn-outline-success btn-inst-verify ms-1"
                        data-id="' . $row['id'] . '" data-action="verified"
                        title="Batalkan penolakan — tandai sebagai terverifikasi">
                    <i class="bi bi-arrow-counterclockwise me-1"></i>Verifikasi Ulang
                </button>';
            }
            // verified → hanya tombol Lihat Bukti (tidak perlu aksi lain)

            // Catatan otomatis (misal dilunasi via pembayaran penuh)
            $notes = ! empty($row['notes'])
                ? '<br><small class="text-muted fst-italic">' . esc($row['notes']) . '</small>'
                : '';

            $row['amount_paid']   = 'Rp ' . number_format((float)$row['amount_paid'], 0, ',', '.');
            $row['status']        = $badge . $notes;
            $row['month_display'] = 'Bulan ke-' . $row['month_number'];
            $row['action']        = '<div class="d-flex align-items-center gap-1 flex-wrap">' . $actionBtns . '</div>';
        }

        return $this->response->setJSON([
            'draw'            => (int)($params['draw'] ?? 1),
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => $data,
            'csrf'            => csrf_hash(),
        ]);
    }
}