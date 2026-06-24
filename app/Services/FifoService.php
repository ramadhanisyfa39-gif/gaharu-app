<?php

namespace App\Services;

use App\Models\Pembelian;
use App\Models\PembelianDetail;
use App\Models\StokGudangBatch;

class FifoService
{
    /*
    |--------------------------------------------------------------------------
    | CREATE BATCH SAAT PEMBELIAN
    |--------------------------------------------------------------------------
    |
    | Setiap pembelian akan membuat batch FIFO baru.
    |
    */

    public function createBatchStock(
        Pembelian $pembelian,
        PembelianDetail $detail
    ): void {

        StokGudangBatch::create([

            'gudang_id'
                => $pembelian->gudang_id,

            'supplier_id'
                => $pembelian->supplier_id,

            'barang_id'
                => $detail->barang_id,

            'pembelian_id'
                => $pembelian->id,

            'pembelian_detail_id'
                => $detail->id,

            'batch_number'
                => $detail->batch_number,

            /*
            |--------------------------------------------------------------------------
            | FIFO QTY
            |--------------------------------------------------------------------------
            */

            'qty_masuk'
                => $detail->qty,

            'qty_keluar'
                => 0,

            'qty_sisa'
                => $detail->qty,

            /*
            |--------------------------------------------------------------------------
            | HARGA FIFO
            |--------------------------------------------------------------------------
            */

            'harga_per_qty'
                => $detail->harga_per_qty,

            /*
            |--------------------------------------------------------------------------
            | STATUS
            |--------------------------------------------------------------------------
            */

            'is_habis'
                => false,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | FIFO CONSUME
    |--------------------------------------------------------------------------
    |
    | Mengurangi stok berdasarkan batch tertua.
    |
    | Contoh:
    |
    | Batch A = 5
    | Batch B = 4
    |
    | Permintaan = 7
    |
    | Maka:
    | - Batch A habis 5
    | - Batch B ambil 2
    |
    */

    public function consumeFIFO(
        int $barangId,
        float $qtyKeluar,
        int $gudangId
    ): array {

        /*
        |--------------------------------------------------------------------------
        | AMBIL BATCH FIFO
        |--------------------------------------------------------------------------
        */

        $batches = StokGudangBatch::where(
                'barang_id',
                $barangId
            )
            ->where(
                'gudang_id',
                $gudangId
            )
            ->where(
                'qty_sisa',
                '>',
                0
            )
            ->where(
                'is_habis',
                false
            )
            ->orderBy('id') // FIFO
            ->get();

        /*
        |--------------------------------------------------------------------------
        | VALIDASI STOK FIFO
        |--------------------------------------------------------------------------
        */

        $totalSisa = $batches->sum('qty_sisa');

        if ($totalSisa < $qtyKeluar) {

            throw new \Exception(
                'Stok FIFO tidak mencukupi.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | FIFO LOOP
        |--------------------------------------------------------------------------
        */

        $sisaPermintaan = $qtyKeluar;

        /*
        |--------------------------------------------------------------------------
        | RESULT FIFO
        |--------------------------------------------------------------------------
        |
        | Dipakai untuk:
        | - histori
        | - costing
        | - audit
        | - HPP
        |
        */

        $result = [];

        foreach ($batches as $batch) {

            /*
            |--------------------------------------------------------------------------
            | JIKA SUDAH TERPENUHI
            |--------------------------------------------------------------------------
            */

            if ($sisaPermintaan <= 0) {
                break;
            }

            /*
            |--------------------------------------------------------------------------
            | HITUNG QTY YANG DIAMBIL
            |--------------------------------------------------------------------------
            */

            $ambilQty = min(
                $batch->qty_sisa,
                $sisaPermintaan
            );

            /*
            |--------------------------------------------------------------------------
            | UPDATE FIFO BATCH
            |--------------------------------------------------------------------------
            */

            $batch->qty_keluar += $ambilQty;

            $batch->qty_sisa -= $ambilQty;

            /*
            |--------------------------------------------------------------------------
            | JIKA BATCH HABIS
            |--------------------------------------------------------------------------
            */

            if ($batch->qty_sisa <= 0) {

                $batch->qty_sisa = 0;

                $batch->is_habis = true;
            }

            $batch->save();

            /*
            |--------------------------------------------------------------------------
            | SIMPAN RESULT FIFO
            |--------------------------------------------------------------------------
            */

            $result[] = [

                'batch_id'
                    => $batch->id,

                'batch_number'
                    => $batch->batch_number,

                'qty_keluar'
                    => $ambilQty,

                'harga_per_qty'
                    => $batch->harga_per_qty,
            ];

            /*
            |--------------------------------------------------------------------------
            | KURANGI SISA PERMINTAAN
            |--------------------------------------------------------------------------
            */

            $sisaPermintaan -= $ambilQty;
        }

        /*
        |--------------------------------------------------------------------------
        | RETURN RESULT
        |--------------------------------------------------------------------------
        */

        return $result;
    }
    
}