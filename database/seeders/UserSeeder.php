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
        User::create([
            'nama' => 'Administrator',
            'username' => 'admin',
            'password' => Hash::make('password123'),
            'role_id' => 1,
        ]);
    }
}
