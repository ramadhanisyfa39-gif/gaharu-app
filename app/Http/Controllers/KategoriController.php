<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller; // ⬅️ INI WAJIB

class KategoriController extends Controller
{
    public function index()
    {
        $data = Kategori::all();
        return view('kategori.index', compact('data'));
    }

    public function create()
    {
        return view('kategori.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required'
        ]);

        Kategori::create($request->all());

        return redirect()->route('kategori.index')
                         ->with('success', 'Data berhasil ditambah');
    }

    public function edit($id)
    {
        $data = Kategori::findOrFail($id);
        return view('kategori.edit', compact('data'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nama' => 'required'
        ]);

        $data = Kategori::findOrFail($id);
        $data->update($request->all());

        return redirect()->route('kategori.index')
                         ->with('success', 'Data berhasil diupdate');
    }

    public function destroy($id)
    {
        $data = Kategori::findOrFail($id);
        $data->delete();

        return redirect()->route('kategori.index')
                         ->with('success', 'Data berhasil dihapus');
    }
}
