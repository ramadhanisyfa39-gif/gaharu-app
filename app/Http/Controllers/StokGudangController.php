<?php

namespace App\Http\Controllers;

use App\Models\StokGudang;
use App\Models\MasterGudang;
use App\Models\MasterBarang;
use Illuminate\Http\Request;

class StokGudangController extends Controller
{
    public function index(Request $request)
    {
        $query = StokGudang::with(['gudang', 'barang']);

        if ($request->filled('gudang_id')) {
            $query->where('gudang_id', $request->gudang_id);
        }

        if ($request->filled('barang_id')) {
            $query->where('barang_id', $request->barang_id);
        }

        $stokGudang = $query
            ->orderBy('gudang_id')
            ->orderBy('barang_id')
            ->paginate(10);

        $gudangs = MasterGudang::orderBy('nama')->get();
        $barangs = MasterBarang::orderBy('nama')->get();

        return view('stok-gudang.index', compact(
            'stokGudang',
            'gudangs',
            'barangs'
        ));
    }
}