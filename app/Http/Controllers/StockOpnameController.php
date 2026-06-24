<?php

namespace App\Http\Controllers;

use App\Models\MasterGudang;
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
    $stockOpname = StockOpname::with([
        'gudang',
        'user'
    ])
    ->latest()
    ->paginate(20);

    $gudangs = MasterGudang::orderBy('nama')
        ->get();

    return view(
        'stock-opname.index',
        compact(
            'stockOpname',
            'gudangs'
        )
    );
}

    /*
    |--------------------------------------------------------------------------
    | FORM CREATE
    |--------------------------------------------------------------------------
    */

    public function create(Request $request)
{
    $gudangId =
        $request->gudang_id;

    if (!$gudangId) {

        return redirect()
            ->route('stock-opname.index')
            ->with(
                'error',
                'Silakan pilih gudang terlebih dahulu.'
            );
    }

    $gudang =
        MasterGudang::findOrFail(
            $gudangId
        );

    return view(
        'stock-opname.create',
        compact(
            'gudang'
        )
    );
}

    /*
    |--------------------------------------------------------------------------
    | LOAD BARANG AJAX
    |--------------------------------------------------------------------------
    */

    public function loadBarang(Request $request)
{
$request->validate([
'gudang_id' => 'required'
]);

$barang = DB::table('stok_gudang')
    ->join(
        'master_barang',
        'stok_gudang.barang_id',
        '=',
        'master_barang.id'
    )

    ->where(
        'stok_gudang.gudang_id',
        $request->gudang_id
    )

    ->select(
        'master_barang.id',
        'master_barang.kode_barang',
        'master_barang.nama',
        'master_barang.satuan',
        'stok_gudang.jumlah as stok'
    )

    ->orderBy(
        'master_barang.nama',
        'asc'
    )

    ->get();

/*
|--------------------------------------------------------------------------
| HITUNG HARGA FIFO RATA-RATA
|--------------------------------------------------------------------------
*/

foreach ($barang as $item) {

    $hargaFIFO =
        DB::table('stok_gudang_batch')
        ->where(
            'gudang_id',
            $request->gudang_id
        )
        ->where(
            'barang_id',
            $item->id
        )
        ->avg('harga_per_qty');

    $item->harga_fifo =
        $hargaFIFO ?? 0;
}

return response()->json($barang);

}

public function hitungFIFORealtime(Request $request)
{
    $gudangId = $request->gudang_id;
    $barangId = $request->barang_id;
    $selisih  = abs($request->selisih);

    $nilai =
        $this->hitungNilaiFIFO(
            $gudangId,
            $barangId,
            $selisih
        );

    return response()->json([
        'nilai' => $nilai
    ]);
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

            $kode =
                'SO-' .
                now()->format('YmdHis');

            $opname = StockOpname::create([

                'kode_opname' => $kode,

                'tanggal' => now(),

                'gudang_id' => $request->gudang_id,

                'status' => 'draft',

                'keterangan' => $request->keterangan,

                'created_by' => Auth::id(),
            ]);

            foreach (
                $request->barang_id
                as $index => $barangId
            ) {

                $stokSistem =
                    (float)
                    $request->stok_sistem[$index];

                $stokFisik =
                    (float)
                    $request->stok_fisik[$index];

                $selisih =
                    $stokFisik -
                    $stokSistem;

                $nilaiSelisih =
                    $this->hitungNilaiFIFO(
                        $request->gudang_id,
                        $barangId,
                        abs($selisih)
                    );

                StockOpnameDetail::create([

                    'stock_opname_id' =>
                        $opname->id,

                    'barang_id' =>
                        $barangId,

                    'stok_sistem' =>
                        $stokSistem,

                    'stok_fisik' =>
                        $stokFisik,

                    'selisih' =>
                        $selisih,

                    'nilai_selisih' =>
                        $nilaiSelisih,
                ]);
            }

            DB::commit();

            return redirect()
                ->route(
                    'stock-opname.show',
                    $opname->id
                )
                ->with(
                    'success',
                    'Draft Stock Opname berhasil dibuat.'
                );

        } catch (\Exception $e) {

            DB::rollBack();

            return back()
                ->withErrors(
                    $e->getMessage()
                );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | HITUNG NILAI FIFO
    |--------------------------------------------------------------------------
    */

    private function hitungNilaiFIFO(
        $gudangId,
        $barangId,
        $qty
    ) {

        $sisa = $qty;

        $nilai = 0;

        $batches =
            DB::table('stok_gudang_batch')

            ->where(
                'gudang_id',
                $gudangId
            )

            ->where(
                'barang_id',
                $barangId
            )

            ->where(
                'qty_sisa',
                '>',
                0
            )

            ->orderBy('id')

            ->get();

        foreach ($batches as $batch) {

            if ($sisa <= 0) {
                break;
            }

            $ambil =
                min(
                    $sisa,
                    $batch->qty_sisa
                );

            $nilai +=
                $ambil *
                $batch->harga_per_qty;

            $sisa -= $ambil;
        }

        return $nilai;
    }

    /*
    |--------------------------------------------------------------------------
    | DETAIL STOCK OPNAME
    |--------------------------------------------------------------------------
    */

    public function show(string $id)
    {
        $stockOpname = StockOpname::with([
            'gudang',
            'user',
            'details.barang'
        ])
        ->findOrFail($id);

        return view(
            'stock-opname.show',
            compact('stockOpname')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | EDIT
    |--------------------------------------------------------------------------
    */

    public function edit(string $id)
    {
        //
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */

    public function update(
        Request $request,
        string $id
    ) {
        //
    }

    /*
    |--------------------------------------------------------------------------
    | HAPUS
    |--------------------------------------------------------------------------
    */

    public function destroy(string $id)
    {
        $opname =
            StockOpname::findOrFail($id);

        if (
            $opname->status ==
            'approved'
        ) {

            return back()->with(
                'error',
                'Stock Opname yang sudah approved tidak dapat dihapus.'
            );
        }

        $opname->details()
            ->delete();

        $opname->delete();

        return redirect()
            ->route('stock-opname.index')
            ->with(
                'success',
                'Stock Opname berhasil dihapus.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | APPROVE
    |--------------------------------------------------------------------------
    */

    public function approve($id)
{
    $opname = StockOpname::findOrFail($id);

    if($opname->status == 'approved')
    {
        return back()->with(
            'error',
            'Stock opname sudah diapprove.'
        );
    }

    $opname->update([
        'status' => 'approved'
    ]);

    return back()->with(
        'success',
        'Stock opname berhasil diapprove.'
    );
}
}