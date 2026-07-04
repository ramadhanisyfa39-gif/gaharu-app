<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JurnalPenjualanB2b extends Model
{
    use HasFactory;

    // Definisikan nama tabel secara eksplisit karena tidak menggunakan standard jamak Bahasa Inggris
    protected $table = 'jurnal_penjualan_b2b';

    // Daftarkan kolom yang boleh diisi secara massal
    protected $fillable = [
        'tanggal',
        'no_ref',
        'deskripsi',
        'source_type',
        'source_id',
        'created_by'
    ];

    // Relasi ke item jurnal (optional, tapi sangat berguna untuk merapikan method index & show)
    public function items()
    {
        return $this->hasMany(JournalItem::class, 'journal_id')->where('journal_type', 'penjualan_b2b');
    }
}
