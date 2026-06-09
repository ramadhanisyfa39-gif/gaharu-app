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
        // Filter Bulan dan Tahun (Default ke bulan & tahun saat ini jika kosong)
        $bulan = $request->get('bulan', date('m'));
        $tahun = $request->get('tahun', date('Y'));

        // Konversi string rentang tanggal bulan berjalan
        $startOfMonth = "$tahun-$bulan-01";
        $endOfMonth = date('Y-m-t', strtotime($startOfMonth));

        // Ambil semua akun COA dari database CV Gaharu Agung Sejahtera
        $neracaSaldo = \App\Models\ChartOfAccount::orderBy('kode', 'asc')
            ->get()
            ->map(function ($coa) use ($startOfMonth, $endOfMonth) {

                // 1. HITUNG SALDO AWAL (Semua transaksi SEBELUM tanggal 1 di bulan terpilih)
                // Catatan: Jika relasi di model JournalItem Anda bukan 'journal', ganti string 'journal' di bawah ini
                $saldoAwalRaw = \App\Models\JournalItem::where('account_id', $coa->id)
                    ->whereHas('journal', function ($q) use ($startOfMonth) {
                        $q->where('tanggal', '<', $startOfMonth);
                    })
                    ->selectRaw('SUM(debit) as total_debit, SUM(kredit) as total_kredit')
                    ->first();

                $netSaldoAwal = ($saldoAwalRaw->total_debit ?? 0) - ($saldoAwalRaw->total_kredit ?? 0);

                $coa->saldo_awal_debit  = $netSaldoAwal > 0 ? $netSaldoAwal : 0;
                $coa->saldo_awal_kredit = $netSaldoAwal < 0 ? abs($netSaldoAwal) : 0;


                // 2. HITUNG MUTASI PERIODE (Hanya transaksi DI DALAM bulan berjalan)
                $mutasiRaw = \App\Models\JournalItem::where('account_id', $coa->id)
                    ->whereHas('journal', function ($q) use ($startOfMonth, $endOfMonth) {
                        $q->whereBetween('tanggal', [$startOfMonth, $endOfMonth]);
                    })
                    ->selectRaw('SUM(debit) as total_debit, SUM(kredit) as total_kredit')
                    ->first();

                $coa->mutasi_debit  = $mutasiRaw->total_debit ?? 0;
                $coa->mutasi_kredit = $mutasiRaw->total_kredit ?? 0;


                // 3. HITUNG SALDO AKHIR (Saldo Awal + Mutasi)
                $totalDebitKeseluruhan  = ($saldoAwalRaw->total_debit ?? 0) + ($mutasiRaw->total_debit ?? 0);
                $totalKreditKeseluruhan = ($saldoAwalRaw->total_kredit ?? 0) + ($mutasiRaw->total_kredit ?? 0);
                $netSaldoAkhir = $totalDebitKeseluruhan - $totalKreditKeseluruhan;

                $coa->debet_akhir  = $netSaldoAkhir > 0 ? $netSaldoAkhir : 0;
                $coa->kredit_akhir = $netSaldoAkhir < 0 ? abs($netSaldoAkhir) : 0;

                return $coa;
            });

        // SEMENTARA KITA MATIKAN FILTER AGAR SEMUA AKUN COA MUNCUL DI VIEW
        // ->filter(function ($coa) { ... });

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
