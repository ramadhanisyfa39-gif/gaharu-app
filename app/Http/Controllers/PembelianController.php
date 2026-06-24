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
use Illuminate\Support\Facades\DB;

class PembelianController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | PROPERTY
    |--------------------------------------------------------------------------
    */

    protected StockService $stockService;

    protected FifoService $fifoService;

    /*
    |--------------------------------------------------------------------------
    | CONSTRUCTOR
    |--------------------------------------------------------------------------
    */

    public function __construct(
        StockService $stockService,
        FifoService $fifoService
    ) {

        $this->stockService = $stockService;

        $this->fifoService = $fifoService;
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
            | HITUNG TOTAL PEMBELIAN
            |--------------------------------------------------------------------------
            |
            | harga sekarang adalah TOTAL HARGA
            |
            */

            $total = collect($data['items'])
                ->sum(function ($item) {

                    return (float) $item['harga'];
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
            | CREATE DETAIL
            |--------------------------------------------------------------------------
            */

            foreach ($data['items'] as $item) {

                /*
                |--------------------------------------------------------------------------
                | HARGA PER QTY
                |--------------------------------------------------------------------------
                */

                $hargaPerQty = 0;

                if ((float) $item['qty'] > 0) {

                    $hargaPerQty =
                        (float) $item['harga']
                        /
                        (float) $item['qty'];
                }

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

                    'harga_per_qty'
                    => $hargaPerQty,

                    /*
    |--------------------------------------------------------------------------
    | TEMP BATCH
    |--------------------------------------------------------------------------
    */

                    'batch_number'
                    => 'TEMP',
                ]);

                /*
|--------------------------------------------------------------------------
| GENERATE BATCH UNIK
|--------------------------------------------------------------------------
*/

                $detail->update([

                    'batch_number'
                    => Carbon::parse(
                        $pembelian->tanggal
                    )->format('Ymd')
                        . '-PB'
                        . $detail->id,
                ]);

                /*
|--------------------------------------------------------------------------
| GENERATE BATCH UNIK
|--------------------------------------------------------------------------
*/

                $detail->update([

                    'batch_number'
                    => date('Ymd')
                        . '-PB'
                        . $detail->id,
                ]);

                /*
                |--------------------------------------------------------------------------
                | FIFO BATCH STOCK
                |--------------------------------------------------------------------------
                */

                $this->fifoService->createBatchStock(
                    $pembelian,
                    $detail
                );

                /*
                |--------------------------------------------------------------------------
                | STOCK IN SUMMARY
                |--------------------------------------------------------------------------
                */

                $this->stockService->stockIn([

                    'barang_id'
                    => $detail->barang_id,

                    'gudang_tujuan_id'
                    => $pembelian->gudang_id,

                    'qty'
                    => $detail->qty,

                    /*
                    |--------------------------------------------------------------------------
                    | TOTAL HARGA
                    |--------------------------------------------------------------------------
                    */

                    'total_harga'
                    => $detail->harga,

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
                    => $detail->harga,

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

                    return (float) $item['harga'];
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

                /*
                |--------------------------------------------------------------------------
                | HARGA PER QTY
                |--------------------------------------------------------------------------
                */

                $hargaPerQty = 0;

                if ((float) $item['qty'] > 0) {

                    $hargaPerQty =
                        (float) $item['harga']
                        /
                        (float) $item['qty'];
                }

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

                    'harga_per_qty'
                    => $hargaPerQty,

                    'batch_number'
                    => 'TEMP',
                ]);

                $detail->update([

                    'batch_number'
                    => Carbon::parse(
                        $pembelian->tanggal
                    )->format('Ymd')
                        . '-PB'
                        . $detail->id,
                ]);

                /*
                |--------------------------------------------------------------------------
                | FIFO BATCH
                |--------------------------------------------------------------------------
                */

                $this->fifoService->createBatchStock(
                    $pembelian,
                    $detail
                );

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
                    => $detail->harga,

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

            foreach ($pembelian->details as $detail) {

                $this->stockService->stockOut([

                    'gudang_asal_id'
                    => $pembelian->gudang_id,

                    'barang_id'
                    => $detail->barang_id,

                    'qty'
                    => $detail->qty,

                    'total_harga'
                    => $detail->harga,

                    'source_type'
                    => 'hapus_pembelian',

                    'source_id'
                    => $pembelian->id,

                    'user_id'
                    => auth()->id(),
                ]);
            }

            $pembelian->details()->delete();

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
