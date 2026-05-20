<?php

namespace App\Http\Controllers;

use App\Models\PenjualanPos;
use App\Models\PenjualanPosDetail;
use App\Models\MasterBarang;
use App\Models\MasterGudang;
use App\Models\StokGudang;
use App\Models\ResepBtklBop;
use App\Models\ResepBahanbaku;
use App\Models\HargaPeriode; 
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
        // 1. Validasi Diperketat (Mengakomodasi array dari input HTML)
        $request->validate([
            'tanggal' => 'required',
            'gudang_id' => 'required|exists:master_gudang,id',
            'produk_id' => 'required|array',
            'produk_id.*' => 'required|exists:master_barang,id',
            'qty' => 'required|array',
            'qty.*' => 'required|numeric|min:0.01',
            'harga' => 'required|array',
            'harga.*' => 'required|numeric',
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
                if (!isset($request->qty[$key]) || !isset($request->harga[$key])) {
                    continue;
                }

                $qtyTerjual = $request->qty[$key];
                $hargaJual = $request->harga[$key];
                $subtotal = $qtyTerjual * $hargaJual;
    
                // =========================================================================
                // 1. PERHITUNGAN HPP & PEMOTONGAN STOK BERDASARKAN RESEP (BYPASS FIFO)
                // =========================================================================
                $hppSatuanTotal = 0;

                // Cari Resep Master untuk Produk yang dijual
                $resepUtama = ResepBtklBop::where('produk_id', $produkId)->first();

                if ($resepUtama) {
                    
                    // A. Hitung HPP dari Tenaga Kerja (BTKL) & Overhead (BOP)
                    if ($resepUtama->output_qty > 0) {
                        $bopBtklPerPcs = ($resepUtama->btkl_per_batch + $resepUtama->bop_per_batch) / $resepUtama->output_qty;
                        $hppSatuanTotal += $bopBtklPerPcs;
                    }

                    // B. Cari Detail Bahan Baku yang dibutuhkan
                    $resepBahan = ResepBahanbaku::where('resep_id', $resepUtama->id)->get();

                    foreach ($resepBahan as $bahan) {
                        $kebutuhanPerPcs = $bahan->qty_bahan;
                        $totalDipotong = $kebutuhanPerPcs * $qtyTerjual;

                        /* |--------------------------------------------------------------------------
                        | BAGIAN FIFO SEMENTARA DI-BYPASS AGAR TIDAK ERROR TARGET CLASS NOT FOUND
                        |--------------------------------------------------------------------------
                        */
                        // $fifoService = app(\App\Services\FifoService::class);
                        // $hasilFifo = $fifoService->consumeFIFO($bahan->bahan_id, (float) $totalDipotong, (int) $request->gudang_id);

                        // SINKRONISASI TABEL SUMMARY STOK GUDANG (Tetap memotong stok global gudang)
                        $stokGudang = StokGudang::where('gudang_id', $request->gudang_id)
                                                ->where('barang_id', $bahan->bahan_id)
                                                ->first();

                        if ($stokGudang) {
                            $stokGudang->decrement('jumlah', $totalDipotong);
                        } else {
                            StokGudang::create([
                                'gudang_id' => $request->gudang_id,
                                'barang_id' => $bahan->bahan_id,
                                'jumlah'    => -$totalDipotong
                            ]);
                        }

                        // Kerangka HPP Bahan baku diset 0 dulu selama FifoService belum aktif
                        $totalHargaBahanBatch = 0; 
                        
                        if ($qtyTerjual > 0) {
                            $hppBahanPerPcs = $totalHargaBahanBatch / $qtyTerjual;
                            $hppSatuanTotal += $hppBahanPerPcs;
                        }
                    }
                }
    
                // 2. SIMPAN DETAIL TRANSAKSI
                // Catatan: Jika di database kolomnya bernama 'penjualan_id', ubah key di bawah ini menjadi 'penjualan_id'
                PenjualanPosDetail::create([ 
                    'penjualan_id' => $penjualan->id, 
                    'produk_id'    => $produkId,
                    'qty'          => $qtyTerjual,
                    'harga'        => $hargaJual,
                    'hpp_satuan'   => $hppSatuanTotal, 
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
            return back()->with('error', 'Gagal memproses transaksi (Line: '.$e->getLine().'): ' . $e->getMessage())->withInput();
        }
    }

    public function show($id)
    {
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
        // 1. Validasi Input Array (Mencegah error 'undefined index' saat manipulasi baris)
        $request->validate([
            'tanggal' => 'required',
            'gudang_id' => 'required|exists:master_gudang,id',
            'produk_id' => 'required|array',
            'produk_id.*' => 'required|exists:master_barang,id',
            'qty' => 'required|array',
            'qty.*' => 'required|numeric|min:0.01',
            'harga' => 'required|array',
            'harga.*' => 'required|numeric',
        ]);

        DB::beginTransaction();
    
        try {
            $penjualan = PenjualanPos::with('details')->findOrFail($id);
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
                    $qtyKembali = $detailLama->qty * $bahan->qty_bahan;
    
                    $stokLama = StokGudang::where('gudang_id', $gudangLamaId)
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
                if (isset($request->harga[$index])) {
                    $total_penjualan += ($qty * $request->harga[$index]);
                }
            }
    
            $penjualan->update([
                'tanggal'   => $request->tanggal,
                'gudang_id' => $request->gudang_id,
                'total'     => $total_penjualan,
            ]);
    
            // ====================================================================
            // FASE 3: APPLY BARU & HITUNG HPP (FIFO DI-BYPASS)
            // ====================================================================
            // $fifoService = app(\App\Services\FifoService::class); // <-- DI-BYPASS SEMENTARA

            foreach ($request->produk_id as $index => $produkId) {
                if (!isset($request->qty[$index]) || !isset($request->harga[$index])) {
                    continue;
                }

                $qtyBaru = $request->qty[$index];
                $hargaJual = $request->harga[$index];

                $dataResep = DB::table('resep_btkl_bop')->where('produk_id', $produkId)->first();
                
                if ($dataResep) {
                    // Hitung Beban BOP & BTKL
                    $biayaOverheadPerUnit = $dataResep->output_qty > 0 ? ($dataResep->btkl_per_batch + $dataResep->bop_per_batch) / $dataResep->output_qty : 0;
                    $totalHppSatuan = $biayaOverheadPerUnit;

                    $listBahan = DB::table('resep_bahanbaku')->where('resep_id', $dataResep->id)->get();

                    foreach ($listBahan as $bahan) {
                        $qtyPotong = $qtyBaru * $bahan->qty_bahan;
                        
                        /* |--------------------------------------------------------------------------
                        | BAGIAN FIFO SEMENTARA DI-BYPASS AGAR TIDAK ERROR TARGET CLASS NOT FOUND
                        |--------------------------------------------------------------------------
                        */
                        // $hasilFifo = $fifoService->consumeFIFO(
                        //     $bahan->bahan_id,
                        //     (float) $qtyPotong,
                        //     (int) $request->gudang_id
                        // );

                        // MOCKUP: Simulasi data array FIFO agar struktur perhitungan ke bawah tidak patah
                        $hasilFifo = [
                            [
                                'qty_keluar' => $qtyPotong,
                                'harga_per_qty' => 0 // HPP bahan baku sementara 0 rupiah
                            ]
                        ];

                        // Potong Stok Summary Global (Tetap berjalan akurat mengurangi isi gudang)
                        $stokGudang = StokGudang::where('gudang_id', $request->gudang_id)
                            ->where('barang_id', $bahan->bahan_id)
                            ->first();

                        if ($stokGudang) {
                            $stokGudang->decrement('jumlah', $qtyPotong);
                        } else {
                            StokGudang::create([
                                'gudang_id' => $request->gudang_id,
                                'barang_id' => $bahan->bahan_id,
                                'jumlah'    => -$qtyPotong
                            ]);
                        }

                        // Akumulasikan harga beli riil dari batch FIFO ke HPP
                        $totalHargaBahanBatch = 0;
                        foreach ($hasilFifo as $fifo) {
                            $totalHargaBahanBatch += ($fifo['qty_keluar'] * $fifo['harga_per_qty']);
                        }

                        if ($qtyBaru > 0) {
                            $hppBahanPerPcs = $totalHargaBahanBatch / $qtyBaru;
                            $totalHppSatuan += $hppBahanPerPcs;
                        }
                    }

                    // Simpan ke Tabel Detail Baru lewat Eloquent Relationship (Otomatis mengisi penjualan_id)
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
            return redirect()
                ->route('penjualan_pos.index')
                ->with('success', 'Penjualan POS berhasil diperbarui!');
    
        } catch (\Exception $e) {
            DB::rollBack();
            // Dialihkan kembali ke form dengan pesan error yang rapi di halaman edit
            return back()
                ->with('error', 'Gagal memproses update transaksi (Line: '.$e->getLine().'): ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
    
        try {
            $penjualan = PenjualanPos::with('details')->findOrFail($id);
            $gudangId = $penjualan->gudang_id;
    
            foreach ($penjualan->details as $detail) {
                $resepBahan = DB::table('resep_btkl_bop')
                    ->join('resep_bahanbaku', 'resep_btkl_bop.id', '=', 'resep_bahanbaku.resep_id')
                    ->where('resep_btkl_bop.produk_id', $detail->produk_id)
                    ->select('resep_bahanbaku.bahan_id', 'resep_bahanbaku.qty_bahan')
                    ->get();
    
                foreach ($resepBahan as $bahan) {
                    $qtyKembali = $detail->qty * $bahan->qty_bahan;
    
                    $stokGudang = StokGudang::where('gudang_id', $gudangId)
                        ->where('barang_id', $bahan->bahan_id)
                        ->first();
    
                    if ($stokGudang) {
                        $stokGudang->increment('jumlah', $qtyKembali);
                    }
                }
            }
    
            $penjualan->details()->delete();
            $penjualan->delete();
    
            DB::commit();
            return back()->with('success', 'Data penjualan dihapus dan stok bahan baku telah dikembalikan ke gudang!');
    
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | API ENDPOINT: AMBIL HARGA POS AKTIF BERDASARKAN PERIODE TANGGAL
    |--------------------------------------------------------------------------
    | REVISI: Menggunakan sistem Fallback (Pengaman) agar data harga tidak return 0
    */
    public function getHargaAktif(Request $request, $produk_id)
    {
        // 1. Ambil tanggal dari form input, ubah formatnya ke Y-m-d
        $tanggal = $request->tanggal ? date('Y-m-d', strtotime($request->tanggal)) : now()->toDateString();

        // 2. Metode Utama: Cari harga aktif sesuai dengan periode tanggal berjalan
        $hargaAktif = HargaPeriode::where('barang_id', $produk_id)
            ->whereDate('tgl_mulai', '<=', $tanggal) 
            ->where(function($query) use ($tanggal) {
                $query->whereNull('tgl_selesai')
                      ->orWhereDate('tgl_selesai', '>=', $tanggal);
            })
            ->orderBy('tgl_mulai', 'desc')
            ->first();

        // 3. METODE PENGAMAN (FALLBACK):
        // Jika filter tanggal di atas gagal menemukan data (misal status periode sudah diakhiri),
        // ambil entri harga terakhir yang tersedia di database untuk produk ini agar kasir tidak kosong (0)
        if (!$hargaAktif) {
            $hargaAktif = HargaPeriode::where('barang_id', $produk_id)
                ->orderBy('tgl_mulai', 'desc')
                ->first();
        }

        // 4. Kembalikan data harga dalam bentuk JSON
        return response()->json([
            'harga' => $hargaAktif ? $hargaAktif->harga_pos : 0
        ]);
    }
}