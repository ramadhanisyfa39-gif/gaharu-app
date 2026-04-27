<?php

namespace App\Http\Controllers;

use App\Models\MasterBarang;
use App\Models\Kategori;
use Illuminate\Http\Request;

class BarangController extends Controller
{
    public function index()
    {
        $data = MasterBarang::with('kategori')->latest()->get();
        return view('barang.index', compact('data'));
    }

    public function create()
    {
        $kategori = Kategori::orderBy('nama')->get();
        return view('barang.create', compact('kategori'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'kategori_id' => 'required|exists:kategori,id',
            'kode_barang' => 'required|unique:master_barang,kode_barang',
            'nama' => 'required|string',
            'satuan' => 'nullable|string',
            'jenis_utama' => 'required|in:BAHAN_BAKU,BARANG_JADI,OPERATIONAL',
            'hpp_referensi' => 'nullable|numeric|min:0',
            'harga_jual_b2b' => 'nullable|numeric|min:0',
            'harga_jual_pos' => 'nullable|numeric|min:0',
        ]);

        // 🔥 Business rules
        if ($request->jenis_utama === 'BAHAN_BAKU') {
            if (!$request->hpp_referensi) {
                return back()->withErrors(['hpp_referensi' => 'Bahan baku wajib isi HPP'])->withInput();
            }
            if ($request->harga_jual_b2b || $request->harga_jual_pos) {
                return back()->withErrors(['harga_jual_b2b' => 'Bahan baku tidak boleh punya harga jual'])->withInput();
            }
        }

        if ($request->jenis_utama === 'BARANG_JADI') {
            if (!$request->harga_jual_b2b && !$request->harga_jual_pos) {
                return back()->withErrors(['harga_jual_b2b' => 'Barang jadi wajib punya minimal 1 harga jual'])->withInput();
            }
        }

        MasterBarang::create([
            'kategori_id' => $request->kategori_id,
            'kode_barang' => $request->kode_barang,
            'nama' => $request->nama,
            'satuan' => $request->satuan,
            'jenis_utama' => $request->jenis_utama,
            'hpp_referensi' => $request->hpp_referensi,
            'harga_jual_b2b' => $request->harga_jual_b2b,
            'harga_jual_pos' => $request->harga_jual_pos,
        ]);

        return redirect()->route('barang.index')->with('success', 'Data berhasil ditambah');
    }

    public function edit($id)
    {
        $data = MasterBarang::findOrFail($id);
        $kategori = Kategori::orderBy('nama')->get();
        return view('barang.edit', compact('data', 'kategori'));
    }

    public function update(Request $request, $id)
    {
        $data = MasterBarang::findOrFail($id);

        $request->validate([
            'kategori_id' => 'required|exists:kategori,id',
            'kode_barang' => 'required|unique:master_barang,kode_barang,' . $data->id,
            'nama' => 'required|string',
            'satuan' => 'nullable|string',
            'jenis_utama' => 'required|in:BAHAN_BAKU,BARANG_JADI,OPERATIONAL',
            'hpp_referensi' => 'nullable|numeric|min:0',
            'harga_jual_b2b' => 'nullable|numeric|min:0',
            'harga_jual_pos' => 'nullable|numeric|min:0',
        ]);

        // 🔥 Business rules (sama seperti store)
        if ($request->jenis_utama === 'BAHAN_BAKU') {
            if (!$request->hpp_referensi) {
                return back()->withErrors(['hpp_referensi' => 'Bahan baku wajib isi HPP'])->withInput();
            }
            if ($request->harga_jual_b2b || $request->harga_jual_pos) {
                return back()->withErrors(['harga_jual_b2b' => 'Bahan baku tidak boleh punya harga jual'])->withInput();
            }
        }

        if ($request->jenis_utama === 'BARANG_JADI') {
            if (!$request->harga_jual_b2b && !$request->harga_jual_pos) {
                return back()->withErrors(['harga_jual_b2b' => 'Barang jadi wajib punya minimal 1 harga jual'])->withInput();
            }
        }

        $data->update([
            'kategori_id' => $request->kategori_id,
            'kode_barang' => $request->kode_barang,
            'nama' => $request->nama,
            'satuan' => $request->satuan,
            'jenis_utama' => $request->jenis_utama,
            'hpp_referensi' => $request->hpp_referensi,
            'harga_jual_b2b' => $request->harga_jual_b2b,
            'harga_jual_pos' => $request->harga_jual_pos,
        ]);

        return redirect()->route('barang.index')->with('success', 'Data berhasil diupdate');
    }

    public function destroy($id)
    {
        MasterBarang::findOrFail($id)->delete();
        return redirect()->route('barang.index')->with('success', 'Data berhasil dihapus');
    }
}