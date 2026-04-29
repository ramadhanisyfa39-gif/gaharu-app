<?php

namespace App\Http\Controllers;

use App\Models\MasterBarang;
use App\Models\Kategori;
use Illuminate\Http\Request;

class BarangController extends Controller
{
    public function index()
    {
        $data = MasterBarang::with('kategori')->get(); 
        return view('barang.index', compact('data'));
    }

    public function create()
    {
        $kategori = Kategori::all();
        return view('barang.create', compact('kategori'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'kategori_id' => 'required',
            'kode_barang' => 'required|unique:master_barang',
            'nama' => 'required',
            'jenis_utama' => 'required',
        ]);
    
        // MEMBERSIHKAN FORMAT TITIK 
        $harga_b2b = str_replace('.', '', $request->harga_jual_b2b ?? 0);
        $harga_pos = str_replace('.', '', $request->harga_jual_pos ?? 0);
        $hpp = str_replace('.', '', $request->hpp_referensi ?? 0);
    
        // aturan bisnis
        if ($request->jenis_utama == 'BAHAN_BAKU' || $request->jenis_utama == 'OPERATIONAL') {
            $harga_b2b = 0;
            $harga_pos = 0;
        }
    
        // if ($request->jenis_utama == 'BARANG_JADI') {
        //     $hpp = 0;
        // }
    
        MasterBarang::create([
            'kategori_id' => $request->kategori_id,
            'kode_barang' => $request->kode_barang,
            'nama' => $request->nama,
            'satuan' => $request->satuan,
    
            'is_bahan_baku' => $request->jenis_utama == 'BAHAN_BAKU',
            'is_barang_jadi' => $request->jenis_utama == 'BARANG_JADI',
            'is_operational' => $request->jenis_utama == 'OPERATIONAL',
            'is_direct_consumption' => false,
    
            'hpp_referensi' => $hpp,
            'harga_jual_b2b' => $harga_b2b,
            'harga_jual_pos' => $harga_pos,
        ]);
    
        return redirect()->route('barang.index')->with('success', 'Data berhasil ditambah');
    }
    public function edit($id)
    {
        $data = MasterBarang::findOrFail($id);
        $kategori = Kategori::all();

        // mapping balik ke dropdown
        $data->jenis_utama =
            $data->is_bahan_baku ? 'BAHAN_BAKU' :
            ($data->is_barang_jadi ? 'BARANG_JADI' : 'OPERATIONAL');

        return view('barang.edit', compact('data', 'kategori'));
    }

    public function update(Request $request, $id)
    {
        $data = MasterBarang::findOrFail($id);
    
        $harga_b2b = str_replace('.', '', $request->harga_jual_b2b ?? 0);
        $harga_pos = str_replace('.', '', $request->harga_jual_pos ?? 0);
        $hpp = str_replace('.', '', $request->hpp_referensi ?? 0);
    
        if ($request->jenis_utama == 'BAHAN_BAKU' || $request->jenis_utama == 'OPERATIONAL') {
            $harga_b2b = 0;
            $harga_pos = 0;
        }
    
    
        $data->update([
            'kategori_id' => $request->kategori_id,
            'kode_barang' => $request->kode_barang,
            'nama' => $request->nama,
            'satuan' => $request->satuan,
    
            'is_bahan_baku' => $request->jenis_utama == 'BAHAN_BAKU',
            'is_barang_jadi' => $request->jenis_utama == 'BARANG_JADI',
            'is_operational' => $request->jenis_utama == 'OPERATIONAL',
            'is_direct_consumption' => false,
    
            'hpp_referensi' => $hpp,
            'harga_jual_b2b' => $harga_b2b,
            'harga_jual_pos' => $harga_pos,
        ]);
    
        return redirect()->route('barang.index')->with('success', 'Data berhasil diupdate');
    }

    public function destroy($id)
    {
        MasterBarang::findOrFail($id)->delete();
        return redirect()->route('barang.index')->with('success', 'Data berhasil dihapus');
    }
}