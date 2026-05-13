<?php

namespace App\Http\Controllers;

use App\Models\PesananDetail;
use Illuminate\Http\Request;

class PesananDetailController extends Controller
{
    public function store(Request $request)
    {
        PesananDetail::create([
            'pesanan_id' => $request->pesanan_id,
            'produk_id' => $request->produk_id,
            'qty' => $request->qty,
            'harga' => $request->harga,
            'subtotal' => $request->subtotal,
        ]);

        return back()->with('success', 'Detail berhasil ditambahkan');
    }

    public function destroy(string $id)
    {
        $detail = PesananDetail::findOrFail($id);

        $detail->delete();

        return back()->with('success', 'Detail berhasil dihapus');
    }
}