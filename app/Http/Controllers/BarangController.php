<?php

namespace App\Http\Controllers;

use App\Models\MasterBarang;
use App\Models\Kategori;
use App\Models\ResepBtklBop; // <--- Tambahkan Model Resep di sini
use Illuminate\Http\Request;

class BarangController extends Controller
{
    public function index()
    {
        // Ambil data beserta kategori dan resep agar bisa tampil di tabel
        $data = MasterBarang::with(['kategori', 'resep'])->get(); 
        return view('barang.index', compact('data'));
    }

    public function create()
    {
        $kategori = Kategori::all();
        $reseps = ResepBtklBop::all(); // <--- Tambahkan ini untuk kirim ke view
        return view('barang.create', compact('kategori', 'reseps'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'kategori_id' => 'required',
            'kode_barang' => 'required|unique:master_barang,kode_barang',
            'nama'        => 'required',
            'jenis_utama' => 'required',
        ]);
    
        try {
            $harga_b2b = str_replace('.', '', $request->harga_jual_b2b ?? 0);
            $harga_pos = str_replace('.', '', $request->harga_jual_pos ?? 0);
            $hpp       = str_replace('.', '', $request->hpp_referensi ?? 0);
    
            if ($request->jenis_utama == 'BAHAN_BAKU' || $request->jenis_utama == 'OPERATIONAL') {
                $harga_b2b = 0;
                $harga_pos = 0;
            }
    
            MasterBarang::create([
                'kategori_id'           => $request->kategori_id,
                'resep_id'              => $request->resep_id, // <--- INI PENTING: Harus ada ini
                'kode_barang'           => $request->kode_barang,
                'nama'                  => $request->nama,
                'satuan'                => $request->satuan,
                'is_bahan_baku'         => $request->jenis_utama == 'BAHAN_BAKU',
                'is_barang_jadi'        => $request->jenis_utama == 'BARANG_JADI',
                'is_operational'        => $request->jenis_utama == 'OPERATIONAL',
                'is_direct_consumption' => false,
                'hpp_referensi'         => $hpp,
                'harga_jual_b2b'        => $harga_b2b,
                'harga_jual_pos'        => $harga_pos,
            ]);
    
            return redirect()->route('barang.index')->with('success', 'Data berhasil ditambah');
    
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->withErrors(['error' => 'Gagal simpan: ' . $e->getMessage()]);
        }
    }
    
    public function edit($id)
    {
        $data = MasterBarang::findOrFail($id);
        $kategori = Kategori::all();
        $reseps = ResepBtklBop::all(); // <--- Tambahkan ini juga di menu edit

        $data->jenis_utama =
            $data->is_bahan_baku ? 'BAHAN_BAKU' :
            ($data->is_barang_jadi ? 'BARANG_JADI' : 'OPERATIONAL');

        return view('barang.edit', compact('data', 'kategori', 'reseps'));
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
            'resep_id'    => $request->resep_id, // <--- INI JUGA: Biar kalau diedit kesimpan
            'kode_barang' => $request->kode_barang,
            'nama'        => $request->nama,
            'satuan'      => $request->satuan,
    
            'is_bahan_baku'  => $request->jenis_utama == 'BAHAN_BAKU',
            'is_barang_jadi' => $request->jenis_utama == 'BARANG_JADI',
            'is_operational' => $request->jenis_utama == 'OPERATIONAL',
            'is_direct_consumption' => false,
    
            'hpp_referensi'  => $hpp,
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