<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ChildrenTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('children')->insert([
            [
                'name' => 'Kim Hoàng Hoàng',
                'dob' => '2020-05-05',
                'user_id' => 1,
                'gender' => 'male',
            ],
        ]);
    }
}
