<?php

namespace App\Http\Controllers;

use App\Models\PenjualanPos;
use App\Models\PenjualanPosDetail;
use App\Models\MasterBarang;
use App\Models\MasterGudang;
use App\Models\StokGudang;
use App\Models\ResepBtklBop;
use App\Models\ResepBahanbaku;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PenjualanPosController extends Controller
{
    public function index()
    {
        $data = PenjualanPos::latest()->get();

        return view('penjualan_pos.index', compact('data'));
    }

    public function create()
    {
        $produk = MasterBarang::where('is_barang_jadi', 1)->get();

        $gudang = MasterGudang::all();

        return view('penjualan_pos.create', compact(
            'produk',
            'gudang'
        ));
    }

    public function store(Request $request)
    {
        // Validasi dasar
        $request->validate([
            'tanggal' => 'required',
            'gudang_id' => 'required',
            'produk_id' => 'required|array',
            'qty' => 'required|array',
        ]);
    
        DB::beginTransaction();
    
        try {
            $penjualan = PenjualanPos::create([
                'kode_transaksi' => 'POS-' . time(),
                'tanggal' => $request->tanggal,
                'gudang_id' => $request->gudang_id,
                'total' => 0,
                'created_by' => auth()->id()
            ]);
    
            $total = 0;
    
            foreach ($request->produk_id as $key => $produkId) {
                $qtyTerjual = $request->qty[$key];
                $hargaJual = $request->harga[$key];
                $subtotal = $qtyTerjual * $hargaJual;
    
                // =========================================================
                // 1. PERHITUNGAN HPP & PEMOTONGAN STOK BERDASARKAN RESEP
                // =========================================================
                $hppSatuanTotal = 0;
    
                // Cari Resep Master untuk Produk yang dijual
                $resepUtama = ResepBtklBop::where('produk_id', $produkId)->first();
    
                if ($resepUtama) {
                    
                    // A. Hitung HPP dari Tenaga Kerja (BTKL) & Overhead (BOP)
                    // Karena inputnya PER BATCH, kita bagi dengan output_qty agar mendapat nilai per 1 pcs
                    if ($resepUtama->output_qty > 0) {
                        $bopBtklPerPcs = ($resepUtama->btkl_per_batch + $resepUtama->bop_per_batch) / $resepUtama->output_qty;
                        $hppSatuanTotal += $bopBtklPerPcs;
                    }
    
                    // B. Cari Detail Bahan Baku yang dibutuhkan
                    $resepBahan = ResepBahanbaku::where('resep_id', $resepUtama->id)->get();
    
                    foreach ($resepBahan as $bahan) {
                        // KARENA INPUT SUDAH PER SATUAN PRODUK, langsung ambil qty_bahan-nya
                        $kebutuhanPerPcs = $bahan->qty_bahan;
                        
                        // Total bahan baku yang harus dipotong dari gudang (Qty Bahan x Qty Produk Terjual)
                        $totalDipotong = $kebutuhanPerPcs * $qtyTerjual;
    
                        // Cari stok di gudang yang dipilih (Gaharu / Kejingga)
                        $stokGudang = StokGudang::where('gudang_id', $request->gudang_id)
                                                ->where('barang_id', $bahan->bahan_id)
                                                ->first();
    
                        if ($stokGudang) {
                            // Kurangi jumlah stok di database
                            $stokGudang->decrement('jumlah', $totalDipotong);
                        } else {
                            // Jika tidak ada data stok sama sekali, buat baru dengan nilai minus
                            StokGudang::create([
                                'gudang_id' => $request->gudang_id,
                                'barang_id' => $bahan->bahan_id,
                                'jumlah'    => -$totalDipotong
                            ]);
                        }
    
                        // C. Tambahkan Biaya Bahan Baku ke HPP Satuan
                        // (Sesuaikan \App\Models\Barang dan 'harga_beli' dengan nama tabel/kolom master barang Anda)
                        $hargaBahanBaku = \App\Models\MasterBarang::find($bahan->bahan_id)->harga_beli ?? 0;
                        $hppSatuanTotal += ($kebutuhanPerPcs * $hargaBahanBaku);
                    }
                }
                // =========================================================
    
                // 2. SIMPAN DETAIL TRANSAKSI
                PenjualanPosDetail::create([ // <-- Pastikan ini memanggil model detail yang benar
                    'penjualan_id' => $penjualan->id,
                    'produk_id'    => $produkId,
                    'qty'          => $qtyTerjual,
                    'harga'        => $hargaJual,
                    'hpp_satuan'   => $hppSatuanTotal, // HPP dari Bahan Baku + BOP + BTKL masuk ke sini
                    'subtotal'     => $subtotal
                ]);
    
                $total += $subtotal;
            }
    
            $penjualan->update([
                'total' => $total
            ]);
    
            DB::commit();
    
            return redirect()
                ->route('penjualan_pos.index')
                ->with('success', 'Penjualan berhasil disimpan dan stok otomatis dipotong.');
    
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal memproses transaksi: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        // Ambil data penjualan berdasarkan ID, sekalian ambil relasi detail, produk, dan gudang
        // Sesuaikan nama relasi ('details', 'produk', 'gudang') dengan yang ada di Model Anda
        $penjualan = PenjualanPos::with(['details', 'gudang'])->findOrFail($id);
    
        return view('penjualan_pos.show', compact('penjualan'));
    }
    
    public function edit($id)
    {
        $penjualan = PenjualanPos::with(
            'details.produk',
            'gudang'
        )->findOrFail($id);

        $produk = MasterBarang::where('is_barang_jadi', 1)->get();

        $gudang = MasterGudang::all();

        return view('penjualan_pos.edit', compact(
            'penjualan',
            'produk',
            'gudang'
        ));
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();
    
        try {
            $penjualan = \App\Models\PenjualanPos::with('details')->findOrFail($id);
            $gudangLamaId = $penjualan->gudang_id;
    
            // ====================================================================
            // FASE 1: REVERT (KEMBALIKAN STOK LAMA)
            // ====================================================================
            foreach ($penjualan->details as $detailLama) {
                $resepLama = DB::table('resep_btkl_bop')
                    ->join('resep_bahanbaku', 'resep_btkl_bop.id', '=', 'resep_bahanbaku.resep_id')
                    ->where('resep_btkl_bop.produk_id', $detailLama->produk_id)
                    ->get();
    
                foreach ($resepLama as $bahan) {
                    // LOGIKA: Bahan baku sudah per satuan, jadi langsung dikali Qty
                    $qtyKembali = $detailLama->qty * $bahan->qty_bahan;
    
                    $stokLama = \App\Models\StokGudang::where('gudang_id', $gudangLamaId)
                        ->where('barang_id', $bahan->bahan_id)
                        ->first();
    
                    if ($stokLama) {
                        $stokLama->jumlah += $qtyKembali;
                        $stokLama->save();
                    }
                }
            }
    
            // Hapus detail lama agar bisa diganti dengan yang baru
            $penjualan->details()->delete();
    
            // ====================================================================
            // FASE 2: UPDATE HEADER & HITUNG TOTAL BARU
            // ====================================================================
            $total_penjualan = 0;
            foreach ($request->qty as $index => $qty) {
                $total_penjualan += ($qty * $request->harga[$index]);
            }
    
            $penjualan->update([
                'tanggal'   => $request->tanggal,
                'gudang_id' => $request->gudang_id,
                'total'     => $total_penjualan,
            ]);
    
            // ====================================================================
            // FASE 3: APPLY (INPUT DETAIL BARU & POTONG STOK)
            // ====================================================================
          // ====================================================================
// FASE 3: APPLY (INPUT DETAIL BARU & POTONG STOK)
// ====================================================================
foreach ($request->produk_id as $index => $produkId) {
    $qtyBaru = $request->qty[$index];
    $hargaJual = $request->harga[$index];

    $dataResep = DB::table('resep_btkl_bop')->where('produk_id', $produkId)->first();
    
    // Pastikan resep ada sebelum lanjut
    if ($dataResep) {
        $biayaOverheadPerUnit = ($dataResep->btkl_per_batch + $dataResep->bop_per_batch) / $dataResep->output_qty;
        $totalHppSatuan = $biayaOverheadPerUnit;

        $listBahan = DB::table('resep_bahanbaku')->where('resep_id', $dataResep->id)->get();

        foreach ($listBahan as $bahan) {
            // GUNAKAN MODEL UNTUK MENGAMBIL HARGA BELI (Lebih aman)
            $masterBahan = \App\Models\MasterBarang::find($bahan->bahan_id);
            $hargaBeliBahan = $masterBahan ? $masterBahan->harga_beli : 0;
            
            $totalHppSatuan += ($bahan->qty_bahan * $hargaBeliBahan);

            // POTONG STOK GUDANG
            $qtyPotong = $qtyBaru * $bahan->qty_bahan;
            
            $stokGudang = \App\Models\StokGudang::where('gudang_id', $request->gudang_id)
                ->where('barang_id', $bahan->bahan_id)
                ->first();

            if ($stokGudang) {
                $stokGudang->decrement('jumlah', $qtyPotong);
            }
        }

        // Simpan ke Tabel Detail
        $penjualan->details()->create([
            'produk_id'  => $produkId,
            'qty'        => $qtyBaru,
            'harga'      => $hargaJual,
            'subtotal'   => $qtyBaru * $hargaJual,
            'hpp_satuan' => $totalHppSatuan,
        ]);
    }
}
            DB::commit();
            return redirect()->route('penjualan_pos.index')->with('success', 'Penjualan berhasil diupdate!');
    
        } catch (\Exception $e) {
            DB::rollBack();
            // Kita paksa sistem memunculkan errornya di layar putih
            dd('Error di baris ' . $e->getLine() . ': ' . $e->getMessage()); 
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
    
        try {
            // 1. Cari data penjualan beserta detailnya
            $penjualan = PenjualanPos::with('details')->findOrFail($id);
            $gudangId = $penjualan->gudang_id;
    
            // 2. Loop setiap produk dalam transaksi tersebut
            foreach ($penjualan->details as $detail) {
                // Cari resep bahan baku untuk produk ini
                $resepBahan = DB::table('resep_btkl_bop')
                    ->join('resep_bahanbaku', 'resep_btkl_bop.id', '=', 'resep_bahanbaku.resep_id')
                    ->where('resep_btkl_bop.produk_id', $detail->produk_id)
                    ->select('resep_bahanbaku.bahan_id', 'resep_bahanbaku.qty_bahan')
                    ->get();
    
                foreach ($resepBahan as $bahan) {
                    // Hitung jumlah yang harus dikembalikan (Qty Jual x Qty Bahan di Resep)
                    $qtyKembali = $detail->qty * $bahan->qty_bahan;
    
                    // Cari stok di gudang terkait
                    $stokGudang = StokGudang::where('gudang_id', $gudangId)
                        ->where('barang_id', $bahan->bahan_id)
                        ->first();
    
                    if ($stokGudang) {
                        // Tambahkan kembali stoknya (Revert)
                        $stokGudang->increment('jumlah', $qtyKembali);
                    }
                }
            }
    
            // 3. Setelah stok kembali, hapus data penjualan (detail akan ikut terhapus jika ada cascade atau hapus manual)
            $penjualan->details()->delete();
            $penjualan->delete();
    
            DB::commit();
            return back()->with('success', 'Data penjualan dihapus dan stok bahan baku telah dikembalikan ke gudang!');
    
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }
}