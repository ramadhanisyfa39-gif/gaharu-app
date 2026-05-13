<?php

namespace App\Http\Controllers;

use App\Models\PenjualanPosDetail;
use Illuminate\Http\Request;

class PenjualanPosDetailController extends Controller
{
    public function store(Request $request)
    {
        PenjualanPosDetail::create([
            'penjualan_id' => $request->penjualan_id,
            'produk_id' => $request->produk_id,
            'qty' => $request->qty,
            'harga' => $request->harga,
            'hpp_satuan' => $request->hpp_satuan,
            'subtotal' => $request->subtotal,
        ]);

        return back()->with('success', 'Detail berhasil ditambahkan');
    }

    public function destroy(string $id)
    {
        $detail = PenjualanPosDetail::findOrFail($id);

        $detail->delete();

        return back()->with('success', 'Detail berhasil dihapus');
    }
}