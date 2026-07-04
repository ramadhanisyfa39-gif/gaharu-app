<?php

namespace App\Services;

use App\Models\Pembelian;
use App\Models\PembelianDetail;
use App\Models\StokGudangBatch;
use Illuminate\Support\Facades\DB;

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
    | Parameter $allowNegative:
    | - false (default) : throw Exception jika stok tidak cukup
    | - true            : lanjutkan meski stok kurang (untuk Stock Opname),
    |                     sisa qty yang tidak ada batch-nya akan menggunakan
    |                     harga fallback (avg historis / hpp_referensi)
    |
    */

    public function consumeFIFO(
        int $barangId,
        float $qtyKeluar,
        int $gudangId,
        bool $allowNegative = false
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
        |
        | Jika $allowNegative = true (dari Stock Opname), stok boleh tidak cukup.
        | Sistem akan menguras semua batch yang tersedia, sisanya dicatat
        | dengan harga fallback (rata-rata batch historis / hpp_referensi).
        |
        */

        $totalSisa = $batches->sum('qty_sisa');

        if ($totalSisa < $qtyKeluar && !$allowNegative) {

            throw new \Exception(
                'Stok FIFO tidak mencukupi. Tersedia: ' . $totalSisa . ', Dibutuhkan: ' . $qtyKeluar . '.'
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
        | FALLBACK: SISA QTY TIDAK ADA BATCH-NYA (allowNegative = true)
        |--------------------------------------------------------------------------
        |
        | Terjadi saat stok FIFO tidak cukup tapi tetap diproses (Stock Opname).
        | Harga diambil dari: avg historis batch → hpp_referensi master barang → 0
        |
        */

        if ($sisaPermintaan > 0 && $allowNegative) {

            $hargaFallback = DB::table('stok_gudang_batch')
                ->where('gudang_id', $gudangId)
                ->where('barang_id', $barangId)
                ->avg('harga_per_qty');

            if (!$hargaFallback) {
                $hargaFallback = DB::table('master_barang')
                    ->where('id', $barangId)
                    ->value('hpp_referensi') ?? 0;
            }

            $result[] = [
                'batch_id'      => null,
                'batch_number'  => 'FALLBACK-OPNAME',
                'qty_keluar'    => $sisaPermintaan,
                'harga_per_qty' => (float) $hargaFallback,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | RETURN RESULT
        |--------------------------------------------------------------------------
        */

        return $result;
    }
    
}