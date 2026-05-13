<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PermintaanBahanBaku extends Model
{
    protected $table = 'permintaan_bahan_baku';

    protected $fillable = [
        'kode_permintaan',
        'produksi_id',
        'gudang_id',
        'tanggal',
        'status',
        'catatan',
        'created_by',
    ];

    protected $casts = [
        'tanggal' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function details()
    {
        return $this->hasMany(
            PermintaanBahanBakuDetail::class,
            'permintaan_id'
        );
    }

    public function gudang()
    {
        return $this->belongsTo(
            MasterGudang::class,
            'gudang_id'
        );
    }

    public function produksi()
    {
        return $this->belongsTo(
            Produksi::class,
            'produksi_id'
        );
    }

    public function creator()
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }
}