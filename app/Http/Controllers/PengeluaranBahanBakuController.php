<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\PengeluaranBahanBaku;
use App\Models\MasterBarang;
use App\Models\MasterGudang;
use App\Models\PengeluaranBahanBakuDetail;

use App\Services\PengeluaranBahanBakuService;
use App\Services\FifoService;
use App\Models\PengeluaranBahanBakuFifo;

class PengeluaranBahanBakuController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | PROPERTY
    |--------------------------------------------------------------------------
    */

    protected $service;

    protected $fifoService;

    /*
    |--------------------------------------------------------------------------
    | CONSTRUCTOR
    |--------------------------------------------------------------------------
    */

    public function __construct(
        PengeluaranBahanBakuService $service,
        FifoService $fifoService
    ) {
        $this->service = $service;

        $this->fifoService = $fifoService;
    }

    /**
     * Display a listing of the resource.
     */

    public function index()
    {
        $data = DB::table('pengeluaran_bahan_baku')
                    ->join(
                        'master_gudang',
                        'pengeluaran_bahan_baku.gudang_id',
                        '=',
                        'master_gudang.id'
                    )
                    ->select(
                        'pengeluaran_bahan_baku.*',
                        'master_gudang.nama as nama_gudang'
                    )
                    ->orderBy('created_at', 'desc')
                    ->get();

        return view(
            'pengeluaran-bahan-baku.index',
            compact('data')
        );
    }

    /**
     * Show the form for creating a new resource.
     */

    public function create()
{
    $barang = MasterBarang::query()
        ->leftJoin('stok_gudang', function ($join) {

            $join->on(
                'master_barang.id',
                '=',
                'stok_gudang.barang_id'
            );

            $join->where(
                'stok_gudang.gudang_id',
                1
            );
        })
        ->where(
            'master_barang.is_bahan_baku',
            1
        )
        ->select([
            'master_barang.*',
            DB::raw('COALESCE(stok_gudang.jumlah,0) as stok')
        ])
        ->orderBy('master_barang.nama')
        ->get();

    $gudang = MasterGudang::all();

    return view(
        'pengeluaran-bahan-baku.create',
        compact(
            'barang',
            'gudang'
        )
    );
}

    /**
     * Store a newly created resource in storage.
     */

    public function store(Request $request)
    {
        $request->validate([

            'gudang_id'
                => 'required|exists:master_gudang,id',

            'barang_id'
                => 'required|array|min:1',

            'barang_id.*'
                => 'required|exists:master_barang,id',

            'qty'
                => 'required|array|min:1',

            'qty.*'
                => 'required|numeric|min:0.01',

            'keterangan'
                => 'nullable|string',
        ]);

        $data = PengeluaranBahanBaku::create([

            'kode_pengeluaran'
                => 'PBK-' . time(),

            'tanggal'
                => now(),

            /*
            |--------------------------------------------------------------------------
            | GUDANG
            |--------------------------------------------------------------------------
            */

            'gudang_id'
                => $request->gudang_id,

            'status'
                => 'draft',

            'keterangan'
                => $request->keterangan,

            'created_by'
                => auth()->id(),
        ]);

        foreach ($request->barang_id as $index => $barangId) {

            PengeluaranBahanBakuDetail::create([

                'pengeluaran_id'
                    => $data->id,

                'barang_id'
                    => $barangId,

                'qty'
                    => $request->qty[$index],

                'satuan'
                    => 'pcs',

                /*
                |--------------------------------------------------------------------------
                | NANTI BISA DIISI FIFO HPP
                |--------------------------------------------------------------------------
                */

                'harga_satuan'
                    => 0,

                'total_harga'
                    => 0,
            ]);
        }

        return redirect()
            ->route('pengeluaran-bahan-baku.index')
            ->with(
                'success',
                'Data pengeluaran bahan baku berhasil dibuat.'
            );
    }

    /**
     * Display the specified resource.
     */

    public function show(string $id)
    {
        $pengeluaran = PengeluaranBahanBaku::with([
            'details.barang',
            'gudang'
        ])->findOrFail($id);

        return view(
            'pengeluaran-bahan-baku.show',
            compact('pengeluaran')
        );
    }

    /**
     * Show the form for editing the specified resource.
     */

    public function edit(string $id)
{
    $pengeluaran = PengeluaranBahanBaku::with(
        'details'
    )->findOrFail($id);

    /*
    |--------------------------------------------------------------------------
    | TIDAK BOLEH EDIT JIKA SUDAH APPROVED
    |--------------------------------------------------------------------------
    */

    if (
        strtolower($pengeluaran->status) === 'approved'
        ||
        strtolower($pengeluaran->status) === 'disetujui'
    ) {

        return redirect()
            ->route('pengeluaran-bahan-baku.index')
            ->with(
                'error',
                'Pengeluaran yang sudah disetujui tidak dapat diedit.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | TIDAK BOLEH EDIT JIKA DARI WORK ORDER
    |--------------------------------------------------------------------------
    */

    if (
        str_contains(
            strtolower($pengeluaran->keterangan ?? ''),
            'permintaan bahan baku untuk'
        )
    ) {

        return redirect()
            ->route(
                'pengeluaran-bahan-baku.show',
                $pengeluaran->id
            )
            ->with(
                'error',
                'Pengeluaran dari Work Order tidak dapat diedit.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | MASTER BARANG
    |--------------------------------------------------------------------------
    */

    $barang = MasterBarang::query()
        ->where('is_bahan_baku', 1)
        ->orderBy('nama')
        ->get();

    /*
    |--------------------------------------------------------------------------
    | MASTER GUDANG
    |--------------------------------------------------------------------------
    */

    $gudang = MasterGudang::orderBy('nama')->get();

    return view(
        'pengeluaran-bahan-baku.edit',
        compact(
            'pengeluaran',
            'barang',
            'gudang'
        )
    );
}
    /**
     * Update the specified resource in storage.
     */

    public function update(
        Request $request,
        string $id
    ) {

    $request->validate([

        'gudang_id'
            => 'required|exists:master_gudang,id',

        'barang_id'
            => 'required|array|min:1',

        'barang_id.*'
            => 'required|exists:master_barang,id',

        'qty'
            => 'required|array|min:1',

        'qty.*'
            => 'required|numeric|min:0.01',

        'keterangan'
            => 'nullable|string',
    ]);

    DB::transaction(function () use (
        $request,
        $id
    ) {

        $data = PengeluaranBahanBaku::with(
            'details'
        )->findOrFail($id);

        /*
        |----------------------------------------------------------------------
        | LOCK APPROVED
        |----------------------------------------------------------------------
        */

        if (
            strtolower($data->status)
            === 'approved'
            ||
            strtolower($data->status)
            === 'disetujui'
        ) {

            throw new \Exception(
                'Pengeluaran yang sudah disetujui tidak dapat diedit.'
            );
        }

        /*
        |----------------------------------------------------------------------
        | UPDATE HEADER
        |----------------------------------------------------------------------
        */

        $data->update([

            'gudang_id'
                => $request->gudang_id,

            'keterangan'
                => $request->keterangan,
        ]);

        /*
        |----------------------------------------------------------------------
        | HAPUS DETAIL LAMA
        |----------------------------------------------------------------------
        */

        $data->details()->delete();

        /*
        |----------------------------------------------------------------------
        | INSERT DETAIL BARU
        |----------------------------------------------------------------------
        */

        foreach ($request->barang_id as $index => $barangId) {

            PengeluaranBahanBakuDetail::create([

                'pengeluaran_id'
                    => $data->id,

                'barang_id'
                    => $barangId,

                'qty'
                    => $request->qty[$index],

                'satuan'
                    => 'pcs',

                'harga_satuan'
                    => 0,

                'total_harga'
                    => 0,
            ]);
        }
    });

    return redirect()
        ->route('pengeluaran-bahan-baku.index')
        ->with(
            'success',
            'Pengeluaran bahan baku berhasil diperbarui.'
        );
    }

    /**
     * Remove the specified resource from storage.
     */

    public function destroy(string $id)
    {
        //
    }

    /*
    |--------------------------------------------------------------------------
    | APPROVE
    |--------------------------------------------------------------------------
    |
    | Saat approve:
    | 1. Stock summary dikurangi
    | 2. FIFO batch dikurangi
    |
    */

    public function approve($id)
    {
        DB::transaction(function () use ($id) {

            $data = PengeluaranBahanBaku::with(
                'details'
            )->findOrFail($id);

            /*
            |--------------------------------------------------------------------------
            | VALIDASI STATUS
            |--------------------------------------------------------------------------
            */

            if ($data->status === 'approved') {

                throw new \Exception(
                    'Pengeluaran sudah diapprove.'
                );
            }

            /*
|--------------------------------------------------------------------------
| FIFO CONSUME
|--------------------------------------------------------------------------
|
| FIFO selalu diambil dari Gudang Utama
| karena seluruh pembelian masuk ke Gudang Utama.
|
*/

$gudangUtama = MasterGudang::where(
    'nama',
    'Gudang Utama'
)->firstOrFail();

foreach ($data->details as $detail) {

    $fifoResult =
        $this->fifoService->consumeFIFO(

            barangId:
                $detail->barang_id,

            qtyKeluar:
                $detail->qty,

            gudangId:
                $gudangUtama->id,
        );

    /*
    |--------------------------------------------------------------------------
    | TOTAL HPP DETAIL
    |--------------------------------------------------------------------------
    */

    $hppTotal = 0;

    /*
    |--------------------------------------------------------------------------
    | SIMPAN HISTORI FIFO
    |--------------------------------------------------------------------------
    */

    foreach ($fifoResult as $fifo) {

        $totalHarga =
            $fifo['qty_keluar']
            *
            $fifo['harga_per_qty'];

        $hppTotal += $totalHarga;

        PengeluaranBahanBakuFifo::create([

            'pengeluaran_id'
                => $data->id,

            'detail_id'
                => $detail->id,

            'batch_id'
                => $fifo['batch_id'],

            'batch_number'
                => $fifo['batch_number'],

            'qty_keluar'
                => $fifo['qty_keluar'],

            'harga_per_qty'
                => $fifo['harga_per_qty'],

            'total_harga'
                => $totalHarga,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | SIMPAN HPP DETAIL
    |--------------------------------------------------------------------------
    */

    $detail->update([

        'hpp_total'
            => $hppTotal,
    ]);
}

            /*
            |--------------------------------------------------------------------------
            | STOCK OUT SUMMARY
            |--------------------------------------------------------------------------
            */

            $this->service->approve(
                $data,
                auth()->id()
            );

            /*
            |--------------------------------------------------------------------------
            | UPDATE STATUS
            |--------------------------------------------------------------------------
            */

            $data->update([

                'status'
                    => 'approved',
            ]);
        });

        return redirect()
            ->route('pengeluaran-bahan-baku.index')
            ->with(
                'success',
                'Pengeluaran berhasil disetujui dan FIFO berhasil diproses.'
            );
    }
}