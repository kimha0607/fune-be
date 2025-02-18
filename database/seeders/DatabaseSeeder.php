<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(RolesTableSeeder::class);
        $this->call(ClinicsTableSeeder::class);
        $this->call(UsersTableSeeder::class);
        $this->call(ChildrenTableSeeder::class);
        $this->call(DoctorClinicSeeder::class);
    }
}
