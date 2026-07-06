<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class CoaController extends Controller
{
    /**
     * Menampilkan daftar semua akun (COA) beserta saldo awal
     */
    public function index(): View
    {
        // Mengambil COA, data relasi parent, beserta nominal saldo awalnya
        $coas = ChartOfAccount::with('parent')
            ->leftJoin('journal_items as ji', function ($join) {
                $join->on('chart_of_accounts.id', '=', 'ji.account_id')
                    ->where('ji.journal_type', '=', 'opening'); // Memfilter baris tipe saldo awal
            })
            ->select(
                'chart_of_accounts.*',
                DB::raw('COALESCE(ji.debit, 0) as opening_debit'),
                DB::raw('COALESCE(ji.kredit, 0) as opening_kredit'),

                // PERBAIKAN: Mengambil data waktu dari chart_of_accounts yang sudah punya timestamps
                'chart_of_accounts.created_at as tgl_input_saldo_awal'
            )
            ->orderBy('chart_of_accounts.kode', 'asc')
            ->get();

        // CEK KUNCI: Jika sudah ada transaksi harian, sistem otomatis mengunci input saldo awal
        $isLocked = DB::table('journal_items')->where('journal_type', '!=', 'opening')->exists();

        return view('coa.index', compact('coas', 'isLocked'));
    }

    /**
     * Menampilkan form tambah akun baru
     */
    public function create(): View
    {
        // Mengambil akun yang hanya bertindak sebagai induk (parent_id kosong)
        $parentAccounts = ChartOfAccount::whereNull('parent_id')
            ->orderBy('kode', 'asc')
            ->get();

        return view('coa.create', compact('parentAccounts'));
    }

    /**
     * Menyimpan master akun baru beserta saldo awal ke database
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'kode' => 'required|unique:chart_of_accounts,kode',
            'nama' => 'required|string|max:255',
            'tipe' => 'required|string|in:Aset,Liabilitas,Ekuitas,Pendapatan,Beban',
            'parent_id' => 'nullable|exists:chart_of_accounts,id', // Validasi relasi parent
            'saldo_awal' => 'nullable|numeric|min:0',
        ]);

        // LOGIKA PRIVATE: Penentuan posisi Saldo Normal otomatis
        $validated['saldo_normal'] = in_array($validated['tipe'], ['Aset', 'Beban']) ? 'debit' : 'kredit';

        DB::transaction(function () use ($validated) {
            // 1. Simpan data master akun
            $coa = ChartOfAccount::create([
                'kode' => $validated['kode'],
                'nama' => $validated['nama'],
                'tipe' => $validated['tipe'],
                'parent_id' => $validated['parent_id'], // Menyimpan ID Induk
                'saldo_normal' => $validated['saldo_normal'],
            ]);

            // 2. Dokumentasikan saldo awal ke tabel journal_items jika diisi
            $saldoAwal = $validated['saldo_awal'] ?? 0;
            if ($saldoAwal > 0) {
                DB::table('journal_items')->insert([
                    'journal_id' => 1, // ID Journal khusus penampung pembukuan saldo awal
                    'account_id' => $coa->id,
                    'journal_type' => 'opening',
                    'debit' => $validated['saldo_normal'] == 'debit' ? $saldoAwal : 0,
                    'kredit' => $validated['saldo_normal'] == 'kredit' ? $saldoAwal : 0,
                ]);
            }
        });

        return redirect()->route('coa.index')->with('success', 'Akun baru berhasil ditambahkan.');
    }

    /**
     * Menampilkan form edit akun
     */
    public function edit(ChartOfAccount $coa): View
    {
        // Mencari apakah ada data saldo awal untuk akun ini di journal_items
        $journalItem = DB::table('journal_items')
            ->where('account_id', $coa->id)
            ->where('journal_type', 'opening')
            ->first();

        $saldoAwal = 0;
        if ($journalItem) {
            $saldoAwal = $coa->saldo_normal == 'debit' ? $journalItem->debit : $journalItem->kredit;
        }

        $isLocked = DB::table('journal_items')->where('journal_type', '!=', 'opening')->exists();

        // Ambil semua pilihan induk kecuali dirinya sendiri agar tidak looping relasi
        $parentAccounts = ChartOfAccount::whereNull('parent_id')
            ->orderBy('kode', 'asc')
            ->get();

        return view('coa.edit', compact('coa', 'saldoAwal', 'isLocked', 'parentAccounts'));
    }

    /**
     * Memperbarui data akun berdasarkan kondisi sistem
     */
    public function update(Request $request, ChartOfAccount $coa): RedirectResponse
    {
        $isLocked = DB::table('journal_items')->where('journal_type', '!=', 'opening')->exists();

        // KONDISI 1: Jika transaksi harian sudah ada, HANYA diizinkan mengubah nama akun (koreksi typo)
        if ($isLocked) {
            $validated = $request->validate([
                'nama' => 'required|string|max:255',
            ]);

            $coa->update([
                'nama' => $validated['nama']
            ]);

            return redirect()->route('coa.index')->with('success', 'Nama akun berhasil diperbarui. Kolom sensitif akuntansi dikunci otomatis.');
        }

        // KONDISI 2: Jika belum ada transaksi harian, user bebas melakukan penyesuaian data penuh
        $validated = $request->validate([
            'kode' => 'required|unique:chart_of_accounts,kode,' . $coa->id,
            'nama' => 'required|string|max:255',
            'tipe' => 'required|string|in:Aset,Liabilitas,Ekuitas,Pendapatan,Beban',
            'parent_id' => 'nullable|exists:chart_of_accounts,id', //
            'saldo_awal' => 'nullable|numeric|min:0',
        ]);

        $validated['saldo_normal'] = in_array($validated['tipe'], ['Aset', 'Beban']) ? 'debit' : 'kredit';

        DB::transaction(function () use ($validated, $coa) {
            // Update data master COA
            $coa->update([
                'kode' => $validated['kode'],
                'nama' => $validated['nama'],
                'tipe' => $validated['tipe'],
                'parent_id' => $validated['parent_id'], //
                'saldo_normal' => $validated['saldo_normal'],
            ]);

            // Sinkronisasi data nominal saldo awal pada journal_items
            $saldoAwal = $validated['saldo_awal'] ?? 0;

            if ($saldoAwal > 0) {
                DB::table('journal_items')->updateOrInsert(
                    ['account_id' => $coa->id, 'journal_type' => 'opening'],
                    [
                        'journal_id' => 1,
                        'debit' => $validated['saldo_normal'] == 'debit' ? $saldoAwal : 0,
                        'kredit' => $validated['saldo_normal'] == 'kredit' ? $saldoAwal : 0,
                    ]
                );
            } else {
                // Jika diubah ke nilai 0, hapus baris pencatatan saldo awal lamanya
                DB::table('journal_items')->where('account_id', $coa->id)->where('journal_type', 'opening')->delete();
            }
        });

        return redirect()->route('coa.index')->with('success', 'Data akun berhasil diperbarui.');
    }

    /**
     * Menghapus akun (Hanya diizinkan jika belum ada transaksi berjalan)
     */
    public function destroy(ChartOfAccount $coa): RedirectResponse
    {
        $isLocked = DB::table('journal_items')->where('journal_type', '!=', 'opening')->exists();

        if ($isLocked) {
            return redirect()->route('coa.index')->with('error', 'Akun terkunci dan tidak bisa dihapus karena riwayat transaksi harian sudah berjalan.');
        }

        $coa->delete();
        return redirect()->route('coa.index')->with('success', 'Akun berhasil dihapus dari daftar.');
    }
}
