<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pesanan extends Model
{
    protected $table = 'pesanan';

    protected $fillable = [
        'kode_pesanan',
        'customer_id',
        'tanggal',
        'estimasi_kirim',
        'total_pesanan',
        'status_pesanan',
        'status_pembayaran', // Tambahkan ini
        'created_by'
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

    //untuk membatasi pengeditan setelah WO dibuat
    public function workOrder()
{
    return $this->hasMany(
        WorkOrder::class,
        'pesanan_id'
    );
}
}