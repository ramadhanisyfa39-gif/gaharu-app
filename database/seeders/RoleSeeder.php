<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('roles')->insert([
            ['nama' => 'Super Admin'],
            ['nama' => 'Kepala Outlet'],
            ['nama' => 'Kepala Gudang'],
            ['nama' => 'Kepala Produksi'],
        ]);
    }
}
