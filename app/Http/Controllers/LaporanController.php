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

        // =========================================================================
        // PENTING: KASUR MAPPING NAMA TABEL MIGRATION KAMU DI SINI
        // Cocokkan 'journal_type' (kiri) dengan nama tabel di database (kanan)
        // =========================================================================
        $tableMapping = [
            'jurnal_penjualan_pos' => 'jurnal_penjualan_pos', 
            'penjualan_b2b' => 'jurnal_penjualan_b2b', 
            'jurnal_pembelian'     => 'jurnal_pembelian',     
        ];

        // 1. AMBIL DATA SALDO AWAL BULK (Murni dari journal_type = 'opening')
        $openingBalances = \App\Models\JournalItem::where('journal_type', 'opening')
            ->select('account_id')
            ->selectRaw('SUM(debit) as total_debit, SUM(kredit) as total_kredit')
            ->groupBy('account_id')
            ->get()
            ->keyBy('account_id');

        // 2. AMBIL MUTASI LALU BULK (Transaksi sebelum bulan berjalan, mengecualikan 'opening')
        $mutasiLaluBalances = \App\Models\JournalItem::where('journal_type', '!=', 'opening')
            ->where(function ($q) use ($startOfMonth, $tableMapping) {
                // A. Jurnal Umum (Manual)
                $q->where(function ($queryManual) use ($startOfMonth) {
                    $queryManual->whereIn('journal_type', ['jurnal_umum', 'jurnal'])
                        ->whereHas('journal', function ($j) use ($startOfMonth) {
                            $j->where('tanggal', '<', $startOfMonth)
                                ->where('status', 'approved');
                        });
                });

                // B. Jurnal Penyesuaian (Manual)
                $q->orWhere(function ($queryAjp) use ($startOfMonth) {
                    $queryAjp->whereIn('journal_type', [\App\Models\JurnalPenyesuaian::class, 'jurnal_penyesuaian'])
                        ->whereHas('jurnalPenyesuaianHeader', function ($j) use ($startOfMonth) {
                            $j->where('tanggal', '<', $startOfMonth)
                                ->where('status', 'approved');
                        });
                });

                // C. Jurnal Otomatis (Looping berdasarkan mapping tabel database)
                foreach ($tableMapping as $type => $tableName) {
                    $q->orWhere(function ($queryOtomatis) use ($type, $tableName, $startOfMonth) {
                        $queryOtomatis->where('journal_type', $type)
                            ->whereExists(function ($sub) use ($tableName, $startOfMonth) {
                                $sub->select(\DB::raw(1))
                                    ->from($tableName)
                                    ->whereColumn("$tableName.id", 'journal_items.journal_id')
                                    ->where('tanggal', '<', $startOfMonth);
                            });
                    });
                }
            })
            ->select('account_id')
            ->selectRaw('SUM(debit) as total_debit, SUM(kredit) as total_kredit')
            ->groupBy('account_id')
            ->get()
            ->keyBy('account_id');

        // 3. AMBIL MUTASI PERIODE BULK (Bulan Berjalan)
        $mutasiBalances = \App\Models\JournalItem::where('journal_type', '!=', 'opening')
            ->where(function ($q) use ($startOfMonth, $endOfMonth, $tableMapping) {
                // A. Jurnal Umum (Manual)
                $q->where(function ($queryManual) use ($startOfMonth, $endOfMonth) {
                    $queryManual->whereIn('journal_type', ['jurnal_umum', 'jurnal'])
                        ->whereHas('journal', function ($j) use ($startOfMonth, $endOfMonth) {
                            $j->whereBetween('tanggal', [$startOfMonth, $endOfMonth])
                                ->where('status', 'approved');
                        });
                });

                // B. Jurnal Penyesuaian (Manual)
                $q->orWhere(function ($queryAjp) use ($startOfMonth, $endOfMonth) {
                    $queryAjp->whereIn('journal_type', [\App\Models\JurnalPenyesuaian::class, 'jurnal_penyesuaian'])
                        ->whereHas('jurnalPenyesuaianHeader', function ($j) use ($startOfMonth, $endOfMonth) {
                            $j->whereBetween('tanggal', [$startOfMonth, $endOfMonth])
                                ->where('status', 'approved');
                        });
                });

                // C. Jurnal Otomatis
                foreach ($tableMapping as $type => $tableName) {
                    $q->orWhere(function ($queryOtomatis) use ($type, $tableName, $startOfMonth, $endOfMonth) {
                        $queryOtomatis->where('journal_type', $type)
                            ->whereExists(function ($sub) use ($tableName, $startOfMonth, $endOfMonth) {
                                $sub->select(\DB::raw(1))
                                    ->from($tableName)
                                    ->whereColumn("$tableName.id", 'journal_items.journal_id')
                                    ->whereBetween('tanggal', [$startOfMonth, $endOfMonth]);
                            });
                    });
                }
            })
            ->select('account_id')
            ->selectRaw('SUM(debit) as total_debit, SUM(kredit) as total_kredit')
            ->groupBy('account_id')
            ->get()
            ->keyBy('account_id');

        // Ambil semua akun COA dari database CV Gaharu Agung Sejahtera
        $neracaSaldo = \App\Models\ChartOfAccount::orderBy('kode', 'asc')
            ->get()
            ->map(function ($coa) use ($openingBalances, $mutasiLaluBalances, $mutasiBalances) {

                $openingRaw = $openingBalances->get($coa->id);
                $openingDebit  = $openingRaw->total_debit ?? 0;
                $openingKredit = $openingRaw->total_kredit ?? 0;

                $mutasiLaluRaw = $mutasiLaluBalances->get($coa->id);
                $mutasiLaluDebit  = $mutasiLaluRaw->total_debit ?? 0;
                $mutasiLaluKredit = $mutasiLaluRaw->total_kredit ?? 0;

                // HITUNG TOTAL SALDO AWAL RIIL
                $totalSaldoAwalDebit  = $openingDebit + $mutasiLaluDebit;
                $totalSaldoAwalKredit = $openingKredit + $mutasiLaluKredit;

                $saldoNormal = strtolower($coa->saldo_normal);

                if ($saldoNormal == 'debit') {
                    $netSaldoAwal = $totalSaldoAwalDebit - $totalSaldoAwalKredit;
                    $coa->saldo_awal_debit  = $netSaldoAwal > 0 ? $netSaldoAwal : 0;
                    $coa->saldo_awal_kredit = $netSaldoAwal < 0 ? abs($netSaldoAwal) : 0;
                } else {
                    $netSaldoAwal = $totalSaldoAwalKredit - $totalSaldoAwalDebit;
                    $coa->saldo_awal_debit  = $netSaldoAwal < 0 ? abs($netSaldoAwal) : 0;
                    $coa->saldo_awal_kredit = $netSaldoAwal > 0 ? $netSaldoAwal : 0;
                }

                // MUTASI PERIODE (Bulan Berjalan)
                $mutasiRaw = $mutasiBalances->get($coa->id);
                $coa->mutasi_debit  = $mutasiRaw->total_debit ?? 0;
                $coa->mutasi_kredit = $mutasiRaw->total_kredit ?? 0;

                // HITUNG SALDO AKHIR
                $totalDebitKeseluruhan  = $totalSaldoAwalDebit + $coa->mutasi_debit;
                $totalKreditKeseluruhan = $totalSaldoAwalKredit + $coa->mutasi_kredit;

                if ($saldoNormal == 'debit') {
                    $netSaldoAkhir = $totalDebitKeseluruhan - $totalKreditKeseluruhan;
                    $coa->debet_akhir  = $netSaldoAkhir >= 0 ? $netSaldoAkhir : 0;
                    $coa->kredit_akhir = $netSaldoAkhir < 0 ? abs($netSaldoAkhir) : 0;
                } else {
                    $netSaldoAkhir = $totalKreditKeseluruhan - $totalDebitKeseluruhan;
                    $coa->debet_akhir  = $netSaldoAkhir < 0 ? abs($netSaldoAkhir) : 0;
                    $coa->kredit_akhir = $netSaldoAkhir >= 0 ? $netSaldoAkhir : 0;
                }

                return $coa;
            });

        if ($request->format === 'pdf') {
            $pdf = app('dompdf.wrapper')->setPaper('a4', 'landscape');
            $pdf->loadView('laporan.neraca-saldo.pdf', compact('neracaSaldo', 'bulan', 'tahun'));
            return $pdf->download('laporan-neraca-saldo-' . now()->format('Ymd') . '.pdf');
        }

        if ($request->format === 'excel') {
            return $this->exportExcelNeracaSaldo($neracaSaldo, $bulan, $tahun);
        }

        return view('laporan.neraca-saldo.index', compact('neracaSaldo', 'bulan', 'tahun'));
    }


    public function labaRugiIndex(Request $request)
    {
        $bulan = $request->get('bulan', date('m'));
        $tahun = $request->get('tahun', date('Y'));

        // 1. Ambil detail Pendapatan dengan format withSum yang benar
        $detailsPendapatan = ChartOfAccount::where('tipe', 'Pendapatan')
            ->withSum(['items as saldo' => function ($query) use ($bulan, $tahun) {
                $query->select(DB::raw('SUM(kredit - debit)'))
                    ->where(function ($q) use ($bulan, $tahun) {

                        // Filter untuk Jurnal Umum (Model Journal)
                        $q->whereHas('journal', function ($journalQuery) use ($bulan, $tahun) {
                            $journalQuery->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun)
                                         ->where('status', 'approved');
                        })

                            // Filter untuk Jurnal Pembelian
                            ->orWhereHas('jurnalPembelianHeader', function ($journalQuery) use ($bulan, $tahun) {
                                $journalQuery->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun);
                            })

                            // Filter untuk Jurnal Penjualan B2B
                            ->orWhereHas('jurnalPenjualanB2bHeader', function ($journalQuery) use ($bulan, $tahun) {
                                $journalQuery->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun);
                            })

                            // Filter untuk Jurnal Penjualan POS
                            ->orWhereHas('jurnalPenjualanPosHeader', function ($journalQuery) use ($bulan, $tahun) {
                                $journalQuery->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun);
                            })

                            // Filter untuk Jurnal Penyesuaian
                            ->orWhereHas('jurnalPenyesuaianHeader', function ($journalQuery) use ($bulan, $tahun) {
                                $journalQuery->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun)
                                             ->where('status', 'approved');
                            });
                    });
            }], 'id') // Parameter kedua wajib diisi string nama kolom (kita gunakan 'id' sebagai placeholder karena SUM() sudah dicustom di dalam)
            ->get()
            ->filter(fn($coa) => $coa->saldo != 0);

        // 2. Ambil detail Beban dengan format withSum yang benar
        $detailsBeban = ChartOfAccount::where('tipe', 'Beban')
            ->withSum(['items as saldo' => function ($query) use ($bulan, $tahun) {
                $query->select(DB::raw('SUM(debit - kredit)'))
                    ->where(function ($q) use ($bulan, $tahun) {
                        $q->whereHas('journal', function ($journalQuery) use ($bulan, $tahun) {
                            $journalQuery->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun)
                                         ->where('status', 'approved');
                        })
                            ->orWhereHas('jurnalPembelianHeader', function ($journalQuery) use ($bulan, $tahun) {
                                $journalQuery->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun);
                            })
                            ->orWhereHas('jurnalPenjualanB2bHeader', function ($journalQuery) use ($bulan, $tahun) {
                                $journalQuery->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun);
                            })
                            ->orWhereHas('jurnalPenjualanPosHeader', function ($journalQuery) use ($bulan, $tahun) {
                                $journalQuery->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun);
                            })
                            ->orWhereHas('jurnalPenyesuaianHeader', function ($journalQuery) use ($bulan, $tahun) {
                                $journalQuery->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun)
                                             ->where('status', 'approved');
                            });
                    });
            }], 'id') // Parameter kedua wajib diisi string nama kolom
            ->get()
            ->filter(fn($coa) => $coa->saldo != 0);

        $totalPendapatan = $detailsPendapatan->sum('saldo');
        $totalBeban = $detailsBeban->sum('saldo');

        if ($request->format === 'pdf') {
            $pdf = app('dompdf.wrapper')->setPaper('a4', 'landscape');
            $pdf->loadView('laporan.laba-rugi.pdf', compact(
                'detailsPendapatan',
                'detailsBeban',
                'totalPendapatan',
                'totalBeban',
                'bulan',
                'tahun'
            ));
            return $pdf->download('laporan-laba-rugi-' . now()->format('Ymd') . '.pdf');
        }

        if ($request->format === 'excel') {
            return $this->exportExcelLabaRugi($detailsPendapatan, $detailsBeban, $totalPendapatan, $totalBeban, $bulan, $tahun);
        }

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

        // Fungsi pembantu (helper closure) untuk memfilter item berdasarkan tanggal
        $filterTanggalJurnal = function ($query) use ($bulan, $tahun) {
            $query->where(function ($q) use ($bulan, $tahun) {
                $q->whereHas('journal', fn($h) => $h->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun));
            })
                ->orWhere(function ($q) use ($bulan, $tahun) {
                    $q->whereHas('jurnalPembelianHeader', fn($h) => $h->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun));
                })
                ->orWhere(function ($q) use ($bulan, $tahun) {
                    $q->whereHas('jurnalPenjualanB2bHeader', fn($h) => $h->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun));
                })
                ->orWhere(function ($q) use ($bulan, $tahun) {
                    $q->whereHas('jurnalPenjualanPosHeader', fn($h) => $h->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun));
                })
                ->orWhere(function ($q) use ($bulan, $tahun) {
                    $q->whereHas('jurnalPenyesuaianHeader', fn($h) => $h->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun));
                });
        };

        // 1. Tarik semua mutasi COA pada periode terpilih secara bulk
        $itemsInPeriod = JournalItem::where($filterTanggalJurnal)
            ->select('account_id')
            ->selectRaw('SUM(debit) as total_debit, SUM(kredit) as total_kredit')
            ->groupBy('account_id')
            ->get()
            ->keyBy('account_id');

        // --- 2. AMBIL DATA AKTIVA (ASET) ---
        $aktiva = ChartOfAccount::where('tipe', 'Aset')->get()->map(function ($coa) use ($itemsInPeriod) {
            $raw = $itemsInPeriod->get($coa->id);
            $debit = $raw->total_debit ?? 0;
            $kredit = $raw->total_kredit ?? 0;
            $coa->saldo = $debit - $kredit;
            return $coa;
        })->where('saldo', '!=', 0);

        // --- 3. AMBIL DATA PASIVA (HANYA KEWAJIBAN & DATA MODAL AWAL SECARA SPESIFIK) ---
        $passiva = ChartOfAccount::whereIn('tipe', ['Liabilitas', 'Ekuitas'])
            ->where('nama', 'not like', '%Prive%') 
            ->get()
            ->map(function ($coa) use ($itemsInPeriod) {
                $raw = $itemsInPeriod->get($coa->id);
                $debit = $raw->total_debit ?? 0;
                $kredit = $raw->total_kredit ?? 0;
                $coa->saldo = $kredit - $debit;
                return $coa;
            })->where('saldo', '!=', 0);

        // --- LOGIKA TAMBAHAN: HITUNG PRIVE SECARA TERPISAH ---
        $akunPrive = ChartOfAccount::where('nama', 'like', '%Prive%')->first();
        $totalPrive = 0;

        if ($akunPrive) {
            $rawPrive = $itemsInPeriod->get($akunPrive->id);
            $debitPrive = $rawPrive->total_debit ?? 0;
            $kreditPrive = $rawPrive->total_kredit ?? 0;
            $totalPrive = $debitPrive - $kreditPrive;
        }

        // --- 4. HITUNG LABA BERJALAN (PENDAPATAN - BEBAN) ---
        $totalPendapatan = JournalItem::whereHas('coa', fn($q) => $q->where('tipe', 'Pendapatan'))
            ->where($filterTanggalJurnal)
            ->sum(DB::raw('kredit - debit'));

        $totalBeban = JournalItem::whereHas('coa', fn($q) => $q->where('tipe', 'Beban'))
            ->where($filterTanggalJurnal)
            ->sum(DB::raw('debit - kredit'));

        $labaBerjalan = $totalPendapatan - $totalBeban;

        // Hitung total nilai modal secara manual menggunakan tipe 'Ekuitas'
        $totalModalAwal = $passiva->where('tipe', 'Ekuitas')->sum('saldo');
        $modalAkhir = $totalModalAwal + $labaBerjalan - $totalPrive;

        if ($request->format === 'pdf') {
            $pdf = app('dompdf.wrapper')->setPaper('a4', 'landscape');
            $pdf->loadView('laporan.neraca.pdf', compact('aktiva', 'passiva', 'labaBerjalan', 'totalPrive', 'modalAkhir', 'bulan', 'tahun'));
            return $pdf->download('laporan-neraca-' . now()->format('Ymd') . '.pdf');
        }

        if ($request->format === 'excel') {
            return $this->exportExcelNeraca($aktiva, $passiva, $labaBerjalan, $totalPrive, $modalAkhir, $bulan, $tahun);
        }

        return view('laporan.neraca.index', compact('aktiva', 'passiva', 'labaBerjalan', 'totalPrive', 'modalAkhir', 'bulan', 'tahun'));
    }

    public function arusKasIndex(Request $request)
    {
        $bulan = $request->get('bulan', date('m'));
        $tahun = $request->get('tahun', date('Y'));

        // Base query langsung dari JournalItem
        $baseQuery = JournalItem::with(['coa'])
            ->where(function ($query) use ($bulan, $tahun) {
                // 1. Jika tipenya jurnal umum, cek tanggal di tabel 'journals'
                $query->where(function ($q) use ($bulan, $tahun) {
                    $q->whereIn('journal_type', ['jurnal_umum', 'jurnal']) // sesuaikan string ini dengan isi DB Anda
                        ->whereHas('journal', fn($j) => $j->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun)->where('status', 'approved'));
                })
                    // 2. Jika tipenya jurnal pembelian, cek tanggal di tabel 'jurnal_pembelian'
                    ->orWhere(function ($q) use ($bulan, $tahun) {
                        $q->where('journal_type', 'jurnal_pembelian')
                            ->whereHas('jurnalPembelianHeader', fn($j) => $j->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun));
                    })
                    // 3. Jika tipenya jurnal penjualan b2b, cek tanggal di tabel 'jurnal_penjualan_b2b'
                    ->orWhere(function ($q) use ($bulan, $tahun) {
                        $q->where('journal_type', 'penjualan_b2b') // sesuai isi Model JurnalPenjualanB2b Anda
                            ->whereHas('jurnalPenjualanB2bHeader', fn($j) => $j->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun));
                    })
                    // 4. Jika tipenya jurnal penjualan pos, cek tanggal di tabel 'jurnal_penjualan_pos'
                    ->orWhere(function ($q) use ($bulan, $tahun) {
                        $q->where('journal_type', 'jurnal_penjualan_pos')
                            ->whereHas('jurnalPenjualanPosHeader', fn($j) => $j->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun));
                    })
                    // 5. Jika tipenya jurnal penyesuaian, cek tanggal di tabel 'jurnal_penyesuaian'
                    ->orWhere(function ($q) use ($bulan, $tahun) {
                        $q->whereIn('journal_type', [\App\Models\JurnalPenyesuaian::class, 'jurnal_penyesuaian']) // sesuaikan string ini jika perlu
                            ->whereHas('jurnalPenyesuaianHeader', fn($j) => $j->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun)->where('status', 'approved'));
                    });
            });

        // 1. OPERASIONAL (Pendapatan & Beban)
        $operasional = (clone $baseQuery)
            ->whereHas('coa', fn($q) => $q->whereIn('tipe', ['Pendapatan', 'Beban']))
            ->get();

        // 2. INVESTASI (Aset Tetap)
        $investasi = (clone $baseQuery)
            ->whereHas('coa', fn($q) => $q->where('tipe', 'Aset Tetap'))
            ->get();

        // 3. PENDANAAN (Kewajiban & Modal)
        $pendanaan = (clone $baseQuery)
            ->whereHas('coa', fn($q) => $q->whereIn('tipe', ['Kewajiban', 'Modal']))
            ->get();

        if ($request->format === 'pdf') {
            $pdf = app('dompdf.wrapper')->setPaper('a4', 'landscape');
            $pdf->loadView('laporan.arus-kas.pdf', compact('operasional', 'investasi', 'pendanaan', 'bulan', 'tahun'));
            return $pdf->download('laporan-arus-kas-' . now()->format('Ymd') . '.pdf');
        }

        if ($request->format === 'excel') {
            return $this->exportExcelArusKas($operasional, $investasi, $pendanaan, $bulan, $tahun);
        }

        return view('laporan.arus-kas.index', compact('operasional', 'investasi', 'pendanaan', 'bulan', 'tahun'));
    }

    public function bukuBesar(Request $request)
    {
        // Ambil periode dari request, default ke bulan/tahun sekarang
        $bulan = $request->get('bulan', date('m'));
        $tahun = $request->get('tahun', date('Y'));
        $firstDayOfMonth = "$tahun-$bulan-01";

        // 1. HITUNG SALDO AWAL (Semua transaksi sebelum bulan ini)
        // Kita langsung tembak tabel journal_items dan filter secara mandiri agar tidak konflik join
        $beginningBalances = DB::table('journal_items')
            ->select('account_id')
            ->selectRaw('SUM(debit) as total_debit')
            ->selectRaw('SUM(kredit) as total_kredit')
            ->where('journal_type', 'opening')
            ->groupBy('account_id')
            ->get()
            ->keyBy('account_id');

        // 2. TARIK MUTASI BERJALAN (Transaksi khusus bulan/tahun ini)
        $mutasiItems = DB::table('journal_items')
            ->leftJoin('journals', 'journal_items.journal_id', '=', 'journals.id')
            ->leftJoin('jurnal_pembelian', 'journal_items.journal_id', '=', 'jurnal_pembelian.id')
            ->leftJoin('jurnal_penjualan_pos', 'journal_items.journal_id', '=', 'jurnal_penjualan_pos.id')
            ->leftJoin('jurnal_penjualan_b2b', 'journal_items.journal_id', '=', 'jurnal_penjualan_b2b.id')
            ->leftJoin('jurnal_penyesuaian', 'journal_items.journal_id', '=', 'jurnal_penyesuaian.id')
            ->select('journal_items.*')
            ->selectRaw("COALESCE(journals.tanggal, jurnal_pembelian.tanggal, jurnal_penjualan_pos.tanggal, jurnal_penjualan_b2b.tanggal, jurnal_penyesuaian.tanggal) as tanggal")
            ->selectRaw("COALESCE(journals.deskripsi, jurnal_pembelian.deskripsi, jurnal_penjualan_pos.deskripsi, jurnal_penjualan_b2b.deskripsi, jurnal_penyesuaian.deskripsi) as deskripsi")
            ->selectRaw("COALESCE(journals.no_ref, jurnal_pembelian.no_ref, jurnal_penjualan_pos.no_ref, jurnal_penjualan_b2b.no_ref, jurnal_penyesuaian.no_ref) as no_ref")
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->whereIn('journal_items.journal_type', ['jurnal_umum', 'jurnal'])
                      ->where('journals.status', 'approved');
                })
                    ->orWhere(function ($q) {
                        $q->whereIn('journal_items.journal_type', [\App\Models\JurnalPenyesuaian::class, 'jurnal_penyesuaian'])
                          ->where('jurnal_penyesuaian.status', 'approved');
                    })
                    ->orWhere('journal_items.journal_type', 'LIKE', '%Pembelian%')
                    ->orWhere('journal_items.journal_type', 'LIKE', '%Pos%')
                    ->orWhere('journal_items.journal_type', 'LIKE', '%B2b%');
            })
            ->whereRaw('MONTH(COALESCE(journals.tanggal, jurnal_pembelian.tanggal, jurnal_penjualan_pos.tanggal, jurnal_penjualan_b2b.tanggal, jurnal_penyesuaian.tanggal)) = ?', [(int)$bulan])
            ->whereRaw('YEAR(COALESCE(journals.tanggal, jurnal_pembelian.tanggal, jurnal_penjualan_pos.tanggal, jurnal_penjualan_b2b.tanggal, jurnal_penyesuaian.tanggal)) = ?', [(int)$tahun])
            ->orderBy('tanggal', 'asc')
            ->get()
            ->groupBy('account_id');

        // 3. MAP DATA KE MODEL COA
        $accountsData = ChartOfAccount::all()
            ->map(function ($coa) use ($beginningBalances, $mutasiItems) {
                // Ambil saldo awal
                $balanceData = $beginningBalances->get($coa->id);
                $initialDebit = $balanceData ? $balanceData->total_debit : 0;
                $initialKredit = $balanceData ? $balanceData->total_kredit : 0;

                // Hitung saldo awal berdasarkan tipe saldo normal COA
                $saldoNormal = strtolower($coa->saldo_normal);
                if ($saldoNormal === 'kredit') {
                    $coa->beginning_balance = $initialKredit - $initialDebit;
                } else {
                    $coa->beginning_balance = $initialDebit - $initialKredit;
                }

                // Tempelkan item transaksi bulan ini
                $coa->items = $mutasiItems->get($coa->id, collect());

                return $coa;
            })
            ->filter(function ($coa) {
                // Tampilkan jika punya transaksi bulan ini ATAU punya saldo awal tidak nol
                return $coa->items->count() > 0 || $coa->beginning_balance != 0;
            });

        if ($request->format === 'pdf') {
            $pdf = app('dompdf.wrapper')->setPaper('a4', 'landscape');
            $pdf->loadView('laporan.buku-besar.pdf', compact('accountsData', 'bulan', 'tahun'));
            return $pdf->download('laporan-buku-besar-' . now()->format('Ymd') . '.pdf');
        }

        if ($request->format === 'excel') {
            return $this->exportExcelBukuBesar($accountsData, $bulan, $tahun);
        }

        return view('laporan.buku-besar.index', compact('accountsData', 'bulan', 'tahun'));
    }

    private function exportExcelNeracaSaldo($data, $bulan, $tahun)
    {
        $filename = 'laporan-neraca-saldo-' . now()->format('Ymd') . '.csv';
        $headers  = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($data, $bulan, $tahun) {
            $f = fopen('php://output', 'w');
            fprintf($f, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($f, ['CV GAHARU AGUNG SEJAHTERA']);
            fputcsv($f, ['LAPORAN NERACA SALDO']);
            fputcsv($f, ["Periode: " . date('F', mktime(0,0,0,$bulan,1)) . " " . $tahun]);
            fputcsv($f, []);
            fputcsv($f, ['Kode Akun', 'Nama Akun', 'Saldo Awal Debit', 'Saldo Awal Kredit', 'Mutasi Debit', 'Mutasi Kredit', 'Saldo Akhir Debit', 'Saldo Akhir Kredit']);
            foreach ($data as $row) {
                fputcsv($f, [
                    $row->kode,
                    $row->nama,
                    $row->saldo_awal_debit,
                    $row->saldo_awal_kredit,
                    $row->mutasi_debit,
                    $row->mutasi_kredit,
                    $row->debet_akhir,
                    $row->kredit_akhir,
                ]);
            }
            fclose($f);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportExcelLabaRugi($detailsPendapatan, $detailsBeban, $totalPendapatan, $totalBeban, $bulan, $tahun)
    {
        $filename = 'laporan-laba-rugi-' . now()->format('Ymd') . '.csv';
        $headers  = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($detailsPendapatan, $detailsBeban, $totalPendapatan, $totalBeban, $bulan, $tahun) {
            $f = fopen('php://output', 'w');
            fprintf($f, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($f, ['CV GAHARU AGUNG SEJAHTERA']);
            fputcsv($f, ['LAPORAN LABA RUGI']);
            fputcsv($f, ["Periode: " . date('F', mktime(0,0,0,$bulan,1)) . " " . $tahun]);
            fputcsv($f, []);
            
            fputcsv($f, ['PENDAPATAN']);
            foreach ($detailsPendapatan as $row) {
                fputcsv($f, [$row->kode, $row->nama, $row->saldo]);
            }
            fputcsv($f, ['TOTAL PENDAPATAN', '', $totalPendapatan]);
            fputcsv($f, []);

            fputcsv($f, ['BEBAN']);
            foreach ($detailsBeban as $row) {
                fputcsv($f, [$row->kode, $row->nama, $row->saldo]);
            }
            fputcsv($f, ['TOTAL BEBAN', '', $totalBeban]);
            fputcsv($f, []);

            fputcsv($f, ['LABA / RUGI BERSIH', '', $totalPendapatan - $totalBeban]);
            fclose($f);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportExcelNeraca($aktiva, $passiva, $labaBerjalan, $totalPrive, $modalAkhir, $bulan, $tahun)
    {
        $filename = 'laporan-neraca-' . now()->format('Ymd') . '.csv';
        $headers  = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($aktiva, $passiva, $labaBerjalan, $totalPrive, $modalAkhir, $bulan, $tahun) {
            $f = fopen('php://output', 'w');
            fprintf($f, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($f, ['CV GAHARU AGUNG SEJAHTERA']);
            fputcsv($f, ['LAPORAN NERACA']);
            fputcsv($f, ["Periode: " . date('F', mktime(0,0,0,$bulan,1)) . " " . $tahun]);
            fputcsv($f, []);

            fputcsv($f, ['AKTIVA', '', '', 'PASSIVA']);
            
            fputcsv($f, ['--- AKTIVA ---']);
            foreach ($aktiva as $row) {
                fputcsv($f, [$row->kode, $row->nama, $row->saldo]);
            }
            fputcsv($f, ['TOTAL AKTIVA', '', $aktiva->sum('saldo')]);
            fputcsv($f, []);

            fputcsv($f, ['--- PASSIVA ---']);
            fputcsv($f, ['Kewajiban (Liabilitas)']);
            $totalKewajiban = 0;
            foreach ($passiva->where('tipe', 'Liabilitas') as $row) {
                $totalKewajiban += $row->saldo;
                fputcsv($f, [$row->kode, $row->nama, $row->saldo]);
            }
            fputcsv($f, ['Total Kewajiban', '', $totalKewajiban]);
            fputcsv($f, []);

            fputcsv($f, ['Ekuitas (Modal)']);
            $totalEkuitasTabel = 0;
            foreach ($passiva->where('tipe', 'Ekuitas') as $row) {
                $totalEkuitasTabel += $row->saldo;
                fputcsv($f, [$row->kode, $row->nama, $row->saldo]);
            }
            fputcsv($f, ['Laba Tahun Berjalan', '', $labaBerjalan]);
            if ($totalPrive != 0) {
                fputcsv($f, ['Prive', '', -$totalPrive]);
            }
            fputcsv($f, ['Total Ekuitas', '', $modalAkhir]);
            fputcsv($f, []);

            fputcsv($f, ['TOTAL PASSIVA', '', $totalKewajiban + $modalAkhir]);
            fclose($f);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportExcelArusKas($operasional, $investasi, $pendanaan, $bulan, $tahun)
    {
        $filename = 'laporan-arus-kas-' . now()->format('Ymd') . '.csv';
        $headers  = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($operasional, $investasi, $pendanaan, $bulan, $tahun) {
            $f = fopen('php://output', 'w');
            fprintf($f, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($f, ['CV GAHARU AGUNG SEJAHTERA']);
            fputcsv($f, ['LAPORAN ARUS KAS']);
            fputcsv($f, ["Periode: " . date('F', mktime(0,0,0,$bulan,1)) . " " . $tahun]);
            fputcsv($f, []);

            fputcsv($f, ['ARUS KAS DARI AKTIVITAS OPERASIONAL']);
            $totalOps = 0;
            foreach ($operasional as $item) {
                $amt = $item->kredit - $item->debit;
                $totalOps += $amt;
                fputcsv($f, [$item->coa->kode, $item->coa->nama, $amt]);
            }
            fputcsv($f, ['Total Arus Kas Aktivitas Operasional', '', $totalOps]);
            fputcsv($f, []);

            fputcsv($f, ['ARUS KAS DARI AKTIVITAS INVESTASI']);
            $totalInv = 0;
            foreach ($investasi as $item) {
                $amt = $item->kredit - $item->debit;
                $totalInv += $amt;
                fputcsv($f, [$item->coa->kode, $item->coa->nama, $amt]);
            }
            fputcsv($f, ['Total Arus Kas Aktivitas Investasi', '', $totalInv]);
            fputcsv($f, []);

            fputcsv($f, ['ARUS KAS DARI AKTIVITAS PENDANAAN']);
            $totalPen = 0;
            foreach ($pendanaan as $item) {
                $amt = $item->kredit - $item->debit;
                $totalPen += $amt;
                fputcsv($f, [$item->coa->kode, $item->coa->nama, $amt]);
            }
            fputcsv($f, ['Total Arus Kas Aktivitas Pendanaan', '', $totalPen]);
            fputcsv($f, []);

            fputcsv($f, ['KENAIKAN / PENURUNAN KAS BERSIH', '', $totalOps + $totalInv + $totalPen]);
            fclose($f);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportExcelBukuBesar($accountsData, $bulan, $tahun)
    {
        $filename = 'laporan-buku-besar-' . now()->format('Ymd') . '.csv';
        $headers  = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($accountsData, $bulan, $tahun) {
            $f = fopen('php://output', 'w');
            fprintf($f, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($f, ['CV GAHARU AGUNG SEJAHTERA']);
            fputcsv($f, ['LAPORAN BUKU BESAR']);
            fputcsv($f, ["Periode: " . date('F', mktime(0,0,0,$bulan,1)) . " " . $tahun]);
            fputcsv($f, []);

            foreach ($accountsData as $coa) {
                fputcsv($f, ['Akun:', $coa->kode . ' - ' . $coa->nama]);
                fputcsv($f, ['Saldo Awal:', $coa->beginning_balance]);
                fputcsv($f, ['Tanggal', 'Deskripsi / Keterangan', 'No. Ref', 'Debit', 'Kredit', 'Saldo']);
                
                $runningBalance = $coa->beginning_balance;
                $saldoNormal = strtolower($coa->saldo_normal);

                foreach ($coa->items as $item) {
                    if ($saldoNormal === 'kredit') {
                        $runningBalance += ($item->kredit - $item->debit);
                    } else {
                        $runningBalance += ($item->debit - $item->kredit);
                    }
                    fputcsv($f, [
                        \Carbon\Carbon::parse($item->tanggal)->format('d-m-Y'),
                        $item->deskripsi ?? '-',
                        $item->no_ref ?? '-',
                        $item->debit,
                        $item->kredit,
                        $runningBalance
                    ]);
                }
                fputcsv($f, []);
            }
            fclose($f);
        };

        return response()->stream($callback, 200, $headers);
    }
}
