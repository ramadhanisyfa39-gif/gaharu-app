<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePembelianRequest;
use App\Models\MasterBarang;
use App\Models\MasterGudang;
use App\Models\Pembelian;
use App\Models\Supplier;

use App\Services\StockService;
use App\Services\FifoService;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PembelianController extends Controller
{
    protected StockService $stockService;
    protected FifoService $fifoService;

    public function __construct(
        StockService $stockService,
        FifoService $fifoService
    ) {
        $this->stockService = $stockService;
        $this->fifoService  = $fifoService;
    }

    /*
    |--------------------------------------------------------------------------
    | INDEX
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $search = $request->query('search');
        $query = Pembelian::with(['supplier', 'gudang', 'user']);

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('no_pembelian', 'like', '%' . $search . '%')
                  ->orWhereHas('supplier', function($sq) use ($search) {
                      $sq->where('nama', 'like', '%' . $search . '%');
                  });
            });
        }

        $pembelian = $query->orderBy('tanggal', 'desc')->paginate(10)->withQueryString();

        $dataPembayaran = $pembelian->mapWithKeys(function ($item) {
            $label = match($item->metode_pembayaran) {
                'cod'    => 'COD',
                'termin' => 'Termin',
                'dp'     => $item->nominal_dp && $item->nominal_dp > 0 
                            ? 'DP Rp ' . number_format((float) $item->nominal_dp, 0, ',', '.')
                            : 'DP ' . $item->persen_dp . '%',
                default  => '-',
            };
            return [$item->id => [
                'kode'                => $item->kode_pembelian,
                'total'               => (float) $item->total,
                'metode'              => $item->metode_pembayaran,
                'label'               => $label,
                'persen_dp'           => $item->persen_dp,
                'nominal_dp'          => (float) $item->nominal_dp,
                'tanggal_jatuh_tempo' => $item->tanggal_jatuh_tempo,
                'tanggal_pelunasan'   => $item->tanggal_pelunasan,
                'catatan'             => $item->catatan_pembayaran,
                'dicatat_pada'        => $item->dicatat_pada,
            ]];
        });

        return view('pembelian.index', compact('pembelian', 'dataPembayaran'));
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE
    |--------------------------------------------------------------------------
    */

    public function create()
    {
        $suppliers = Supplier::orderBy('nama')->get();
        $gudangs   = MasterGudang::orderBy('nama')->get();
        $barangs   = MasterBarang::query()
            ->where('is_bahan_baku', true)
            ->orWhere('is_operational', true)
            ->orWhere('is_direct_consumption', true)
            ->orderBy('nama')
            ->get();

        return view('pembelian.create', compact('suppliers', 'gudangs', 'barangs'));
    }

    /*
    |--------------------------------------------------------------------------
    | STORE — stok TIDAK langsung masuk, tunggu konfirmasi terima
    |--------------------------------------------------------------------------
    */

    public function store(StorePembelianRequest $request)
    {
        $data = $request->validated();

        DB::transaction(function () use ($data) {

            $total = collect($data['items'])->sum(fn($item) => (float) $item['harga']);

            $pembelian = Pembelian::create([
                'kode_pembelian' => $this->generateKodePembelian($data['tanggal']),
                'supplier_id'    => $data['supplier_id'],
                'gudang_id'      => $data['gudang_id'],
                'tanggal'        => $data['tanggal'],
                'total'          => $total,
                'created_by'     => auth()->id(),
                'is_diterima'    => false,
                'is_lunas'       => false,
            ]);

            foreach ($data['items'] as $item) {

                $hargaPerQty = (float) $item['qty'] > 0
                    ? (float) $item['harga'] / (float) $item['qty']
                    : 0;

                // Simpan detail dulu — FIFO & stok masuk saat "Terima"
                $detail = $pembelian->details()->create([
                    'barang_id'    => $item['barang_id'],
                    'qty'          => $item['qty'],
                    'harga'        => $item['harga'],
                    'harga_per_qty'=> $hargaPerQty,
                    'batch_number' => 'TEMP',
                ]);

                $detail->update([
                    'batch_number' => date('Ymd') . '-PB' . $detail->id,
                ]);
            }
        });

        return redirect()
            ->route('pembelian.index')
            ->with('success', 'Pembelian berhasil disimpan. Klik Terima setelah barang tiba.');
    }

    /*
    |--------------------------------------------------------------------------
    | TERIMA BARANG — stok masuk di sini
    |--------------------------------------------------------------------------
    */

    public function terima(Request $request, Pembelian $pembelian)
    {
        if ($pembelian->is_diterima) {
            return back()->with('error', 'Barang sudah pernah diterima.');
        }

        DB::transaction(function () use ($pembelian) {

            $pembelian->load('details');

            foreach ($pembelian->details as $detail) {

                // Buat FIFO batch
                $this->fifoService->createBatchStock($pembelian, $detail);

                // Tambah stok gudang
                $this->stockService->stockIn([
                    'barang_id'       => $detail->barang_id,
                    'gudang_tujuan_id'=> $pembelian->gudang_id,
                    'qty'             => $detail->qty,
                    'total_harga'     => $detail->harga,
                    'source_type'     => 'pembelian',
                    'source_id'       => $pembelian->id,
                    'user_id'         => auth()->id(),
                ]);
            }

            $pembelian->update([
                'is_diterima'  => true,
                'diterima_at'  => now(),
                'diterima_oleh'=> auth()->id(),
            ]);
        });

        return back()->with('success', 'Barang berhasil diterima dan stok sudah diperbarui.');
    }

    /*
    |--------------------------------------------------------------------------
    | LUNASI — untuk DP & Termin
    |--------------------------------------------------------------------------
    */

    public function lunasi(Request $request, Pembelian $pembelian)
    {
        if ($pembelian->is_lunas) {
            return back()->with('error', 'Pembelian ini sudah lunas.');
        }

        if (!in_array($pembelian->metode_pembayaran, ['dp', 'termin'])) {
            return back()->with('error', 'Hanya pembelian DP atau Termin yang perlu dilunasi.');
        }

        $request->validate([
            'nominal_pelunasan' => 'required|numeric|min:1',
            'catatan_pelunasan' => 'nullable|string|max:500',
        ]);

        $pembelian->update([
            'is_lunas'          => true,
            'lunas_at'          => now(),
            'nominal_pelunasan' => $request->nominal_pelunasan,
            'catatan_pelunasan' => $request->catatan_pelunasan,
        ]);

        return back()->with('success', 'Pembayaran lunas berhasil dicatat.');
    }

    /*
    |--------------------------------------------------------------------------
    | CATAT PEMBAYARAN
    |--------------------------------------------------------------------------
    */

    public function catatPembayaran(Request $request, Pembelian $pembelian)
    {
        $validated = $request->validate([
            'metode_pembayaran'   => 'required|in:cod,termin,dp',
            'tanggal_jatuh_tempo' => 'required_if:metode_pembayaran,termin|nullable|date',
            'persen_dp'           => 'nullable|integer|min:1|max:99',
            'nominal_dp'          => 'nullable|numeric|min:0',
            'tanggal_pelunasan'   => 'required_if:metode_pembayaran,dp,termin|nullable|date',
            'catatan_pembayaran'  => 'nullable|string|max:500',
        ], [
            'tanggal_pelunasan.required_if' => 'Tanggal pelunasan wajib diisi untuk metode DP/Termin.'
        ]);

        if ($validated['metode_pembayaran'] === 'dp') {
            if (empty($validated['persen_dp']) && empty($validated['nominal_dp'])) {
                return back()->withErrors(['persen_dp' => 'Persentase DP atau Nominal DP wajib diisi.'])->withInput();
            }

            $total = (float) $pembelian->total;
            if (!empty($validated['persen_dp']) && empty($validated['nominal_dp'])) {
                $validated['nominal_dp'] = round($total * $validated['persen_dp'] / 100, 2);
            } elseif (!empty($validated['nominal_dp']) && empty($validated['persen_dp'])) {
                $validated['persen_dp'] = (int) round(($validated['nominal_dp'] / $total) * 100);
            }
        }

        // COD langsung lunas
        $isLunas = $validated['metode_pembayaran'] === 'cod';

        $pembelian->update([
            ...$validated,
            'dicatat_oleh' => auth()->id(),
            'dicatat_pada' => now(),
            'is_lunas'     => $isLunas,
            'lunas_at'     => $isLunas ? now() : null,
        ]);

        return redirect()
            ->route('pembelian.index')
            ->with('success', 'Informasi pembayaran berhasil disimpan.');
    }

    /*
    |--------------------------------------------------------------------------
    | SHOW
    |--------------------------------------------------------------------------
    */

    public function show(Pembelian $pembelian)
    {
        $pembelian->load(['supplier', 'gudang', 'details.barang', 'user']);
        return view('pembelian.show', compact('pembelian'));
    }

    /*
    |--------------------------------------------------------------------------
    | EDIT
    |--------------------------------------------------------------------------
    */

    public function edit(Pembelian $pembelian)
    {
        if ($pembelian->isTerkunci()) {
            return redirect()->route('pembelian.index')
                ->with('error', 'Pembelian ini tidak bisa diedit karena sudah diterima atau lunas.');
        }

        $pembelian->load('details');
        $suppliers = Supplier::orderBy('nama')->get();
        $gudangs   = MasterGudang::orderBy('nama')->get();
        $barangs   = MasterBarang::query()
            ->where('is_bahan_baku', true)
            ->orWhere('is_operational', true)
            ->orWhere('is_direct_consumption', true)
            ->orderBy('nama')
            ->get();

        return view('pembelian.edit', compact('pembelian', 'suppliers', 'gudangs', 'barangs'));
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */

    public function update(StorePembelianRequest $request, Pembelian $pembelian)
    {
        if ($pembelian->isTerkunci()) {
            return redirect()->route('pembelian.index')
                ->with('error', 'Pembelian ini tidak bisa diubah.');
        }

        $data = $request->validated();

        DB::transaction(function () use ($data, $pembelian) {

            $pembelian->load('details');

            // Hapus detail lama (belum ada stok karena belum diterima)
            $pembelian->details()->delete();

            $total = collect($data['items'])->sum(fn($item) => (float) $item['harga']);

            $pembelian->update([
                'supplier_id' => $data['supplier_id'],
                'gudang_id'   => $data['gudang_id'],
                'tanggal'     => $data['tanggal'],
                'total'       => $total,
            ]);

            foreach ($data['items'] as $item) {

                $hargaPerQty = (float) $item['qty'] > 0
                    ? (float) $item['harga'] / (float) $item['qty']
                    : 0;

                $detail = $pembelian->details()->create([
                    'barang_id'     => $item['barang_id'],
                    'qty'           => $item['qty'],
                    'harga'         => $item['harga'],
                    'harga_per_qty' => $hargaPerQty,
                    'batch_number'  => 'TEMP',
                ]);

                $detail->update([
                    'batch_number' => date('Ymd') . '-PB' . $detail->id,
                ]);
            }
        });

        return redirect()
            ->route('pembelian.index')
            ->with('success', 'Pembelian berhasil diperbarui.');
    }

    /*
    |--------------------------------------------------------------------------
    | DESTROY
    |--------------------------------------------------------------------------
    */

    public function destroy(Pembelian $pembelian)
    {
        if ($pembelian->isTerkunci()) {
            return back()->with('error', 'Pembelian yang sudah diterima atau lunas tidak bisa dihapus.');
        }

        DB::transaction(function () use ($pembelian) {
            $pembelian->details()->delete();
            $pembelian->delete();
        });

        return redirect()
            ->route('pembelian.index')
            ->with('success', 'Pembelian berhasil dihapus.');
    }

    /*
    |--------------------------------------------------------------------------
    | GENERATE KODE
    |--------------------------------------------------------------------------
    */

    private function generateKodePembelian(string $tanggal): string
    {
        $prefix = 'PB' . Carbon::parse($tanggal)->format('Ymd');

        $last = Pembelian::where('kode_pembelian', 'like', $prefix . '%')
            ->lockForUpdate()
            ->orderByDesc('id')
            ->first();

        $number = $last ? ((int) substr($last->kode_pembelian, -4)) + 1 : 1;

        return $prefix . str_pad($number, 4, '0', STR_PAD_LEFT);
    }
}