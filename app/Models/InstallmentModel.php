<?php

namespace App\Models;

use CodeIgniter\Model;

class InstallmentModel extends Model
{
    protected $table         = 'installment_payments';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'payment_schedule_id', 'payment_proof',
        'amount_paid', 'status', 'verified_at', 'notes',
    ];

    /**
     * Buat jadwal cicilan bulanan setelah booking disetujui.
     */
    public static function generateSchedule(array $booking): void
    {
        $db           = \Config\Database::connect();
        $checkin      = new \DateTime($booking['checkin_date']);
        $monthlyPrice = bcdiv((string)$booking['total_price'], (string)$booking['duration'], 2);

        $rows = [];
        for ($i = 0; $i < (int)$booking['duration']; $i++) {
            $due = clone $checkin;
            $due->modify("+{$i} month");

            $rows[] = [
                'booking_id'   => $booking['id'],
                'month_number' => $i + 1,
                'due_date'     => $due->format('Y-m-d'),
                'amount'       => $monthlyPrice,
                'status'       => 'unpaid',
                'created_at'   => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s'),
            ];
        }

        $db->table('payment_schedules')->ignore(true)->insertBatch($rows);
    }

    public static function getScheduleForBooking(int $bookingId): array
    {
        return \Config\Database::connect()
            ->table('payment_schedules ps')
            ->select('ps.*, ip.id as payment_id, ip.payment_proof, ip.status as payment_status, ip.verified_at')
            ->join('installment_payments ip', 'ip.payment_schedule_id = ps.id', 'left')
            ->where('ps.booking_id', $bookingId)
            ->orderBy('ps.month_number', 'asc')
            ->get()
            ->getResultArray();
    }

    public static function markOverdue(): int
    {
        \Config\Database::connect()
            ->table('payment_schedules')
            ->where('status', 'unpaid')
            ->where('due_date <', date('Y-m-d'))
            ->update(['status' => 'overdue', 'updated_at' => date('Y-m-d H:i:s')]);

        return \Config\Database::connect()->affectedRows();
    }
}