<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Menggunakan 'nama' sesuai validasi di RoleController Anda
        Role::updateOrCreate(['nama' => 'HRD']);
        Role::updateOrCreate(['nama' => 'Kepala Outlet Gaharu']);
        Role::updateOrCreate(['nama' => 'Kepala Outlet Kejingga']);
        Role::updateOrCreate(['nama' => 'Bagian Produksi']);
        Role::updateOrCreate(['nama' => 'Kepala Gudang']);
    }
}