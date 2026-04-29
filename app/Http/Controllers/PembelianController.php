<?php

namespace App\Http\Controllers;
    use App\Http\Requests\StorePembelianRequest;
    use App\Models\MasterBarang;
    use App\Models\MasterGudang;
    use App\Models\Pembelian;
    use App\Models\Supplier;
    use App\Services\StockService;
    use Carbon\Carbon;
    use Illuminate\Support\Facades\DB;

// use Illuminate\Http\Request;

class PembelianController extends Controller
{
    protected StockService $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    public function index()
    {
        $pembelian = Pembelian::with(['supplier', 'gudang', 'user'])
            ->orderByDesc('tanggal')
            ->paginate(10);

        return view('pembelian.index', compact('pembelian'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
    $suppliers = Supplier::orderBy('nama')->get();
        $gudangs = MasterGudang::orderBy('nama')->get();

        $barangs = MasterBarang::query()
            ->where('is_bahan_baku', true)
            ->orWhere('is_operational', true)
            ->orWhere('is_direct_consumption', true)
            ->orderBy('nama')
            ->get();

        return view('pembelian.create', compact('suppliers', 'gudangs', 'barangs'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePembelianRequest $request)
    {
        $data = $request->validated();

        DB::transaction(function () use ($data) {
            $total = collect($data['items'])->sum(function ($item) {
                return (float) $item['qty'] * (float) $item['harga'];
            });

            $pembelian = Pembelian::create([
                'kode_pembelian' => $this->generateKodePembelian($data['tanggal']),
                'supplier_id' => $data['supplier_id'],
                'gudang_id' => $data['gudang_id'],
                'tanggal' => $data['tanggal'],
                'total' => $total,
                'created_by' => auth()->id(),
            ]);

            foreach ($data['items'] as $item) {
                $pembelian->details()->create([
                    'barang_id' => $item['barang_id'],
                    'qty' => $item['qty'],
                    'harga' => $item['harga'],
                    'batch_number' => $item['batch_number'] ?? null,
                ]);

                $this->stockService->stockIn([
                    'gudang_id' => $data['gudang_id'],
                    'barang_id' => $item['barang_id'],
                    'qty' => $item['qty'],
                    'total_harga' => (float) $item['qty'] * (float) $item['harga'],

                    'source_type' => 'pembelian',
                    'source_id' => $pembelian->id,

                    'user_id' => auth()->id(),
                ]);
            }
        });

        return redirect()
            ->route('pembelian.index')
            ->with('success', 'Pembelian berhasil disimpan dan stok berhasil ditambahkan.');
    }

    public function show(Pembelian $pembelian)
    {
        $pembelian->load(['supplier', 'gudang', 'details.barang', 'user']);

        return view('pembelian.show', compact('pembelian'));
    }

    private function generateKodePembelian(string $tanggal): string
    {
        $prefix = 'PB' . Carbon::parse($tanggal)->format('Ymd');

        $last = Pembelian::where('kode_pembelian', 'like', $prefix . '%')
            ->lockForUpdate()
            ->orderByDesc('id')
            ->first();

        $number = $last
            ? ((int) substr($last->kode_pembelian, -4)) + 1
            : 1;

        return $prefix . str_pad($number, 4, '0', STR_PAD_LEFT);
    }
    public function edit(Pembelian $pembelian)
    {
        $pembelian->load('details');

        $suppliers = Supplier::orderBy('nama')->get();
        $gudangs = MasterGudang::orderBy('nama')->get();

        $barangs = MasterBarang::query()
            ->where('is_bahan_baku', true)
            ->orWhere('is_operational', true)
            ->orWhere('is_direct_consumption', true)
            ->orderBy('nama')
            ->get();

        return view('pembelian.edit', compact(
            'pembelian',
            'suppliers',
            'gudangs',
            'barangs'
        ));
    }   
    public function update(StorePembelianRequest $request, Pembelian $pembelian)
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $pembelian) {
            $pembelian->load('details');

            // 1. Keluarkan stok lama
            foreach ($pembelian->details as $detail) {
                $this->stockService->stockOut([
                    'gudang_id' => $pembelian->gudang_id,
                    'barang_id' => $detail->barang_id,
                    'qty' => $detail->qty,
                    'total_harga' => $detail->qty * $detail->harga,

                    'source_type' => 'edit_pembelian',
                    'source_id' => $pembelian->id,

                    'user_id' => auth()->id(),
                ]);
            }

            // 2. Hapus detail lama
            $pembelian->details()->delete();

            // 3. Hitung total baru
            $total = collect($data['items'])->sum(function ($item) {
                return (float) $item['qty'] * (float) $item['harga'];
            });

            // 4. Update header pembelian
            $pembelian->update([
                'supplier_id' => $data['supplier_id'],
                'gudang_id' => $data['gudang_id'],
                'tanggal' => $data['tanggal'],
                'total' => $total,
            ]);

            // 5. Simpan detail baru dan masukkan stok baru
            foreach ($data['items'] as $item) {
                $pembelian->details()->create([
                    'barang_id' => $item['barang_id'],
                    'qty' => $item['qty'],
                    'harga' => $item['harga'],
                    'batch_number' => $item['batch_number'] ?? null,
                ]);

                $this->stockService->stockIn([
                    'gudang_id' => $data['gudang_id'],
                    'barang_id' => $item['barang_id'],
                    'qty' => $item['qty'],
                    'total_harga' => (float) $item['qty'] * (float) $item['harga'],

                    'source_type' => 'edit_pembelian',
                    'source_id' => $pembelian->id,

                    'user_id' => auth()->id(),
                ]);
            }
        });

        return redirect()
            ->route('pembelian.index')
            ->with('success', 'Pembelian berhasil diperbarui dan stok berhasil disesuaikan.');
    }
        public function destroy(Pembelian $pembelian)
    {
        DB::transaction(function () use ($pembelian) {
            $pembelian->load('details');

            // 1. Keluarkan stok dari pembelian yang akan dihapus
            foreach ($pembelian->details as $detail) {
                $this->stockService->stockOut([
                    'gudang_id' => $pembelian->gudang_id,
                    'barang_id' => $detail->barang_id,
                    'qty' => $detail->qty,
                    'total_harga' => $detail->qty * $detail->harga,

                    'source_type' => 'hapus_pembelian',
                    'source_id' => $pembelian->id,

                    'user_id' => auth()->id(),
                ]);
            }

            // 2. Hapus detail
            $pembelian->details()->delete();

            // 3. Hapus header
            $pembelian->delete();
        });

        return redirect()
            ->route('pembelian.index')
            ->with('success', 'Pembelian berhasil dihapus dan stok berhasil dikurangi.');
    }
}
