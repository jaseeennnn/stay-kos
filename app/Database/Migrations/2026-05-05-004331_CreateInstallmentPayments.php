<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateInstallmentPayments extends Migration
{
    public function up()
    {
         $this->forge->addField([
            'id' => ['type'=>'INT','unsigned'=>true,'auto_increment'=>true],
            'payment_schedule_id' => ['type'=>'INT','unsigned'=>true,'unique'=>true],
            'payment_proof' => ['type'=>'VARCHAR','constraint'=>255],
            'amount_paid' => ['type'=>'DECIMAL','constraint'=>'14,2'],
            'status' => [
                'type'=>'ENUM',
                'constraint'=>['pending','verified','rejected'],
                'default'=>'pending'
            ],
            'verified_at' => ['type'=>'DATETIME','null'=>true],
            'notes' => ['type'=>'TEXT','null'=>true],
            'created_at DATETIME NULL',
            'updated_at DATETIME NULL',
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('payment_schedule_id','payment_schedules','id','CASCADE','CASCADE');

        $this->forge->createTable('installment_payments');
    }

    public function down()
    {
        $this->forge->dropTable('installment_payments');
    }
}
