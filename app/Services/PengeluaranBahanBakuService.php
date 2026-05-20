<?php

namespace App\Services;

use App\Models\PengeluaranBahanBaku;
use App\Models\MasterGudang;

use App\Services\StockService;
use App\Services\FifoService;

use Illuminate\Support\Facades\DB;

class PengeluaranBahanBakuService
{
    /*
    |--------------------------------------------------------------------------
    | CONSTRUCTOR
    |--------------------------------------------------------------------------
    */

    public function __construct(

        protected StockService $stockService,

        protected FifoService $fifoService

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

            /*
            |--------------------------------------------------------------------------
            | GUDANG UTAMA
            |--------------------------------------------------------------------------
            */

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
                | FIFO CONSUME
                |--------------------------------------------------------------------------
                |
                | Mengurangi batch FIFO tertua.
                |
                */


                /*
                |--------------------------------------------------------------------------
                | OPTIONAL DEBUG FIFO
                |--------------------------------------------------------------------------
                |
                | Bisa dipakai nanti untuk:
                | - histori FIFO
                | - costing
                | - audit batch
                |
                */

                // dd($fifoResult);

                /*
                |--------------------------------------------------------------------------
                | KURANGI STOK SUMMARY
                |--------------------------------------------------------------------------
                */

                $this->stockService->stockOut([

                    'barang_id'
                        => $detail->barang_id,

                    'gudang_asal_id'
                        => $gudangUtama->id,

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
                | TAMBAH STOK KE GUDANG TUJUAN
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
            }

            /*
            |--------------------------------------------------------------------------
            | UPDATE STATUS
            |--------------------------------------------------------------------------
            */

            $pengeluaran->update([

                'status'
                    => 'disetujui',

                'approved_by'
                    => $userId,

                'approved_at'
                    => now(),
            ]);

            return $pengeluaran;
        });
    }
}