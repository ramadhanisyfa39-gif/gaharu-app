<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class CoaController extends Controller
{
    public function index(): View
    {
        $coas = ChartOfAccount::orderBy('kode', 'asc')->get();
        return view('coa.index', compact('coas'));
    }

    public function create(): View
    {
        return view('coa.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'kode' => 'required|unique:chart_of_accounts,kode',
            'nama' => 'required|string|max:255',
            'tipe' => 'required|string',
            'saldo_normal' => 'required|in:debit,kredit',
        ]);

        ChartOfAccount::create($validated);

        return redirect()->route('coa.index')->with('success', 'Akun berhasil dibuat.');
    }

    public function edit(ChartOfAccount $coa): View
    {
        return view('coa.edit', compact('coa'));
    }

    public function update(Request $request, ChartOfAccount $coa): RedirectResponse
    {
        $validated = $request->validate([
            'kode' => 'required|unique:chart_of_accounts,kode,' . $coa->id,
            'nama' => 'required|string|max:255',
            'tipe' => 'required|string',
            'saldo_normal' => 'required|in:debit,kredit',
        ]);

        $coa->update($validated);

        return redirect()->route('coa.index')->with('success', 'Akun berhasil diperbarui.');
    }

    public function destroy(ChartOfAccount $coa): RedirectResponse
    {
        $coa->delete();
        return redirect()->route('coa.index')->with('success', 'Akun berhasil dihapus.');
    }
}
