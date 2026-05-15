<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenjualanPosDetail extends Model
{
    protected $table = 'penjualanpos_detail';

    protected $fillable = [
        'penjualan_id',
        'produk_id',
        'qty',
        'harga',
        'hpp_satuan',
        'subtotal'
    ];

    public function penjualan()
    {
        return $this->belongsTo(PenjualanPos::class, 'penjualan_id');
    }

    public function produk()
    {
        return $this->belongsTo(MasterBarang::class, 'produk_id');
    }
}