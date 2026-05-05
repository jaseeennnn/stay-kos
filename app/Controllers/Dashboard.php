<?php

namespace App\Controllers;

use App\Models\BookingModel;
use App\Models\RoomModel;
use App\Models\UserModel;

class Dashboard extends BaseController
{
    protected BookingModel $bookingModel;
    protected RoomModel    $roomModel;
    protected UserModel    $userModel;

    public function __construct()
    {
        $this->bookingModel = new BookingModel();
        $this->roomModel    = new RoomModel();
        $this->userModel    = new UserModel();
    }

    // ===============================
    // 🔹 ADMIN DASHBOARD
    // ===============================
    public function admin()
    {
        $cache = cache();

        $stats = $cache->remember('dashboard_stats', 120, function () {

            $bStats = $this->bookingModel->getDashboardStats();

            return [
                'totalRooms'       => $this->roomModel->countAll(),
                'totalUsers'       => $this->userModel
                    ->where('role', 'user')
                    ->countAllResults(),

                'totalBookings'    => $bStats['total'],
                'pendingBookings'  => $bStats['pending'],
                'approvedBookings' => $bStats['approved'],
                'rejectedBookings' => $bStats['rejected'],

                'pendingCount'     => $bStats['pending'],
            ];
        });

        return view('admin/dashboard', [
            ...$stats,
            'title'      => 'Dashboard Admin',
            'allowIndex' => 'noindex, nofollow',
        ]);
    }

    // ===============================
    // 🔹 USER DASHBOARD
    // ===============================
    public function user()
    {
        $userId = (int) session()->get('userId');

        $myBookings = $this->bookingModel->db->table('bookings b')
            ->select('b.*, r.room_name')
            ->join('rooms r', 'r.id = b.room_id')
            ->where('b.user_id', $userId)
            ->where('b.deleted_at', null)
            ->orderBy('b.created_at', 'desc')
            ->limit(5)
            ->get()
            ->getResultArray();

        $stats = $this->bookingModel->getDashboardStats($userId);

        return view('user/dashboard', [
            'title'      => 'Dashboard',
            'myBookings' => $myBookings,
            'stats'      => $stats,
            'allowIndex' => 'noindex, nofollow',
        ]);
    }
}