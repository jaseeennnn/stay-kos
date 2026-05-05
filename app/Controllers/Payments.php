<?php

namespace App\Controllers;

use App\Models\PaymentModel;
use App\Models\BookingModel;

class Payments extends BaseController
{
    protected PaymentModel  $paymentModel;
    protected BookingModel  $bookingModel;

    public function __construct()
    {
        $this->paymentModel = new PaymentModel();
        $this->bookingModel = new BookingModel();
    }

    // ─────────────────────────────────────────────────────────────
    // ADMIN
    // ─────────────────────────────────────────────────────────────

    public function adminIndex()
    {
        return view('admin/payments/index', ['title' => 'Verifikasi Pembayaran']);
    }

    public function adminData()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(403);
        }

        $result = $this->paymentModel->getDataTable($this->request->getPost());

        foreach ($result['data'] as &$row) {
            $badge = match ($row['status']) {
                'pending'  => '<span class="badge bg-warning text-dark">Menunggu Verifikasi</span>',
                'verified' => '<span class="badge bg-success">Terverifikasi ✓</span>',
                'rejected' => '<span class="badge bg-danger">Ditolak</span>',
                default    => '<span class="badge bg-secondary">' . esc($row['status']) . '</span>',
            };

            $actions = '
                <button class="btn btn-sm btn-info btn-view-proof me-1"
                    data-id="' . $row['id'] . '"
                    data-booking="' . $row['booking_id'] . '"
                    title="Lihat bukti bayar">
                    <i class="bi bi-eye"></i>
                </button>';

            if ($row['status'] === 'pending') {
                $actions .= '
                <button class="btn btn-sm btn-success btn-verify me-1"
                    data-id="' . $row['id'] . '" data-action="verified">
                    <i class="bi bi-check-lg"></i> Verifikasi
                </button>
                <button class="btn btn-sm btn-danger btn-verify"
                    data-id="' . $row['id'] . '" data-action="rejected">
                    <i class="bi bi-x-lg"></i> Tolak
                </button>';
            } elseif ($row['status'] === 'verified') {
                $actions .= '
                <a href="/admin/export/invoice/' . $row['booking_id'] . '" target="_blank"
                   class="btn btn-sm btn-outline-primary" title="Download Invoice">
                    <i class="bi bi-file-earmark-pdf me-1"></i>Invoice
                </a>';
            }

            $row['amount'] = 'Rp ' . number_format((float)$row['amount'], 0, ',', '.');
            $row['status'] = $badge;
            $row['action'] = $actions;
        }

        return $this->response->setJSON([
            'draw'            => (int)($this->request->getPost('draw') ?? 1),
            'recordsTotal'    => $result['recordsTotal'],
            'recordsFiltered' => $result['recordsFiltered'],
            'data'            => $result['data'],
            'csrf'            => csrf_hash(),
        ]);
    }

    /**
     * Verifikasi / tolak pembayaran LUNAS.
     *
     * BUG FIX #1:
     * Sebelumnya, saat pembayaran diverifikasi, tabel payment_schedules
     * tidak pernah diupdate → semua cicilan selamanya tampil "unpaid".
     *
     * Fix: setelah verified, tandai SEMUA payment_schedules booking ini
     * menjadi 'paid' sekaligus buat/update record di installment_payments
     * agar riwayat cicilan konsisten.
     */
    public function verify(int $id)
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(403);
        }

        $status  = $this->request->getPost('status');
        $allowed = ['verified', 'rejected'];

        if (! in_array($status, $allowed)) {
            return $this->jsonResponse('error', 'Status tidak valid.');
        }

        $payment = $this->paymentModel->find($id);
        if (! $payment) {
            return $this->jsonResponse('error', 'Data pembayaran tidak ditemukan.', [], 404);
        }

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            // 1. Update tabel payments
            $this->paymentModel->update($id, [
                'status'      => $status,
                'verified_at' => $status === 'verified' ? date('Y-m-d H:i:s') : null,
            ]);

            if ($status === 'verified') {
                $now = date('Y-m-d H:i:s');

                // 2. Ambil semua payment_schedules untuk booking ini
                $schedules = $db->table('payment_schedules')
                    ->where('booking_id', $payment['booking_id'])
                    ->get()
                    ->getResultArray();

                foreach ($schedules as $schedule) {
                    // 3. Tandai schedule sebagai 'paid'
                    $db->table('payment_schedules')
                        ->where('id', $schedule['id'])
                        ->update([
                            'status'     => 'paid',
                            'paid_at'    => $now,
                            'updated_at' => $now,
                        ]);

                    // 4. Buat atau update installment_payments agar riwayat cicilan
                    //    menunjukkan setiap bulan sudah terverifikasi
                    $existing = $db->table('installment_payments')
                        ->where('payment_schedule_id', $schedule['id'])
                        ->get()
                        ->getRowArray();

                    if ($existing) {
                        // Sudah ada record (user pernah upload cicilan per bulan),
                        // update statusnya saja
                        $db->table('installment_payments')
                            ->where('payment_schedule_id', $schedule['id'])
                            ->update([
                                'status'      => 'verified',
                                'verified_at' => $now,
                                'updated_at'  => $now,
                                'notes'       => 'Dilunasi sekaligus via pembayaran penuh.',
                            ]);
                    } else {
                        // Belum ada record, buat otomatis dengan referensi bukti bayar lunas
                        $db->table('installment_payments')->insert([
                            'payment_schedule_id' => $schedule['id'],
                            'payment_proof'       => $payment['payment_proof'],
                            'amount_paid'         => $schedule['amount'],
                            'status'              => 'verified',
                            'verified_at'         => $now,
                            'notes'               => 'Dilunasi sekaligus via pembayaran penuh.',
                            'created_at'          => $now,
                            'updated_at'          => $now,
                        ]);
                    }
                }
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                return $this->jsonResponse('error', 'Gagal menyimpan. Coba lagi.');
            }

            $msg = $status === 'verified'
                ? 'Pembayaran diverifikasi. Semua cicilan ditandai lunas.'
                : 'Pembayaran ditolak.';

            return $this->jsonResponse('success', $msg);

        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', '[Payments::verify] ' . $e->getMessage());
            return $this->jsonResponse('error', 'Terjadi kesalahan sistem.');
        }
    }

    public function viewImage(int $id)
    {
        $payment = $this->paymentModel->find($id);
        if (! $payment) {
            return $this->response->setStatusCode(404)->setBody('Tidak ditemukan.');
        }

        $path = FCPATH . 'uploads/payments/' . $payment['payment_proof'];
        if (! file_exists($path)) {
            return $this->response->setStatusCode(404)->setBody('File tidak ditemukan.');
        }

        $mime = mime_content_type($path);
        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setBody(file_get_contents($path));
    }

    // ─────────────────────────────────────────────────────────────
    // USER
    // ─────────────────────────────────────────────────────────────

    public function userIndex()
    {
        $userId = session()->get('userId');
        $db     = \Config\Database::connect();

        // Booking yang sudah approved tapi belum ada bukti pembayaran sama sekali
        $unpaid = $db->table('bookings b')
            ->select('b.id as booking_id, r.room_name, b.total_price, b.checkin_date')
            ->join('rooms r',    'r.id = b.room_id')
            ->join('payments p', 'p.booking_id = b.id', 'left')
            ->where('b.user_id',     $userId)
            ->where('b.status',      'approved')
            ->where('b.deleted_at',  null)
            ->where('p.id',          null)   // belum ada record payment sama sekali
            ->get()
            ->getResultArray();

        return view('user/payments', [
            'title'  => 'Pembayaran',
            'unpaid' => $unpaid,
        ]);
    }

    public function userData()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(403);
        }

        $userId = session()->get('userId');
        $result = $this->paymentModel->getDataTable($this->request->getPost(), $userId);

        foreach ($result['data'] as &$row) {
            $badge = match ($row['status']) {
                'pending'  => '<span class="badge bg-warning text-dark">Menunggu Verifikasi</span>',
                'verified' => '<span class="badge bg-success">Lunas ✓</span>',
                'rejected' => '<span class="badge bg-danger">Ditolak — Upload Ulang</span>',
                default    => '<span class="badge bg-secondary">' . esc($row['status']) . '</span>',
            };

            // ── BUG FIX #2 ─────────────────────────────────────────────
            // Sebelumnya: hanya pending/rejected yang dapat tombol,
            // verified mendapat '' → tidak ada aksi apapun setelah lunas.
            // Fix: verified mendapat tombol Invoice PDF.
            // ──────────────────────────────────────────────────────────
            $action = match ($row['status']) {
                'verified' => '
                    <div class="d-flex gap-1">
                        <a href="/user/export/invoice/' . $row['booking_id'] . '" target="_blank"
                           class="btn btn-sm btn-outline-primary" title="Download Invoice">
                            <i class="bi bi-file-earmark-pdf me-1"></i>Invoice
                        </a>
                        <a href="/user/installments/' . $row['booking_id'] . '"
                           class="btn btn-sm btn-outline-success" title="Lihat jadwal cicilan">
                            <i class="bi bi-calendar2-check"></i>
                        </a>
                    </div>',
                'pending' => '
                    <button class="btn btn-sm btn-outline-secondary btn-upload"
                        data-id="' . $row['booking_id'] . '"
                        data-room="' . esc($row['room_name']) . '"
                        data-amount="' . $row['amount'] . '"
                        title="Upload ulang jika perlu">
                        <i class="bi bi-upload me-1"></i>Upload Ulang
                    </button>',
                'rejected' => '
                    <button class="btn btn-sm btn-danger btn-upload"
                        data-id="' . $row['booking_id'] . '"
                        data-room="' . esc($row['room_name']) . '"
                        data-amount="' . $row['amount'] . '"
                        title="Bukti ditolak, upload ulang">
                        <i class="bi bi-upload me-1"></i>Upload Ulang
                    </button>',
                default => '',
            };

            $row['amount'] = 'Rp ' . number_format((float)$row['amount'], 0, ',', '.');
            $row['status'] = $badge;
            $row['action'] = $action;
        }

        return $this->response->setJSON([
            'draw'            => (int)($this->request->getPost('draw') ?? 1),
            'recordsTotal'    => $result['recordsTotal'],
            'recordsFiltered' => $result['recordsFiltered'],
            'data'            => $result['data'],
            'csrf'            => csrf_hash(),
        ]);
    }

    public function upload(int $bookingId)
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(403);
        }

        $booking = $this->bookingModel
            ->where('id',      $bookingId)
            ->where('user_id', session()->get('userId'))
            ->where('status',  'approved')
            ->first();

        if (! $booking) {
            return $this->jsonResponse('error', 'Booking tidak valid.');
        }

        // Tolak upload jika sudah terverifikasi
        $existing = $this->paymentModel->where('booking_id', $bookingId)->first();
        if ($existing && $existing['status'] === 'verified') {
            return $this->jsonResponse('error', 'Pembayaran ini sudah terverifikasi dan tidak bisa diubah.');
        }

        $file = $this->request->getFile('payment_proof');
        if (! $file || ! $file->isValid()) {
            return $this->jsonResponse('error', 'File tidak valid.');
        }

        $validTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        if (! in_array($file->getMimeType(), $validTypes)) {
            return $this->jsonResponse('error', 'Hanya JPG dan PNG yang diperbolehkan.');
        }

        if ($file->getSize() / 1024 > 2048) {
            return $this->jsonResponse('error', 'Ukuran file maksimum 2 MB.');
        }

        $newName = $file->getRandomName();
        $file->move(FCPATH . 'uploads/payments', $newName);

        // Hapus file lama
        if ($existing) {
            $oldPath = FCPATH . 'uploads/payments/' . $existing['payment_proof'];
            if (file_exists($oldPath)) @unlink($oldPath);
            $this->paymentModel->where('booking_id', $bookingId)->delete();
        }

        $this->paymentModel->insert([
            'booking_id'    => $bookingId,
            'payment_proof' => $newName,
            'amount'        => $booking['total_price'],
            'status'        => 'pending',
        ]);

        return $this->jsonResponse('success', 'Bukti pembayaran berhasil diunggah. Menunggu verifikasi admin.');
    }
}