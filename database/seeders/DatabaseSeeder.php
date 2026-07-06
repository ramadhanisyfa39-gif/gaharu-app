<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            MasterGudangSeeder::class, // Menggunakan seeder gudang Anda
            UserSeeder::class,
            MasterGudangSeeder::class,
            CoaMasterSeeder::class, // Seeder untuk master COA
        ]);
    }
}