<?php

namespace App\Http\Controllers;

use App\Models\MasterGudang;
use App\Models\PengeluaranBahanBaku;
use App\Models\PengeluaranBahanBakuDetail;
use App\Models\StockOpname;
use App\Models\StockOpnameDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockOpnameController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | LIST DATA
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $stockOpname = StockOpname::with(['gudang', 'user'])
            ->latest()
            ->paginate(20);

        $gudangs = MasterGudang::orderBy('nama')->get();

        return view('stock-opname.index', compact('stockOpname', 'gudangs'));
    }

    /*
    |--------------------------------------------------------------------------
    | FORM CREATE
    |--------------------------------------------------------------------------
    */

    public function create(Request $request)
    {
        $gudangId = $request->gudang_id;

        if (!$gudangId) {
            return redirect()
                ->route('stock-opname.index')
                ->with('error', 'Silakan pilih gudang terlebih dahulu.');
        }

        $gudang = MasterGudang::findOrFail($gudangId);

        return view('stock-opname.create', compact('gudang'));
    }

    /*
    |--------------------------------------------------------------------------
    | LOAD BARANG AJAX
    |--------------------------------------------------------------------------
    */

    public function loadBarang(Request $request)
    {
        $request->validate(['gudang_id' => 'required']);

        $barang = DB::table('stok_gudang')
            ->join('master_barang', 'stok_gudang.barang_id', '=', 'master_barang.id')
            ->where('stok_gudang.gudang_id', $request->gudang_id)
            ->select(
                'master_barang.id',
                'master_barang.kode_barang',
                'master_barang.nama',
                'master_barang.satuan',
                'stok_gudang.jumlah as stok'
            )
            ->orderBy('master_barang.nama', 'asc')
            ->get();

        foreach ($barang as $item) {
            $item->harga_fifo = $this->getHargaFIFO(
                $request->gudang_id,
                $item->id
            );
        }

        return response()->json($barang);
    }

    /*
    |--------------------------------------------------------------------------
    | HITUNG FIFO REALTIME (AJAX)
    |--------------------------------------------------------------------------
    */

    public function hitungFIFORealtime(Request $request)
    {
        $nilai = $this->hitungNilaiFIFO(
            $request->gudang_id,
            $request->barang_id,
            abs($request->selisih)
        );

        return response()->json(['nilai' => $nilai]);
    }

    /*
    |--------------------------------------------------------------------------
    | SIMPAN DRAFT STOCK OPNAME
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        $request->validate([
            'gudang_id'   => 'required',
            'barang_id'   => 'required|array',
            'stok_sistem' => 'required|array',
            'stok_fisik'  => 'required|array',
        ]);

        DB::beginTransaction();

        try {
            $opname = StockOpname::create([
                'kode_opname' => 'SO-' . now()->format('YmdHis'),
                'tanggal'     => now(),
                'gudang_id'   => $request->gudang_id,
                'status'      => 'draft',
                'keterangan'  => $request->keterangan,
                'created_by'  => Auth::id(),
            ]);

            foreach ($request->barang_id as $index => $barangId) {
                $stokSistem   = (float) $request->stok_sistem[$index];
                $stokFisik    = (float) $request->stok_fisik[$index];
                $selisih      = $stokFisik - $stokSistem;
                $nilaiSelisih = $this->hitungNilaiFIFO(
                    $request->gudang_id,
                    $barangId,
                    abs($selisih)
                );

                StockOpnameDetail::create([
                    'stock_opname_id' => $opname->id,
                    'barang_id'       => $barangId,
                    'stok_sistem'     => $stokSistem,
                    'stok_fisik'      => $stokFisik,
                    'selisih'         => $selisih,
                    'nilai_selisih'   => $nilaiSelisih,
                ]);
            }

            DB::commit();

            return redirect()
                ->route('stock-opname.show', $opname->id)
                ->with('success', 'Draft Stock Opname berhasil dibuat.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors($e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DETAIL STOCK OPNAME
    |--------------------------------------------------------------------------
    */

    public function show(string $id)
    {
        $stockOpname = StockOpname::with(['gudang', 'user', 'details.barang'])
            ->findOrFail($id);

        return view('stock-opname.show', compact('stockOpname'));
    }
/*
|--------------------------------------------------------------------------
| DETAIL STOCK OPNAME (JSON UNTUK MODAL)
|--------------------------------------------------------------------------
*/

public function detailJson(string $id)
{
    $stockOpname = StockOpname::with(['gudang', 'user', 'details.barang'])
        ->findOrFail($id);

    $details = $stockOpname->details->map(function ($detail) {
        return [
            'barang'        => $detail->barang->nama ?? '-',
            'stok_sistem'   => (float) $detail->stok_sistem,
            'stok_fisik'    => (float) $detail->stok_fisik,
            'selisih'       => (float) $detail->selisih,
            'nilai_selisih' => (float) $detail->nilai_selisih,
        ];
    });

    $grandTotal = $stockOpname->details->sum(function ($detail) {
        return abs($detail->nilai_selisih);
    });

    return response()->json([
        'kode_opname' => $stockOpname->kode_opname,
        'gudang'      => $stockOpname->gudang->nama ?? '-',
        'tanggal'     => \Carbon\Carbon::parse($stockOpname->tanggal)->format('d M Y H:i'),
        'status'      => $stockOpname->status,
        'keterangan'  => $stockOpname->keterangan ?: '-',
        'details'     => $details,
        'grand_total' => (float) $grandTotal,
        'approve_url' => route('stock-opname.approve', $stockOpname->id),
    ]);
}
    /*
    |--------------------------------------------------------------------------
    | APPROVE STOCK OPNAME
    |--------------------------------------------------------------------------
    |
    | Alur:
    | 1. Validasi status belum approved
    | 2. Hitung nilai selisih FIFO dari batch terlama (re-hitung saat approve)
    | 3. Update nilai_selisih di detail
    | 4. Untuk setiap item yang selisih NEGATIF (stok fisik < sistem):
    |    → Auto-buat PengeluaranBahanBaku (header)
    |    → Auto-buat PengeluaranBahanBakuDetail per item selisih
    |    Pengeluaran dibuat status 'draft', admin approve sendiri
    |    di menu Raw Material Output menggunakan alur FIFO yang sudah ada.
    | 5. Update status opname → approved
    |
    */

    public function approve($id)
    {
        DB::beginTransaction();

        try {
            $opname = StockOpname::with(['details.barang', 'gudang'])
                ->findOrFail($id);

            // ── Guard: sudah approved ──
            if ($opname->status === 'approved') {
                return back()->with('error', 'Stock opname sudah diapprove.');
            }

            // ── Kumpulkan item yang selisihnya negatif ──
            $itemSelisihNegatif = [];

            foreach ($opname->details as $detail) {

                // Re-hitung nilai selisih FIFO saat approve (data terkini)
                $nilaiSelisih = $this->hitungNilaiFIFO(
                    $opname->gudang_id,
                    $detail->barang_id,
                    abs($detail->selisih)
                );

                // Update nilai_selisih dengan kalkulasi FIFO terbaru
                $detail->update(['nilai_selisih' => $nilaiSelisih]);

                // Selisih negatif = stok fisik < stok sistem → perlu pengurangan stok
                if ($detail->selisih < 0) {
                    $itemSelisihNegatif[] = [
                        'barang_id' => $detail->barang_id,
                        'qty'       => abs($detail->selisih),
                        'satuan'    => $detail->barang->satuan ?? 'pcs',
                    ];
                }
            }

            // ── Buat Pengeluaran Bahan Baku otomatis jika ada selisih negatif ──
            if (!empty($itemSelisihNegatif)) {

                $kode = 'PBK-SO-' . $opname->kode_opname;

                $pengeluaran = PengeluaranBahanBaku::create([
                    'kode_pengeluaran' => $kode,
                    'tanggal'          => now(),
                    'gudang_id'        => $opname->gudang_id,
                    'status'           => 'draft',
                    'keterangan'       => 'Auto dari Stock Opname: ' . $opname->kode_opname,
                    'created_by'       => Auth::id(),
                    'approved_by'      => null,
                    'approved_at'      => null,
                ]);

                foreach ($itemSelisihNegatif as $item) {
                    PengeluaranBahanBakuDetail::create([
                        'pengeluaran_id' => $pengeluaran->id,
                        'barang_id'      => $item['barang_id'],
                        'qty'            => $item['qty'],
                        'satuan'         => $item['satuan'],
                        'harga_satuan'   => 0, // diisi saat approve pengeluaran via FIFO
                        'total_harga'    => 0,
                        'hpp_total'      => 0,
                    ]);
                }
            }

            // ── Update status opname ──
            $opname->update(['status' => 'approved']);

            DB::commit();

            $pesanTambahan = !empty($itemSelisihNegatif)
                ? ' Pengeluaran bahan baku (draft) telah dibuat otomatis — silakan approve di menu Raw Material Output.'
                : '';

            return back()->with(
                'success',
                'Stock opname berhasil diapprove.' . $pesanTambahan
            );

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal approve: ' . $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | HAPUS
    |--------------------------------------------------------------------------
    */

    public function destroy(string $id)
    {
        $opname = StockOpname::findOrFail($id);

        if ($opname->status === 'approved') {
            return back()->with(
                'error',
                'Stock Opname yang sudah approved tidak dapat dihapus.'
            );
        }

        $opname->details()->delete();
        $opname->delete();

        return redirect()
            ->route('stock-opname.index')
            ->with('success', 'Stock Opname berhasil dihapus.');
    }

    /*
    |--------------------------------------------------------------------------
    | EDIT / UPDATE (reserved)
    |--------------------------------------------------------------------------
    */

    public function edit(string $id) {}

    public function update(Request $request, string $id) {}

    /*
    |==========================================================================
    | PRIVATE HELPERS
    |==========================================================================
    */

    /*
    |--------------------------------------------------------------------------
    | GET HARGA FIFO (untuk preview di form)
    |--------------------------------------------------------------------------
    */

    private function getHargaFIFO($gudangId, $barangId): float
    {
        // Batch aktif (qty_sisa > 0), ambil yang terlama dulu (order by id asc)
        $harga = DB::table('stok_gudang_batch')
            ->where('gudang_id', $gudangId)
            ->where('barang_id', $barangId)
            ->where('qty_sisa', '>', 0)
            ->orderBy('id', 'asc')
            ->value('harga_per_qty');

        // Fallback: rata-rata semua batch historis
        if (!$harga) {
            $harga = DB::table('stok_gudang_batch')
                ->where('gudang_id', $gudangId)
                ->where('barang_id', $barangId)
                ->avg('harga_per_qty');
        }

        // Fallback akhir: hpp_referensi di master barang
        if (!$harga) {
            $harga = DB::table('master_barang')
                ->where('id', $barangId)
                ->value('hpp_referensi') ?? 0;
        }

        return (float) $harga;
    }

    /*
    |--------------------------------------------------------------------------
    | HITUNG NILAI FIFO
    |--------------------------------------------------------------------------
    |
    | Menghitung nilai rupiah dari sejumlah qty berdasarkan batch terlama
    | (FIFO murni: batch id terkecil diambil terlebih dahulu).
    |
    */

    private function hitungNilaiFIFO($gudangId, $barangId, $qty): float
    {
        if ($qty <= 0) return 0;

        $sisa  = $qty;
        $nilai = 0;

        // ── Tahap 1: FIFO dari batch terlama yang masih punya sisa ──
        $batches = DB::table('stok_gudang_batch')
            ->where('gudang_id', $gudangId)
            ->where('barang_id', $barangId)
            ->where('qty_sisa', '>', 0)
            ->orderBy('id', 'asc')          // terlama dulu
            ->get();

        foreach ($batches as $batch) {
            if ($sisa <= 0) break;
            $ambil  = min($sisa, $batch->qty_sisa);
            $nilai += $ambil * $batch->harga_per_qty;
            $sisa  -= $ambil;
        }

        // ── Tahap 2: Fallback rata-rata batch historis jika qty_sisa semua 0 ──
        if ($sisa > 0) {
            $hargaRata = DB::table('stok_gudang_batch')
                ->where('gudang_id', $gudangId)
                ->where('barang_id', $barangId)
                ->avg('harga_per_qty');

            if ($hargaRata) {
                $nilai += $sisa * $hargaRata;
                $sisa   = 0;
            }
        }

        // ── Tahap 3: Fallback hpp_referensi master barang ──
        if ($sisa > 0) {
            $hpp = DB::table('master_barang')
                ->where('id', $barangId)
                ->value('hpp_referensi');

            if ($hpp) {
                $nilai += $sisa * $hpp;
            }
        }

        return (float) $nilai;
    }
}