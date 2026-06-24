<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pembelian extends Model
{
    protected $table = 'pembelian';

    protected $fillable = [
    'kode_pembelian',
    'supplier_id',
    'gudang_id',
    'tanggal',
    'total',
    'created_by',
    // --- TAMBAHKAN 7 BARIS INI ---
    'metode_pembayaran',
    'tanggal_jatuh_tempo',
    'persen_dp',
    'tanggal_pelunasan',
    'catatan_pembayaran',
    'dicatat_oleh',
    'dicatat_pada',
];

    public $timestamps = false;

    /*
    |--------------------------------------------------------------------------
    | RELATION
    |--------------------------------------------------------------------------
    */

    public function supplier()
    {
        return $this->belongsTo(
            Supplier::class,
            'supplier_id'
        );
    }

    public function gudang()
    {
        return $this->belongsTo(
            MasterGudang::class,
            'gudang_id'
        );
    }

    public function details()
    {
        return $this->hasMany(
            PembelianDetail::class,
            'pembelian_id'
        );
    }

    public function user()
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDASI EDITABLE
    |--------------------------------------------------------------------------
    */

    public function isEditable(): bool
    {
        foreach ($this->details as $detail) {

            $stok = \App\Models\StokGudang::where(
                'barang_id',
                $detail->barang_id
            )
            ->where(
                'gudang_id',
                $this->gudang_id
            )
            ->first();

            /*
            |--------------------------------------------------------------------------
            | JIKA STOK SUDAH BERKURANG
            |--------------------------------------------------------------------------
            */

            if (
                !$stok ||
                $stok->jumlah < $detail->qty
            ) {
                return false;
            }
        }

        return true;
    }
}