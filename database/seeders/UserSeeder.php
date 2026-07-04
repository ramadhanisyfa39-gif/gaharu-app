<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Menyuntikkan data user admin default ke database
        User::updateOrInsert(
            ['username' => 'admin'], // Kondisi untuk mencari user dengan username 'admin'
            [
                'nama' => 'Administrator',
                'username' => 'admin',
                'password' => Hash::make('make123'), // Password di-hash sebelum disimpan
                'role_id' => 1, // Role ID untuk Super Admin
            ]
        );
    }
}
