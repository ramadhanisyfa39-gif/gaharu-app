<?php

namespace App\Http\Controllers;

use App\Models\ResepBahanBaku;
use App\Models\ResepBtklBop;
use App\Models\MasterBarang;
use Illuminate\Http\Request;

class ResepBahanBakuController extends Controller
{
    public function index()
    {
        $data = ResepBahanBaku::with('bahan')->get();
        return view('resep_bahan.index', compact('data'));
    }

    public function show($id)
    {
        $resep = ResepBtklBop::with('produk')->findOrFail($id);

        $bahan = ResepBahanBaku::with('bahan')
                    ->where('resep_id', $id)
                    ->get();

        // hanya bahan baku
        $master = MasterBarang::where('is_bahan_baku', 1)->get();

        return view('resep.bahan', compact('resep', 'bahan', 'master'));
    }

    public function store(Request $request, $id)
    {
        $request->validate([
            'bahan_id' => 'required|array',
            'bahan_id.*' => 'required',
            'qty_bahan' => 'required|array',
            'qty_bahan.*' => 'required|numeric|min:0',
            'satuan' => 'required|array',
            'satuan.*' => 'required'
        ]);

        foreach ($request->bahan_id as $i => $bahan_id) {
            ResepBahanBaku::create([
                'resep_id' => $id,
                'bahan_id' => $bahan_id,
                'qty_bahan' => $request->qty_bahan[$i],
                'satuan' => $request->satuan[$i],
            ]);
        }

        return back()->with('success', 'Bahan berhasil ditambahkan');
    }

    public function destroy($id)
    {
        ResepBahanBaku::destroy($id);
        return back()->with('success', 'Bahan dihapus');
    }
}