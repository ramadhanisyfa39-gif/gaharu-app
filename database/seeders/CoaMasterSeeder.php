<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CoaMasterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Daftar Akun Induk Utama yang dipatenkan oleh sistem
        $parentAccounts = [
            // 1. KELOMPOK ASET (Debit)
            ['id' => 1, 'kode' => '11000', 'nama' => 'Kas dan Bank', 'tipe' => 'Aset', 'saldo_normal' => 'debit', 'parent_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'kode' => '1103', 'nama' => 'Piutang Usaha', 'tipe' => 'Aset', 'saldo_normal' => 'debit', 'parent_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'kode' => '1104', 'nama' => 'Persediaan', 'tipe' => 'Aset', 'saldo_normal' => 'debit', 'parent_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'kode' => '1201', 'nama' => 'Aset Tetap', 'tipe' => 'Aset', 'saldo_normal' => 'debit', 'parent_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'kode' => '1202', 'nama' => 'Akumulasi Penyusutan', 'tipe' => 'Aset', 'saldo_normal' => 'kredit', 'parent_id' => null, 'created_at' => now(), 'updated_at' => now()],

            // 2. KELOMPOK LIABILITAS / KEWAJIBAN (Kredit)
            ['id' => 6, 'kode' => '2101', 'nama' => 'Hutang Usaha', 'tipe' => 'Liabilitas', 'saldo_normal' => 'kredit', 'parent_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'kode' => '2104', 'nama' => 'Kewajiban Lancar Lainnya', 'tipe' => 'Liabilitas', 'saldo_normal' => 'kredit', 'parent_id' => null, 'created_at' => now(), 'updated_at' => now()],

            // 3. KELOMPOK EKUITAS / MODAL (Kredit)
            ['id' => 8, 'kode' => '3000', 'nama' => 'Modal', 'tipe' => 'Ekuitas', 'saldo_normal' => 'kredit', 'parent_id' => null, 'created_at' => now(), 'updated_at' => now()],

            // 4. KELOMPOK PENDAPATAN & BEBAN
            ['id' => 9, 'kode' => '4101', 'nama' => 'Penjualan', 'tipe' => 'Pendapatan', 'saldo_normal' => 'kredit', 'parent_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 10, 'kode' => '51000', 'nama' => 'Beban Pokok Penjualan', 'tipe' => 'Beban', 'saldo_normal' => 'debit', 'parent_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 11, 'kode' => '6101', 'nama' => 'Beban Operasional', 'tipe' => 'Beban', 'saldo_normal' => 'debit', 'parent_id' => null, 'created_at' => now(), 'updated_at' => now()],
        ];

        // Gunakan insertOrUpdate atau truncate agar data tidak double saat dijalankan ulang
        foreach ($parentAccounts as $account) {
            DB::table('chart_of_accounts')->updateOrInsert(
                ['kode' => $account['kode']], // Kunci pengecekan agar tidak duplikat
                $account
            );
        }
    }
}
