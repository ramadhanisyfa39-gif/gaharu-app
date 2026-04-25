<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProduksiPesanan extends Model
{
    protected $table = 'ProduksiPesanan';
    protected $fillable = ['produksi_detail_id', 'pesanan_detail_id', 'qty_dialokasikan'];
}
