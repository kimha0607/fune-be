<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClinicsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('clinics')->insert([
            [
                'name' => 'Fune',
                'address' => '401 Xuân Đỉnh',
            ],
        ]);
    }
}
