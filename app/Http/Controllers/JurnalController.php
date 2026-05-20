<?php

namespace App\Http\Controllers;

use App\Models\Journal;
use App\Models\ChartOfAccount;
use App\Models\JournalItem;
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
            'details.*.account_id' => 'required|exists:chart_of_accounts,id',
            'details.*.debit' => 'required|numeric|min:0',
            'details.*.kredit' => 'required|numeric|min:0',
        ]);

        $isClosed = Journal::where('deskripsi', 'like', '%Jurnal Penutup%')
            ->whereMonth('tanggal', date('m', strtotime($request->tanggal)))
            ->whereYear('tanggal', date('Y', strtotime($request->tanggal)))
            ->exists();

        if ($isClosed) {
            return back()->with('error', 'Transaksi ditolak! Periode ini sudah ditutup (Closing).')->withInput();
        }

        try {
            DB::beginTransaction();

            // Simpan Header
            $jurnal = Journal::create([
                'tanggal' => $request->tanggal,
                'deskripsi' => $request->deskripsi,
                'no_ref' => $request->no_ref ?? 'JR-' . time(),
                'source_type' => 'manual', // Karena diinput manual lewat CRUD
                'source_id' => 0,
                'created_by' => Auth::id(), // Mengambil ID user yang sedang login
            ]);

            // Simpan Detail
            foreach ($request->details as $item) {
                $jurnal->details()->create([
                    'account_id' => $item['account_id'],
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

    public function show($id)
    {
        // Mengambil data jurnal beserta detailnya dan coa terkait (Eager Loading)
        // Gunakan nama relasi 'details' dan 'coa' (atau 'account') sesuai model Anda
        $jurnal = Journal::with(['details.coa'])->findOrFail($id);

        return view('jurnal.show', compact('jurnal'));
    }

    public function closingPage()
    {
        // Pastikan file view ini ada di resources/views/closing/index.blade.php
        return view('closing.index');
    }

    public function closePeriod(Request $request)
    {
        $request->validate([
            'bulan' => 'required|integer|between:1,12',
            'tahun' => 'required|integer',
        ]);

        $bulan = $request->bulan;
        $tahun = $request->tahun;

        try {
            return DB::transaction(function () use ($bulan, $tahun) {

                // 1. Ambil Akun Penting (Gunakan firstOrFail agar jika tidak ada langsung ketahuan)
                $ikhtisar = ChartOfAccount::where('kode', '3-9000')->firstOrFail();
                $labaDitahan = ChartOfAccount::where('kode', '3-9999')->firstOrFail();

                // 2. Buat Header Jurnal Penutup
                $journal = Journal::create([
                    'tanggal' => date("Y-m-t", strtotime("$tahun-$bulan-01")), // Akhir bulan
                    'deskripsi' => "Jurnal Penutup Periode " . date('F', mktime(0, 0, 0, $bulan, 1)) . " $tahun",
                    'no_ref' => 'CLS-' . time(),
                    'source_type' => 'closing',
                    'source_id' => 0,
                    'created_by' => Auth::id(),
                ]);

                $totalPendapatan = 0;
                $totalBeban = 0;

                // 3. Ambil Saldo Pendapatan (Kredit - Debit)
                $pendapatans = ChartOfAccount::where('tipe', 'Pendapatan')->get();
                foreach ($pendapatans as $coa) {
                    // Query diarahkan ke JournalItem, bukan Journal
                    $saldo = JournalItem::where('account_id', $coa->id)
                        ->whereHas('journal', function ($q) use ($bulan, $tahun) {
                            $q->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun);
                        })->sum(DB::raw('kredit - debit'));

                    if ($saldo != 0) {
                        $journal->details()->create([
                            'account_id' => $coa->id,
                            'debit' => $saldo, // Menolkan kredit dengan taruh di debit
                            'kredit' => 0
                        ]);
                        $totalPendapatan += $saldo;
                    }
                }

                // 4. Ambil Saldo Beban (Debit - Kredit)
                $bebans = ChartOfAccount::where('tipe', 'Beban')->get();
                foreach ($bebans as $coa) {
                    $saldo = JournalItem::where('account_id', $coa->id)
                        ->whereHas('journal', function ($q) use ($bulan, $tahun) {
                            $q->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun);
                        })->sum(DB::raw('debit - kredit'));

                    if ($saldo != 0) {
                        $journal->details()->create([
                            'account_id' => $coa->id,
                            'debit' => 0,
                            'kredit' => $saldo // Menolkan debit dengan taruh di kredit
                        ]);
                        $totalBeban += $saldo;
                    }
                }

                // 5. Tutup ke Ikhtisar & Laba Ditahan
                $labaBersih = $totalPendapatan - $totalBeban;

                // A. Pindahkan total Pendapatan ke Kredit Ikhtisar
                $journal->details()->create([
                    'account_id' => $ikhtisar->id,
                    'debit'      => 0,
                    'kredit'     => $totalPendapatan
                ]);

                // B. Pindahkan total Beban ke Debit Ikhtisar
                $journal->details()->create([
                    'account_id' => $ikhtisar->id,
                    'debit'      => $totalBeban,
                    'kredit'     => 0
                ]);

                // C. Pindahkan saldo Ikhtisar (Laba Bersih) ke Laba Ditahan
                // Jika Laba: Debit Ikhtisar, Kredit Laba Ditahan
                // Jika Rugi: Kredit Ikhtisar, Debit Laba Ditahan
                if ($labaBersih != 0) {
                    // Baris Ikhtisar untuk meng-nolkan dirinya sendiri
                    $journal->details()->create([
                        'account_id' => $ikhtisar->id,
                        'debit'      => $labaBersih > 0 ? $labaBersih : 0,
                        'kredit'     => $labaBersih < 0 ? abs($labaBersih) : 0,
                    ]);

                    // Baris Laba Ditahan
                    $journal->details()->create([
                        'account_id' => $labaDitahan->id,
                        'debit'      => $labaBersih < 0 ? abs($labaBersih) : 0,
                        'kredit'     => $labaBersih > 0 ? $labaBersih : 0,
                    ]);
                }
                return redirect()->route('jurnal.index')->with('success', 'Periode berhasil ditutup!');
            });
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    // Menampilkan Daftar Jurnal Penyesuaian
    public function adjustmentIndex()
    {
        $adjustments = Journal::with('details.coa')
            ->where('source_type', 'adjustment')
            ->orderBy('tanggal', 'desc')
            ->get();

        // Mengarah ke resources/views/adjustment/index.blade.php
        return view('adjustment.index', compact('adjustments'));
    }

    /**
     * Menampilkan Form Create Penyesuaian
     */
    public function adjustmentPage()
    {
        $coas = ChartOfAccount::orderBy('kode', 'asc')->get();

        // Mengarah ke resources/views/adjustment/create.blade.php
        return view('adjustment.create', compact('coas'));
    }

    /**
     * Menyimpan data Jurnal Penyesuaian
     */
    public function adjustmentStore(Request $request)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'deskripsi' => 'required|string|max:255',
            'details' => 'required|array|min:2',
            'details.*.account_id' => 'required|exists:chart_of_accounts,id',
            'details.*.debit' => 'required|numeric|min:0',
            'details.*.kredit' => 'required|numeric|min:0',
        ]);

        // Cek Periode Closing
        $isClosed = Journal::where('source_type', 'closing')
            ->whereMonth('tanggal', date('m', strtotime($request->tanggal)))
            ->whereYear('tanggal', date('Y', strtotime($request->tanggal)))
            ->exists();

        if ($isClosed) {
            return back()->with('error', 'Gagal! Periode ini sudah dikunci (Closing).')->withInput();
        }

        try {
            DB::beginTransaction();

            // Simpan Header
            $jurnal = Journal::create([
                'tanggal'     => $request->tanggal,
                'deskripsi'   => "[AJP] " . $request->deskripsi,
                'no_ref'      => 'AJP-' . date('Ymd') . '-' . time(),
                'source_type' => 'adjustment',
                'source_id'   => 0,
                'created_by'  => Auth::id(),
            ]);

            // Simpan Detail
            foreach ($request->details as $item) {
                // Skip jika debit dan kredit sama-sama nol
                if ($item['debit'] == 0 && $item['kredit'] == 0) continue;

                $jurnal->details()->create([
                    'account_id' => $item['account_id'],
                    'debit'      => $item['debit'],
                    'kredit'     => $item['kredit'],
                ]);
            }

            // Refresh model untuk mendapatkan details yang baru disimpan
            $jurnal->load('details');

            // Validasi Balance
            if (round($jurnal->details->sum('debit'), 2) != round($jurnal->details->sum('kredit'), 2)) {
                throw new \Exception("Total Debit (" . $jurnal->details->sum('debit') . ") dan Kredit (" . $jurnal->details->sum('kredit') . ") tidak seimbang!");
            }

            DB::commit();
            return redirect()->route('adjustment.index')->with('success', 'Jurnal Penyesuaian berhasil direkam!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage())->withInput();
        }
    }
}
