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
        return view('penjualan_pos.create', compact('produk', 'gudang'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'tanggal'     => 'required',
            'gudang_id'   => 'required|exists:master_gudang,id',
            'produk_id'   => 'required|array',
            'produk_id.*' => 'required|exists:master_barang,id',
            'qty'         => 'required|array',
            'qty.*'       => 'required|numeric|min:0.01',
            'harga'       => 'required|array',
            'harga.*'     => 'required|numeric',
        ]);
    
        DB::beginTransaction();
    
        try {
            // ====================================================================
            // FASE 1: VALIDASI TOTAL KEBUTUHAN BAHAN BAKU (MENGGUNAKAN QUERY BUILDER MURNI)
            // ====================================================================
            $totalKebutuhanBahan = []; 

            foreach ($request->produk_id as $key => $produkId) {
                if (!isset($request->qty[$key])) continue;
                $qtyTerjual = floatval($request->qty[$key]);

                // Ambil data resep utama
                $resepUtama = DB::table('resep_btkl_bop')->where('produk_id', $produkId)->first();
                
                if ($resepUtama) {
                    // Ambil daftar bahan baku pendukung resep
                    $resepBahan = DB::table('resep_bahanbaku')->where('resep_id', $resepUtama->id)->get();
                    $outputQty = floatval($resepUtama->output_qty) > 0 ? floatval($resepUtama->output_qty) : 1;

                    foreach ($resepBahan as $bahan) {
                        // Rumus: Kebutuhan bahan baku disesuaikan dengan output qty per batch resep
                        $kebutuhanPerPcs = floatval($bahan->qty_bahan) / $outputQty;
                        $butuh = $kebutuhanPerPcs * $qtyTerjual;

                        if (isset($totalKebutuhanBahan[$bahan->bahan_id])) {
                            $totalKebutuhanBahan[$bahan->bahan_id]['jumlah'] += $butuh;
                        } else {
                            // Ambil nama barang langsung via DB Query untuk menghindari crash model relation
                            $barang = DB::table('master_barang')->where('id', $bahan->bahan_id)->first();
                            $namaBahan = $barang ? $barang->nama : 'Bahan ID #' . $bahan->bahan_id;

                            $totalKebutuhanBahan[$bahan->bahan_id] = [
                                'nama' => $namaBahan,
                                'jumlah' => $butuh
                            ];
                        }
                    }
                }
            }

            // Cek ketersediaan stok fisik di database berdasarkan total akumulasi kebutuhan
            $pesanErrorStok = [];
            foreach ($totalKebutuhanBahan as $bahanId => $dataBahan) {
                $stokTersedia = DB::table('stok_gudang_batch')
                    ->where('gudang_id', $request->gudang_id)
                    ->where('barang_id', $bahanId)
                    ->where('qty_sisa', '>', 0)
                    ->sum('qty_sisa');

                if ($stokTersedia < $dataBahan['jumlah']) {
                    $kurangnya = $dataBahan['jumlah'] - $stokTersedia;
                    $pesanErrorStok[] = "• {$dataBahan['nama']} (Butuh: {$dataBahan['jumlah']}, Sisa di sistem: {$stokTersedia})";
                }
            }

            // Jika ada bahan baku kurang, kirim error flash message kembali ke halaman create
            if (!empty($pesanErrorStok)) {
                DB::rollBack();
                $errorList = implode('<br>', $pesanErrorStok);
                return back()->with('error', "<b>Gagal Simpan Rekap!</b> Stok operasional tidak mencukupi. Silakan lakukan permintaan bahan baku ke Gudang Utama terlebih dahulu untuk barang berikut:<br>" . $errorList)->withInput();
            }

            // ====================================================================
            // FASE 2: PROSES SIMPAN TRANSAKSI & POTONG STOK (FIFO)
            // ====================================================================
            $penjualan = PenjualanPos::create([
                'kode_transaksi' => 'POS-' . time(),
                'tanggal'        => date('Y-m-d H:i:s', strtotime($request->tanggal)),
                'gudang_id'      => $request->gudang_id,
                'total'          => 0,
                'created_by'     => auth()->id() ?? 1
            ]);
    
            $total_penjualan = 0;
    
            foreach ($request->produk_id as $key => $produkId) {
                if (!isset($request->qty[$key]) || !isset($request->harga[$key])) continue;

                $qtyTerjual = floatval($request->qty[$key]);
                $hargaJual  = floatval($request->harga[$key]);
                $subtotal   = $qtyTerjual * $hargaJual;
    
                $hppSatuanTotal = 0;
                $bopBtklPerPcs  = 0;
                $totalHppBahan  = 0;

                $resepUtama = DB::table('resep_btkl_bop')->where('produk_id', $produkId)->first();

                if ($resepUtama) {
                    $outputQty = floatval($resepUtama->output_qty) > 0 ? floatval($resepUtama->output_qty) : 1;
                    $bopBtklPerPcs = (floatval($resepUtama->btkl_per_batch) + floatval($resepUtama->bop_per_batch)) / $outputQty;

                    $resepBahan = DB::table('resep_bahanbaku')->where('resep_id', $resepUtama->id)->get();

                    foreach ($resepBahan as $bahan) {
                        $kebutuhanPerPcs = floatval($bahan->qty_bahan) / $outputQty;
                        $totalDipotong = $kebutuhanPerPcs * $qtyTerjual;

                        // Potong Stok Global Gudang
                        $stokGudang = StokGudang::firstOrCreate(
                            ['gudang_id' => $request->gudang_id, 'barang_id' => $bahan->bahan_id],
                            ['jumlah' => 0]
                        );
                        $stokGudang->decrement('jumlah', $totalDipotong);

                        // Potong FIFO pada tabel Batch Bahan Baku
                        $stokBatches = DB::table('stok_gudang_batch')
                            ->where('gudang_id', $request->gudang_id)
                            ->where('barang_id', $bahan->bahan_id)
                            ->where('qty_sisa', '>', 0)
                            ->orderBy('id', 'asc')
                            ->get();

                        $sisaKebutuhan = $totalDipotong;
                        
                        foreach ($stokBatches as $batch) {
                            if ($sisaKebutuhan <= 0) break;

                            $diambil = min($sisaKebutuhan, $batch->qty_sisa);
                            $totalHppBahan += ($diambil * $batch->harga_per_qty); 

                            DB::table('stok_gudang_batch')->where('id', $batch->id)->decrement('qty_sisa', $diambil);
                            $sisaKebutuhan -= $diambil;
                        }

                        DB::table('stok_gudang_batch')->where('qty_sisa', '<=', 0)->update(['is_habis' => 1]);
                    }
                }

                if ($qtyTerjual > 0) {
                    $hppBahanPerPcs = $totalHppBahan / $qtyTerjual;
                    $hppSatuanTotal = $hppBahanPerPcs + $bopBtklPerPcs;
                }
    
                PenjualanPosDetail::create([ 
                    'penjualan_id' => $penjualan->id, 
                    'produk_id'    => $produkId,
                    'qty'          => $qtyTerjual,
                    'harga'        => $hargaJual,
                    'hpp_satuan'   => $hppSatuanTotal, 
                    'subtotal'     => $subtotal
                ]);
    
                $total_penjualan += $subtotal;
            }
    
            $penjualan->update(['total' => $total_penjualan]);
            DB::commit();
    
            return redirect()->route('penjualan_pos.index')->with('success', 'Rekap penjualan berhasil disimpan!');
    
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan sistem: ' . $e->getMessage())->withInput();
        }
    }

    public function show($id)
    {
        $penjualan = PenjualanPos::with(['details', 'gudang'])->findOrFail($id);
        return view('penjualan_pos.show', compact('penjualan'));
    }
    
    public function edit($id)
    {
        $penjualan = PenjualanPos::with('details.produk', 'gudang')->findOrFail($id);
        $produk    = MasterBarang::where('is_barang_jadi', 1)->get();
        $gudang    = MasterGudang::all();

        return view('penjualan_pos.edit', compact('penjualan', 'produk', 'gudang'));
    }

    public function destroy($id)
    {
        DB::beginTransaction();
    
        try {
            $penjualan = PenjualanPos::with('details')->findOrFail($id);
            $gudangId  = $penjualan->gudang_id;
    
            foreach ($penjualan->details as $detail) {
                $resepBahan = DB::table('resep_btkl_bop')
                    ->join('resep_bahanbaku', 'resep_btkl_bop.id', '=', 'resep_bahanbaku.resep_id')
                    ->where('resep_btkl_bop.produk_id', $detail->produk_id)
                    ->select('resep_bahanbaku.bahan_id', 'resep_bahanbaku.qty_bahan')
                    ->get();
    
                foreach ($resepBahan as $bahan) {
                    $qtyKembali = $detail->qty * $bahan->qty_bahan;
    
                    $stokGudang = StokGudang::where('gudang_id', $gudangId)->where('barang_id', $bahan->bahan_id)->first();
                    if ($stokGudang) $stokGudang->increment('jumlah', $qtyKembali);

                    $lastBatch = DB::table('stok_gudang_batch')->where('gudang_id', $gudangId)->where('barang_id', $bahan->bahan_id)->orderBy('id', 'desc')->first();
                    if ($lastBatch) {
                        DB::table('stok_gudang_batch')->where('id', $lastBatch->id)->update([
                            'qty_sisa' => DB::raw("qty_sisa + $qtyKembali"),
                            'is_habis' => 0
                        ]);
                    }
                }
            }
    
            $penjualan->details()->delete();
            $penjualan->delete();
    
            DB::commit();
            return back()->with('success', 'Data penjualan dihapus, stok Bahan Baku otomatis dikembalikan!');
    
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }

    public function getHargaAktif(Request $request, $produk_id)
    {
        $tanggal = $request->tanggal ? date('Y-m-d', strtotime($request->tanggal)) : now()->toDateString();
        $hargaAktif = HargaPeriode::where('barang_id', $produk_id)
            ->whereDate('tgl_mulai', '<=', $tanggal) 
            ->where(function($query) use ($tanggal) {
                $query->whereNull('tgl_selesai')->orWhereDate('tgl_selesai', '>=', $tanggal);
            })
            ->orderBy('tgl_mulai', 'desc')
            ->first();

        if (!$hargaAktif) {
            $hargaAktif = HargaPeriode::where('barang_id', $produk_id)->orderBy('tgl_mulai', 'desc')->first();
        }

        return response()->json(['harga' => $hargaAktif ? $hargaAktif->harga_pos : 0]);
    }
}