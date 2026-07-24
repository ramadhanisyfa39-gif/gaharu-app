<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenerimaanPembelianDetail extends Model
{
    use HasFactory;

    protected $table = 'penerimaan_pembelian_detail';

    protected $fillable = [
        'penerimaan_pembelian_id',
        'pembelian_detail_id',
        'barang_id',
        'qty',
        'harga_per_qty'
    ];

    public function header()
    {
        return $this->belongsTo(PenerimaanPembelian::class, 'penerimaan_pembelian_id');
    }

    public function pembelianDetail()
    {
        return $this->belongsTo(PembelianDetail::class, 'pembelian_detail_id');
    }

    public function barang()
    {
        return $this->belongsTo(MasterBarang::class, 'barang_id');
    }
}
