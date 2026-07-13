<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\MasterGudang;

class Pesanan extends Model
{
    protected $table = 'pesanan';

    protected $fillable = [
        'kode_pesanan',
        'customer_id',
        'tanggal',
        'estimasi_kirim',
        'estimasi_produksi',
        'total_pesanan',
        'status_pesanan',
        'status_pembayaran',
        'created_by',
        'gudang_id'
    ];

    // Hubungkan ke tabel pembayaran yang akan kita buat nanti
    public function pembayaran()
    {
        return $this->hasMany(Pembayaran::class, 'pesanan_id');
    }

    // Fungsi bantu untuk cek sisa tagihan
    public function getSisaTagihanAttribute()
    {
        $totalBayar = $this->pembayaran()->sum('jumlah_bayar');
        return $this->total_pesanan - $totalBayar;
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function details()
    {
        return $this->hasMany(PesananDetail::class, 'pesanan_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function gudang()
    {
        return $this->belongsTo(MasterGudang::class, 'gudang_id');
    }

    //untuk membatasi pengeditan setelah WO dibuat
    public function workOrder()
    {
        return $this->hasMany(
            WorkOrder::class,
            'pesanan_id'
        );
    }
}
