<?php

namespace App\Http\Controllers;
use App\Models\ResepBahanBaku;
use App\Models\ResepBtklBop;
use App\Models\MasterBarang;
use Illuminate\Http\Request;

class ResepBtklBopController extends Controller
{
    public function index()
    {
        $data = ResepBtklBop::with('produk')->get();
        return view('resep.index', compact('data'));
    }

    public function create()
    {
        $produk = MasterBarang::where('is_barang_jadi', '1')->get();
        $bahan  = MasterBarang::where('is_bahan_baku', '1')->get();
    
        return view('resep.create', compact('produk','bahan'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'produk_id' => 'required',
            'output_qty' => 'required|numeric|min:1',
            'btkl_per_batch' => 'required|numeric',
            'bop_per_batch' => 'required|numeric',
            'bahan_id' => 'required|array'
        ]);
    
        // simpan header
        $resep = ResepBtklBop::create([
            'produk_id' => $request->produk_id,
            'output_qty' => $request->output_qty,
            'satuan_output' => $request->satuan_output, // ← WAJIB
            'btkl_per_batch' => $request->btkl_per_batch,
            'bop_per_batch' => $request->bop_per_batch,
        ]);
    
        // simpan bahan
        foreach ($request->bahan_id as $i => $bahan_id) {
    
            ResepBahanBaku::create([
                'resep_id' => $resep->id,
                'bahan_id' => $bahan_id,
                'qty_bahan' => $request->qty_bahan[$i],
                'satuan' => $request->satuan[$i],
            ]);
        }
    
        return redirect()->route('resep.index')->with('success', 'Resep berhasil disimpan');
    }

    public function edit($id)
    {
        $data = ResepBtklBop::findOrFail($id);
        $produk = MasterBarang::where('is_barang_jadi', '1')->get();

        return view('resep.edit', compact('data', 'produk'));
    }

    public function update(Request $request, $id)
    {
        $data = ResepBtklBop::findOrFail($id);

        $request->validate([
            'produk_id' => 'required',
            'output_qty' => 'required|numeric|min:1',
            'btkl_per_batch' => 'required|numeric',
            'bop_per_batch' => 'required|numeric',
        ]);

        $data->update($request->all());

        return redirect()->route('resep.index')->with('success', 'Data berhasil diupdate');
    }

    public function destroy($id)
    {
        ResepBtklBop::destroy($id);
        return back()->with('success', 'Data berhasil dihapus');
    }
}