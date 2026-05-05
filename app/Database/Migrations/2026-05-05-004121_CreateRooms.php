<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRooms extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type'=>'INT','unsigned'=>true,'auto_increment'=>true],
            'room_name' => ['type'=>'VARCHAR','constraint'=>100],
            'price' => ['type'=>'DECIMAL','constraint'=>'12,2'],
            'facilities' => ['type'=>'TEXT','null'=>true],
            'status' => [
                'type'=>'ENUM',
                'constraint'=>['available','booked','maintenance'],
                'default'=>'available'
            ],
            'image' => ['type'=>'VARCHAR','constraint'=>255,'null'=>true],
            'locked_by' => ['type'=>'INT','unsigned'=>true,'null'=>true],
            'locked_at' => ['type'=>'DATETIME','null'=>true],
            'lock_token' => ['type'=>'VARCHAR','constraint'=>64,'null'=>true],
            'created_at DATETIME NULL',
            'updated_at DATETIME NULL',
            'deleted_at DATETIME NULL',
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('status');
        $this->forge->addKey('locked_by');
        $this->forge->createTable('rooms');
    }

    public function down()
    {
         $this->forge->dropTable('rooms');
    }
}
