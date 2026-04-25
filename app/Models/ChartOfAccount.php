<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChartOfAccount extends Model
{
    protected $table = 'ChartOfAccount';
    protected $fillable = ['kode', 'nama', 'tipe', 'saldo_normal'];
}
