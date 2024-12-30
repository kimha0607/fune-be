<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->insert([
            [
                'email' => 'hakh@nal.vn',
                'name' => 'Kim Hoàng Hà',
                'phone' => '123456789',
                'address' => '123 Admin St',
                'role_id' => 3,
                'password' => Hash::make('@Hakim123'),
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'email' => 'tuyenpc@nal.vn',
                'name' => 'Phạm Công Tuyền',
                'phone' => '987654321',
                'address' => '456 User Ave',
                'role_id' => 2,
                'password' => Hash::make('@Hakim123'),
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'email' => 'khainq@nal.vn',
                'name' => 'Nguyễn Quang Khải',
                'phone' => '987654321',
                'address' => '456 User Ave',
                'role_id' => 1,
                'password' => Hash::make('@Hakim123'),
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
