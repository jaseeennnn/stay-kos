<?php

namespace App\Models;

use CodeIgniter\Model;

class BookingModel extends Model
{
    protected $table          = 'bookings';
    protected $primaryKey     = 'id';
    protected $useTimestamps  = true;
    protected $useSoftDeletes = true;

    protected $allowedFields = [
        'user_id', 'room_id', 'checkin_date',
        'duration', 'total_price', 'status', 'notes',
    ];

    // ===============================
    // 🔹 Base Query (biar DRY)
    // ===============================
    private function baseDataTableQuery(?int $userId = null)
    {
        $builder = $this->db->table('bookings b')
            ->select('b.id, u.username, r.room_name, b.checkin_date, b.duration, b.total_price, b.status, b.created_at')
            ->join('users u', 'u.id = b.user_id')
            ->join('rooms r', 'r.id = b.room_id')
            ->where('b.deleted_at', null);

        if ($userId !== null) {
            $builder->where('b.user_id', $userId);
        }

        return $builder;
    }

    // ===============================
    // 🔹 DataTables
    // ===============================
    public function getDataTable(array $params, ?int $userId = null): array
    {
        $search   = $params['search']['value'] ?? '';
        $start    = (int)($params['start'] ?? 0);
        $length   = (int)($params['length'] ?? 10);
        $orderCol = (int)($params['order'][0]['column'] ?? 0);
        $orderDir = $params['order'][0]['dir'] ?? 'desc';

        $columns = ['b.id', 'u.username', 'r.room_name', 'b.checkin_date', 'b.status'];
        $col     = $columns[$orderCol] ?? 'b.id';

        $builder = $this->baseDataTableQuery($userId);

        // 🔍 search
        if ($search !== '') {
            $builder->groupStart()
                ->like('u.username', $search)
                ->orLike('r.room_name', $search)
                ->orLike('b.status', $search)
                ->groupEnd();
        }

        // 🔢 count total (tanpa search)
        $totalBuilder = $this->db->table('bookings')->where('deleted_at', null);
        if ($userId !== null) {
            $totalBuilder->where('user_id', $userId);
        }
        $total = $totalBuilder->countAllResults();

        // 🔢 count filtered (pakai clone)
        $filtered = (clone $builder)->countAllResults(false);

        // 📄 data
        $data = $builder->orderBy($col, $orderDir)
            ->limit($length, $start)
            ->get()
            ->getResultArray();

        return [
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => $data,
        ];
    }

    // ===============================
    // 🔹 Dashboard Stats (OPTIMIZED 🔥)
    // ===============================
    public function getDashboardStats(?int $userId = null): array
    {
        $builder = $this->db->table('bookings')
            ->select("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
            ")
            ->where('deleted_at', null);

        if ($userId !== null) {
            $builder->where('user_id', $userId);
        }

        $row = $builder->get()->getRowArray();

        return [
            'total'    => (int) ($row['total'] ?? 0),
            'pending'  => (int) ($row['pending'] ?? 0),
            'approved' => (int) ($row['approved'] ?? 0),
            'rejected' => (int) ($row['rejected'] ?? 0),
        ];
    }

    // ===============================
    // 🔹 Lock row (tetap sama)
    // ===============================
    public function lockForUpdate($id)
    {
        return $this->db->query(
            'SELECT * FROM bookings WHERE id = ? AND deleted_at IS NULL FOR UPDATE',
            [$id]
        )->getRowArray();
    }
}