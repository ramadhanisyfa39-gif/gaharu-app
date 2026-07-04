<?php

namespace App\Http\Controllers;

use App\Models\PenjualanPos;
use App\Models\PenjualanPosDetail;
use App\Models\MasterBarang;
use App\Models\MasterGudang;
use App\Models\StokGudang;
use App\Models\HargaPeriode; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
            $kodePos = 'POS-' . time();
            $tanggalTrans = date('Y-m-d H:i:s', strtotime($request->tanggal));

            // ====================================================================
            // FASE 1: REKAP TOTAL KEBUTUHAN BAHAN BAKU UNTUK SEMUA ITEM POS
            // ====================================================================
            $totalKebutuhanBahan = []; 

            foreach ($request->produk_id as $key => $produkId) {
                if (!isset($request->qty[$key])) continue;
                $qtyTerjual = floatval($request->qty[$key]);

                $resepUtama = DB::table('resep_btkl_bop')->where('produk_id', $produkId)->first();
                
                if ($resepUtama) {
                    $resepBahan = DB::table('resep_bahanbaku')->where('resep_id', $resepUtama->id)->get();
                    $outputQty = floatval($resepUtama->output_qty) > 0 ? floatval($resepUtama->output_qty) : 1;

                    foreach ($resepBahan as $bahan) {
                        $kebutuhanPerPcs = floatval($bahan->qty_bahan) / $outputQty;
                        $butuh = $kebutuhanPerPcs * $qtyTerjual;

                        if (isset($totalKebutuhanBahan[$bahan->bahan_id])) {
                            $totalKebutuhanBahan[$bahan->bahan_id]['jumlah'] += $butuh;
                        } else {
                            $barang = DB::table('master_barang')->where('id', $bahan->bahan_id)->first();
                            $totalKebutuhanBahan[$bahan->bahan_id] = [
                                'nama'   => $barang ? $barang->nama : 'Bahan',
                                'satuan' => $barang ? $barang->satuan : 'Pcs',
                                'jumlah' => $butuh
                            ];
                        }
                    }
                }
            }

            // Cek Ketersediaan Stok sebelum memotong
            $pesanErrorStok = [];
            foreach ($totalKebutuhanBahan as $bahanId => $dataBahan) {
                $stokTersedia = DB::table('stok_gudang_batch')
                    ->where('gudang_id', $request->gudang_id)
                    ->where('barang_id', $bahanId)
                    ->where('qty_sisa', '>', 0)
                    ->sum('qty_sisa');

                if ($stokTersedia < $dataBahan['jumlah']) {
                    $pesanErrorStok[] = "• {$dataBahan['nama']} (Butuh: {$dataBahan['jumlah']}, Sisa: {$stokTersedia})";
                }
            }

            if (!empty($pesanErrorStok)) {
                DB::rollBack();
                $errorList = implode('<br>', $pesanErrorStok);
                return back()->with('error', "<b>Gagal!</b> Stok bahan baku tidak mencukupi:<br>" . $errorList)->withInput();
            }

            // ====================================================================
            // FASE 2: BUAT DOKUMEN PENGELUARAN BAHAN BAKU & POTONG FIFO
            // ====================================================================
            $pengeluaranId = DB::table('pengeluaran_bahan_baku')->insertGetId([
                'kode_pengeluaran' => 'OUT-' . $kodePos,
                'tanggal'          => $tanggalTrans,
                'gudang_id'        => $request->gudang_id,
                'status'           => 'approved', 
                'keterangan'       => 'AUTO_POS:' . $kodePos, 
                'created_by'       => auth()->id() ?? 1,
                'approved_by'      => auth()->id() ?? 1,
                'approved_at'      => now(),
                'created_at'       => now(),
                'updated_at'       => now()
            ]);

            $mapHppBahanAvg = []; 

            foreach ($totalKebutuhanBahan as $bahanId => $dataBahan) {
                $totalDipotong = $dataBahan['jumlah'];
                $totalHppBahanGrup = 0;

                $pengeluaranDetailId = DB::table('pengeluaran_bahan_baku_detail')->insertGetId([
                    'pengeluaran_id' => $pengeluaranId,
                    'barang_id'      => $bahanId,
                    'qty'            => $totalDipotong,
                    'satuan'         => $dataBahan['satuan'],
                    'harga_satuan'   => 0, 
                    'total_harga'    => 0,
                    'hpp_total'      => 0,
                    'created_at'     => now(),
                    'updated_at'     => now()
                ]);

                // Potong Stok Global Gudang
                $stokGudang = StokGudang::firstOrCreate(
                    ['gudang_id' => $request->gudang_id, 'barang_id' => $bahanId],
                    ['jumlah' => 0]
                );
                $stokGudang->decrement('jumlah', $totalDipotong);

                // Ambil batch FIFO terlama
                $stokBatches = DB::table('stok_gudang_batch')
                    ->where('gudang_id', $request->gudang_id)
                    ->where('barang_id', $bahanId)
                    ->where('qty_sisa', '>', 0)
                    ->orderBy('id', 'asc')
                    ->get();

                $sisaKebutuhan = $totalDipotong;
                
                foreach ($stokBatches as $batch) {
                    if ($sisaKebutuhan <= 0) break;

                    $diambil = min($sisaKebutuhan, $batch->qty_sisa);
                    $nilaiHppDiambil = $diambil * $batch->harga_per_qty;
                    $totalHppBahanGrup += $nilaiHppDiambil; 

                    // UPDATE UTAMA: Kurangi sisa & Tambah qty_keluar pada tabel stok_gudang_batch
                    DB::table('stok_gudang_batch')->where('id', $batch->id)->update([
                        'qty_sisa'   => DB::raw("qty_sisa - {$diambil}"),
                        'qty_keluar' => DB::raw("qty_keluar + {$diambil}")
                    ]);
                    
                    // Catat ke Log Pengeluaran FIFO
                    DB::table('pengeluaran_bahan_baku_fifo')->insert([
                        'pengeluaran_id' => $pengeluaranId,
                        'detail_id'      => $pengeluaranDetailId,
                        'batch_id'       => $batch->id,
                        'batch_number'   => $batch->no_batch ?? $batch->batch_number ?? '-',
                        'qty_keluar'     => $diambil,
                        'harga_per_qty'  => $batch->harga_per_qty,
                        'total_harga'    => $nilaiHppDiambil,
                        'created_at'     => now(),
                        'updated_at'     => now()
                    ]);

                    $sisaKebutuhan -= $diambil;
                }

                DB::table('stok_gudang_batch')->where('qty_sisa', '<=', 0)->update(['is_habis' => 1]);

                $avgHppSatuan = $totalDipotong > 0 ? ($totalHppBahanGrup / $totalDipotong) : 0;
                DB::table('pengeluaran_bahan_baku_detail')->where('id', $pengeluaranDetailId)->update([
                    'harga_satuan' => $avgHppSatuan,
                    'total_harga'  => $totalHppBahanGrup,
                    'hpp_total'    => $totalHppBahanGrup
                ]);

                $mapHppBahanAvg[$bahanId] = $avgHppSatuan;
            }

            // ====================================================================
            // FASE 3: SIMPAN DATA PENJUALAN POS
            // ====================================================================
            $penjualan = PenjualanPos::create([
                'kode_transaksi' => $kodePos,
                'status'         => 'SUKSES', 
                'tanggal'        => $tanggalTrans,
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
    
                $hppSatuanProduk = 0;
                $bopBtklPerPcs   = 0;
                $totalHppBahan   = 0;

                $resepUtama = DB::table('resep_btkl_bop')->where('produk_id', $produkId)->first();

                if ($resepUtama) {
                    $outputQty = floatval($resepUtama->output_qty) > 0 ? floatval($resepUtama->output_qty) : 1;
                    $bopBtklPerPcs = (floatval($resepUtama->btkl_per_batch) + floatval($resepUtama->bop_per_batch)) / $outputQty;

                    $resepBahan = DB::table('resep_bahanbaku')->where('resep_id', $resepUtama->id)->get();
                    foreach ($resepBahan as $bahan) {
                        $kebutuhanPerPcs = floatval($bahan->qty_bahan) / $outputQty;
                        $hppBahanIni = $mapHppBahanAvg[$bahan->bahan_id] ?? 0;
                        $totalHppBahan += ($kebutuhanPerPcs * $hppBahanIni);
                    }
                }

                $hppSatuanProduk = $totalHppBahan + $bopBtklPerPcs;
    
                PenjualanPosDetail::create([ 
                    'penjualan_id' => $penjualan->id, 
                    'produk_id'    => $produkId,
                    'qty'          => $qtyTerjual,
                    'harga'        => $hargaJual,
                    'hpp_satuan'   => $hppSatuanProduk, 
                    'subtotal'     => $subtotal
                ]);
    
                $total_penjualan += $subtotal;
            }
    
            $penjualan->update(['total' => $total_penjualan]);
            DB::commit();
    
            return redirect()->route('penjualan_pos.index')->with('success', 'Transaksi berhasil, Stok Gudang & Batch FIFO diperbarui!');
    
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error Simpan POS: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan sistem: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $penjualan = PenjualanPos::findOrFail($id);
            
            if ($penjualan->status == 'VOID') {
                return back()->with('error', 'Transaksi ini sudah di-Void.');
            }

            $keteranganLink = 'AUTO_POS:' . $penjualan->kode_transaksi;
            $pengeluaran = DB::table('pengeluaran_bahan_baku')
                ->where('keterangan', $keteranganLink)
                ->first();

            if ($pengeluaran) {
                $fifoLogs = DB::table('pengeluaran_bahan_baku_fifo')
                    ->where('pengeluaran_id', $pengeluaran->id)
                    ->get();

                foreach ($fifoLogs as $log) {
                    if ($log->qty_keluar > 0) {
                        // UPDATE UTAMA VOID: Kembalikan qty_sisa dan kurangi qty_keluar di tabel stok_gudang_batch
                        DB::table('stok_gudang_batch')->where('id', $log->batch_id)->update([
                            'qty_sisa'   => DB::raw("qty_sisa + {$log->qty_keluar}"),
                            'qty_keluar' => DB::raw("qty_keluar - {$log->qty_keluar}"),
                            'is_habis'   => 0
                        ]);

                        // Kembalikan ke stok global gudang
                        $detail = DB::table('pengeluaran_bahan_baku_detail')->where('id', $log->detail_id)->first();
                        if ($detail) {
                            $stokGudang = StokGudang::where('gudang_id', $pengeluaran->gudang_id)
                                                    ->where('barang_id', $detail->barang_id)
                                                    ->first();
                            if ($stokGudang) {
                                $stokGudang->increment('jumlah', $log->qty_keluar);
                            }
                        }
                    }
                }

                DB::table('pengeluaran_bahan_baku')->where('id', $pengeluaran->id)->update([
                    'status'     => 'batal', 
                    'keterangan' => $pengeluaran->keterangan . ' (VOIDED)'
                ]);
            }

            $penjualan->update(['status' => 'VOID']);

            DB::commit();
            return back()->with('success', 'Transaksi berhasil di-VOID! Seluruh kolom Stok Batch sinkron kembali.');
    
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error Void POS: ' . $e->getMessage());
            return back()->with('error', 'Gagal melakukan VOID: ' . $e->getMessage());
        }
    }

    public function show($id) 
    {
        $penjualan = PenjualanPos::with('details.produk')->findOrFail($id);
        return view('penjualan_pos.show', compact('penjualan'));
    }

    public function edit($id) 
    {
        $penjualan = PenjualanPos::findOrFail($id);
        if ($penjualan->status == 'VOID') {
            return redirect()->route('penjualan_pos.index')->with('error', 'Transaksi yang telah di-Void tidak dapat diubah.');
        }
        $produk = MasterBarang::where('is_barang_jadi', 1)->get();
        $gudang = MasterGudang::all();
        return view('penjualan_pos.edit', compact('penjualan', 'produk', 'gudang'));
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