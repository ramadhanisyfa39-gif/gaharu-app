<?php

namespace App\Http\Controllers;

use App\Models\Journal;
use App\Models\ChartOfAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class JurnalController extends Controller
{
    public function index()
    {
        $jurnals = Journal::with('details.coa')->orderBy('tanggal', 'desc')->get();
        return view('jurnal.index', compact('jurnals'));
    }

    public function create()
    {
        $coas = ChartOfAccount::all(); // Mengambil daftar akun untuk dropdown
        return view('jurnal.create', compact('coas'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'deskripsi' => 'required|string',
            'details' => 'required|array|min:2',
            'details.*.coa_id' => 'required|exists:chart_of_accounts,id',
            'details.*.debit' => 'required|numeric|min:0',
            'details.*.kredit' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            // Simpan Header
            $jurnal = Journal::create([
                'tanggal' => $request->tanggal,
                'deskripsi' => $request->deskripsi,
                'no_ref' => $request->no_ref ?? 'JR-' . time(),
                'source_type' => 'manual', // Karena diinput manual lewat CRUD
                'source_id' => null,
                'created_by' => Auth::id(), // Mengambil ID user yang sedang login
            ]);

            // Simpan Detail
            foreach ($request->details as $item) {
                $jurnal->details()->create([
                    'coa_id' => $item['coa_id'],
                    'debit'  => $item['debit'],
                    'kredit' => $item['kredit'],
                ]);
            }

            // Validasi Balance
            if ($jurnal->details->sum('debit') != $jurnal->details->sum('kredit')) {
                throw new \Exception("Total Debit dan Kredit tidak seimbang!");
            }

            DB::commit();
            return redirect()->route('jurnal.index')->with('success', 'Jurnal berhasil disimpan!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }
}
