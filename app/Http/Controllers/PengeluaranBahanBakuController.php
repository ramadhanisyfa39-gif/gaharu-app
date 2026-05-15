<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\PengeluaranBahanBaku;
use App\Services\PengeluaranBahanBakuService;
use App\Models\MasterBarang;
use App\Models\MasterGudang;
use App\Models\PengeluaranBahanBakuDetail;

class PengeluaranBahanBakuController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | PROPERTY
    |--------------------------------------------------------------------------
    */ 

    protected $service;

    /*
    |--------------------------------------------------------------------------
    | CONSTRUCTOR
    |--------------------------------------------------------------------------
    */

    public function __construct(
        PengeluaranBahanBakuService $service
    ) {
        $this->service = $service;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
{
    // 1. Pastikan nama variabel di sini adalah $data
    $data = DB::table('pengeluaran_bahan_baku')
                ->join('master_gudang', 'pengeluaran_bahan_baku.gudang_id', '=', 'master_gudang.id')
                ->select('pengeluaran_bahan_baku.*', 'master_gudang.nama as nama_gudang')
                ->orderBy('created_at', 'desc')
                ->get();

    // 2. Kirim variabel $data ke view
    return view('pengeluaran-bahan-baku.index', compact('data'));
}
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $barang = MasterBarang::with('kategori')
            ->where('is_bahan_baku', 1)
            ->get();

        $gudang = MasterGudang::all();

        return view(
            'pengeluaran-bahan-baku.create',
            compact('barang', 'gudang')
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'gudang_id' => 'required|exists:master_gudang,id',
            'barang_id' => 'required|array|min:1',
            'barang_id.*' => 'required|exists:master_barang,id',
            'qty' => 'required|array|min:1',
            'qty.*' => 'required|numeric|min:0.01',
            'keterangan' => 'nullable|string',
        ]);

        $data = PengeluaranBahanBaku::create([
            'kode_pengeluaran' => 'PBK-' . time(),
            'tanggal' => now(),

            // saat ini gudang_id dipakai sebagai gudang tujuan
            'gudang_id' => $request->gudang_id,

            'status' => 'draft',
            'keterangan' => $request->keterangan,
            'created_by' => auth()->id(),
        ]);

        foreach ($request->barang_id as $index => $barangId) {
            PengeluaranBahanBakuDetail::create([
                'pengeluaran_id' => $data->id,
                'barang_id' => $barangId,
                'qty' => $request->qty[$index],
                'satuan' => 'pcs',
                'harga_satuan' => 0,
                'total_harga' => 0,
            ]);
        }

        return redirect()
            ->route('pengeluaran-bahan-baku.index')
            ->with('success', 'Data pengeluaran bahan baku berhasil dibuat.');
    }
    public function show(string $id)
    {
        $data = PengeluaranBahanBaku::with([
            'details.barang',
            'gudang'
        ])->findOrFail($id);

        return view('pengeluaran-bahan-baku.show', compact('pengeluaran'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
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
    */

    public function approve($id)
    {
        $data = PengeluaranBahanBaku::with('details')
            ->findOrFail($id);

        $this->service->approve(
            $dat,
            auth()->id()
        );

        return redirect()
            ->route('pengeluaran-bahan-baku.index')
            ->with('success', 'Pengeluaran berhasil disetujui');
    }
}
