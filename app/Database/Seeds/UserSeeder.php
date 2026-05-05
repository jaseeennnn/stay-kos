<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run()
    {
        $this->db->table('users')->insert([
            'username'   => 'admin',
            'email'      => 'admin@kos.local',
            'password'   => password_hash('admin123', PASSWORD_BCRYPT),
            'role'       => 'admin',
            'created_at' => date('Y-m-d H:i:s'),
        ], [
            'username'   => 'user',
            'email'      => 'user@kos.local',
            'password'   => password_hash('user123', PASSWORD_BCRYPT),
            'role'       => 'user',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
