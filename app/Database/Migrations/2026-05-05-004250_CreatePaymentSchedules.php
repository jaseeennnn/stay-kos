<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePaymentSchedules extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type'=>'INT','unsigned'=>true,'auto_increment'=>true],
            'booking_id' => ['type'=>'INT','unsigned'=>true],
            'month_number' => ['type'=>'TINYINT','unsigned'=>true],
            'due_date' => ['type'=>'DATE'],
            'amount' => ['type'=>'DECIMAL','constraint'=>'14,2'],
            'status' => [
                'type'=>'ENUM',
                'constraint'=>['unpaid','paid','overdue'],
                'default'=>'unpaid'
            ],
            'paid_at' => ['type'=>'DATETIME','null'=>true],
            'created_at DATETIME NULL',
            'updated_at DATETIME NULL',
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['booking_id','month_number']);

        $this->forge->addForeignKey('booking_id','bookings','id','CASCADE','CASCADE');

        $this->forge->createTable('payment_schedules');
    }

    public function down()
    {
        $this->forge->dropTable('payment_schedules');
    }
}
