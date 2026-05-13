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
        $data = ResepBtklBop::with(['produk','bahanbaku'])->get();
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
 
           //bahan wajib
           'bahan_id' => 'required|array',
           'bahan_id.*' => 'required|exists:master_barang,id',
           'qty_bahan' => 'required|array',
           'qty_bahan.*' => 'required|numeric|min:0.01',
           'satuan' => 'required|array',
           'satuan.*' => 'required'
       ]);

       //CEK DUPLIKAT PRODUK
       $cek = ResepBtklBop::where('produk_id', $request->produk_id)->exists();

       if ($cek) {
           return back()->with('error', 'Produk sudah punya resep!');
       }

       //simpan header resep
       $resep = ResepBtklBop::create([
           'produk_id' => $request->produk_id,
           'output_qty' => $request->output_qty,
           'satuan_output' => $request->satuan_output ?? $resep->satuan_output,
           'btkl_per_batch' => $request->btkl_per_batch,
           'bop_per_batch' => $request->bop_per_batch,
       ]);

       //GROUPING BAHAN (BIAR TIDAK DOBEL)
       $grouped = [];

       foreach ($request->bahan_id as $i => $bahan_id) {

           $qty = $request->qty_bahan[$i];
           $satuan = $request->satuan[$i];

           if (isset($grouped[$bahan_id])) {
               // tambah qty kalau bahan sama
               $grouped[$bahan_id]['qty'] += $qty;
           } else {
               $grouped[$bahan_id] = [
                   'qty' => $qty,
                   'satuan' => $satuan
               ];
           }
       }

       //simpan hasil grouping
       foreach ($grouped as $bahan_id => $val) {
           ResepBahanBaku::create([
               'resep_id' => $resep->id,
               'bahan_id' => $bahan_id,
               'qty_bahan' => $val['qty'],
               'satuan' => $val['satuan'],
           ]);
       }

       return redirect()->route('resep.index')
           ->with('success', 'Resep berhasil dibuat');
   }

   public function edit($id)
   {
       $data = ResepBtklBop::with('bahanbaku.bahan')->findOrFail($id);
   
       $produk = MasterBarang::where('is_barang_jadi', 1)->get();
       $bahan  = MasterBarang::where('is_bahan_baku', 1)->get();
   
       return view('resep.edit', compact('data','produk','bahan'));
   }

   public function update(Request $request, $id)
   {
       $request->validate([
           'produk_id' => 'required',
           'output_qty' => 'required|numeric|min:1',
           'btkl_per_batch' => 'required|numeric',
           'bop_per_batch' => 'required|numeric',
   
           'bahan_id' => 'required|array',
           'bahan_id.*' => 'required|exists:master_barang,id',
   
           'qty_bahan' => 'required|array',
           'qty_bahan.*' => 'required|numeric|min:0.01',
       ]);
   
       $resep = ResepBtklBop::findOrFail($id);
   
       // update header
       $resep->update([
           'produk_id' => $request->produk_id,
           'output_qty' => $request->output_qty,
           'satuan_output' => $request->satuan_output,
           'btkl_per_batch' => $request->btkl_per_batch,
           'bop_per_batch' => $request->bop_per_batch,
       ]);
   
       // hapus bahan lama
       ResepBahanBaku::where('resep_id', $id)->delete();
   
       // grouping bahan
       $grouped = [];
   
       foreach ($request->bahan_id as $i => $bahan_id) {
   
           $qty = $request->qty_bahan[$i];
   
           if (isset($grouped[$bahan_id])) {
               $grouped[$bahan_id] += $qty;
           } else {
               $grouped[$bahan_id] = $qty;
           }
       }
   
       // simpan ulang (AMBIL SATUAN DARI DB)
       foreach ($grouped as $bahan_id => $qty) {
   
           $barang = MasterBarang::find($bahan_id);
   
           ResepBahanBaku::create([
               'resep_id' => $id,
               'bahan_id' => $bahan_id,
               'qty_bahan' => $qty,
               'satuan' => $barang->satuan ?? '-',
           ]);
       }
   
       return redirect()->route('resep.index')
           ->with('success', 'Resep berhasil diupdate');
   }
    public function destroy($id)
    {
        // hapus semua bahan dulu
        ResepBahanBaku::where('resep_id', $id)->delete();

        // baru hapus resep
        ResepBtklBop::destroy($id);

        return back()->with('success', 'Resep berhasil dihapus');
    }
 }