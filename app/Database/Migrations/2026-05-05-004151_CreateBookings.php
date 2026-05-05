<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBookings extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type'=>'INT','unsigned'=>true,'auto_increment'=>true],
            'user_id' => ['type'=>'INT','unsigned'=>true],
            'room_id' => ['type'=>'INT','unsigned'=>true],
            'checkin_date' => ['type'=>'DATE'],
            'duration' => ['type'=>'INT'],
            'total_price' => ['type'=>'DECIMAL','constraint'=>'14,2'],
            'status' => [
                'type'=>'ENUM',
                'constraint'=>['pending','approved','rejected'],
                'default'=>'pending'
            ],
            'notes' => ['type'=>'TEXT','null'=>true],
            'created_at DATETIME NULL',
            'updated_at DATETIME NULL',
            'deleted_at DATETIME NULL',
        ]);

        $this->forge->addKey('id', true);

        $this->forge->addForeignKey('user_id','users','id','CASCADE','CASCADE');
        $this->forge->addForeignKey('room_id','rooms','id','CASCADE','CASCADE');

        $this->forge->addKey('user_id');
        $this->forge->addKey('room_id');
        $this->forge->addKey('status');
        $this->forge->addKey('created_at');

        $this->forge->createTable('bookings');
    }

    public function down()
    {
        $this->forge->dropTable('bookings');
    }
}
