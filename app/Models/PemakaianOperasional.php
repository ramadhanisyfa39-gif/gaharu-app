<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PemakaianOperasional extends Model
{
    protected $table = 'pemakaian_operasional';
    protected $fillable = ['kode_pemakaian', 'tanggal', 'gudang_id', 'keterangan', 'created_by'];
}
