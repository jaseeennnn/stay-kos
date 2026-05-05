<?php

namespace App\Models;

use CodeIgniter\Model;

class RoomModel extends Model
{
    protected $table          = 'rooms';
    protected $primaryKey     = 'id';
    protected $useTimestamps  = true;
    protected $useSoftDeletes = true;

    protected $allowedFields = [
        'room_name', 'price', 'facilities', 'status', 'image',
        'locked_by', 'locked_at', 'lock_token',
    ];

    protected $validationRules = [
        'room_name' => 'required|max_length[100]',
        'price'     => 'required|numeric',
        'status'    => 'in_list[available,booked,maintenance]',
    ];

    public function getDataTable(array $params): array
    {
        $search   = $params['search']['value'] ?? '';
        $start    = (int)($params['start'] ?? 0);
        $length   = (int)($params['length'] ?? 10);
        $orderCol = (int)($params['order'][0]['column'] ?? 0);
        $orderDir = $params['order'][0]['dir'] ?? 'asc';

        $columns = ['id', 'room_name', 'price', 'status', 'created_at'];
        $col = $columns[$orderCol] ?? 'id';

        $builder = $this->builder();

        if ($search !== '') {
            $builder->groupStart()
                ->like('room_name', $search)
                ->orLike('facilities', $search)
                ->orLike('status', $search)
                ->groupEnd();
        }

        $total    = $this->countAll();
        $filtered = (clone $builder)->countAllResults(false);

        $data = $builder
            ->orderBy($col, $orderDir)
            ->limit($length, $start)
            ->getWhere('deleted_at', NULL)
            ->getResultArray();

        return [
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => $data,
        ];
    }

    public function getAvailableRooms(): array
    {
        return $this->where('status', 'available')->findAll();
    }

    /**
     * Coba kunci kamar secara atomik (soft lock).
     * Mengembalikan token jika berhasil, null jika gagal.
     */
    public function acquireLock(int $roomId, int $userId, int $ttlSeconds = 300): ?string
    {
        $db  = \Config\Database::connect();
        $now = date('Y-m-d H:i:s');
        $exp = date('Y-m-d H:i:s', time() - $ttlSeconds);

        $token = bin2hex(random_bytes(16));

        $db->query("
            UPDATE rooms
            SET locked_by  = ?,
                locked_at  = ?,
                lock_token = ?
            WHERE id = ?
              AND status = 'available'
              AND (locked_by IS NULL OR locked_at < ?)
        ", [$userId, $now, $token, $roomId, $exp]);

        return $db->affectedRows() === 1 ? $token : null;
    }

    /**
     * Lepaskan kunci menggunakan token yang tepat.
     */
    public function releaseLock(int $roomId, string $token): bool
    {
        $db = \Config\Database::connect();
        $db->query("
            UPDATE rooms
            SET locked_by  = NULL,
                locked_at  = NULL,
                lock_token = NULL
            WHERE id = ? AND lock_token = ?
        ", [$roomId, $token]);

        return $db->affectedRows() === 1;
    }
}