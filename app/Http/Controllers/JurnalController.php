<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use App\Models\Journal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class JurnalController extends Controller
{
    public function index(): View
    {
        $jurnals = Journal::with('details.coa')->latest()->get();
        return view('jurnal.index', compact('jurnals'));
    }

    public function create(): View
    {
        $coas = ChartOfAccount::all();
        return view('jurnal.create', compact('coas'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'tanggal' => 'required|date',
            'keterangan' => 'required|string',
            'details' => 'required|array|min:2',
            'details.*.coa_id' => 'required|exists:coas,id',
            'details.*.debit' => 'required|numeric|min:0',
            'details.*.kredit' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $jurnal = Journal::create([
                'tanggal' => $request->tanggal,
                'keterangan' => $request->keterangan,
                'nomor_bukti' => 'JR-' . time(), // Contoh generate otomatis
            ]);

            foreach ($request->details as $detail) {
                $jurnal->details()->create([
                    'coa_id' => $detail['coa_id'],
                    'debit' => $detail['debit'],
                    'kredit' => $detail['kredit'],
                ]);
            }

            // Cek Keseimbangan (Balance)
            $totalDebit = $jurnal->details->sum('debit');
            $totalKredit = $jurnal->details->sum('kredit');

            if ($totalDebit != $totalKredit) {
                throw new \Exception('Jurnal tidak seimbang (Total Debit ≠ Total Kredit).');
            }

            DB::commit();
            return redirect()->route('jurnal.index')->with('success', 'Jurnal berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function show(Journal $jurnal): View
    {
        $jurnal->load('details.coa');
        return view('jurnal.show', compact('jurnal'));
    }
}
