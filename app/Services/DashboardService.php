<?php

namespace App\Services;

use Config\Database;

class DashboardService
{
    protected $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function getPendingData(): array
    {
        if (! session()->get('isLoggedIn')) {
            return [
                'pendingCount'        => 0,
                'pendingInstallments' => 0,
            ];
        }

        $role = session()->get('role');

        if ($role === 'admin') {
            $pendingCount = $this->db->table('bookings')
                ->where('status', 'pending')
                ->where('deleted_at', null)
                ->countAllResults();

            $pendingInstallments = $this->db->table('installment_payments')
                ->where('status', 'pending')
                ->countAllResults();
        } else {
            $userId = (int) session()->get('userId');

            $pendingCount = $this->db->table('bookings')
                ->where('user_id', $userId)
                ->where('status', 'pending')
                ->where('deleted_at', null)
                ->countAllResults();

            $pendingInstallments = 0;
        }

        return [
            'pendingCount'        => $pendingCount,
            'pendingInstallments' => $pendingInstallments,
        ];
    }
}