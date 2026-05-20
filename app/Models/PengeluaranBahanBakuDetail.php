<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PengeluaranBahanBakuDetail extends Model
{
    protected $table = 'pengeluaran_bahan_baku_detail';

    protected $fillable = [

    'pengeluaran_id',

    'barang_id',

    'qty',

    'satuan',

    'harga_satuan',

    'total_harga',

    'hpp_total',
];

    public function barang()
    {
        return $this->belongsTo(
            MasterBarang::class,
            'barang_id'
        );
    }
    public function store(Request $request)
    {
    $pengeluaran = PengeluaranBahanBaku::create([
        'kode_pengeluaran' => 'PBK-' . time(),
        'tanggal' => now(),
        'gudang_id' => $request->gudang_id,
        'status' => 'draft',
        'created_by' => auth()->id(),
    ]);

    PengeluaranBahanBakuDetail::create([
        'pengeluaran_id' => $pengeluaran->id,
        'barang_id' => $request->barang_id,
        'qty' => $request->qty,
        'satuan' => 'pcs',
        'harga_satuan' => 0,
        'total_harga' => 0,
    ]);

    return redirect()
        ->route('pengeluaran-bahan-baku.index')
        ->with('success', 'Data pengeluaran berhasil dibuat');
    }
}