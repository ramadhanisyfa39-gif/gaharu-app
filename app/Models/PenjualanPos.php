<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenjualanPos extends Model
{
    protected $table = 'penjualan_pos';

    protected $fillable = [
        'kode_transaksi',
        'tanggal',
        'gudang_id',
        'total',
        'created_by'
    ];

    public function details()
    {
        return $this->hasMany(PenjualanPosDetail::class, 'penjualan_id');
    }

    public function gudang()
    {
        return $this->belongsTo(MasterGudang::class, 'gudang_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}