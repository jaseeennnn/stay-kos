<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePayments extends Migration
{
    public function up()
    {
         $this->forge->addField([
            'id' => ['type'=>'INT','unsigned'=>true,'auto_increment'=>true],
            'booking_id' => ['type'=>'INT','unsigned'=>true,'unique'=>true],
            'payment_proof' => ['type'=>'VARCHAR','constraint'=>255],
            'amount' => ['type'=>'DECIMAL','constraint'=>'14,2'],
            'status' => [
                'type'=>'ENUM',
                'constraint'=>['pending','verified','rejected'],
                'default'=>'pending'
            ],
            'verified_at' => ['type'=>'DATETIME','null'=>true],
            'created_at DATETIME NULL',
            'updated_at DATETIME NULL',
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('booking_id','bookings','id','CASCADE','CASCADE');

        $this->forge->createTable('payments');
    }

    public function down()
    {
        $this->forge->dropTable('payments');
    }
}
