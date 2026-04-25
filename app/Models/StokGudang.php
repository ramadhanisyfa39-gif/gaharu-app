<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StokGudang extends Model
{
    protected $table = 'StokGudang';
    protected $fillable = ['gudang_id', 'barang_id', 'jumlah'];
}
