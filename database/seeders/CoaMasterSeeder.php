<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CoaMasterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Matikan constraint foreign key dan kosongkan tabel transaksi serta COA
        Schema::disableForeignKeyConstraints();
        DB::table('journal_items')->truncate();
        DB::table('jurnal_pembelian')->truncate();
        DB::table('chart_of_accounts')->truncate();
        Schema::enableForeignKeyConstraints();

        // 2. Daftar Akun Induk (Parent Accounts)
        $parents = [
            ['kode' => '1100', 'nama' => 'kas di bank bri dan Bank', 'tipe' => 'Aset', 'saldo_normal' => 'debit'],
            ['kode' => '1200', 'nama' => 'Piutang Usaha', 'tipe' => 'Aset', 'saldo_normal' => 'debit'],
            ['kode' => '1300', 'nama' => 'Persediaan', 'tipe' => 'Aset', 'saldo_normal' => 'debit'],
            ['kode' => '1400', 'nama' => 'Aset Tetap (Milik Sendiri)', 'tipe' => 'Aset', 'saldo_normal' => 'debit'],
            ['kode' => '2100', 'nama' => 'Utang Usaha', 'tipe' => 'Liabilitas', 'saldo_normal' => 'kredit'],
            ['kode' => '2200', 'nama' => 'Utang Pajak & Gaji', 'tipe' => 'Liabilitas', 'saldo_normal' => 'kredit'],
            ['kode' => '3100', 'nama' => 'Ekuitas', 'tipe' => 'Ekuitas', 'saldo_normal' => 'kredit'],
            ['kode' => '4100', 'nama' => 'Penjualan', 'tipe' => 'Pendapatan', 'saldo_normal' => 'kredit'],
            ['kode' => '5100', 'nama' => 'Harga Pokok Penjualan', 'tipe' => 'Beban', 'saldo_normal' => 'debit'],
            ['kode' => '6100', 'nama' => 'Beban Gaji Karyawan', 'tipe' => 'Beban', 'saldo_normal' => 'debit'],
            ['kode' => '6200', 'nama' => 'Beban Utilitas Pemelihaaran & Perlengkapan', 'tipe' => 'Beban', 'saldo_normal' => 'debit'],
            ['kode' => '6300', 'nama' => 'Beban Pemasaran & Penyusutan Aset', 'tipe' => 'Beban', 'saldo_normal' => 'debit'],
            ['kode' => '8100', 'nama' => 'Pendapatan Non-Operasional', 'tipe' => 'Pendapatan', 'saldo_normal' => 'kredit'],
            ['kode' => '9100', 'nama' => 'Beban Non-Operasional', 'tipe' => 'Beban', 'saldo_normal' => 'debit'],
        ];

        $parentIds = [];
        foreach ($parents as $parent) {
            $id = DB::table('chart_of_accounts')->insertGetId(array_merge($parent, [
                'parent_id'  => null,
                'created_at' => now(),
                'updated_at' => now()
            ]));
            $parentIds[$parent['kode']] = $id;
        }

        // 3. Daftar Akun Anak (Child Accounts) beserta pemetaan parent_id nya
        $children = [
            // Kas & Bank (1100)
            ['kode' => '1101', 'nama' => 'kas di bank bri di Bank BRI', 'tipe' => 'Aset', 'saldo_normal' => 'debit', 'parent_kode' => '1100'],

            // Piutang Usaha (1200)
            ['kode' => '1201', 'nama' => 'Piutang Dagang Klien B2B', 'tipe' => 'Aset', 'saldo_normal' => 'debit', 'parent_kode' => '1200'],
            ['kode' => '1202', 'nama' => 'Uang Muka Pembelian', 'tipe' => 'Aset', 'saldo_normal' => 'debit', 'parent_kode' => '1200'],
            ['kode' => '1203', 'nama' => 'PPN Masukan', 'tipe' => 'Aset', 'saldo_normal' => 'debit', 'parent_kode' => '1200'],

            // Persediaan (1300)
            ['kode' => '1301', 'nama' => 'Persediaan Bahan Baku', 'tipe' => 'Aset', 'saldo_normal' => 'debit', 'parent_kode' => '1300'],
            ['kode' => '1302', 'nama' => 'Persediaan Perlengkapan Operasional & ATK', 'tipe' => 'Aset', 'saldo_normal' => 'debit', 'parent_kode' => '1300'],

            // Aset Tetap (1400)
            ['kode' => '1401', 'nama' => 'Tanah', 'tipe' => 'Aset', 'saldo_normal' => 'debit', 'parent_kode' => '1400'],
            ['kode' => '1402', 'nama' => 'Gedung & Bangunan Kafe', 'tipe' => 'Aset', 'saldo_normal' => 'debit', 'parent_kode' => '1400'],
            ['kode' => '1403', 'nama' => 'Akumulasi Penyusutan - Gedung & Bangunan', 'tipe' => 'Aset', 'saldo_normal' => 'kredit', 'parent_kode' => '1400'],
            ['kode' => '1404', 'nama' => 'Mesin Espresso & Peralatan Kafe', 'tipe' => 'Aset', 'saldo_normal' => 'debit', 'parent_kode' => '1400'],
            ['kode' => '1405', 'nama' => 'Akumulasi Penyusutan - Mesin & Peralatan Kafe', 'tipe' => 'Aset', 'saldo_normal' => 'kredit', 'parent_kode' => '1400'],
            ['kode' => '1406', 'nama' => 'Kendaraan Operasional', 'tipe' => 'Aset', 'saldo_normal' => 'debit', 'parent_kode' => '1400'],
            ['kode' => '1407', 'nama' => 'Akumulasi Penyusutan - Kendaraan Operasional', 'tipe' => 'Aset', 'saldo_normal' => 'kredit', 'parent_kode' => '1400'],
            ['kode' => '1408', 'nama' => 'Perlengkapan Kantor', 'tipe' => 'Aset', 'saldo_normal' => 'debit', 'parent_kode' => '1400'],
            ['kode' => '1409', 'nama' => 'Akumulasi Penyusutan - Inventaris Kantor & Interior', 'tipe' => 'Aset', 'saldo_normal' => 'kredit', 'parent_kode' => '1400'],

            // Utang Usaha (2100)
            ['kode' => '2101', 'nama' => 'Utang Dagang Supplier', 'tipe' => 'Liabilitas', 'saldo_normal' => 'kredit', 'parent_kode' => '2100'],
            ['kode' => '2102', 'nama' => 'Uang Muka Penjualan B2B', 'tipe' => 'Liabilitas', 'saldo_normal' => 'kredit', 'parent_kode' => '2100'],

            // Utang Pajak & Gaji (2200)
            ['kode' => '2201', 'nama' => 'PPN Keluaran', 'tipe' => 'Liabilitas', 'saldo_normal' => 'kredit', 'parent_kode' => '2200'],
            ['kode' => '2202', 'nama' => 'Utang Pajak Restoran PB1', 'tipe' => 'Liabilitas', 'saldo_normal' => 'kredit', 'parent_kode' => '2200'],
            ['kode' => '2203', 'nama' => 'Utang Gaji Karyawan', 'tipe' => 'Liabilitas', 'saldo_normal' => 'kredit', 'parent_kode' => '2200'],
            ['kode' => '2204', 'nama' => 'Utang THR', 'tipe' => 'Liabilitas', 'saldo_normal' => 'kredit', 'parent_kode' => '2200'],

            // Ekuitas (3100)
            ['kode' => '3101', 'nama' => 'Modal Disetor', 'tipe' => 'Ekuitas', 'saldo_normal' => 'kredit', 'parent_kode' => '3100'],
            ['kode' => '3102', 'nama' => 'Prive', 'tipe' => 'Ekuitas', 'saldo_normal' => 'kredit', 'parent_kode' => '3100'],
            ['kode' => '3103', 'nama' => 'Laba Ditahan', 'tipe' => 'Ekuitas', 'saldo_normal' => 'kredit', 'parent_kode' => '3100'],
            ['kode' => '3104', 'nama' => 'Laba/Rugi Periode Berjalan', 'tipe' => 'Ekuitas', 'saldo_normal' => 'kredit', 'parent_kode' => '3100'],

            // Penjualan (4100)
            ['kode' => '4101', 'nama' => 'Penjualan POS', 'tipe' => 'Pendapatan', 'saldo_normal' => 'kredit', 'parent_kode' => '4100'],
            ['kode' => '4102', 'nama' => 'Penjualan B2B', 'tipe' => 'Pendapatan', 'saldo_normal' => 'kredit', 'parent_kode' => '4100'],

            // Harga Pokok Penjualan (5100)
            ['kode' => '5101', 'nama' => 'HPP Penjualan POS', 'tipe' => 'Beban', 'saldo_normal' => 'debit', 'parent_kode' => '5100'],
            ['kode' => '5102', 'nama' => 'HPP Penjualan B2B', 'tipe' => 'Beban', 'saldo_normal' => 'debit', 'parent_kode' => '5100'],
            ['kode' => '5103', 'nama' => 'Selisih Stock Opname', 'tipe' => 'Beban', 'saldo_normal' => 'debit', 'parent_kode' => '5100'],

            // Beban Gaji Karyawan (6100)
            ['kode' => '6101', 'nama' => 'Beban Gaji Karyawan', 'tipe' => 'Beban', 'saldo_normal' => 'debit', 'parent_kode' => '6100'],
            ['kode' => '6102', 'nama' => 'Beban THR Karyawan', 'tipe' => 'Beban', 'saldo_normal' => 'debit', 'parent_kode' => '6100'],

            // Beban Utilitas Pemeliharaan & Perlengkapan (6200)
            ['kode' => '6201', 'nama' => 'Beban Listrik, Air, & Internet CV Gaharu + Resto', 'tipe' => 'Beban', 'saldo_normal' => 'debit', 'parent_kode' => '6200'],
            ['kode' => '6202', 'nama' => 'Beban Listrik, Air, & Internet Kejingga', 'tipe' => 'Beban', 'saldo_normal' => 'debit', 'parent_kode' => '6200'],
            ['kode' => '6203', 'nama' => 'Beban Pemeliharaan & Perbaikan Gedung', 'tipe' => 'Beban', 'saldo_normal' => 'debit', 'parent_kode' => '6200'],
            ['kode' => '6204', 'nama' => 'Beban Pemeliharaan Mesin Kopi & Peralatan Kafe', 'tipe' => 'Beban', 'saldo_normal' => 'debit', 'parent_kode' => '6200'],
            ['kode' => '6205', 'nama' => 'Beban Bahan Bakar & Pemeliharaan Kendaraan Operasional', 'tipe' => 'Beban', 'saldo_normal' => 'debit', 'parent_kode' => '6200'],
            ['kode' => '6206', 'nama' => 'Beban Alat Tulis Kantor (ATK) & Administrasi', 'tipe' => 'Beban', 'saldo_normal' => 'debit', 'parent_kode' => '6200'],
            ['kode' => '6207', 'nama' => 'Beban Perlengkapan & Kebersihan Habis Pakai', 'tipe' => 'Beban', 'saldo_normal' => 'debit', 'parent_kode' => '6200'],

            // Beban Pemasaran & Penyusutan Aset (6300)
            ['kode' => '6301', 'nama' => 'Biaya Iklan & Promosi B2B / Kafe', 'tipe' => 'Beban', 'saldo_normal' => 'debit', 'parent_kode' => '6300'],
            ['kode' => '6302', 'nama' => 'Potongan Diskon Penjualan Outlet 1', 'tipe' => 'Beban', 'saldo_normal' => 'debit', 'parent_kode' => '6300'],
            ['kode' => '6303', 'nama' => 'Potongan Diskon Penjualan Outlet 2', 'tipe' => 'Beban', 'saldo_normal' => 'debit', 'parent_kode' => '6300'],
            ['kode' => '6304', 'nama' => 'Beban Penyusutan - Gedung & Bangunan', 'tipe' => 'Beban', 'saldo_normal' => 'debit', 'parent_kode' => '6300'],
            ['kode' => '6305', 'nama' => 'Beban Penyusutan - Mesin Kopi & Peralatan Kafe', 'tipe' => 'Beban', 'saldo_normal' => 'debit', 'parent_kode' => '6300'],
            ['kode' => '6306', 'nama' => 'Beban Penyusutan - Kendaraan Operasional', 'tipe' => 'Beban', 'saldo_normal' => 'debit', 'parent_kode' => '6300'],
            ['kode' => '6307', 'nama' => 'Beban Penyusutan - Inventaris Kantor & Interior', 'tipe' => 'Beban', 'saldo_normal' => 'debit', 'parent_kode' => '6300'],

            // Pendapatan Non-Operasional (8100)
            ['kode' => '8101', 'nama' => 'Pendapatan Selisih kas di bank briir / Uang Kembalian Diikhlaskan', 'tipe' => 'Pendapatan', 'saldo_normal' => 'kredit', 'parent_kode' => '8100'],
            ['kode' => '8102', 'nama' => 'Pendapatan Jasa Giro / Bunga Bank', 'tipe' => 'Pendapatan', 'saldo_normal' => 'kredit', 'parent_kode' => '8100'],

            // Beban Non-Operasional (9100)
            ['kode' => '9101', 'nama' => 'Beban Selisih kas di bank briir / Pembulatan Kurang Kembalian', 'tipe' => 'Beban', 'saldo_normal' => 'debit', 'parent_kode' => '9100'],
            ['kode' => '9102', 'nama' => 'Beban Administrasi Bank & Pajak Bunga Bank', 'tipe' => 'Beban', 'saldo_normal' => 'debit', 'parent_kode' => '9100'],
        ];

        foreach ($children as $child) {
            $parentKode = $child['parent_kode'];
            unset($child['parent_kode']);
            
            DB::table('chart_of_accounts')->insert(array_merge($child, [
                'parent_id'  => $parentIds[$parentKode] ?? null,
                'created_at' => now(),
                'updated_at' => now()
            ]));
        }
    }
}
