<?php

namespace App\Http\Controllers;

use App\Models\MasterGudang;
use Illuminate\Http\Request;

class GudangController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $gudangs = MasterGudang::orderBy('id', 'desc')->paginate(10);

        return view('gudangs.index', compact('gudangs'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('gudangs.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'kategori' => 'required|string|max:255',
        ]);

        MasterGudang::create([
            'nama' => $request->nama,
            'kategori' => $request->kategori,
        ]);

        return redirect()->route('gudangs.index')
            ->with('success', 'Data gudang berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $gudang = MasterGudang::findOrFail($id);

        return view('gudangs.show', compact('gudang'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $gudang = MasterGudang::findOrFail($id);

        return view('gudangs.edit', compact('gudang'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $gudang = MasterGudang::findOrFail($id);

        $request->validate([
            'nama' => 'required|string|max:255',
            'kategori' => 'required|string|max:255',
        ]);

        $gudang->update([
            'nama' => $request->nama,
            'kategori' => $request->kategori,
        ]);

        return redirect()->route('gudangs.index')
            ->with('success', 'Data gudang berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $gudang = MasterGudang::findOrFail($id);

        $gudang->delete();

        return redirect()->route('gudangs.index')
            ->with('success', 'Data gudang berhasil dihapus.');
    }
}