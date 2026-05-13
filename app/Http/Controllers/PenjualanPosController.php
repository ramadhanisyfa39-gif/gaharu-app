<?php

namespace App\Http\Controllers;

use App\Models\PenjualanPos;
use App\Models\PenjualanPosDetail;
use App\Models\MasterBarang;
use App\Models\MasterGudang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PenjualanPosController extends Controller
{
    public function index()
    {
        $data = PenjualanPos::latest()->get();

        return view('penjualan_pos.index', compact('data'));
    }

    public function create()
    {
        $produk = MasterBarang::where('is_barang_jadi', 1)->get();

        $gudang = MasterGudang::all();

        return view('penjualan_pos.create', compact(
            'produk',
            'gudang'
        ));
    }

    public function store(Request $request)
    {
        DB::beginTransaction();

        try {

            $penjualan = PenjualanPos::create([
                'kode_transaksi' => 'POS-' . time(),
                'tanggal' => $request->tanggal,
                'gudang_id' => $request->gudang_id,
                'total' => 0,
                'created_by' => auth()->id()
            ]);

            $total = 0;

            foreach ($request->produk_id as $key => $produkId) {

                $qty = $request->qty[$key];
                $harga = $request->harga[$key];

                $subtotal = $qty * $harga;

                PenjualanPosDetail::create([
                    'penjualan_id' => $penjualan->id,
                    'produk_id' => $produkId,
                    'qty' => $qty,
                    'harga' => $harga,
                    'hpp_satuan' => 0,
                    'subtotal' => $subtotal
                ]);

                $total += $subtotal;
            }

            $penjualan->update([
                'total' => $total
            ]);

            DB::commit();

            return redirect()
                ->route('penjualan_pos.index')
                ->with('success', 'Penjualan berhasil disimpan');

        } catch (\Exception $e) {

            DB::rollBack();

            return back()->with('error', $e->getMessage());
        }
    }

    public function show($id)
    {
        $penjualan = PenjualanPos::with(
            'details.produk',
            'gudang',
            'creator'
        )->findOrFail($id);

        return view('penjualan_pos.show', compact('penjualan'));
    }

    public function edit($id)
    {
        $penjualan = PenjualanPos::with(
            'details.produk',
            'gudang'
        )->findOrFail($id);

        $produk = MasterBarang::where('is_barang_jadi', 1)->get();

        $gudang = MasterGudang::all();

        return view('penjualan_pos.edit', compact(
            'penjualan',
            'produk',
            'gudang'
        ));
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {

            $penjualan = PenjualanPos::findOrFail($id);

            $penjualan->update([
                'tanggal' => $request->tanggal,
                'gudang_id' => $request->gudang_id,
            ]);

            // hapus detail lama
            PenjualanPosDetail::where(
                'penjualan_id',
                $id
            )->delete();

            $total = 0;

            foreach ($request->produk_id as $key => $produkId) {

                $qty = $request->qty[$key];
                $harga = $request->harga[$key];

                $subtotal = $qty * $harga;

                PenjualanPosDetail::create([
                    'penjualan_id' => $penjualan->id,
                    'produk_id' => $produkId,
                    'qty' => $qty,
                    'harga' => $harga,
                    'hpp_satuan' => 0,
                    'subtotal' => $subtotal
                ]);

                $total += $subtotal;
            }

            $penjualan->update([
                'total' => $total
            ]);

            DB::commit();

            return redirect()
                ->route('penjualan_pos.index')
                ->with('success', 'Data berhasil diupdate');

        } catch (\Exception $e) {

            DB::rollBack();

            return back()->with('error', $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $penjualan = PenjualanPos::findOrFail($id);

        $penjualan->delete();

        return back()->with('success', 'Data berhasil dihapus');
    }
}