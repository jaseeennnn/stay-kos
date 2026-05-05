<?php

namespace App\Controllers;

use App\Models\BookingModel;
use App\Models\RoomModel;
use App\Models\InstallmentModel;

class Bookings extends BaseController
{
    protected BookingModel $bookingModel;
    protected RoomModel    $roomModel;

    public function __construct()
    {
        $this->bookingModel = new BookingModel();
        $this->roomModel    = new RoomModel();
    }

    public function adminIndex()
    {
        return view('admin/bookings/index', ['title' => 'Manajemen Booking']);
    }

    public function adminData()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(403);
        }

        $result = $this->bookingModel->getDataTable($this->request->getPost());

        foreach ($result['data'] as &$row) {
            $statusBadge = match ($row['status']) {
                'pending'  => '<span class="badge bg-warning text-dark">Pending</span>',
                'approved' => '<span class="badge bg-success">Disetujui</span>',
                'rejected' => '<span class="badge bg-danger">Ditolak</span>',
                default    => '<span class="badge bg-secondary">' . esc($row['status']) . '</span>',
            };

            $rawStatus = $row['status']; // simpan sebelum ditimpa badge

            $actions = '';
            if ($rawStatus === 'pending') {
                $actions = '
                    <div class="d-flex gap-1">
                        <button class="btn btn-sm btn-success btn-approve" data-id="' . $row['id'] . '">
                            <i class="bi bi-check-lg"></i> Setujui
                        </button>
                        <button class="btn btn-sm btn-danger btn-reject" data-id="' . $row['id'] . '">
                            <i class="bi bi-x-lg"></i> Tolak
                        </button>
                    </div>';
            } elseif ($rawStatus === 'approved') {
                $actions = '
                    <a href="/admin/export/invoice/' . $row['id'] . '" target="_blank"
                       class="btn btn-sm btn-outline-primary" title="Download Invoice PDF">
                        <i class="bi bi-file-earmark-pdf me-1"></i>Invoice
                    </a>';
            }

            $row['status']      = $statusBadge;
            $row['total_price'] = 'Rp ' . number_format((float)$row['total_price'], 0, ',', '.');
            $row['action']      = $actions;
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
     * Update status booking (approve/reject) + generate cicilan jika approved.
     *
     * PERBAIKAN: Ganti $this->bookingModel->lockForUpdate()->find($id)
     * (method tidak ada → Exception) dengan raw query SELECT ... FOR UPDATE
     * yang valid di dalam transaksi InnoDB.
     */
    public function updateStatus(int $id)
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(403);
        }

        $status  = $this->request->getPost('status');
        $allowed = ['approved', 'rejected'];

        if (! in_array($status, $allowed)) {
            return $this->jsonResponse('error', 'Status tidak valid.');
        }

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            // ── FIX: ganti lockForUpdate()->find() dengan raw SELECT FOR UPDATE ──
            $booking = $db->query(
                'SELECT * FROM bookings WHERE id = ? AND deleted_at IS NULL FOR UPDATE',
                [$id]
            )->getRowArray();

            if (! $booking) {
                $db->transRollback();
                return $this->jsonResponse('error', 'Booking tidak ditemukan.', [], 404);
            }

            if ($booking['status'] !== 'pending') {
                $db->transRollback();
                return $this->jsonResponse('error', 'Booking sudah diproses sebelumnya.');
            }

            $this->bookingModel->update($id, ['status' => $status]);

            if ($status === 'approved') {
                $this->roomModel->update($booking['room_id'], ['status' => 'booked']);
                InstallmentModel::generateSchedule($booking);
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                return $this->jsonResponse('error', 'Gagal memproses. Coba lagi.');
            }

            return $this->jsonResponse('success', 'Booking berhasil di-' . $status . '.');

        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', '[Bookings::updateStatus] ' . $e->getMessage());
            return $this->jsonResponse('error', 'Terjadi kesalahan sistem: ' . $e->getMessage());
        }
    }

    public function userIndex()
    {
        $rooms = $this->roomModel->getAvailableRooms();
        return view('user/bookings', [
            'rooms' => $rooms,
            'title' => 'Booking Saya',
        ]);
    }

    public function userData()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(403);
        }

        $userId = session()->get('userId');
        $result = $this->bookingModel->getDataTable($this->request->getPost(), $userId);

        foreach ($result['data'] as &$row) {
            $badge = match ($row['status']) {
                'pending'  => '<span class="badge bg-warning text-dark">Pending</span>',
                'approved' => '<span class="badge bg-success">Disetujui</span>',
                'rejected' => '<span class="badge bg-danger">Ditolak</span>',
                default    => '<span class="badge bg-secondary">' . esc($row['status']) . '</span>',
            };

            $cancelBtn = '';
            if ($row['status'] === 'pending') {
                $cancelBtn = '<button class="btn btn-sm btn-outline-danger btn-cancel"
                    data-id="' . $row['id'] . '"><i class="bi bi-x-circle"></i> Batal</button>';
            }
            if ($row['status'] === 'approved') {
                $cancelBtn = '
                    <div class="d-flex gap-1 flex-wrap">
                        <a href="/user/installments/' . $row['id'] . '" class="btn btn-sm btn-success">
                            <i class="bi bi-credit-card"></i> Bayar
                        </a>
                        <a href="/user/export/invoice/' . $row['id'] . '" target="_blank"
                           class="btn btn-sm btn-outline-primary" title="Download Invoice PDF">
                            <i class="bi bi-file-earmark-pdf"></i>
                        </a>
                    </div>';
            }

            $row['total_price'] = 'Rp ' . number_format((float)$row['total_price'], 0, ',', '.');
            $row['status']      = $badge;
            $row['action']      = $cancelBtn;
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
     * Buat booking baru dengan 3-layer race condition prevention:
     * Soft Lock → DB Transaction → SELECT FOR UPDATE
     */
    public function store()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(403);
        }

        $rules = [
            'room_id'      => 'required|is_natural_no_zero',
            'checkin_date' => 'required|valid_date',
            'duration'     => 'required|is_natural_no_zero|less_than_equal_to[24]',
        ];

        if (! $this->validate($rules)) {
            return $this->jsonResponse('error', 'Validasi gagal.', ['errors' => $this->validator->getErrors()]);
        }

        $roomId = (int)$this->request->getPost('room_id');
        $userId = (int)session()->get('userId');
        $dur    = (int)$this->request->getPost('duration');
        $db     = \Config\Database::connect();

        // Layer 1: Soft lock
        $lockToken = $this->roomModel->acquireLock($roomId, $userId);
        if ($lockToken === null) {
            return $this->jsonResponse(
                'error',
                'Kamar sedang dipesan pengguna lain. Coba beberapa saat lagi.'
            );
        }

        // Layer 2: DB Transaction
        $db->transStart();

        try {
            // Layer 3: SELECT FOR UPDATE
            $room = $db->query(
                'SELECT * FROM rooms WHERE id = ? AND status = ? AND deleted_at IS NULL FOR UPDATE',
                [$roomId, 'available']
            )->getRowArray();

            if (! $room) {
                $db->transRollback();
                $this->roomModel->releaseLock($roomId, $lockToken);
                return $this->jsonResponse('error', 'Kamar tidak tersedia atau sudah dipesan.');
            }

            $existingApproved = $db->table('bookings')
                ->where('room_id', $roomId)
                ->whereIn('status', ['pending', 'approved'])
                ->where('deleted_at', null)
                ->countAllResults();

            if ($existingApproved > 0) {
                $db->transRollback();
                $this->roomModel->releaseLock($roomId, $lockToken);
                return $this->jsonResponse('error', 'Kamar ini sudah ada yang memesan.');
            }

            $totalPrice = bcmul((string)$room['price'], (string)$dur, 2);

            $db->table('bookings')->insert([
                'user_id'      => $userId,
                'room_id'      => $roomId,
                'checkin_date' => $this->request->getPost('checkin_date'),
                'duration'     => $dur,
                'total_price'  => $totalPrice,
                'status'       => 'pending',
                'notes'        => $this->request->getPost('notes'),
                'created_at'   => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s'),
            ]);

            $db->transComplete();

            if ($db->transStatus() === false) {
                $this->roomModel->releaseLock($roomId, $lockToken);
                return $this->jsonResponse('error', 'Gagal menyimpan booking. Coba lagi.');
            }

            $this->roomModel->releaseLock($roomId, $lockToken);

            return $this->jsonResponse('success', 'Booking berhasil! Menunggu persetujuan admin.', [
                'total' => 'Rp ' . number_format((float)$totalPrice, 0, ',', '.'),
            ]);

        } catch (\Exception $e) {
            $db->transRollback();
            $this->roomModel->releaseLock($roomId, $lockToken);
            log_message('error', '[Bookings::store] ' . $e->getMessage());
            return $this->jsonResponse('error', 'Terjadi kesalahan sistem.');
        }
    }

    public function cancel(int $id)
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(403);
        }

        $booking = $this->bookingModel
            ->where('id', $id)
            ->where('user_id', session()->get('userId'))
            ->first();

        if (! $booking || $booking['status'] !== 'pending') {
            return $this->jsonResponse('error', 'Tidak dapat membatalkan booking ini.');
        }

        $this->bookingModel->delete($id);

        return $this->jsonResponse('success', 'Booking berhasil dibatalkan.');
    }
}