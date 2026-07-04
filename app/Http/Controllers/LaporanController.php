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
            'jurnal_penjualan_pos' => 'jurnal_penjualan_pos', // Ganti jika nama tabelnya berbeda
            'jurnal_penjualan_b2b' => 'jurnal_penjualan_b2b', // Cek apakah namanya 'penjualans' atau 'sales_b2b'
            'jurnal_pembelian'     => 'jurnal_pembelian',     // Ganti jika nama tabelnya 'pembelians' atau 'purchases'
        ];

        // Ambil semua akun COA dari database CV Gaharu Agung Sejahtera
        $neracaSaldo = \App\Models\ChartOfAccount::orderBy('kode', 'asc')
            ->get()
            ->map(function ($coa) use ($startOfMonth, $endOfMonth, $tableMapping) {

                // =========================================================================
                // 1. AMBIL DATA SALDO AWAL (Murni dari journal_type = 'opening')
                // =========================================================================
                $openingRaw = \App\Models\JournalItem::where('account_id', $coa->id)
                    ->where('journal_type', 'opening')
                    ->selectRaw('SUM(debit) as total_debit, SUM(kredit) as total_kredit')
                    ->first();

                $openingDebit  = $openingRaw->total_debit ?? 0;
                $openingKredit = $openingRaw->total_kredit ?? 0;

                // =========================================================================
                // 2. AMBIL MUTASI LALU (Transaksi sebelum bulan berjalan, mengecualikan 'opening')
                // =========================================================================
                $mutasiLaluRaw = \App\Models\JournalItem::where('account_id', $coa->id)
                    ->where('journal_type', '!=', 'opening')
                    ->where(function ($q) use ($startOfMonth, $tableMapping) {
                        // Jurnal Manual (Umum & Penyesuaian)
                        $q->where(function ($queryManual) use ($startOfMonth) {
                            $queryManual->whereIn('journal_type', ['jurnal_umum', 'jurnal_penyesuaian'])
                                ->whereHas('journal', function ($j) use ($startOfMonth) {
                                    $j->where('tanggal', '<', $startOfMonth)
                                        ->where('status', 'posted');
                                });
                        });

                        // Jurnal Otomatis (Looping berdasarkan mapping tabel database)
                        foreach ($tableMapping as $type => $tableName) {
                            $q->orWhere(function ($queryOtomatis) use ($type, $tableName, $startOfMonth) {
                                $queryOtomatis->where('journal_type', $type)
                                    ->whereExists(function ($sub) use ($tableName, $startOfMonth) {
                                        $sub->select(\DB::raw(1))
                                            ->from($tableName)
                                            ->whereColumn("$tableName.id", 'journal_items.journal_id')
                                            ->where('tanggal', '<', $startOfMonth); // Pastikan kolom tanggal di tabel tersebut bernama 'tanggal'
                                    });
                            });
                        }
                    })
                    ->selectRaw('SUM(debit) as total_debit, SUM(kredit) as total_kredit')
                    ->first();

                $mutasiLaluDebit  = $mutasiLaluRaw->total_debit ?? 0;
                $mutasiLaluKredit = $mutasiLaluRaw->total_kredit ?? 0;

                // =========================================================================
                // 3. HITUNG TOTAL SALDO AWAL RIIL
                // =========================================================================
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

                // =========================================================================
                // 4. HITUNG MUTASI PERIODE (Bulan Berjalan)
                // =========================================================================
                $mutasiRaw = \App\Models\JournalItem::where('account_id', $coa->id)
                    ->where('journal_type', '!=', 'opening')
                    ->where(function ($q) use ($startOfMonth, $endOfMonth, $tableMapping) {
                        // Jurnal Manual
                        $q->where(function ($queryManual) use ($startOfMonth, $endOfMonth) {
                            $queryManual->whereIn('journal_type', ['jurnal_umum', 'jurnal_penyesuaian'])
                                ->whereHas('journal', function ($j) use ($startOfMonth, $endOfMonth) {
                                    $j->whereBetween('tanggal', [$startOfMonth, $endOfMonth])
                                        ->where('status', 'posted');
                                });
                        });

                        // Jurnal Otomatis
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
                    ->selectRaw('SUM(debit) as total_debit, SUM(kredit) as total_kredit')
                    ->first();

                $coa->mutasi_debit  = $mutasiRaw->total_debit ?? 0;
                $coa->mutasi_kredit = $mutasiRaw->total_kredit ?? 0;

                // =========================================================================
                // 5. HITUNG SALDO AKHIR
                // =========================================================================
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
                            $journalQuery->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun);
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
                                $journalQuery->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun);
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
                            $journalQuery->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun);
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
                                $journalQuery->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun);
                            });
                    });
            }], 'id') // Parameter kedua wajib diisi string nama kolom
            ->get()
            ->filter(fn($coa) => $coa->saldo != 0);

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

        // --- 1. AMBIL DATA AKTIVA (ASET) ---
        $aktiva = ChartOfAccount::where('tipe', 'Aset')->get()->map(function ($coa) use ($filterTanggalJurnal) {
            $coa->saldo = JournalItem::where('account_id', $coa->id)
                ->where($filterTanggalJurnal)
                ->sum(DB::raw('debit - kredit'));
            return $coa;
        })->where('saldo', '!=', 0);

        // --- 2. AMBIL DATA PASIVA (HANYA KEWAJIBAN & DATA MODAL AWAL SECARA SPESIFIK) ---
        // Catatan: Kita pisahkan akun Prive agar tidak masuk ke perulangan pasiva biasa.
        // Asumsi nama akun prive mengandung kata 'Prive' atau Anda bisa filter berdasarkan kode akun tertentu.
        $passiva = ChartOfAccount::whereIn('tipe', ['Kewajiban', 'Modal'])
            ->where('nama', 'not like', '%Prive%') // Pastikan akun prive tidak ikut terambil di sini
            ->get()
            ->map(function ($coa) use ($filterTanggalJurnal) {
                $coa->saldo = JournalItem::where('account_id', $coa->id)
                    ->where($filterTanggalJurnal)
                    ->sum(DB::raw('kredit - debit'));
                return $coa;
            })->where('saldo', '!=', 0);

        // --- LOGIKA TAMBAHAN: HITUNG PRIVE SECARA TERPISAH ---
        $akunPrive = ChartOfAccount::where('nama', 'like', '%Prive%')->first();
        $totalPrive = 0;

        if ($akunPrive) {
            // Prive saldo normalnya Debit, jadi dihitung Debit - Kredit
            $totalPrive = JournalItem::where('account_id', $akunPrive->id)
                ->where($filterTanggalJurnal)
                ->sum(DB::raw('debit - kredit'));
        }

        // --- 3. HITUNG LABA BERJALAN (PENDAPATAN - BEBAN) ---
        $totalPendapatan = JournalItem::whereHas('coa', fn($q) => $q->where('tipe', 'Pendapatan'))
            ->where($filterTanggalJurnal)
            ->sum(DB::raw('kredit - debit'));

        $totalBeban = JournalItem::whereHas('coa', fn($q) => $q->where('tipe', 'Beban'))
            ->where($filterTanggalJurnal)
            ->sum(DB::raw('debit - kredit'));

        $labaBerjalan = $totalPendapatan - $totalBeban;

        // Hitung total nilai modal secara manual untuk return data jika dibutuhkan oleh sistem
        $totalModalAwal = $passiva->where('tipe', 'Modal')->sum('saldo');
        $modalAkhir = $totalModalAwal + $labaBerjalan - $totalPrive;

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
                    $q->where('journal_type', 'jurnal_umum') // sesuaikan string ini dengan isi DB Anda
                        ->whereHas('journal', fn($j) => $j->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun));
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
                        $q->where('journal_type', 'jurnal_penyesuaian') // sesuaikan string ini jika perlu
                            ->whereHas('jurnalPenyesuaianHeader', fn($j) => $j->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun));
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
                    $q->where('journal_items.journal_type', 'LIKE', '%Journal%')->where('journals.status', 'approved');
                })
                    ->orWhere(function ($q) {
                        $q->where('journal_items.journal_type', 'LIKE', '%Penyesuaian%')->where('jurnal_penyesuaian.status', 'approved');
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

        return view('laporan.buku-besar.index', compact('accountsData', 'bulan', 'tahun'));
    }
}
