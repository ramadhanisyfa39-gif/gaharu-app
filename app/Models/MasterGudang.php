<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class MasterGudang extends Model
{
    protected $table = 'master_gudang';
    protected $fillable = ['nama', 'kategori'];
    public $timestamps = false;
    public function stok() {
        return $this->hasMany(StokGudang::class, 'gudang_id');
    }
    public function permintaanBahanBaku()
    {
        return $this->hasMany(
            PermintaanBahanBaku::class,
            'gudang_id'
        );
    }
    public function stockOpname()
    {
        return $this->hasMany(
            StockOpname::class,
            'gudang_id'
        );
    }
}