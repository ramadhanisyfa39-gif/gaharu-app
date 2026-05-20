<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChartOfAccount extends Model
{
    protected $table = 'chart_of_accounts';
    public $timestamps = false;
    protected $fillable = ['kode', 'nama', 'tipe', 'saldo_normal'];

    public function items()
    {
        return $this->hasMany(JournalItem::class, 'account_id');
    }
}
