<?php

namespace App\Http\Controllers;

use App\Models\Karyawan;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class KaryawanController extends Controller
{
    /**
     * Menampilkan daftar semua karyawan.
     */
    public function index(Request $request)
    {
        $search = $request->query('search');
        $query = Karyawan::query();

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('nama_karyawan', 'like', '%' . $search . '%')
                  ->orWhere('jabatan', 'like', '%' . $search . '%')
                  ->orWhere('departemen', 'like', '%' . $search . '%');
            });
        }

        $karyawans = $query->orderBy('id', 'desc')->paginate(10)->withQueryString();
        return view('karyawan.index', compact('karyawans'));
    }

    /**
     * Menampilkan detil karyawan.
     */
    public function show($id)
    {
        $karyawan = Karyawan::findOrFail($id);
        return view('karyawan.show', compact('karyawan'));
    }

    /**
     * Menampilkan form untuk menambah karyawan baru.
     */
    public function create(): View
    {
        return view('karyawan.create');
    }

    /**
     * Menyimpan data karyawan baru ke database.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_karyawan'      => 'required|string|max:255',
            'jabatan'            => 'required|string',
            'jenis_tenaga_kerja' => 'required|string', // Contoh: Tetap, Kontrak, Freelance
            'departemen'         => 'required|string', // Contoh: Produksi, Akuntansi, Penjualan
            'gaji_pokok'         => 'required|numeric|min:0',
        ]);

        Karyawan::create($validated);

        return redirect()->route('karyawan.index')->with('success', 'Data Karyawan berhasil disimpan!');
    }

    /**
     * Menampilkan form edit untuk satu karyawan tertentu.
     */
    public function edit(Karyawan $karyawan): View
    {
        return view('karyawan.edit', compact('karyawan'));
    }

    /**
     * Memperbarui data karyawan di database.
     */
    public function update(Request $request, Karyawan $karyawan): RedirectResponse
    {
        $validated = $request->validate([
            'nama_karyawan'      => 'required|string|max:255',
            'jabatan'            => 'required|string',
            'jenis_tenaga_kerja' => 'required|string', // Contoh: Tetap, Kontrak, Freelance
            'departemen'         => 'required|string', // Contoh: Produksi, Akuntansi, Penjualan
            'gaji_pokok'         => 'required|numeric|min:0',
        ]);

        $karyawan->update($validated);

        return redirect()->route('karyawan.index')
            ->with('success', 'Data berhasil diperbarui.');
    }

    /**
     * Menghapus data karyawan.
     */
    public function destroy(Karyawan $karyawan): RedirectResponse
    {
        $karyawan->delete();

        return redirect()->route('karyawan.index')
            ->with('success', 'Data berhasil dihapus.');
    }
}
