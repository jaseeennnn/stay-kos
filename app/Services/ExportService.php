<?php

namespace App\Services;

use Config\Database;

class ExportService
{
    protected $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    // 🔹 BOOKINGS (JOIN)
    public function getBookings(?int $userId = null): array
    {
        $builder = $this->db->table('bookings b')
            ->select('b.id, u.username, r.room_name, b.checkin_date, b.duration, b.total_price, b.status, b.created_at')
            ->join('users u', 'u.id = b.user_id')
            ->join('rooms r', 'r.id = b.room_id')
            ->where('b.deleted_at', null)
            ->orderBy('b.created_at', 'desc');

        if ($userId !== null) {
            $builder->where('b.user_id', $userId);
        }

        return $builder->get()->getResultArray();
    }

    // 🔹 ROOMS
    public function getRooms(): array
    {
        return $this->db->table('rooms')
            ->where('deleted_at', null)
            ->orderBy('room_name')
            ->get()
            ->getResultArray();
    }

    // 🔹 SINGLE BOOKING
    public function getBookingDetail(int $id, ?int $userId = null): ?array
    {
        $builder = $this->db->table('bookings b')
            ->select('b.*, u.username, u.email, r.room_name, r.price, r.facilities')
            ->join('users u', 'u.id = b.user_id')
            ->join('rooms r', 'r.id = b.room_id')
            ->where('b.id', $id)
            ->where('b.deleted_at', null);

        if ($userId !== null) {
            $builder->where('b.user_id', $userId);
        }

        return $builder->get()->getRowArray();
    }

    // 🔹 IMPORT ROOMS (PAKAI TRANSACTION 🔥)
    public function importRooms(array $rows): array
    {
        $this->db->transStart();

        $success = 0;
        $errors  = [];

        foreach ($rows as $i => $row) {
            if ($i < 3) continue;

            $roomName   = trim($row['A'] ?? '');
            $price      = (float) $row['B'];
            $facilities = trim($row['C'] ?? '');
            $status     = strtolower(trim($row['D'] ?? 'available'));

            if (empty($roomName) || $price <= 0) {
                $errors[] = "Baris {$i}: data tidak valid";
                continue;
            }

            if (! in_array($status, ['available', 'booked', 'maintenance'], true)) {
                $status = 'available';
            }

            $this->db->table('rooms')->insert([
                'room_name'  => $roomName,
                'price'      => $price,
                'facilities' => $facilities,
                'status'     => $status,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            $success++;
        }

        $this->db->transComplete();

        return [
            'success' => $success,
            'errors'  => $errors,
            'status'  => $this->db->transStatus(),
        ];
    }
}