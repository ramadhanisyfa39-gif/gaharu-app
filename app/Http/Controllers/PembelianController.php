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

class PembelianController extends Controller
{
    protected StockService $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    /*
    |--------------------------------------------------------------------------
    | INDEX
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $pembelian = Pembelian::with([
            'supplier',
            'gudang',
            'user'
        ])
            ->orderByDesc('tanggal')
            ->paginate(10);

        return view(
            'pembelian.index',
            compact('pembelian')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE
    |--------------------------------------------------------------------------
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

        return view(
            'pembelian.create',
            compact(
                'suppliers',
                'gudangs',
                'barangs'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | STORE
    |--------------------------------------------------------------------------
    */

    public function store(StorePembelianRequest $request)
    {
        $data = $request->validated();

        DB::transaction(function () use ($data) {

            /*
            |--------------------------------------------------------------------------
            | HITUNG TOTAL
            |--------------------------------------------------------------------------
            */

            $total = collect($data['items'])
                ->sum(function ($item) {

                    return
                        (float) $item['qty']
                        *
                        (float) $item['harga'];
                });

            /*
            |--------------------------------------------------------------------------
            | CREATE HEADER
            |--------------------------------------------------------------------------
            */

            $pembelian = Pembelian::create([

                'kode_pembelian'
                => $this->generateKodePembelian(
                    $data['tanggal']
                ),

                'supplier_id'
                => $data['supplier_id'],

                'gudang_id'
                => $data['gudang_id'],

                'tanggal'
                => $data['tanggal'],

                'total'
                => $total,

                'created_by'
                => auth()->id(),
            ]);

            /*
            |--------------------------------------------------------------------------
            | CREATE DETAIL + STOCK IN
            |--------------------------------------------------------------------------
            */

            foreach ($data['items'] as $item) {

                /*
                |--------------------------------------------------------------------------
                | CREATE DETAIL
                |--------------------------------------------------------------------------
                */

                $detail = $pembelian->details()->create([

                    'barang_id'
                    => $item['barang_id'],

                    'qty'
                    => $item['qty'],

                    'harga'
                    => $item['harga'],

                    'batch_number'
                    => $item['batch_number'] ?? null,
                ]);

                /*
                |--------------------------------------------------------------------------
                | STOCK IN
                |--------------------------------------------------------------------------
                */

                $this->stockService->stockIn([

                    'barang_id'
                    => $detail->barang_id,

                    'gudang_tujuan_id'
                    => $pembelian->gudang_id,

                    'qty'
                    => $detail->qty,

                    'total_harga'
                    => $detail->qty * $detail->harga,

                    'source_type'
                    => 'pembelian',

                    'source_id'
                    => $pembelian->id,

                    'user_id'
                    => auth()->id(),
                ]);
            }
        });

        return redirect()
            ->route('pembelian.index')
            ->with(
                'success',
                'Pembelian berhasil disimpan dan stok berhasil ditambahkan.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | SHOW
    |--------------------------------------------------------------------------
    */

    public function show(Pembelian $pembelian)
    {
        $pembelian->load([
            'supplier',
            'gudang',
            'details.barang',
            'user'
        ]);

        return view(
            'pembelian.show',
            compact('pembelian')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | EDIT
    |--------------------------------------------------------------------------
    */

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

        return view(
            'pembelian.edit',
            compact(
                'pembelian',
                'suppliers',
                'gudangs',
                'barangs'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */

    public function update(
        StorePembelianRequest $request,
        Pembelian $pembelian
    ) {

        $data = $request->validated();

        DB::transaction(function () use (
            $data,
            $pembelian
        ) {

            $pembelian->load('details');

            /*
            |--------------------------------------------------------------------------
            | KELUARKAN STOK LAMA
            |--------------------------------------------------------------------------
            */

            foreach ($pembelian->details as $detail) {

                $this->stockService->stockOut([

                    'gudang_asal_id'
                    => $pembelian->gudang_id,

                    'barang_id'
                    => $detail->barang_id,

                    'qty'
                    => $detail->qty,

                    'total_harga'
                    => $detail->qty * $detail->harga,

                    'source_type'
                    => 'edit_pembelian',

                    'source_id'
                    => $pembelian->id,

                    'user_id'
                    => auth()->id(),
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | HAPUS DETAIL LAMA
            |--------------------------------------------------------------------------
            */

            $pembelian->details()->delete();

            /*
            |--------------------------------------------------------------------------
            | HITUNG TOTAL BARU
            |--------------------------------------------------------------------------
            */

            $total = collect($data['items'])
                ->sum(function ($item) {

                    return
                        (float) $item['qty']
                        *
                        (float) $item['harga'];
                });

            /*
            |--------------------------------------------------------------------------
            | UPDATE HEADER
            |--------------------------------------------------------------------------
            */

            $pembelian->update([

                'supplier_id'
                => $data['supplier_id'],

                'gudang_id'
                => $data['gudang_id'],

                'tanggal'
                => $data['tanggal'],

                'total'
                => $total,
            ]);

            /*
            |--------------------------------------------------------------------------
            | SIMPAN DETAIL BARU
            |--------------------------------------------------------------------------
            */

            foreach ($data['items'] as $item) {

                $detail = $pembelian->details()->create([

                    'barang_id'
                    => $item['barang_id'],

                    'qty'
                    => $item['qty'],

                    'harga'
                    => $item['harga'],

                    'batch_number'
                    => $item['batch_number'] ?? null,
                ]);

                /*
                |--------------------------------------------------------------------------
                | STOCK IN BARU
                |--------------------------------------------------------------------------
                */

                $this->stockService->stockIn([

                    'barang_id'
                    => $detail->barang_id,

                    'gudang_tujuan_id'
                    => $pembelian->gudang_id,

                    'qty'
                    => $detail->qty,

                    'total_harga'
                    => $detail->qty * $detail->harga,

                    'source_type'
                    => 'edit_pembelian',

                    'source_id'
                    => $pembelian->id,

                    'user_id'
                    => auth()->id(),
                ]);
            }
        });

        return redirect()
            ->route('pembelian.index')
            ->with(
                'success',
                'Pembelian berhasil diperbarui dan stok berhasil disesuaikan.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | DESTROY
    |--------------------------------------------------------------------------
    */

    public function destroy(Pembelian $pembelian)
    {
        DB::transaction(function () use ($pembelian) {

            $pembelian->load('details');

            /*
        |--------------------------------------------------------------------------
        | VALIDASI STOK MASIH ADA
        |--------------------------------------------------------------------------
        */

            foreach ($pembelian->details as $detail) {

                $stok = \App\Models\StokGudang::where(
                    'barang_id',
                    $detail->barang_id
                )
                    ->where(
                        'gudang_id',
                        $pembelian->gudang_id
                    )
                    ->first();

                /*
            |--------------------------------------------------------------------------
            | JIKA STOK SUDAH TERPAKAI
            |--------------------------------------------------------------------------
            */

                if (
                    !$stok ||
                    $stok->jumlah < $detail->qty
                ) {

                    throw new \RuntimeException(
                        'Pembelian tidak bisa dihapus karena stok sudah terpakai.'
                    );
                }
            }

            /*
        |--------------------------------------------------------------------------
        | KELUARKAN STOK
        |--------------------------------------------------------------------------
        */

            foreach ($pembelian->details as $detail) {

                $this->stockService->stockOut([

                    'gudang_asal_id'
                    => $pembelian->gudang_id,

                    'barang_id'
                    => $detail->barang_id,

                    'qty'
                    => $detail->qty,

                    'total_harga'
                    => $detail->qty * $detail->harga,

                    'source_type'
                    => 'hapus_pembelian',

                    'source_id'
                    => $pembelian->id,

                    'user_id'
                    => auth()->id(),
                ]);
            }

            /*
        |--------------------------------------------------------------------------
        | HAPUS DETAIL
        |--------------------------------------------------------------------------
        */

            $pembelian->details()->delete();

            /*
        |--------------------------------------------------------------------------
        | HAPUS HEADER
        |--------------------------------------------------------------------------
        */

            $pembelian->delete();
        });

        return redirect()
            ->route('pembelian.index')
            ->with(
                'success',
                'Pembelian berhasil dihapus dan stok berhasil dikurangi.'
            );
    }
    /*
    |--------------------------------------------------------------------------
    | GENERATE KODE
    |--------------------------------------------------------------------------
    */

    private function generateKodePembelian(
        string $tanggal
    ): string {

        $prefix = 'PB'
            . Carbon::parse($tanggal)
            ->format('Ymd');

        $last = Pembelian::where(
            'kode_pembelian',
            'like',
            $prefix . '%'
        )
            ->lockForUpdate()
            ->orderByDesc('id')
            ->first();

        $number = $last
            ? ((int) substr(
                $last->kode_pembelian,
                -4
            )) + 1
            : 1;

        return $prefix
            . str_pad(
                $number,
                4,
                '0',
                STR_PAD_LEFT
            );
    }
}
