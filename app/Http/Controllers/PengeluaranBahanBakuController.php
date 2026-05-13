<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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
        $data = PengeluaranBahanBaku::latest()->get();

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

        $pengeluaran = PengeluaranBahanBaku::create([
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
                'pengeluaran_id' => $pengeluaran->id,
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
        $pengeluaran = PengeluaranBahanBaku::with([
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
        $pengeluaran = PengeluaranBahanBaku::with('details')
            ->findOrFail($id);

        $this->service->approve(
            $pengeluaran,
            auth()->id()
        );

        return redirect()
            ->route('pengeluaran-bahan-baku.index')
            ->with('success', 'Pengeluaran berhasil disetujui');
    }
}
