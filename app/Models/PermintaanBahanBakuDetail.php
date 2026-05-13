<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PermintaanBahanBakuDetail extends Model
{
    protected $table = 'permintaan_bahan_baku_detail';

    protected $fillable = [
        'permintaan_id',
        'barang_id',
        'qty_permintaan',
        'qty_disetujui',
        'qty_dikeluarkan',
        'keterangan',
    ];

    protected $casts = [
        'qty_permintaan' => 'decimal:4',
        'qty_disetujui' => 'decimal:4',
        'qty_dikeluarkan' => 'decimal:4',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function permintaan()
    {
        return $this->belongsTo(
            PermintaanBahanBaku::class,
            'permintaan_id'
        );
    }

    public function barang()
    {
        return $this->belongsTo(
            MasterBarang::class,
            'barang_id'
        );
    }
}