<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\MasterGudang; // Menggunakan MasterGudang sesuai seeder Anda
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ambil ID dari Role (menggunakan kolom nama)
        $idSuperAdmin = Role::where('nama', 'Super Admin')->first()?->id;
        $idHrd        = Role::where('nama', 'HRD')->first()?->id;
        $idGaharu     = Role::where('nama', 'Kepala Outlet Gaharu')->first()?->id;
        $idKejingga   = Role::where('nama', 'Kepala Outlet Kejingga')->first()?->id;
        $idProduksi   = Role::where('nama', 'Bagian Produksi')->first()?->id;
        $idGudang     = Role::where('nama', 'Kepala Gudang')->first()?->id;

        // 2. Ambil ID dari MasterGudang (menyesuaikan nama persis dari MasterGudangSeeder Anda)
        $idGudangUtama    = MasterGudang::where('nama', 'Gudang Utama')->first()?->id;
        $idGudangGaharu   = MasterGudang::where('nama', 'Gudang Gaharu')->first()?->id;
        $idGudangKejingga = MasterGudang::where('nama', 'Gudang KeJingga')->first()?->id; // J Kapital

        $passwordTesting = Hash::make('password123');

        // 3. Buat data user dummy (sesuai fillable: nama, username, password, role_id)
        // Pastikan Anda sudah menambahkan 'gudang_id' ke $fillable di User.php jika ingin disimpan ke database
        
        // Akun Super Admin
        User::updateOrCreate(
            ['username' => 'superadmin'],
            [
                'nama'      => 'Super Admin Utama',
                'password'  => Hash::make('admin123'),
                'role_id'   => $idSuperAdmin,
                'gudang_id' => null,
            ]
        );

        // Akun HRD
        User::updateOrCreate(
            ['username' => 'hrd'],
            [
                'nama'      => 'Dwi Novita',
                'password'  => $passwordTesting,
                'role_id'   => $idHrd,
                'gudang_id' => null,
            ]
        );

        // Akun Kepala Outlet Gaharu
        User::updateOrCreate(
            ['username' => 'gaharu'],
            [
                'nama'      => 'Adellia Syifa (Gaharu)',
                'password'  => $passwordTesting,
                'role_id'   => $idGaharu,
                'gudang_id' => $idGudangGaharu, // Terikat ke Gudang Gaharu
            ]
        );

        // Akun Kepala Outlet Kejingga
        User::updateOrCreate(
            ['username' => 'kejingga'],
            [
                'nama'      => 'Annisa Tahira (Kejingga)',
                'password'  => $passwordTesting,
                'role_id'   => $idKejingga,
                'gudang_id' => $idGudangKejingga, // Terikat ke Gudang KeJingga
            ]
        );

        // Akun Bagian Produksi
        User::updateOrCreate(
            ['username' => 'produksi'],
            [
                'nama'      => 'Eko Produksi',
                'password'  => $passwordTesting,
                'role_id'   => $idProduksi,
                'gudang_id' => null,
            ]
        );

        // Akun Kepala Gudang
        User::updateOrCreate(
            ['username' => 'gudang'],
            [
                'nama'      => 'Randi Logistik',
                'password'  => $passwordTesting,
                'role_id'   => $idGudang,
                'gudang_id' => $idGudangUtama, // Terikat ke Gudang Utama
            ]
        );
    }

}