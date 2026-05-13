<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkOrder extends Model
{
    use HasFactory;

    protected $table = 'work_order';
    protected $fillable = ['kode_wo', 'tanggal_wo', 'status_wo', 'catatan', 'created_by'];

    public function details()
    {
        return $this->hasMany(WorkOrderDetail::class, 'work_order_id');
    }

    public function pembuat()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function pesanan()
    {
        return $this->belongsTo(Pesanan::class, 'pesanan_id');
    }
}