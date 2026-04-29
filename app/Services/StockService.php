<?php

namespace App\Services;

use App\Models\StokGudang;
use App\Models\TransaksiStok;
use Illuminate\Support\Facades\DB;
use Exception;

class StockService
{
    public function stockIn(array $data)
{
    DB::beginTransaction();

    try {
        $this->increaseStock($data);

        $transaksi = $this->createTransaction($data, 'masuk');

        DB::commit();

        return $transaksi;
    } catch (\Throwable $e) {
        DB::rollBack();
        throw $e;
    }
}

        public function stockOut(array $data)
        {
            DB::beginTransaction();

            try {
                // 1. Validasi stok
                $this->validateStock($data);

                // 2. Kurangi stok
                $this->decreaseStock($data);

                // 3. Catat transaksi
                $this->createTransaction($data, 'keluar');

                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                throw $e;
            }
        }
        protected function increaseStock($data)
    {
        $stok = StokGudang::where('barang_id', $data['barang_id'])
            ->where('gudang_id', $data['gudang_id'])
            ->lockForUpdate()
            ->first();

        if ($stok) {
            $stok->increment('jumlah', $data['qty']);
        } else {
            StokGudang::create([
                'barang_id' => $data['barang_id'],
                'gudang_id' => $data['gudang_id'],
                'jumlah' => $data['qty'],
            ]);
        }
    }
        protected function decreaseStock($data)
        {
            $stok = StokGudang::where('barang_id', $data['barang_id'])
                ->where('gudang_id', $data['gudang_id'])
                ->lockForUpdate()
                ->first();

            if (!$stok) {
                throw new Exception('Stok tidak ditemukan');
            }

            if ($stok->jumlah < $data['qty']) {
                throw new Exception('Stok tidak cukup');
            }

            $stok->decrement('jumlah', $data['qty']);
        }
        protected function validateStock($data)
        {
            $stok = StokGudang::where('barang_id', $data['barang_id'])
                ->where('gudang_id', $data['gudang_id'])
                ->first();

            if (!$stok || $stok->jumlah < $data['qty']) {
                throw new Exception('Stok tidak cukup');
            }
        }
        protected function createTransaction($data, $tipe)
    {
        return TransaksiStok::create([
            'tanggal' => now(),
            'tipe' => $tipe,
            'source_type' => $data['source_type'] ?? null,
            'source_id' => $data['source_id'] ?? null,

            'gudang_asal_id' => $tipe === 'keluar' ? $data['gudang_id'] : null,
            'gudang_tujuan_id' => $tipe === 'masuk' ? $data['gudang_id'] : null,

            'barang_id' => $data['barang_id'],
            'qty' => $data['qty'],
            'total_harga' => $data['total_harga'] ?? null,

            'created_by' => $data['user_id'],
        ]);
    }
}