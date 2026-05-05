<?php

namespace App\Models;

use CodeIgniter\Model;

class PaymentModel extends Model
{
    protected $table         = 'payments';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'booking_id', 'payment_proof', 'amount', 'status', 'verified_at',
    ];

    public function getDataTable(array $params, ?int $userId = null): array
    {
        $search   = $params['search']['value'] ?? '';
        $start    = (int)($params['start'] ?? 0);
        $length   = (int)($params['length'] ?? 10);

        $db      = \Config\Database::connect();
        $builder = $db->table('payments p')
            ->select('p.id, u.username, r.room_name, p.amount, p.status, p.payment_proof, p.created_at, b.id as booking_id')
            ->join('bookings b', 'b.id = p.booking_id')
            ->join('users u',    'u.id = b.user_id')
            ->join('rooms r',    'r.id = b.room_id');

        if ($userId !== null) {
            $builder->where('b.user_id', $userId);
        }

        if ($search !== '') {
            $builder->groupStart()
                ->like('u.username', $search)
                ->orLike('p.status', $search)
                ->groupEnd();
        }

        $countBuilder = $db->table('payments p')
            ->join('bookings b', 'b.id = p.booking_id');
        if ($userId !== null) {
            $countBuilder->where('b.user_id', $userId);
        }
        $total    = $countBuilder->countAllResults();
        $filtered = (clone $builder)->countAllResults(false);

        $data = $builder->orderBy('p.created_at', 'desc')
            ->limit($length, $start)
            ->get()
            ->getResultArray();

        return [
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => $data,
        ];
    }
}