<?php

namespace App\Services;

use App\Models\StokGudang;
use App\Models\TransaksiStok;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StockService
{
    /**
     * STOCK MASUK
     * Contoh:
     * - Pembelian
     * - Hasil produksi
     * - Retur customer
     */
    public function stockIn(array $data)
    {
        return DB::transaction(function () use ($data) {

            $this->increaseStock($data);

            return $this->createTransaction($data, 'masuk');
        });
    }

    /**
     * STOCK KELUAR
     * Contoh:
     * - Penjualan
     * - Pemakaian produksi
     * - Pemakaian operasional
     */
    public function stockOut(array $data)
    {
        return DB::transaction(function () use ($data) {

            // Validasi stok
            $this->validateStock($data);

            // Kurangi stok
            $this->decreaseStock($data);

            // Catat transaksi
            return $this->createTransaction($data, 'keluar');
        });
    }

    /**
     * TRANSFER STOCK ANTAR GUDANG
     */
    public function transfer(array $data)
    {
        return DB::transaction(function () use ($data) {

            /*
            |--------------------------------------------------------------------------
            | VALIDASI
            |--------------------------------------------------------------------------
            */

            if (
                $data['gudang_asal_id']
                == $data['gudang_tujuan_id']
            ) {
                throw new RuntimeException(
                    'Gudang asal dan tujuan tidak boleh sama'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | VALIDASI STOK
            |--------------------------------------------------------------------------
            */

            $this->validateStock([
                'barang_id' => $data['barang_id'],
                'gudang_asal_id' => $data['gudang_asal_id'],
                'qty' => $data['qty'],
            ]);

            /*
            |--------------------------------------------------------------------------
            | KURANGI STOK GUDANG ASAL
            |--------------------------------------------------------------------------
            */

            $this->decreaseStock([
                'barang_id' => $data['barang_id'],
                'gudang_asal_id' => $data['gudang_asal_id'],
                'qty' => $data['qty'],
            ]);

            /*
            |--------------------------------------------------------------------------
            | TAMBAH STOK GUDANG TUJUAN
            |--------------------------------------------------------------------------
            */

            $this->increaseStock([
                'barang_id' => $data['barang_id'],
                'gudang_tujuan_id' => $data['gudang_tujuan_id'],
                'qty' => $data['qty'],
            ]);

            /*
            |--------------------------------------------------------------------------
            | TRANSAKSI STOK
            |--------------------------------------------------------------------------
            */

            return $this->createTransaction($data, 'transfer');
        });
    }

    /**
     * TAMBAH STOK
     */
    protected function increaseStock(array $data): void
    {
        $stok = StokGudang::where(
                'barang_id',
                $data['barang_id']
            )
            ->where(
                'gudang_id',
                $data['gudang_tujuan_id']
            )
            ->lockForUpdate()
            ->first();

        if ($stok) {

            $stok->increment(
                'jumlah',
                $data['qty']
            );

        } else {

            StokGudang::create([
                'barang_id' => $data['barang_id'],
                'gudang_id' => $data['gudang_tujuan_id'],
                'jumlah' => $data['qty'],
            ]);
        }
    }

    /**
     * KURANGI STOK
     */
    protected function decreaseStock(array $data): void
    {
        $stok = StokGudang::where(
                'barang_id',
                $data['barang_id']
            )
            ->where(
                'gudang_id',
                $data['gudang_asal_id']
            )
            ->lockForUpdate()
            ->first();

        if (!$stok) {
            throw new RuntimeException(
                'Stok tidak ditemukan'
            );
        }

        if ($stok->jumlah < $data['qty']) {
            throw new RuntimeException(
                'Stok tidak cukup'
            );
        }

        $stok->decrement(
            'jumlah',
            $data['qty']
        );
    }

    /**
     * VALIDASI STOK
     */
    protected function validateStock(array $data): void
    {
        $stok = StokGudang::where(
                'barang_id',
                $data['barang_id']
            )
            ->where(
                'gudang_id',
                $data['gudang_asal_id']
            )
            ->first();

        if (!$stok) {
            throw new RuntimeException(
                'Stok tidak ditemukan'
            );
        }

        if ($stok->jumlah < $data['qty']) {
            throw new RuntimeException(
                'Stok tidak cukup'
            );
        }
    }

    /**
     * CATAT TRANSAKSI STOK
     */
    protected function createTransaction(
        array $data,
        string $tipe
    ) {
        return TransaksiStok::create([

            'tanggal' => now(),

            'tipe' => $tipe,

            'source_type' => $data['source_type'] ?? null,
            'source_id' => $data['source_id'] ?? null,

            'gudang_asal_id'
                => $data['gudang_asal_id'] ?? null,

            'gudang_tujuan_id'
                => $data['gudang_tujuan_id'] ?? null,

            'barang_id' => $data['barang_id'],

            'qty' => $data['qty'],

            'total_harga'
                => $data['total_harga'] ?? 0,

            'created_by' => $data['user_id'],
        ]);
    }
}