<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChartOfAccount extends Model
{
    // Menentukan nama tabel di database
    protected $table = 'chart_of_accounts';

    // Kolom yang diizinkan untuk diisi secara massal (Mass Assignment)
    // Ditambahkan 'parent_id' ke dalam list
    protected $fillable = [
        'kode',
        'nama',
        'tipe',
        'saldo_normal',
        'parent_id'
    ];

    /**
     * Relasi ke data transaksi jurnal harian (Bawaan sistemmu)
     */
    public function items(): HasMany
    {
        return $this->hasMany(JournalItem::class, 'account_id'); //
    }

    /**
     * Relasi untuk mengambil data AKUN INDUK (Parent)
     * Sebuah akun bisa memiliki satu atau tidak memiliki akun induk sama sekali
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'parent_id');
    }

    /**
     * Relasi untuk mengambil daftar SUB-AKUN (Children)
     * Sebuah akun induk bisa membawahi banyak sub-akun sekaligus
     */
    public function children(): HasMany
    {
        return $this->hasMany(ChartOfAccount::class, 'parent_id');
    }
}
