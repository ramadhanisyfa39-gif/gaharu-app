<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WorkOrder;
use App\Models\WorkOrderDetail;
use App\Models\MasterBarang;
use App\Models\ResepBahanBaku;
use App\Models\StokGudang;
use App\Models\Produksi;
use App\Models\ProduksiDetail;
use App\Models\ProduksiPesanan;
use Illuminate\Support\Facades\DB;

class ProduksiController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | 1. HALAMAN RIWAYAT & DRAFT PRODUKSI
    |--------------------------------------------------------------------------
    */
    public function index()
    {
        $riwayatProduksi = Produksi::with(['details.produk', 'pesanan.customer'])
            ->orderBy('id', 'desc')
            ->get();

        return view('produksi.index', compact('riwayatProduksi'));
    }

    /*
    |--------------------------------------------------------------------------
    | 2. HALAMAN FORM INPUT DRAFT PRODUKSI (Mendukung Parsial & Sisa Target)
    |--------------------------------------------------------------------------
    */
    public function create(Request $request)
    {
        $workOrders = WorkOrder::where('status_wo', 'Diproses')->get();
        $gudangs = DB::table('master_gudang')->get();

        $selectedWoId = $request->get('work_order_id');
        $items = collect();

        if ($selectedWoId) {
            $woDetails = WorkOrderDetail::where('work_order_id', $selectedWoId)
                ->with('produk')
                ->get();

            $groupedItems = [];

            // Petakan grup produk & hitung total rencana targetnya
            foreach ($woDetails as $wod) {
                $pid = $wod->produk_id;
                
                if (!isset($groupedItems[$pid])) {
                    $groupedItems[$pid] = [
                        'produk_id'        => $pid,
                        'produk'           => $wod->produk,
                        'total_target'     => 0,
                        'sudah_diproduksi' => 0,
                    ];
                }
                
                $groupedItems[$pid]['total_target'] += $wod->qty_rencana;
            }

            // Hitung akumulasi produksi riil yang sudah tersimpan untuk WO ini
            foreach ($groupedItems as $pid => $data) {
                $currentPesananIds = $woDetails->where('produk_id', $pid)
                    ->pluck('pesanan_id')
                    ->filter()
                    ->toArray();

                $terproduksi = 0;

                if (!empty($currentPesananIds)) {
                    $terproduksi = DB::table('alokasi_produksi_pesanan')
                        ->whereIn('pesanan_id', $currentPesananIds)
                        ->where('produk_id', $pid)
                        ->sum('qty_alokasi');
                } else {
                    $pesananIdsAll = $woDetails->pluck('pesanan_id')->filter()->toArray();
                    $pesananIdUtama = !empty($pesananIdsAll) ? $pesananIdsAll[0] : null;

                    if ($pesananIdUtama) {
                        $terproduksi = DB::table('alokasi_produksi_pesanan')
                            ->join('produksi', 'alokasi_produksi_pesanan.produksi_id', '=', 'produksi.id')
                            ->where('produksi.pesanan_id', $pesananIdUtama)
                            ->where('alokasi_produksi_pesanan.produk_id', $pid)
                            ->whereNull('alokasi_produksi_pesanan.pesanan_id')
                            ->sum('alokasi_produksi_pesanan.qty_alokasi');
                    }
                }

                $groupedItems[$pid]['sudah_diproduksi'] = $terproduksi;
            }

            // Format data sisa target untuk dikirim ke blade view
            $items = collect($groupedItems)->map(function ($item) {
                $sisa = $item['total_target'] - $item['sudah_diproduksi'];
                return (object) [
                    'produk_id'        => $item['produk_id'],
                    'produk'           => $item['produk'],
                    'total_target'     => $item['total_target'],
                    'sudah_diproduksi' => $item['sudah_diproduksi'],
                    'sisa_target'      => $sisa > 0 ? $sisa : 0,
                ];
            })->filter(function($item) {
                return $item->sisa_target > 0; 
            })->values();
        }

        return view('produksi.create', compact('workOrders', 'gudangs', 'selectedWoId', 'items'));
    }

    /*
    |--------------------------------------------------------------------------
    | 3. SIMPAN DRAFT PRODUKSI (Tanpa kolom work_order_id di DB)
    |--------------------------------------------------------------------------
    */
    public function store(Request $request)
    {
        $request->validate([
            'work_order_id'    => 'required',
            'tanggal_produksi' => 'required|date',
            'produk_id'        => 'required|array',
            'qty_hasil'        => 'required|array',
        ]);

        // =========================================================================
        // BLOK VALIDASI CEK SISA TARGET (Mencegah input melebihi sisa WO)
        // =========================================================================
        $woDetails = DB::table('work_order_detail')->where('work_order_id', $request->work_order_id)->get();
        
        foreach ($request->produk_id as $key => $produkId) {
            $qtyInput = floatval($request->qty_hasil[$key]);
            if ($qtyInput <= 0) continue;

            $totalTarget = $woDetails->where('produk_id', $produkId)->sum('qty_rencana');

            $currentPesananIds = $woDetails->where('produk_id', $produkId)
                ->pluck('pesanan_id')->filter()->toArray();

            $terproduksi = 0;
            if (!empty($currentPesananIds)) {
                $terproduksi = DB::table('alokasi_produksi_pesanan')
                    ->whereIn('pesanan_id', $currentPesananIds)
                    ->where('produk_id', $produkId)
                    ->sum('qty_alokasi');
            } else {
                $pesananIdsAll = $woDetails->pluck('pesanan_id')->filter()->toArray();
                $pesananIdUtama = !empty($pesananIdsAll) ? $pesananIdsAll[0] : null;

                if ($pesananIdUtama) {
                    $terproduksi = DB::table('alokasi_produksi_pesanan')
                        ->join('produksi', 'alokasi_produksi_pesanan.produksi_id', '=', 'produksi.id')
                        ->where('produksi.pesanan_id', $pesananIdUtama)
                        ->where('alokasi_produksi_pesanan.produk_id', $produkId)
                        ->whereNull('alokasi_produksi_pesanan.pesanan_id')
                        ->sum('alokasi_produksi_pesanan.qty_alokasi');
                }
            }

            $sisaTarget = $totalTarget - $terproduksi;

            if ($qtyInput > $sisaTarget) {
                $namaProduk = DB::table('master_barang')->where('id', $produkId)->value('nama');
                return redirect()->back()->with('error', "Gagal Simpan! Jumlah produk '{$namaProduk}' ({$qtyInput} unit) melebihi sisa target. Maksimal yang bisa diinput adalah {$sisaTarget} unit.");
            }
        }
        // =========================================================================

        DB::beginTransaction();

        try {
            // Ambil referensi pesanan dari Work Order
            $pesananIds = DB::table('work_order_detail')
                ->where('work_order_id', $request->work_order_id)
                ->pluck('pesanan_id')
                ->filter()
                ->unique()
                ->values()
                ->toArray();

            $pesananIdUtama = !empty($pesananIds) ? $pesananIds[0] : null;

            if (!$pesananIdUtama) {
                return redirect()->back()->with('error', 'Tidak dapat membuat Draft: Work Order ini tidak memiliki referensi Pesanan.');
            }

            // Simpan header sebagai DRAFT (HPP = 0, Stok belum dipotong)
            $produksiId = DB::table('produksi')->insertGetId([
                'kode_produksi'   => 'PRD-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3))),
                'pesanan_id'      => $pesananIdUtama,
                'tanggal_mulai'   => $request->tanggal_produksi,
                'tanggal_selesai' => null, 
                'status_produksi' => 'Draft', 
                'gudang_bahan_id' => 3,
                'gudang_hasil_id' => 3,
                'created_by'      => auth()->id() ?? 1,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            foreach ($request->produk_id as $key => $produkId) {
                $qtyHasil = floatval($request->qty_hasil[$key]);
                if ($qtyHasil <= 0) continue;

                DB::table('produksi_detail')->insert([
                    'produksi_id' => $produksiId,
                    'produk_id'   => $produkId,
                    'qty'         => $qtyHasil,
                    'hpp_total'   => 0, 
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }

            DB::commit();
            return redirect()->route('produksi.index')->with('success', 'Draft Produksi berhasil disimpan. Data masih bisa diedit sebelum di-Approve.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal Simpan Draft! Pesan: ' . $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | 4. HALAMAN EDIT DRAFT
    |--------------------------------------------------------------------------
    */
    public function edit($id)
    {
        $produksi = Produksi::with('details.produk')->findOrFail($id);
        return view('produksi.edit', compact('produksi'));
    }

    /*
    |--------------------------------------------------------------------------
    | 5. UPDATE DRAFT PRODUKSI (Edit qty hasil fisik sebelum approve)
    |--------------------------------------------------------------------------
    */
    public function update(Request $request, $id)
    {
        $produksi = Produksi::findOrFail($id);

        if ($produksi->status_produksi !== 'Draft') {
            return redirect()->back()->with('error', 'Data sudah di-Approve dan tidak dapat diedit.');
        }

        // =========================================================================
        // BLOK VALIDASI CEK SISA TARGET SAAT UPDATE
        // =========================================================================
        $wodUtama = DB::table('work_order_detail')
            ->where('pesanan_id', $produksi->pesanan_id)
            ->orderBy('id', 'desc')
            ->first();

        if ($wodUtama) {
            $workOrderId = $wodUtama->work_order_id;
            $woDetails = DB::table('work_order_detail')->where('work_order_id', $workOrderId)->get();

            foreach ($request->produk_id as $key => $produkId) {
                $qtyInput = floatval($request->qty_hasil[$key]);
                if ($qtyInput <= 0) continue;

                $totalTarget = $woDetails->where('produk_id', $produkId)->sum('qty_rencana');

                $currentPesananIds = $woDetails->where('produk_id', $produkId)
                    ->pluck('pesanan_id')->filter()->toArray();

                $terproduksi = 0;
                if (!empty($currentPesananIds)) {
                    $terproduksi = DB::table('alokasi_produksi_pesanan')
                        ->whereIn('pesanan_id', $currentPesananIds)
                        ->where('produk_id', $produkId)
                        ->sum('qty_alokasi');
                } else {
                    $pesananIdsAll = $woDetails->pluck('pesanan_id')->filter()->toArray();
                    $pesananIdUtamaCek = !empty($pesananIdsAll) ? $pesananIdsAll[0] : null;

                    if ($pesananIdUtamaCek) {
                        $terproduksi = DB::table('alokasi_produksi_pesanan')
                            ->join('produksi', 'alokasi_produksi_pesanan.produksi_id', '=', 'produksi.id')
                            ->where('produksi.pesanan_id', $pesananIdUtamaCek)
                            ->where('alokasi_produksi_pesanan.produk_id', $produkId)
                            ->whereNull('alokasi_produksi_pesanan.pesanan_id')
                            ->sum('alokasi_produksi_pesanan.qty_alokasi');
                    }
                }

                $sisaTarget = $totalTarget - $terproduksi;

                if ($qtyInput > $sisaTarget) {
                    $namaProduk = DB::table('master_barang')->where('id', $produkId)->value('nama');
                    return redirect()->back()->with('error', "Gagal Edit! Jumlah produk '{$namaProduk}' ({$qtyInput} unit) melebihi sisa target. Maksimal yang bisa diinput adalah {$sisaTarget} unit.");
                }
            }
        }
        // =========================================================================

        DB::beginTransaction();
        try {
            DB::table('produksi_detail')->where('produksi_id', $id)->delete();

            foreach ($request->produk_id as $key => $produkId) {
                $qtyHasil = floatval($request->qty_hasil[$key]);
                if ($qtyHasil <= 0) continue;

                DB::table('produksi_detail')->insert([
                    'produksi_id' => $id,
                    'produk_id'   => $produkId,
                    'qty'         => $qtyHasil,
                    'hpp_total'   => 0,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }

            $produksi->update(['tanggal_mulai' => $request->tanggal_produksi]);

            DB::commit();
            return redirect()->route('produksi.index')->with('success', 'Draft Produksi berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal update draft: ' . $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | 6. HAPUS DRAFT PRODUKSI
    |--------------------------------------------------------------------------
    */
    public function destroy($id)
    {
        $produksi = Produksi::findOrFail($id);

        if ($produksi->status_produksi !== 'Draft') {
            return redirect()->back()->with('error', 'Tidak dapat menghapus produksi yang sudah di-Approve (Terkunci).');
        }

        DB::table('produksi_detail')->where('produksi_id', $id)->delete();
        $produksi->delete();

        return redirect()->route('produksi.index')->with('success', 'Draft Produksi berhasil dihapus secara permanen.');
    }

    /*
    |--------------------------------------------------------------------------
    | 7. APPROVE PRODUKSI (Tahap Final - Hitung FIFO HPP, Potong Stok, Jembatan WO)
    |--------------------------------------------------------------------------
    */
    public function approve(Request $request, $id)
    {
        // Ambil data draft produksi beserta item detail fisiknya
        $produksi = Produksi::with('details')->findOrFail($id);

        if ($produksi->status_produksi !== 'Draft') {
            return redirect()->back()->with('error', 'Data ini sudah disetujui sebelumnya dan terkunci.');
        }

        DB::beginTransaction();

        try {
            $gudangBahanId = $produksi->gudang_bahan_id;
            $gudangHasilId = $produksi->gudang_hasil_id;
            $fifoService   = app(\App\Services\FifoService::class);

            // --- LOGIKA JEMBATAN PELACAK WORK ORDER ID ---
            $wodUtama = DB::table('work_order_detail')
                ->where('pesanan_id', $produksi->pesanan_id)
                ->orderBy('id', 'desc')
                ->first();
                
            if (!$wodUtama) {
                throw new \Exception('Tidak dapat menemukan Work Order yang terkait dengan pesanan ini.');
            }
            $workOrderId = $wodUtama->work_order_id;
            // ----------------------------------------------

            // Ambil semua pesanan yang tergabung dalam Work Order tersebut
            $pesananIds = DB::table('work_order_detail')
                ->where('work_order_id', $workOrderId)
                ->pluck('pesanan_id')
                ->unique()
                ->values()
                ->toArray();

            // Eksekusi perhitungan per item produk hasil produksi
            foreach ($produksi->details as $detail) {
                $produkId = $detail->produk_id;
                $qtyHasil = floatval($detail->qty);
                $produk   = MasterBarang::find($produkId);

                if (!$produk) {
                    throw new \Exception("ID Produk {$produkId} tidak valid.");
                }

                if (is_null($produk->resep_id)) {
                    throw new \Exception("Produk '{$produk->nama}' belum memiliki resep.");
                }

                $totalBbbProduk = 0;

                // A. FIFO BAHAN BAKU
                $resepItems = ResepBahanBaku::where('resep_id', $produk->resep_id)->get();

                foreach ($resepItems as $item) {
                    $qtyButuh = $item->qty_bahan * $qtyHasil;

                    $fifoResult = $fifoService->consumeFIFO(
                        $item->bahan_id,
                        $qtyButuh,
                        $gudangBahanId
                    );

                    foreach ($fifoResult as $layer) {
                        $totalBbbProduk += floatval($layer['qty_keluar']) * floatval($layer['harga_per_qty']);
                    }

                    $stokBahanGlobal = StokGudang::where('gudang_id', $gudangBahanId)
                        ->where('barang_id', $item->bahan_id)
                        ->first();

                    if ($stokBahanGlobal) {
                        $stokBahanGlobal->decrement('jumlah', $qtyButuh);
                    } else {
                        StokGudang::create([
                            'gudang_id' => $gudangBahanId,
                            'barang_id' => $item->bahan_id,
                            'jumlah'    => 0 - $qtyButuh,
                        ]);
                    }
                }

                // B. HITUNG BTKL & BOP
                $totalBtklBop = 0;
                $biayaTambahan = DB::table('resep_btkl_bop')->where('produk_id', $produkId)->first();

                if ($biayaTambahan && $biayaTambahan->output_qty > 0) {
                    $btklPerBatch = floatval($biayaTambahan->btkl_per_batch);
                    $bopPerBatch  = floatval($biayaTambahan->bop_per_batch);
                    $outputBatch  = floatval($biayaTambahan->output_qty);
                    $biayaPerItem = ($btklPerBatch + $bopPerBatch) / $outputBatch;

                    $totalBtklBop = $biayaPerItem * $qtyHasil;
                }

                // C. HITUNG TOTAL HPP & UPDATE KE DETAIL PRODUKSI
                $hppKeseluruhan = $totalBbbProduk + $totalBtklBop;

                DB::table('produksi_detail')
                    ->where('id', $detail->id)
                    ->update([
                        'hpp_total'  => $hppKeseluruhan,
                        'updated_at' => now()
                    ]);

                // D. TAMBAH STOK BARANG JADI KE GUDANG HASIL
                $stokBarangJadi = StokGudang::where('gudang_id', $gudangHasilId)
                    ->where('barang_id', $produkId)
                    ->first();

                if ($stokBarangJadi) {
                    $stokBarangJadi->increment('jumlah', $qtyHasil);
                } else {
                    StokGudang::create([
                        'gudang_id' => $gudangHasilId,
                        'barang_id' => $produkId,
                        'jumlah'    => $qtyHasil,
                    ]);
                }

                // E. DISTRIBUSI ALOKASI PESANAN DALAM WO (PRO-RATA/SEQUENTIAL)
                $hppPerUnit = $qtyHasil > 0 ? ($hppKeseluruhan / $qtyHasil) : 0;
                $sisaBarangSiapBagi = $qtyHasil; 

                $detailPesananWO = WorkOrderDetail::where('work_order_id', $workOrderId)
                    ->where('produk_id', $produkId)
                    ->orderBy('id', 'asc')
                    ->get();

                foreach ($detailPesananWO as $detailWO) {
                    $qtyRencanaWO = floatval($detailWO->qty_rencana);
                    $qtyAlokasi = min($qtyRencanaWO, $sisaBarangSiapBagi);

                    if ($qtyAlokasi > 0) {
                        ProduksiPesanan::create([
                            'produksi_id'       => $produksi->id,
                            'pesanan_id'        => $detailWO->pesanan_id,
                            'produk_id'         => $produkId,
                            'qty_alokasi'       => $qtyAlokasi,
                            'qty_terkirim'      => 0,
                            'hpp_per_unit'      => $hppPerUnit,
                            'total_hpp_alokasi' => $qtyAlokasi * $hppPerUnit,
                        ]);

                        $sisaBarangSiapBagi -= $qtyAlokasi;
                    }
                }
            }

            // F. VALIDASI STATUS AKUMULASI KETERPENUHAN (WO & PESANAN)
            $semuaWODetail = DB::table('work_order_detail')->where('work_order_id', $workOrderId)->get();
            $woSelesaiSempurna = true;

            foreach ($semuaWODetail as $wod) {
                $totalAlokasiTercatat = DB::table('alokasi_produksi_pesanan')
                    ->where('produk_id', $wod->produk_id)
                    ->where('pesanan_id', $wod->pesanan_id)
                    ->sum('qty_alokasi');

                if ($totalAlokasiTercatat < $wod->qty_rencana) {
                    $woSelesaiSempurna = false;
                }
            }

            DB::table('work_order')
                ->where('id', $workOrderId)
                ->update([
                    'status_wo'  => $woSelesaiSempurna ? 'Selesai' : 'Diproses',
                    'updated_at' => now(),
                ]);

            if (!empty($pesananIds)) {
                foreach ($pesananIds as $pesananId) {
                    $detailPesanan = DB::table('pesanan_detail')->where('pesanan_id', $pesananId)->get();
                    $pesananSelesaiSempurna = true;

                    foreach ($detailPesanan as $dp) {
                        $totalAlokasiPesanan = DB::table('alokasi_produksi_pesanan')
                            ->where('pesanan_id', $pesananId)
                            ->where('produk_id', $dp->produk_id)
                            ->sum('qty_alokasi');

                        if ($totalAlokasiPesanan < $dp->qty) {
                            $pesananSelesaiSempurna = false;
                            break; 
                        }
                    }

                    DB::table('pesanan')
                        ->where('id', $pesananId)
                        ->update([
                            'status_pesanan' => $pesananSelesaiSempurna ? 'Siap kirim' : 'Diproses',
                            'updated_at'     => now(),
                        ]);
                }
            }

            // G. UPDATE DATA UTAMA PRODUKSI DARI DRAFT MENJADI SELESAI
            DB::table('produksi')
                ->where('id', $produksi->id)
                ->update([
                    'status_produksi' => 'Selesai',
                    'tanggal_selesai' => now(),
                    'updated_at'      => now(),
                ]);

            DB::commit();
            return redirect()->route('produksi.index')->with('success', 'Produksi berhasil disetujui! Seluruh stok gudang, FIFO, HPP, dan status pesanan telah diperbarui.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal Approve! Pesan: ' . $e->getMessage());
        }
    }

/*
    |--------------------------------------------------------------------------
    | HALAMAN DETAIL PRODUKSI
    |--------------------------------------------------------------------------
    */
    public function show($id)
    {
        // Pastikan relasi ke detail, produk, pesanan, dan customer dimuat
        $produksi = Produksi::with(['details.produk', 'pesanan.customer'])->findOrFail($id);
        
        // Ambil nama gudang hasil secara manual agar tidak perlu repot mengubah Model
        $gudangHasil = DB::table('master_gudang')->where('id', $produksi->gudang_hasil_id)->first();
        $namaGudang = $gudangHasil ? $gudangHasil->nama : 'Gudang Tidak Diketahui';
        
        return view('produksi.show', compact('produksi', 'namaGudang'));
    }

    public function dashboard()
    {
        // 1. Mini Summary Cards
        $woAktif = WorkOrder::where('status_wo', 'Diproses')->count();
        
        $produksiSelesaiTahunIni = Produksi::where('status_produksi', 'Selesai')
            ->whereYear('tanggal_selesai', date('Y'))
            ->count();

        $totalQtyHasil = DB::table('produksi_detail')
            ->join('produksi', 'produksi_detail.produksi_id', '=', 'produksi.id')
            ->where('produksi.status_produksi', 'Selesai')
            ->sum('produksi_detail.qty');

        // Target Achievement Calculation
        $workOrders = WorkOrder::whereIn('status_wo', ['Draft', 'Diproses', 'Selesai'])->get();
        $achievements = [];

        foreach ($workOrders as $wo) {
            $totalRencana = $wo->details()->sum('qty_rencana');
            
            $pesananIds = $wo->details()->pluck('pesanan_id')->filter()->unique()->toArray();
            $produkIds = $wo->details()->pluck('produk_id')->filter()->unique()->toArray();
            
            $totalAlokasi = 0;
            if (!empty($pesananIds) && !empty($produkIds)) {
                $totalAlokasi = DB::table('alokasi_produksi_pesanan')
                    ->whereIn('pesanan_id', $pesananIds)
                    ->whereIn('produk_id', $produkIds)
                    ->sum('qty_alokasi');
            }
            
            if ($totalRencana > 0) {
                $achievements[] = min(100, ($totalAlokasi / $totalRencana) * 100);
            }
        }

        $rataRataCapaian = count($achievements) > 0 ? (array_sum($achievements) / count($achievements)) : 0;

        // 2. Grafik Tren Produksi 7 Hari Terakhir
        $labelsProduksi = [];
        $dataProduksi = [];

        $chartData = DB::table('produksi')
            ->join('produksi_detail', 'produksi.id', '=', 'produksi_detail.produksi_id')
            ->where('produksi.status_produksi', 'Selesai')
            ->where('produksi.tanggal_selesai', '>=', now()->subDays(6)->startOfDay())
            ->selectRaw('DATE(produksi.tanggal_selesai) as date_label, SUM(produksi_detail.qty) as daily_qty')
            ->groupBy('date_label')
            ->get()
            ->pluck('daily_qty', 'date_label');

        $periode = \Carbon\CarbonPeriod::create(
            now()->subDays(6),
            now()
        );

        foreach ($periode as $tanggal) {
            $dateStr = $tanggal->format('Y-m-d');
            $labelsProduksi[] = $tanggal->format('d M');
            $dataProduksi[] = (float) ($chartData->get($dateStr) ?? 0);
        }

        // 3. List Bahan Baku yang Sudah Masuk ke Batas Minimum
        $bahanBakuMinimum = DB::table('master_barang')
            ->leftJoin('stok_gudang', 'master_barang.id', '=', 'stok_gudang.barang_id')
            ->where('master_barang.is_bahan_baku', 1)
            ->select(
                'master_barang.nama',
                'master_barang.satuan',
                'master_barang.minimum_stock',
                DB::raw('COALESCE(SUM(stok_gudang.jumlah), 0) as total_stok')
            )
            ->groupBy('master_barang.id', 'master_barang.nama', 'master_barang.satuan', 'master_barang.minimum_stock')
            ->havingRaw('total_stok <= master_barang.minimum_stock')
            ->get();

        // 4. Produk Teratas Diproduksi (Top 5)
        $produkTeratas = DB::table('produksi_detail')
            ->join('produksi', 'produksi_detail.produksi_id', '=', 'produksi.id')
            ->join('master_barang', 'produksi_detail.produk_id', '=', 'master_barang.id')
            ->where('produksi.status_produksi', 'Selesai')
            ->select('master_barang.nama', 'master_barang.satuan', DB::raw('SUM(produksi_detail.qty) as total_qty'))
            ->groupBy('master_barang.id', 'master_barang.nama', 'master_barang.satuan')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        // 5. Status Work Order
        $workOrderStatus = WorkOrder::with('pembuat')
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($wo) {
                $totalRencana = $wo->details()->sum('qty_rencana');
                
                $pesananIds = $wo->details()->pluck('pesanan_id')->filter()->unique()->toArray();
                $produkIds = $wo->details()->pluck('produk_id')->filter()->unique()->toArray();
                
                $totalAlokasi = 0;
                if (!empty($pesananIds) && !empty($produkIds)) {
                    $totalAlokasi = DB::table('alokasi_produksi_pesanan')
                        ->whereIn('pesanan_id', $pesananIds)
                        ->whereIn('produk_id', $produkIds)
                        ->sum('qty_alokasi');
                }
                
                $wo->total_rencana = $totalRencana;
                $wo->total_realisasi = $totalAlokasi;
                $wo->persentase = $totalRencana > 0 ? round(($totalAlokasi / $totalRencana) * 100, 2) : 0;
                return $wo;
            });

        return view('produksi.dashboard', compact(
            'woAktif',
            'produksiSelesaiTahunIni',
            'totalQtyHasil',
            'rataRataCapaian',
            'labelsProduksi',
            'dataProduksi',
            'bahanBakuMinimum',
            'produkTeratas',
            'workOrderStatus'
        ));
    }
}