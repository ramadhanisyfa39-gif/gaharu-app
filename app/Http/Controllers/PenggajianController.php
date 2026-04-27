<?php

namespace App\Http\Controllers;

use App\Models\Penggajian;
use App\Models\Karyawan;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class PenggajianController extends Controller
{
    public function index(): View
    {
        $penggajians = Penggajian::with('karyawan')->latest()->get();
        return view('penggajian.index', compact('penggajians'));
    }

    public function create(): View
    {
        $karyawans = Karyawan::all();
        return view('penggajian.create', compact('karyawans'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'karyawan_id' => 'required|exists:karyawans,id',
            'periode' => 'required|date',
            'gaji_pokok' => 'required|numeric',
            'tunjangan' => 'nullable|numeric',
            'potongan' => 'nullable|numeric',
            'total_gaji' => 'required|numeric',
        ]);

        Penggajian::create($validated);

        return redirect()->route('penggajian.index')->with('success', 'Slip gaji berhasil dibuat.');
    }

    public function show(Penggajian $penggajian): View
    {
        return view('penggajian.show', compact('penggajian'));
    }

    public function destroy(Penggajian $penggajian): RedirectResponse
    {
        $penggajian->delete();
        return redirect()->route('penggajian.index')->with('success', 'Data gaji dihapus.');
    }
}
