<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\JournalItem;
use App\Models\ChartOfAccount;
use Illuminate\Support\Facades\DB;

class LaporanController extends Controller
{
    public function neracaSaldo(Request $request)
    {
        // Filter Bulan dan Tahun
        $bulan = $request->get('bulan', date('m'));
        $tahun = $request->get('tahun', date('Y'));

        // Tanggal akhir bulan terpilih (untuk mengambil semua transaksi sampai tgl tsb)
        $lastDay = date('Y-m-t', strtotime("$tahun-$bulan-01"));

        // Ambil semua akun COA
        $neracaSaldo = \App\Models\ChartOfAccount::orderBy('kode', 'asc')
            ->get()
            ->map(function ($coa) use ($lastDay) {
                // Hitung total mutasi debet dan kredit sampai periode terpilih
                $mutasi = \App\Models\JournalItem::where('account_id', $coa->id)
                    ->whereHas('journal', function ($q) use ($lastDay) {
                        $q->where('tanggal', '<=', $lastDay);
                    })
                    ->selectRaw('SUM(debit) as total_debit, SUM(kredit) as total_kredit')
                    ->first();

                // Hitung Saldo Akhir (Net)
                $saldoAkhir = $mutasi->total_debit - $mutasi->total_kredit;

                // Masukkan ke objek coa
                $coa->debet_akhir = $saldoAkhir > 0 ? $saldoAkhir : 0;
                $coa->kredit_akhir = $saldoAkhir < 0 ? abs($saldoAkhir) : 0;

                return $coa;
            })
            ->filter(function ($coa) {
                // Hanya tampilkan akun yang punya saldo (tidak nol)
                return $coa->debet_akhir != 0 || $coa->kredit_akhir != 0;
            });

        return view('laporan.neraca-saldo.index', compact('neracaSaldo', 'bulan', 'tahun'));
    }

    public function labaRugiIndex(Request $request)
    {
        $bulan = $request->get('bulan', date('m'));
        $tahun = $request->get('tahun', date('Y'));

        // Ambil detail Pendapatan
        $detailsPendapatan = ChartOfAccount::where('tipe', 'Pendapatan')->get()->map(function ($coa) use ($bulan, $tahun) {
            $coa->saldo = JournalItem::where('account_id', $coa->id)
                ->whereHas('journal', fn($q) => $q->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun))
                ->sum(DB::raw('kredit - debit'));
            return $coa;
        })->where('saldo', '!=', 0);

        // Ambil detail Beban
        $detailsBeban = ChartOfAccount::where('tipe', 'Beban')->get()->map(function ($coa) use ($bulan, $tahun) {
            $coa->saldo = JournalItem::where('account_id', $coa->id)
                ->whereHas('journal', fn($q) => $q->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun))
                ->sum(DB::raw('debit - kredit'));
            return $coa;
        })->where('saldo', '!=', 0);

        $totalPendapatan = $detailsPendapatan->sum('saldo');
        $totalBeban = $detailsBeban->sum('saldo');

        return view('laporan.laba-rugi.index', compact(
            'detailsPendapatan',
            'detailsBeban',
            'totalPendapatan',
            'totalBeban',
            'bulan',
            'tahun'
        ));
    }

    public function neracaIndex(Request $request)
    {
        $bulan = $request->get('bulan', date('m'));
        $tahun = $request->get('tahun', date('Y'));

        // Ambil data hanya jika ada input bulan/tahun atau biarkan default
        $aktiva = ChartOfAccount::where('tipe', 'Aset')->get()->map(function ($coa) use ($bulan, $tahun) {
            $coa->saldo = JournalItem::where('account_id', $coa->id)
                ->whereHas('journal', fn($q) => $q->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun))
                ->sum(DB::raw('debit - kredit'));
            return $coa;
        })->where('saldo', '!=', 0);

        $passiva = ChartOfAccount::whereIn('tipe', ['Kewajiban', 'Modal'])->get()->map(function ($coa) use ($bulan, $tahun) {
            $coa->saldo = JournalItem::where('account_id', $coa->id)
                ->whereHas('journal', fn($q) => $q->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun))
                ->sum(DB::raw('kredit - debit'));
            return $coa;
        })->where('saldo', '!=', 0);

        $totalPendapatan = JournalItem::whereHas('coa', fn($q) => $q->where('tipe', 'Pendapatan'))
            ->whereHas('journal', fn($q) => $q->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun))
            ->sum(DB::raw('kredit - debit'));

        $totalBeban = JournalItem::whereHas('coa', fn($q) => $q->where('tipe', 'Beban'))
            ->whereHas('journal', fn($q) => $q->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun))
            ->sum(DB::raw('debit - kredit'));

        $labaBerjalan = $totalPendapatan - $totalBeban;

        return view('laporan.neraca.index', compact('aktiva', 'passiva', 'labaBerjalan', 'bulan', 'tahun'));
    }

    public function arusKasIndex(Request $request)
    {
        $bulan = $request->get('bulan', date('m'));
        $tahun = $request->get('tahun', date('Y'));

        // 1. OPERASIONAL (Pendapatan & Beban)
        $operasional = JournalItem::whereHas('coa', fn($q) => $q->whereIn('tipe', ['Pendapatan', 'Beban']))
            ->whereHas('journal', fn($q) => $q->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun))
            ->with(['journal', 'coa'])
            ->get();

        // 2. INVESTASI (Aset Tetap)
        $investasi = JournalItem::whereHas('coa', fn($q) => $q->where('tipe', 'Aset Tetap'))
            ->whereHas('journal', fn($q) => $q->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun))
            ->with(['journal', 'coa'])
            ->get();

        // 3. PENDANAAN (Kewajiban & Modal)
        $pendanaan = JournalItem::whereHas('coa', fn($q) => $q->whereIn('tipe', ['Kewajiban', 'Modal']))
            ->whereHas('journal', fn($q) => $q->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun))
            ->with(['journal', 'coa'])
            ->get();

        return view('laporan.arus-kas.index', compact('operasional', 'investasi', 'pendanaan', 'bulan', 'tahun'));
    }

    public function bukuBesar(Request $request)
    {
        // Ambil periode dari request, default ke bulan/tahun sekarang
        $bulan = $request->get('bulan', date('m'));
        $tahun = $request->get('tahun', date('Y'));
        $firstDayOfMonth = "$tahun-$bulan-01";

        // Tarik semua akun beserta item jurnalnya pada periode tersebut
        $accountsData = \App\Models\ChartOfAccount::with(['items' => function ($q) use ($bulan, $tahun) {
            $q->whereHas('journal', function ($join) use ($bulan, $tahun) {
                $join->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun);
            })
                ->join('journals', 'journal_items.journal_id', '=', 'journals.id')
                ->orderBy('journals.tanggal', 'asc')
                ->select('journal_items.*');
        }])
            ->get()
            ->map(function ($coa) use ($firstDayOfMonth) {
                // Hitung Saldo Awal per Akun (Semua sebelum bulan ini)
                $coa->beginning_balance = \App\Models\JournalItem::where('account_id', $coa->id)
                    ->whereHas('journal', function ($q) use ($firstDayOfMonth) {
                        $q->where('tanggal', '<', $firstDayOfMonth);
                    })
                    ->sum(JournalItem::raw('debit - kredit'));

                return $coa;
            })
            ->filter(function ($coa) {
                // Hanya tampilkan yang ada transaksi atau saldo awal agar tidak penuh data kosong
                return $coa->items->count() > 0 || $coa->beginning_balance != 0;
            });

        return view('laporan.buku-besar.index', compact('accountsData', 'bulan', 'tahun'));
    }
}
