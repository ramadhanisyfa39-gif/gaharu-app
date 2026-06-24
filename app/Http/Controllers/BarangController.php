<?php

namespace App\Http\Controllers;

use App\Models\MasterBarang;
use App\Models\Kategori;
use App\Models\ResepBtklBop; // <--- Tambahkan Model Resep di sini
use Illuminate\Http\Request;

class BarangController extends Controller
{
    // Jalur: app/Http/Controllers/BarangController.php

    public function index()
    {
        // 1. Kode kamu yang sudah ada untuk mengambil data barang (biasanya seperti ini)
        $data = MasterBarang::all(); // atau Barang::latest()->get(); sesuaikan dengan kode aslimu

        // 2. TAMBAHKAN BARIS INI untuk mengambil semua data kategori dari database
        // (Pastikan nama Model Kategori sesuai dengan nama model di projekmu, misal: Kategori atau Category)
        $kategori = \App\Models\Kategori::all(); 

        // 3. Tambahkan 'kategori' di dalam fungsi compact() agar terkirim ke view index
        return view('barang.index', compact('data', 'kategori'));
    }

    public function create()
    {
        $kategori = Kategori::all();
        $reseps = ResepBtklBop::all(); // <--- Tambahkan ini untuk kirim ke view
        return view('barang.create', compact('kategori', 'reseps'));
    }

    public function generateKode($kategoriId)
    {
        $kategori = Kategori::findOrFail($kategoriId);

        // Ambil 3 huruf depan nama kategori
        $prefix = strtoupper(substr($kategori->nama, 0, 3));

        // Cari kode terakhir berdasarkan prefix
        $lastBarang = MasterBarang::where('kode_barang', 'like', $prefix . '%')
            ->orderBy('kode_barang', 'desc')
            ->first();

        if ($lastBarang) {

            // Ambil angka terakhir
            $lastNumber = (int) substr($lastBarang->kode_barang, 3);

            $newNumber = $lastNumber + 1;

        } else {

            $newNumber = 1;
        }

        // Format jadi COF001
        $kodeBarang = $prefix . str_pad($newNumber, 3, '0', STR_PAD_LEFT);

        return response()->json([
            'kode_barang' => $kodeBarang
        ]);
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
                'minimum_stock'         => $request->minimum_stock, // <-- FIX: Sekarang data minimum_stock ikut tersimpan ke database
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
            'minimum_stock'  => $request->minimum_stock, // <-- FIX: Biar kalau diedit nilainya ter-update
        ]);
    
        return redirect()->route('barang.index')->with('success', 'Data berhasil diupdate');
    }

    public function destroy(MasterBarang $barang)
    {
        // Cek apakah barang sudah dipakai di tabel manapun
        $dipakai = \Illuminate\Support\Facades\DB::table('pembelian_detail')
                    ->where('barang_id', $barang->id)->exists()
                || \Illuminate\Support\Facades\DB::table('stok_gudang')
                    ->where('barang_id', $barang->id)->exists()
                || \Illuminate\Support\Facades\DB::table('pengeluaran_bahan_baku_detail')
                    ->where('barang_id', $barang->id)->exists()
                || \Illuminate\Support\Facades\DB::table('stock_opname_detail')
                    ->where('barang_id', $barang->id)->exists();

        if ($dipakai) {
            return back()->with('error', 'Barang sudah digunakan dalam transaksi dan tidak bisa dihapus. Gunakan fitur nonaktifkan jika barang tidak lagi dipakai.');
        }

        $barang->delete();

        return back()->with('success', 'Barang berhasil dihapus.');
    }

    public function toggleStatus($id)
    {
        $barang = \App\Models\MasterBarang::findOrFail($id);
        $barang->is_active = !$barang->is_active;
        $barang->save();

        return back()->with('success', 'Status barang berhasil diubah.');
    }

    public function toggle(MasterBarang $barang)
    {
        $barang->update([
            'is_active' => !$barang->is_active,
        ]);

        return back()->with('success', 'Status barang berhasil diubah.');
    }
} // <-- FIX: Kurung tutup ganda yang salah sudah dihapus