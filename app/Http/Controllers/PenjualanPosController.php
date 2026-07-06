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

    /**
     * 1. SIMPAN INPUTAN BARU (STATUS: Draft)
     */
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

            $penjualan = PenjualanPos::create([
                'kode_transaksi' => $kodePos,
                'status'         => 'Draft', // Status awal selalu Draft
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
    
                // HPP diset 0 saat simpan awal (Draft)
                PenjualanPosDetail::create([ 
                    'penjualan_id' => $penjualan->id, 
                    'produk_id'    => $produkId,
                    'qty'          => $qtyTerjual,
                    'harga'        => $hargaJual,
                    'hpp_satuan'   => 0, 
                    'subtotal'     => $subtotal
                ]);
    
                $total_penjualan += $subtotal;
            }
    
            $penjualan->update(['total' => $total_penjualan]);
            DB::commit();
    
            return redirect()->route('penjualan_pos.index')->with('success', 'Rekap berhasil disimpan (Status: Draft). HPP dan Stok belum dipotong sebelum di-Approve.');
    
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error Simpan POS: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan sistem saat simpan: ' . $e->getMessage())->withInput();
        }
    }

    public function show($id) 
    {
        $penjualan = PenjualanPos::with('details.produk')->findOrFail($id);
        return view('penjualan_pos.show', compact('penjualan'));
    }

    /**
     * 2. TRANSAKSI HANYA BISA DIEDIT JIKA STATUSNYA Draft
     */
    public function edit($id) 
    {
        $penjualan = PenjualanPos::findOrFail($id);
        
        if ($penjualan->status !== 'Draft') {
            return redirect()->route('penjualan_pos.index')->with('error', 'Transaksi yang telah di-Approve atau di-Void tidak dapat diubah lagi.');
        }
        
        $produk = MasterBarang::where('is_barang_jadi', 1)->get();
        $gudang = MasterGudang::all();
        return view('penjualan_pos.edit', compact('penjualan', 'produk', 'gudang'));
    }

    /**
     * 3. PROSES UPDATE DATA Draft
     */
    public function update(Request $request, $id)
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
            $penjualan = PenjualanPos::findOrFail($id);
            
            if ($penjualan->status !== 'Draft') {
                return redirect()->route('penjualan_pos.index')->with('error', 'Transaksi yang telah di-Approve tidak dapat diubah lagi.');
            }

            $penjualan->update([
                'tanggal'   => date('Y-m-d H:i:s', strtotime($request->tanggal)),
                'gudang_id' => $request->gudang_id,
            ]);

            // Hapus detail lama, tulis detail baru dengan HPP tetap 0
            PenjualanPosDetail::where('penjualan_id', $id)->delete();
            $total_penjualan = 0;

            foreach ($request->produk_id as $key => $produkId) {
                if (!isset($request->qty[$key]) || !isset($request->harga[$key])) continue;

                $qtyTerjual = floatval($request->qty[$key]);
                $hargaJual  = floatval($request->harga[$key]);
                $subtotal   = $qtyTerjual * $hargaJual;

                PenjualanPosDetail::create([ 
                    'penjualan_id' => $penjualan->id, 
                    'produk_id'    => $produkId,
                    'qty'          => $qtyTerjual,
                    'harga'        => $hargaJual,
                    'hpp_satuan'   => 0,
                    'subtotal'     => $subtotal
                ]);

                $total_penjualan += $subtotal;
            }

            $penjualan->update(['total' => $total_penjualan]);
            DB::commit();

            return redirect()->route('penjualan_pos.index')->with('success', 'Perubahan rekap penjualan berhasil diperbarui!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal update data: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * 4. PROSES APPROVAL: HPP DIHITUNG & STOK BARU TERPOTONG
     */
    public function approve($id)
    {
        DB::beginTransaction();

        try {
            $penjualan = PenjualanPos::with('details')->findOrFail($id);

            if ($penjualan->status !== 'Draft') {
                return redirect()->route('penjualan_pos.index')->with('error', 'Transaksi ini sudah pernah diproses sebelumnya.');
            }

            $kodePos = $penjualan->kode_transaksi;
            $tanggalTrans = $penjualan->tanggal;
            $gudangId = $penjualan->gudang_id;

            // -- A. Hitung total kebutuhan bahan baku
            $totalKebutuhanBahan = []; 
            foreach ($penjualan->details as $detail) {
                $qtyTerjual = floatval($detail->qty);
                $produkId = $detail->produk_id;

                $barangJadi = DB::table('master_barang')->where('id', $produkId)->first();
                $resepUtama = ($barangJadi && $barangJadi->resep_id) ? DB::table('resep_btkl_bop')->where('id', $barangJadi->resep_id)->first() : null;
                
                if ($resepUtama) {
                    $resepBahan = DB::table('resep_bahanbaku')->where('resep_id', $resepUtama->id)->get();
                    $outputQty = floatval($resepUtama->output_qty) > 0 ? floatval($resepUtama->output_qty) : 1;

                    foreach ($resepBahan as $bahan) {
                        $kebutuhanPerPcs = floatval($bahan->qty_bahan);
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

            // -- B. Validasi Stok
            $pesanErrorStok = [];
            foreach ($totalKebutuhanBahan as $bahanId => $dataBahan) {
                $stokTersedia = DB::table('stok_gudang_batch')
                    ->where('gudang_id', $gudangId)
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
                return back()->with('error', "<b>Gagal Approve!</b> Stok bahan baku tidak mencukupi:<br>" . $errorList);
            }

            // -- C. Potong Stok & Hitung FIFO
            $pengeluaranId = DB::table('pengeluaran_bahan_baku')->insertGetId([
                'kode_pengeluaran' => 'OUT-' . $kodePos,
                'tanggal'          => $tanggalTrans,
                'gudang_id'        => $gudangId,
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

                // Global Stok Pengurang
                $stokGudang = StokGudang::firstOrCreate(
                    ['gudang_id' => $gudangId, 'barang_id' => $bahanId],
                    ['jumlah' => 0]
                );
                $stokGudang->decrement('jumlah', $totalDipotong);

                // Potong Batch (FIFO)
                $stokBatches = DB::table('stok_gudang_batch')
                    ->where('gudang_id', $gudangId)
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

                    DB::table('stok_gudang_batch')->where('id', $batch->id)->update([
                        'qty_sisa'   => DB::raw("qty_sisa - {$diambil}"),
                        'qty_keluar' => DB::raw("qty_keluar + {$diambil}")
                    ]);
                    
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

            // -- D. Update HPP ke Detail Transaksi
            foreach ($penjualan->details as $detail) {
                $qtyTerjual = floatval($detail->qty);
                $produkId = $detail->produk_id;
    
                $hppSatuanProduk = 0;
                $bopBtklPerPcs   = 0;
                $totalHppBahan   = 0;

                $barangJadi = DB::table('master_barang')->where('id', $produkId)->first();
                $resepUtama = ($barangJadi && $barangJadi->resep_id) ? DB::table('resep_btkl_bop')->where('id', $barangJadi->resep_id)->first() : null;

                if ($resepUtama) {
                    $outputQty = floatval($resepUtama->output_qty) > 0 ? floatval($resepUtama->output_qty) : 1;
                    $bopBtklPerPcs = (floatval($resepUtama->btkl_per_batch) + floatval($resepUtama->bop_per_batch)) / $outputQty;

                    $resepBahan = DB::table('resep_bahanbaku')->where('resep_id', $resepUtama->id)->get();
                    foreach ($resepBahan as $bahan) {
                        $kebutuhanPerPcs = floatval($bahan->qty_bahan);
                        $hppBahanIni = $mapHppBahanAvg[$bahan->bahan_id] ?? 0;
                        $totalHppBahan += ($kebutuhanPerPcs * $hppBahanIni);
                    }
                }

                $hppSatuanProduk = $totalHppBahan + $bopBtklPerPcs;
    
                $detail->update([
                    'hpp_satuan' => $hppSatuanProduk
                ]);
            }
    
            $penjualan->update(['status' => 'SUKSES']);
            
            DB::commit();
            return redirect()->route('penjualan_pos.index')->with('success', 'Transaksi berhasil di-Approve! Stok terpotong dan HPP telah tersimpan.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error Approve POS: ' . $e->getMessage());
            return back()->with('error', 'Gagal approve transaksi: ' . $e->getMessage());
        }
    }

    /**
     * 5. HAPUS ATAU VOID
     */
    public function destroy($id)
    {
        DB::beginTransaction();
    
        try {
            $penjualan = PenjualanPos::with('details')->findOrFail($id);
            
            if ($penjualan->status == 'SUKSES') {
                // A. JIKA SUDAH APPROVE -> Kembalikan Stok & Set Menjadi VOID
                $gudangId = $penjualan->gudang_id;
                foreach ($penjualan->details as $detail) {
                    $barangJadi = DB::table('master_barang')->where('id', $detail->produk_id)->first();
                    $resepUtama = ($barangJadi && $barangJadi->resep_id) ? DB::table('resep_btkl_bop')->where('id', $barangJadi->resep_id)->first() : null;
                    $outputQty = ($resepUtama && floatval($resepUtama->output_qty) > 0) ? floatval($resepUtama->output_qty) : 1;

                    if ($resepUtama) {
                        $resepBahan = DB::table('resep_bahanbaku')->where('resep_id', $resepUtama->id)->get();
                        foreach ($resepBahan as $bahan) {
                            $kebutuhanPerPcs = floatval($bahan->qty_bahan);
                            $qtyKembali = $kebutuhanPerPcs * floatval($detail->qty);

                            $stokGudang = StokGudang::where('gudang_id', $gudangId)->where('barang_id', $bahan->bahan_id)->first();
                            if ($stokGudang) {
                                $stokGudang->increment('jumlah', $qtyKembali);
                            }

                            // Revert ke batch yang terpotong terakhir
                            $batchTerakhir = DB::table('stok_gudang_batch')->where('gudang_id', $gudangId)->where('barang_id', $bahan->bahan_id)->orderBy('id', 'desc')->first();
                            if ($batchTerakhir) {
                                DB::table('stok_gudang_batch')->where('id', $batchTerakhir->id)->update([
                                    'qty_sisa'   => DB::raw("qty_sisa + {$qtyKembali}"),
                                    'qty_keluar' => DB::raw("qty_keluar - {$qtyKembali}"),
                                    'is_habis'   => 0
                                ]);
                            }
                        }
                    }
                }
        
                DB::table('pengeluaran_bahan_baku')->where('keterangan', 'AUTO_POS:' . $penjualan->kode_transaksi)->update(['status' => 'void', 'updated_at' => now()]);
                $penjualan->update(['status' => 'VOID']);
                $msg = 'Transaksi dibatalkan. Status berubah menjadi VOID dan stok dikembalikan!';
                
            } else {
                // B. JIKA MASIH Draft -> Hapus Permanen bersih
                $penjualan->details()->delete(); 
                $penjualan->delete();           
                $msg = 'Transaksi berstatus Draft berhasil dihapus secara permanen!';
            }
    
            DB::commit();
            return redirect()->route('penjualan_pos.index')->with('success', $msg);
    
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal memproses penghapusan: ' . $e->getMessage());
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

        return response()->json($hargaAktif);
    }
}