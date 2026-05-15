<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MasterGudang;

class MasterGudangSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [

            [
                'nama' => 'Gudang Utama',
                'kategori' => 'Utama',
            ],

            [
                'nama' => 'Gudang Gaharu',
                'kategori' => 'Operasional',
            ],

            [
                'nama' => 'Gudang B2B',
                'kategori' => 'Produksi',
            ],

            [
                'nama' => 'Gudang KeJingga',
                'kategori' => 'Operasional',
            ],
        ];

        foreach ($data as $item) {

            MasterGudang::updateOrCreate(
                ['nama' => $item['nama']],
                $item
            );
        }
    }
}