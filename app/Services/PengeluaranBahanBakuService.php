<?php

namespace App\Services;

use App\Models\PengeluaranBahanBaku;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;
use App\Models\MasterGudang;

class PengeluaranBahanBakuService
{
    public function __construct(
        protected StockService $stockService
    ) {
    }

    /**
     * APPROVE PENGELUARAN
     */
    public function approve(
        PengeluaranBahanBaku $pengeluaran,
        int $userId
    ) {

        return DB::transaction(function () use (
            $pengeluaran,
            $userId
        ) {

            /*
            |--------------------------------------------------------------------------
            | VALIDASI STATUS
            |--------------------------------------------------------------------------
            */

            if ($pengeluaran->status == 'disetujui') {
                throw new \Exception(
                    'Pengeluaran sudah disetujui'
                );
            }
            $gudangUtama = MasterGudang::where(
                'nama',
                'Gudang Utama'
            )->firstOrFail();
            /*
            |--------------------------------------------------------------------------
            | LOOP DETAIL BARANG
            |--------------------------------------------------------------------------
            */

            foreach ($pengeluaran->details as $detail) {

                /*
                |--------------------------------------------------------------------------
                | KURANGI STOK
                |--------------------------------------------------------------------------
                */

                $this->stockService->stockOut([

                    'barang_id'
                        => $detail->barang_id,

                    'gudang_asal_id'
                        => $gudangUtama->id, // Gudang Utama

                    'qty'
                        => $detail->qty,

                    'source_type'
                        => 'pengeluaran_bahan_baku',

                    'source_id'
                        => $pengeluaran->id,

                    'user_id'
                        => $userId,
                ]);
            }
             /*
                |--------------------------------------------------------------------------
                | PENAMBAHAN STOK
                |--------------------------------------------------------------------------
                */
            $this->stockService->stockIn([

                'barang_id'
                    => $detail->barang_id,

                'gudang_tujuan_id'
                    => $pengeluaran->gudang_id,

                'qty'
                    => $detail->qty,

                'source_type'
                    => 'pengeluaran_bahan_baku',

                'source_id'
                    => $pengeluaran->id,

                'user_id'
                    => $userId,
            ]);

            /*
            |--------------------------------------------------------------------------
            | UPDATE STATUS
            |--------------------------------------------------------------------------
            */

            $pengeluaran->update([

                'status' => 'disetujui',

                'approved_by' => $userId,

                'approved_at' => now(),
            ]);

            return $pengeluaran;
        });
    }
}