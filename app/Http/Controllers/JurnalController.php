<?php

namespace App\Http\Controllers;

use App\Models\Journal;
use App\Models\ChartOfAccount;
use App\Models\JournalItem;
use App\Models\JurnalPenyesuaian;
use App\Models\Pembelian;
use App\Models\PenjualanPos;
use App\Models\Pesanan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class JurnalController extends Controller
{
    public function index()
    {
        // Menyaring header jurnal yang memiliki item bertipe 'jurnal_umum'
        // dan melakukan eager loading hanya pada item yang tipenya sesuai
        $jurnals = Journal::whereHas('details', function ($query) {
            $query->where('journal_type', 'jurnal_umum');
        })
            ->with(['details' => function ($query) {
                $query->where('journal_type', 'jurnal_umum')->with('coa');
            }])
            ->orderBy('tanggal', 'desc')
            ->get();

        return view('jurnal.index', compact('jurnals'));
    }

    public function create()
    {
        $coas = ChartOfAccount::all();
        $latestClosing = Journal::where('source_type', 'closing')->orderBy('tanggal', 'desc')->first();
        $latestClosingDate = $latestClosing ? $latestClosing->tanggal : null;
        return view('jurnal.create', compact('coas', 'latestClosingDate'));
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

        $tanggalJurnal = $request->tanggal;
        $alertMessage = null;

        $latestClosing = Journal::where('source_type', 'closing')
            ->orderBy('tanggal', 'desc')
            ->first();

        if ($latestClosing) {
            $closingDate = \Carbon\Carbon::parse($latestClosing->tanggal)->endOfMonth();
            $targetDate = \Carbon\Carbon::parse($request->tanggal);
            
            if ($targetDate->lte($closingDate)) {
                // Periode ini atau bulan sebelumnya sudah ditutup.
                // Geser ke 1 bulan setelah closing terbaru.
                $tanggalJurnal = $closingDate->addMonth()->startOfMonth()->toDateString();
                $alertMessage = "Karena periode akuntansi s/d " . \Carbon\Carbon::parse($latestClosing->tanggal)->translatedFormat('F Y') . " sudah ditutup, jurnal ini otomatis dicatat pada awal periode berjalan selanjutnya tanggal " . \Carbon\Carbon::parse($tanggalJurnal)->translatedFormat('d M Y') . ".";
            }
        }

        try {
            DB::beginTransaction();

            // Simpan Header dengan source_type menjadi 'jurnal_umum'
            $jurnal = Journal::create([
                'tanggal' => $tanggalJurnal,
                'deskripsi' => $request->deskripsi,
                'no_ref' => $request->no_ref ?? 'JR-' . time(),
                'source_type' => 'jurnal_umum',
                'source_id' => 0,
                'created_by' => Auth::id(),
            ]);

            $totalDebit = 0;
            $totalKredit = 0;

            // Simpan Detail dengan menyertakan journal_type
            foreach ($request->details as $item) {
                $jurnal->details()->create([
                    'account_id'   => $item['account_id'],
                    'journal_type' => 'jurnal_umum',
                    'debit'        => $item['debit'],
                    'kredit'       => $item['kredit'],
                ]);

                $totalDebit += $item['debit'];
                $totalKredit += $item['kredit'];
            }

            // Validasi Balance
            if (number_format($totalDebit, 2, '.', '') !== number_format($totalKredit, 2, '.', '')) {
                throw new \Exception("Total Debit dan Kredit tidak seimbang!");
            }

            DB::commit();
            
            $successMsg = 'Jurnal berhasil disimpan!';
            if ($alertMessage) {
                $successMsg .= ' ' . $alertMessage;
            }
            return redirect()->route('jurnal.index')->with('success', $successMsg);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function approveBatch(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        // Memastikan hanya menyetujui jurnal draft yang itemnya bertipe 'jurnal_umum'
        $updatedCount = Journal::where('status', 'draft')
            ->whereBetween('tanggal', [$request->start_date, $request->end_date])
            ->whereHas('details', function ($query) {
                $query->where('journal_type', 'jurnal_umum');
            })
            ->update(['status' => 'approved']);

        if ($updatedCount > 0) {
            return redirect()->back()->with('success', "Berhasil memposting {$updatedCount} jurnal ke Buku Besar!");
        }

        return redirect()->back()->with('error', 'Tidak ada jurnal berstatus draft yang ditemukan pada rentang tanggal tersebut.');
    }

    public function show($id)
    {
        $jurnal = Journal::with(['details' => function ($query) {
            $query->where('journal_type', 'jurnal_umum')->with('coa');
        }])
            ->whereHas('details', function ($query) {
                $query->where('journal_type', 'jurnal_umum');
            })
            ->findOrFail($id);

        // DIPERBAIKI: dari 'jurnal umum' menjadi 'jurnal_umum'
        $items = JournalItem::where('journal_id', $id)
            ->where('journal_type', 'jurnal_umum')
            ->get();

        return view('jurnal.show', compact('jurnal', 'items'));
    }

    public function closingPage()
    {
        // Menampilkan halaman form penutupan dan daftar riwayat khusus closing
        $closings = Journal::where('source_type', 'closing')
            ->orderBy('tanggal', 'desc')
            ->get();

        return view('closing.index', compact('closings'));
    }

    public function closePeriod(Request $request)
    {
        $request->validate([
            'bulan' => 'required|integer|between:1,12',
            'tahun' => 'required|integer',
        ]);

        $bulan = sprintf('%02d', $request->bulan);
        $tahun = $request->tahun;

        $startOfMonth = "$tahun-$bulan-01";
        $endOfMonth = date('Y-m-t', strtotime($startOfMonth));

        try {
            DB::beginTransaction();

            // 1. Validasi apakah periode ini sudah pernah diclosing sebelumnya
            $alreadyClosed = Journal::where('source_type', 'closing')
                ->whereMonth('tanggal', $bulan)
                ->whereYear('tanggal', $tahun)
                ->exists();

            if ($alreadyClosed) {
                return back()->withErrors(['error' => 'Periode akuntansi ini sudah ditutup sebelumnya!']);
            }

            // 2. Ambil Akun Laba Ditahan (Muara Akhir Ekuitas)
            $labaDitahan = ChartOfAccount::where('kode', '3103')->firstOrFail();
            $tanggalClosing = $endOfMonth;

            // 3. Buat Header Jurnal Penutup di tabel 'journals'
            $journal = Journal::create([
                'tanggal'     => $tanggalClosing,
                'deskripsi'   => "Jurnal Penutup Periode " . date('F', mktime(0, 0, 0, (int)$bulan, 1)) . " $tahun",
                'no_ref'      => 'CLS-' . time(),
                'source_type' => 'closing', 
                'source_id'   => 0,
                'status'      => 'approved', 
                'created_by'  => Auth::id(),
            ]);

            // --- PERBAIKAN MAPPING TABEL (MANUAL VS OTOMATIS) ---
            $tabelManual = [
                'jurnal_umum'                        => 'journals',
                \App\Models\JurnalPenyesuaian::class => 'jurnal_penyesuaian',
            ];

            $tabelOtomatis = [
                'jurnal_penjualan_pos' => 'jurnal_penjualan_pos', 
                'jurnal_penjualan_b2b' => 'jurnal_penjualan_b2b', 
                'jurnal_pembelian'     => 'jurnal_pembelian',     
            ];

            // 4. Ambil data mutasi berjalan secara bulk menggunakan query agregasi polimorfik
            $mutasiBalances = \App\Models\JournalItem::where('journal_type', '!=', 'opening')
                ->where('journal_type', '!=', 'closing') 
                ->where(function ($q) use ($startOfMonth, $endOfMonth, $tabelManual, $tabelOtomatis) {
                    
                    // Jalur Jurnal Manual (Harus Approved)
                    foreach ($tabelManual as $type => $tableName) {
                        $q->orWhere(function ($queryManual) use ($type, $tableName, $startOfMonth, $endOfMonth) {
                            $queryManual->where('journal_type', $type)
                                ->whereExists(function ($sub) use ($tableName, $startOfMonth, $endOfMonth) {
                                    $sub->select(DB::raw(1))
                                        ->from($tableName)
                                        ->whereColumn("$tableName.id", 'journal_items.journal_id')
                                        ->whereBetween('tanggal', [$startOfMonth, $endOfMonth])
                                        ->where('status', 'approved');
                                });
                        });
                    }

                    // Jalur Jurnal Otomatis (Langsung Dihitung)
                    foreach ($tabelOtomatis as $type => $tableName) {
                        $q->orWhere(function ($queryOtomatis) use ($type, $tableName, $startOfMonth, $endOfMonth) {
                            $queryOtomatis->where('journal_type', $type)
                                ->whereExists(function ($sub) use ($tableName, $startOfMonth, $endOfMonth) {
                                    $sub->select(DB::raw(1))
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

            $totalPendapatan = 0;
            $totalHppDanBeban = 0;
            $itemsToInsert = [];

            // Mengambil semua akun COA Pendapatan (4), HPP (5), dan Beban (6)
            $coas = ChartOfAccount::where('kode', 'like', '4%')
                ->orWhere('kode', 'like', '5%')
                ->orWhere('kode', 'like', '6%')
                ->get();

            foreach ($coas as $coa) {
                $raw = $mutasiBalances->get($coa->id);
                if (!$raw) continue;

                $debit = (float) $raw->total_debit;
                $kredit = (float) $raw->total_kredit;

                if (str_starts_with($coa->kode, '4')) {
                    // Pendapatan (Saldo Normal: Kredit) -> Saldo Penutupan didebit sebesar net kredit
                    $neto = $kredit - $debit;
                    if ($neto != 0) {
                        $itemsToInsert[] = [
                            'journal_id'   => $journal->id,
                            'account_id'   => $coa->id,
                            'journal_type' => 'closing',
                            'debit'        => $neto > 0 ? $neto : 0,
                            'kredit'       => $neto < 0 ? abs($neto) : 0,
                        ];
                        $totalPendapatan += $neto;
                    }
                } else {
                    // HPP & Beban (Saldo Normal: Debit) -> Saldo Penutupan dikredit sebesar net debit
                    $neto = $debit - $kredit;
                    if ($neto != 0) {
                        $itemsToInsert[] = [
                            'journal_id'   => $journal->id,
                            'account_id'   => $coa->id,
                            'journal_type' => 'closing',
                            'debit'        => $neto < 0 ? abs($neto) : 0,
                            'kredit'       => $neto > 0 ? $neto : 0,
                        ];
                        $totalHppDanBeban += $neto;
                    }
                }
            }

            // 5. Hitung Laba/Rugi Bersih & Tembak ke Laba Ditahan
            $labaBersih = $totalPendapatan - $totalHppDanBeban;

            if ($labaBersih != 0) {
                $itemsToInsert[] = [
                    'journal_id'   => $journal->id,
                    'account_id'   => $labaDitahan->id,
                    'journal_type' => 'closing',
                    'debit'        => $labaBersih < 0 ? abs($labaBersih) : 0,
                    'kredit'       => $labaBersih > 0 ? $labaBersih : 0,
                ];
            }

            // Bulk insert semua detail baris jurnal penutupan ke 'journal_items'
            if (!empty($itemsToInsert)) {
                DB::table('journal_items')->insert($itemsToInsert);
            }

            DB::commit();
            return redirect()->route('closing.index')->with('success', 'Periode berhasil ditutup!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Gagal menutup periode: ' . $e->getMessage()]);
        }
    }
    // Menampilkan Daftar Jurnal Penyesuaian
    public function adjustmentIndex()
    {
        // Mengambil data dari tabel baru (jurnal_penyesuaian) beserta detail akunnya
        $adjustments = JurnalPenyesuaian::with('details.coa')
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
        $latestClosing = Journal::where('source_type', 'closing')->orderBy('tanggal', 'desc')->first();
        $latestClosingDate = $latestClosing ? $latestClosing->tanggal : null;

        // Mengarah ke resources/views/adjustment/create.blade.php
        return view('adjustment.create', compact('coas', 'latestClosingDate'));
    }

    /**
     * Menyimpan data Jurnal Penyesuaian
     */
    public function adjustmentStore(Request $request)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'no_ref' => 'required|string|max:100',
            'deskripsi' => 'required|string|max:255',
            'details' => 'required|array|min:2',
            'details.*.account_id' => 'required|exists:chart_of_accounts,id',
            'details.*.debit' => 'required|numeric|min:0',
            'details.*.kredit' => 'required|numeric|min:0',
            // Tambahkan validasi action untuk menangkap tombol draft/post
            'action' => 'required|in:draft,post',
        ]);

        $tanggalJurnal = $request->tanggal;
        $alertMessage = null;

        $latestClosing = Journal::where('source_type', 'closing')
            ->orderBy('tanggal', 'desc')
            ->first();

        if ($latestClosing) {
            $closingDate = \Carbon\Carbon::parse($latestClosing->tanggal)->endOfMonth();
            $targetDate = \Carbon\Carbon::parse($request->tanggal);
            
            if ($targetDate->lte($closingDate)) {
                // Periode ini atau bulan sebelumnya sudah ditutup.
                // Geser ke 1 bulan setelah closing terbaru.
                $tanggalJurnal = $closingDate->addMonth()->startOfMonth()->toDateString();
                $alertMessage = "Karena periode akuntansi s/d " . \Carbon\Carbon::parse($latestClosing->tanggal)->translatedFormat('F Y') . " sudah ditutup, jurnal penyesuaian ini otomatis dicatat pada awal periode berjalan selanjutnya tanggal " . \Carbon\Carbon::parse($tanggalJurnal)->translatedFormat('d M Y') . ".";
            }
        }

        try {
            DB::beginTransaction();

            // 1. Simpan Header ke tabel: jurnal_penyesuaian dengan penambahan kolom status
            $jurnal = JurnalPenyesuaian::create([
                'tanggal'     => $tanggalJurnal,
                'deskripsi'   => "[AJP] " . $request->deskripsi,
                'no_ref'      => $request->no_ref,
                'source_type' => 'adjustment',
                'source_id'   => 0,
                'created_by'  => Auth::id(),
                // Skema Hybrid: tentukan status berdasarkan tombol yang diklik
                'status'      => $request->action === 'post' ? 'approved' : 'draft',
            ]);

            // 2. Simpan Detail ke tabel journal_items dengan menyertakan nama class model pada journal_type
            foreach ($request->details as $item) {
                // Skip jika debit dan kredit sama-sama nol
                if ($item['debit'] == 0 && $item['kredit'] == 0) continue;

                $jurnal->details()->create([
                    'account_id'   => $item['account_id'],
                    'debit'        => $item['debit'],
                    'kredit'       => $item['kredit'],
                    // Diseragamkan menggunakan namespace class model JurnalPenyesuaian
                    'journal_type' => \App\Models\JurnalPenyesuaian::class,
                ]);
            }

            // Refresh model untuk mendapatkan details yang baru disimpan
            $jurnal->load('details');

            // 3. Validasi Balance
            if (round($jurnal->details->sum('debit'), 2) != round($jurnal->details->sum('kredit'), 2)) {
                throw new \Exception("Total Debit (" . $jurnal->details->sum('debit') . ") dan Kredit (" . $jurnal->details->sum('kredit') . ") tidak seimbang!");
            }

            DB::commit();

            $pesan = $jurnal->status === 'approved'
                ? 'Jurnal Penyesuaian berhasil disimpan dan diposting ke Buku Besar!'
                : 'Jurnal Penyesuaian berhasil disimpan sebagai Draft!';

            if ($alertMessage) {
                $pesan .= ' ' . $alertMessage;
            }

            return redirect()->route('adjustment.index')->with('success', $pesan);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function adjustmentApprove($id)
    {
        $jurnal = JurnalPenyesuaian::findOrFail($id);
        $jurnal->update(['status' => 'approved']);

        return redirect()->back()->with('success', 'Jurnal berhasil diposting ke Buku Besar!');
    }

    public function adjustmentApproveBatch(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $updatedCount = JurnalPenyesuaian::where('status', 'draft')
            ->whereBetween('tanggal', [$request->start_date, $request->end_date])
            ->update(['status' => 'approved']);

        if ($updatedCount > 0) {
            return redirect()->back()->with('success', "Berhasil memposting {$updatedCount} jurnal penyesuaian ke Buku Besar!");
        }

        return redirect()->back()->with('error', 'Tidak ada jurnal penyesuaian berstatus draft yang ditemukan pada rentang tanggal tersebut.');
    }

    public function pembelianIndex()
    {
        $semuaPembelian = \App\Models\Pembelian::with(['supplier', 'gudang'])
            ->orderBy('tanggal', 'desc')
            ->get();

        $tahapTersimpan = DB::table('jurnal_pembelian')
            ->where('source_type', 'pembelian')
            ->select('source_id', 'tahap')
            ->get()
            ->groupBy('source_id')
            ->map(function ($rows) {
                return $rows->pluck('tahap')->map(fn($t) => trim(strtolower((string)$t)))->filter()->toArray();
            });

        $pembeliansBelum = collect();

        foreach ($semuaPembelian as $p) {
            $tahapSeharusnya = $this->tahapSeharusnyaAda($p);
            $sudahAda        = $tahapTersimpan->get($p->id, []);
            $tahapKurang     = array_diff($tahapSeharusnya, $sudahAda);

            foreach ($tahapKurang as $tahap) {
                // Kloning objek agar aman diubah propertinya per baris antrean
                $clone = clone $p;
                $clone->tahap_selanjutnya = $tahap;
                $clone->total_keluar = $this->hitungTotalKeluar($p, $tahap);
                $pembeliansBelum->push($clone);
            }
        }

        $jurnalsSudah = DB::table('jurnal_pembelian')
            ->join('journal_items', function ($join) {
                $join->on('journal_items.journal_id', '=', 'jurnal_pembelian.id')
                    ->where('journal_items.journal_type', '=', 'jurnal_pembelian');
            })
            ->join('pembelian', 'jurnal_pembelian.source_id', '=', 'pembelian.id')
            ->where('jurnal_pembelian.source_type', 'pembelian')
            ->select(
                'jurnal_pembelian.id',
                'jurnal_pembelian.tanggal',
                'jurnal_pembelian.no_ref',
                'jurnal_pembelian.deskripsi',
                'jurnal_pembelian.tahap',
                'jurnal_pembelian.source_id',
                DB::raw('SUM(journal_items.debit) as total_transaksi')
            )
            ->groupBy(
                'jurnal_pembelian.id',
                'jurnal_pembelian.tanggal',
                'jurnal_pembelian.no_ref',
                'jurnal_pembelian.deskripsi',
                'jurnal_pembelian.tahap',
                'jurnal_pembelian.source_id'
            )
            ->orderBy('jurnal_pembelian.tanggal', 'desc')
            ->orderBy('jurnal_pembelian.id', 'desc')
            ->get();

        return view('jurnal-pembelian.index', compact('pembeliansBelum', 'jurnalsSudah'));
    }

    private function tahapSeharusnyaAda($pembelian): array
    {
        $persenDP   = floatval($pembelian->presen_dp ?? $pembelian->persen_dp ?? 0);
        $isDiterima = (bool) $pembelian->is_diterima;
        $isLunas    = (bool) $pembelian->is_lunas;

        $tahap = [];

        if ($persenDP > 0) {
            $tahap[] = 'dp';

            if ($isLunas) {
                $tahap[] = 'pelunasan';
            }

            // Jurnal persediaan (reklas_lunas / gabungan) hanya boleh muncul jika barang SUDAH DITERIMA
            if ($isDiterima) {
                if ($isLunas) {
                    $tahap[] = 'reklas_lunas';
                } else {
                    $tahap[] = 'gabungan';
                }
            }
        } else {
            // Untuk COD, Jurnal persediaan hanya boleh muncul jika barang SUDAH DITERIMA
            if ($isDiterima) {
                $tahap[] = 'cod';
            }
        }

        return $tahap;
    }

    // PERBAIKAN: Menambahkan parameter kedua $tahapBerikutnya langsung ke fungsi
    private function hitungTotalKeluar($pembelian, $tahapBerikutnya = null): float
    {
        $dppTotal     = floatval($pembelian->total);
        $ppnTotal     = round($dppTotal * 0.10, 0);
        $totalKontrak = $dppTotal + $ppnTotal;
        $persenDP     = floatval($pembelian->persen_dp ?? 0);

        $nominalDP = floatval($pembelian->nominal_dp ?? ($totalKontrak * ($persenDP / 100)));

        // Pastikan format string kecil dan bersih dari spasi gantung
        $tahapClean = trim(strtolower((string)$tahapBerikutnya));

        if ($tahapClean === 'dp') {
            return $nominalDP;
        }

        if ($tahapClean === 'pelunasan' || $tahapClean === 'gabungan') {
            return $totalKontrak - $nominalDP;
        }

        if ($tahapClean === 'reklas_lunas') {
            return 0; // Reklas murni tidak mengeluarkan kas lagi
        }

        if ($tahapClean === 'cod') {
            return $totalKontrak;
        }

        return 0;
    }

    public function pembelianCreate(Request $request, $id)
    {
        $pembelian = \App\Models\Pembelian::with(['supplier', 'details.barang'])->findOrFail($id);
        $coas = \App\Models\ChartOfAccount::orderBy('kode', 'asc')->get();

        // Deteksi apakah pembelian berisi barang operasional / perlengkapan (bukan bahan baku)
        $hasOperational = false;
        foreach ($pembelian->details as $det) {
            if ($det->barang && ($det->barang->is_operational || !$det->barang->is_bahan_baku)) {
                $hasOperational = true;
                break;
            }
        }

        $debitCoaCode = $hasOperational ? '1302' : '1301'; // 1302 = Persediaan Perlengkapan Operasional & ATK, 1301 = Persediaan Bahan Baku

        $idKasBank        = DB::table('chart_of_accounts')->where('kode', '1101')->value('id') ?? 1;
        $idPersediaan     = DB::table('chart_of_accounts')->where('kode', $debitCoaCode)->value('id') ?? ($hasOperational ? 19 : 18);
        $idUangMukaPemb   = DB::table('chart_of_accounts')->where('kode', '1202')->value('id') ?? 7;
        $idPPNMasukan     = DB::table('chart_of_accounts')->where('kode', '1203')->value('id') ?? 8;

        $tarifPpn = 0.10;
        $defaultDetails = [];

        $dppTotal     = floatval($pembelian->total);
        $ppnTotal     = round($dppTotal * $tarifPpn, 0);
        $totalKontrak = $dppTotal + $ppnTotal;

        $persenDP   = floatval($pembelian->persen_dp ?? 0);
        $nominalDP        = floatval($pembelian->nominal_dp ?? ($totalKontrak * ($persenDP / 100)));
        $nominalPelunasan = $totalKontrak - $nominalDP;

        $tahapTersimpan = DB::table('jurnal_pembelian')
            ->where('source_type', 'pembelian')
            ->where('source_id', $pembelian->id)
            ->pluck('tahap')
            ->map(fn($t) => trim(strtolower((string)$t)))
            ->filter()
            ->toArray();

        // Tentukan tahap berdasarkan query parameter, jika tidak ada fallback ke saran tahap kurang pertama
        $tahapReq = trim(strtolower((string)$request->query('tahap')));
        $tahapSeharusnya = $this->tahapSeharusnyaAda($pembelian);
        $tahapKurang = array_diff($tahapSeharusnya, $tahapTersimpan);
        $tahapSaran = array_values($tahapKurang)[0] ?? null;

        $tahap = in_array($tahapReq, ['dp', 'pelunasan', 'reklas_lunas', 'gabungan', 'cod']) ? $tahapReq : $tahapSaran;

        // Proteksi Tambahan: Jika tahap membutuhkan pengakuan persediaan namun barang belum diterima
        if (in_array($tahap, ['cod', 'reklas_lunas', 'gabungan']) && !$pembelian->is_diterima) {
            return redirect()
                ->route('jurnal-pembelian.index')
                ->with('error', 'Barang pada Pembelian ' . $pembelian->kode_pembelian . ' belum diterima. Jurnal pengakuan Persediaan baru dapat dicatat setelah barang diterima di menu Pembelian.');
        }

        if ($tahap === 'dp') {
            $defaultDetails = [
                ['account_id' => $idUangMukaPemb, 'debit' => $nominalDP, 'kredit' => 0],
                ['account_id' => $idKasBank, 'debit' => 0, 'kredit' => $nominalDP]
            ];
        } elseif ($tahap === 'pelunasan') {
            $defaultDetails = [
                ['account_id' => $idUangMukaPemb, 'debit' => $nominalPelunasan, 'kredit' => 0],
                ['account_id' => $idKasBank, 'debit' => 0, 'kredit' => $nominalPelunasan]
            ];
        } elseif ($tahap === 'reklas_lunas') {
            $defaultDetails = [
                ['account_id' => $idPersediaan, 'debit' => $dppTotal, 'kredit' => 0],
                ['account_id' => $idPPNMasukan, 'debit' => $ppnTotal, 'kredit' => 0],
                ['account_id' => $idUangMukaPemb, 'debit' => 0, 'kredit' => $totalKontrak]
            ];
        } elseif ($tahap === 'gabungan') {
            $defaultDetails = [
                ['account_id' => $idPersediaan, 'debit' => $dppTotal, 'kredit' => 0],
                ['account_id' => $idPPNMasukan, 'debit' => $ppnTotal, 'kredit' => 0],
                ['account_id' => $idUangMukaPemb, 'debit' => 0, 'kredit' => $nominalDP],
                ['account_id' => $idKasBank, 'debit' => 0, 'kredit' => $nominalPelunasan]
            ];
        } else {
            // Default ke COD
            $tahap = 'cod';
            $defaultDetails = [
                ['account_id' => $idPersediaan, 'debit' => $dppTotal, 'kredit' => 0],
                ['account_id' => $idPPNMasukan, 'debit' => $ppnTotal, 'kredit' => 0],
                ['account_id' => $idKasBank, 'debit' => 0, 'kredit' => $totalKontrak]
            ];
        }

        return view('jurnal-pembelian.create', compact('pembelian', 'coas', 'defaultDetails', 'tahap'));
    }

    public function prosesJurnalPembelian(Request $request, $id)
    {
        // 1. Validasi data form
        $request->validate([
            'tanggal'              => 'required|date',
            'deskripsi'            => 'required|string',
            'no_ref'               => 'nullable|string',
            'tahap'                => 'required|in:dp,pelunasan,reklas_lunas,gabungan,cod',
            'details'              => 'required|array|min:2',
            'details.*.account_id' => 'required|exists:chart_of_accounts,id',
            'details.*.debit'      => 'required|numeric|min:0',
            'details.*.kredit'     => 'required|numeric|min:0',
        ]);

        $pembelian = \App\Models\Pembelian::findOrFail($id);

        // 1a. Validasi penutupan periode: Blokir pencatatan jurnal pada periode yang sudah ditutup
        if (\App\Models\Journal::isPeriodClosed($request->tanggal)) {
            return back()->with('error', 'Periode akuntansi tanggal ' . date('d/m/Y', strtotime($request->tanggal)) . ' sudah ditutup buku. Tidak dapat menambah jurnal pada periode yang sudah ditutup.')->withInput();
        }

        // 1b. Validasi backend: Blokir pengakuan Persediaan (13xx) jika barang belum diterima
        if (!$pembelian->is_diterima) {
            $persediaanCoaIds = \App\Models\ChartOfAccount::where('kode', 'like', '13%')->pluck('id')->toArray();
            foreach ($request->details as $item) {
                if (in_array($item['account_id'], $persediaanCoaIds) && floatval($item['debit'] ?? 0) > 0) {
                    return back()
                        ->with('error', "Nominal Persediaan (13xx) tidak dapat dicatat karena barang pada Pembelian '{$pembelian->kode_pembelian}' belum diterima (is_diterima = false). Silakan lakukan penerimaan barang terlebih dahulu di Menu Pembelian.")
                        ->withInput();
                }
            }
        }

        try {
            DB::beginTransaction();

            // Validasi Keseimbangan Saldo (Balance Check)
            $totalDebit = 0;
            $totalKredit = 0;
            foreach ($request->details as $item) {
                $totalDebit += floatval($item['debit'] ?? 0);
                $totalKredit += floatval($item['kredit'] ?? 0);
            }

            if (round($totalDebit, 2) != round($totalKredit, 2)) {
                throw new \Exception("Total Debit (Rp " . number_format($totalDebit) . ") dan Kredit (Rp " . number_format($totalKredit) . ") tidak seimbang!");
            }

            // 2. Simpan Header LANGSUNG ke tabel jurnal_pembelian dan ambil ID-nya
            $jurnalPembelianId = DB::table('jurnal_pembelian')->insertGetId([
                'tanggal'     => $request->tanggal,
                'deskripsi'   => $request->deskripsi,
                'no_ref'      => $request->no_ref ?? 'JR-PEMB-' . time(),
                'source_type' => 'pembelian',
                'source_id'   => $pembelian->id, // Mengunci ID pembelian agar hilang dari antrean atas
                'tahap'       => $request->tahap, // Tahap jurnal: dp, pelunasan, reklas, gabungan, atau cod
                'created_by'  => Auth::id() ?? 1,
            ]);

            // 3. Simpan baris detail debit/kredit ke tabel journal_items
            // [OPSI A] Setiap baris kini ikut menyimpan journal_type = 'jurnal_pembelian'
            // supaya nantinya bisa dibedakan dari baris journal_items milik modul lain
            // (mis. jurnal_penjualan_pos) walau journal_id-nya kebetulan bernilai sama.
            foreach ($request->details as $item) {
                if (floatval($item['debit']) == 0 && floatval($item['kredit']) == 0) {
                    continue;
                }

                DB::table('journal_items')->insert([
                    'journal_id'   => $jurnalPembelianId,
                    'journal_type' => 'jurnal_pembelian', // [OPSI A]
                    'account_id'   => $item['account_id'],
                    'debit'        => $item['debit'],
                    'kredit'       => $item['kredit'],
                ]);
            }

            DB::commit();
            return redirect()
                ->route('jurnal-pembelian.index')
                ->with('success', 'Invoice ' . $pembelian->kode_pembelian . ' berhasil disimpan ke Jurnal Pembelian (Tahap: ' . $request->tahap . ')!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    public function pembelianShow($id)
    {
        $jurnal = DB::table('jurnal_pembelian')->where('id', $id)->where('source_type', 'pembelian')->first();

        if (!$jurnal) {
            return redirect()->route('jurnal-pembelian.index')->with('error', 'Data riwayat tidak ditemukan.');
        }

        $details = DB::table('journal_items')
            ->leftJoin('chart_of_accounts', 'journal_items.account_id', '=', 'chart_of_accounts.id')
            ->where('journal_items.journal_id', $id)
            ->where('journal_items.journal_type', 'jurnal_pembelian')
            ->select('journal_items.debit', 'journal_items.kredit', 'chart_of_accounts.kode', 'chart_of_accounts.nama')
            ->orderBy('journal_items.debit', 'desc')
            ->get();

        $totalDebit = $details->sum('debit');
        $totalKredit = $details->sum('kredit');

        return view('jurnal-pembelian.show', compact('jurnal', 'details', 'totalDebit', 'totalKredit'));
    }

    public function penjualanposIndex()
    {
        $user = auth()->user();

        // 1. Ambil semua ID transaksi POS yang sudah pernah dijurnal
        $sudahDijurnal = DB::table('jurnal_penjualan_pos')
            ->where('source_type', 'penjualan_pos')
            ->pluck('source_id')
            ->toArray();

        // 2. Antrean Atas: Tarik data transaksi POS harian yang BELUM dijurnal dan sudah disetujui (status = 'SUKSES')
        $queryBelum = DB::table('penjualan_pos')
            ->leftJoin('master_gudang', 'penjualan_pos.gudang_id', '=', 'master_gudang.id')
            ->whereNotIn('penjualan_pos.id', $sudahDijurnal)
            ->where('penjualan_pos.status', 'SUKSES');

        if ($user && $user->gudang_id) {
            $queryBelum->where('penjualan_pos.gudang_id', $user->gudang_id);
        }

        $penjualanPosBelum = $queryBelum->select(
                'penjualan_pos.id',
                'penjualan_pos.tanggal',
                'penjualan_pos.kode_transaksi',
                'penjualan_pos.total',
                DB::raw("COALESCE(master_gudang.nama, 'Gudang') as nama_outlet")
            )
            ->orderBy('penjualan_pos.tanggal', 'desc')
            ->get();

        // 3. Riwayat Bawah: Ringkas menjadi satu baris per dokumen (Group By No. Ref)
        $querySudah = DB::table('jurnal_penjualan_pos')
            ->join('journal_items', function ($join) {
                $join->on('journal_items.journal_id', '=', 'jurnal_penjualan_pos.id')
                    ->where('journal_items.journal_type', '=', 'jurnal_penjualan_pos');
            })
            ->leftJoin('penjualan_pos', 'jurnal_penjualan_pos.source_id', '=', 'penjualan_pos.id')
            ->where('jurnal_penjualan_pos.source_type', 'penjualan_pos');

        if ($user && $user->gudang_id) {
            $querySudah->where('penjualan_pos.gudang_id', $user->gudang_id);
        }

        $jurnalsSudah = $querySudah->select(
                'jurnal_penjualan_pos.id',
                'jurnal_penjualan_pos.tanggal',
                'jurnal_penjualan_pos.no_ref',
                'jurnal_penjualan_pos.deskripsi',
                DB::raw('SUM(journal_items.debit) as total_debit'),
                DB::raw('SUM(journal_items.kredit) as total_kredit')
            )
            ->groupBy(
                'jurnal_penjualan_pos.id',
                'jurnal_penjualan_pos.tanggal',
                'jurnal_penjualan_pos.no_ref',
                'jurnal_penjualan_pos.deskripsi'
            )
            ->orderBy('jurnal_penjualan_pos.tanggal', 'desc')
            ->orderBy('jurnal_penjualan_pos.id', 'desc')
            ->get();

        return view('jurnal-penjualanpos.index', compact('penjualanPosBelum', 'jurnalsSudah'));
    }

    public function penjualanposCreate(Request $request, $id) // <-- Tambahkan Request $request di sini 
    {
        // Bersihkan sisa data old input dari menu/modul lain agar tidak merusak draf POS
        $request->session()->forget('_old_input');

        // 1. Ambil data induk penjualan POS berdasarkan ID antrean
        $penjualan = DB::table('penjualan_pos')->where('id', $id)->first();

        if (!$penjualan) {
            return back()->with('error', 'Data induk penjualan POS tidak ditemukan.');
        }

        // --- VALIDASI ROLE / OUTLET AKSES ---
        $user = auth()->user();
        if ($user && $user->gudang_id && $penjualan->gudang_id != $user->gudang_id) {
            abort(403, 'Anda tidak memiliki akses ke data penjualan outlet lain.');
        }

        if (($penjualan->status ?? 'Draft') !== 'SUKSES') {
            return redirect()->route('jurnal-penjualanpos.index')->with('error', 'Transaksi POS ini belum di-approve.');
        }

        // 2. Ambil master data COA untuk dropdown penyesuaian di form view
        $coas = \App\Models\ChartOfAccount::orderBy('kode', 'asc')->get();

        // 3. Hitung akumulasi nilai total HPP riil dari tabel detail POS
        // Jika hpp_satuan bernilai 0 (Draft), gunakan hpp_referensi dari master_barang sebagai fallback
        $posDetails = DB::table('penjualanpos_detail')
            ->join('master_barang', 'penjualanpos_detail.produk_id', '=', 'master_barang.id')
            ->where('penjualanpos_detail.penjualan_id', $id)
            ->select('penjualanpos_detail.qty', 'penjualanpos_detail.hpp_satuan', 'master_barang.hpp_referensi', 'penjualanpos_detail.subtotal')
            ->get();

        $totalHppRiil = 0;
        $nilaiPenjualan = 0;
        foreach ($posDetails as $pd) {
            $hpp = floatval($pd->hpp_satuan);
            if ($hpp <= 0) {
                $hpp = floatval($pd->hpp_referensi);
            }
            $totalHppRiil += floatval($pd->qty) * $hpp;
            $nilaiPenjualan += floatval($pd->subtotal);
        }

        $tarifPpn       = 0.10; // Sesuaikan dengan tarif PPN yang berlaku (misal 10%)
        $nilaiPpn       = $nilaiPenjualan * $tarifPpn;
        $totalKasMasuk  = $nilaiPenjualan + $nilaiPpn;

        // Ambil ID COA secara dinamis berdasarkan kode resmi
        $idKasOutlet      = DB::table('chart_of_accounts')->where('kode', '1101')->value('id') ?? 1;
        $idHppPos         = DB::table('chart_of_accounts')->where('kode', '5101')->value('id') ?? 5;
        $idPenjualanPos   = DB::table('chart_of_accounts')->where('kode', '4101')->value('id') ?? 4;
        $idPpnKeluaran    = DB::table('chart_of_accounts')->where('kode', '2201')->value('id') ?? 6;
        $idPersediaanJadi = DB::table('chart_of_accounts')->where('kode', '1301')->value('id') ?? 3;

        // 4. Susun Draf Jurnal Otomatis (5 Baris Berpasangan - Pastikan Seimbang)
        $defaultDetails = [
            // --- Kelompok Penerimaan Arus Kas Omzet & Pajak ---
            [
                'account_id' => $idKasOutlet,
                'debit'      => $totalKasMasuk,
                'kredit'     => 0
            ],
            [
                'account_id' => $idPenjualanPos,
                'debit'      => 0,
                'kredit'     => $nilaiPenjualan
            ],
            [
                'account_id' => $idPpnKeluaran,
                'debit'      => 0,
                'kredit'     => $nilaiPpn
            ],
            // --- Kelompok Matching Concept Pengurangan Stok Harian ---
            [
                'account_id' => $idHppPos,
                'debit'      => floatval($totalHppRiil),
                'kredit'     => 0
            ],
            [
                'account_id' => $idPersediaanJadi,
                'debit'      => 0,
                'kredit'     => floatval($totalHppRiil)
            ]
        ];

        return view('jurnal-penjualanpos.create', compact('penjualan', 'coas', 'defaultDetails'));
    }

    public function penjualanposStore(Request $request, $id)
    {
        $penjualan = DB::table('penjualan_pos')->where('id', $id)->first();

        if (!$penjualan || ($penjualan->status ?? 'Draft') !== 'SUKSES') {
            return redirect()->route('jurnal-penjualanpos.index')->with('error', 'Transaksi POS ini belum di-approve.');
        }

        // --- VALIDASI ROLE / OUTLET AKSES ---
        $user = auth()->user();
        if ($user && $user->gudang_id && $penjualan->gudang_id != $user->gudang_id) {
            abort(403, 'Anda tidak memiliki akses ke data penjualan outlet lain.');
        }

        $request->validate([
            'tanggal'              => 'required|date',
            'no_ref'               => 'required|string',
            'deskripsi'            => 'required|string',
            'details'              => 'required|array|min:2',
            'details.*.account_id' => 'required|exists:chart_of_accounts,id',
            'details.*.debit'      => 'required|numeric|min:0',
            'details.*.kredit'     => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            // Cek Keseimbangan saldo form sebelum masuk database
            $totalDebit = 0;
            $totalKredit = 0;
            foreach ($request->details as $item) {
                $totalDebit += floatval($item['debit'] ?? 0);
                $totalKredit += floatval($item['kredit'] ?? 0);
            }

            if (round($totalDebit, 2) != round($totalKredit, 2)) {
                throw new \Exception("Total Debit dan Kredit tidak seimbang!");
            }

            // Simpan Header ke tabel jurnal_penjualan_pos
            $jurnalId = DB::table('jurnal_penjualan_pos')->insertGetId([
                'tanggal'     => $request->tanggal,
                'no_ref'      => $request->no_ref,
                'deskripsi'   => $request->deskripsi,
                'source_type' => 'penjualan_pos',
                'source_id'   => $penjualan->id, // Mengunci ID POS agar keluar dari antrean index atas
                'created_by'  => auth()->id() ?? 1,
            ]);

            // Simpan Detail baris jurnal secara dinamis dari form browser
            // [OPSI A] Setiap baris kini ikut menyimpan journal_type = 'jurnal_penjualan_pos'
            // supaya nantinya bisa dibedakan dari baris journal_items milik modul lain
            // (mis. jurnal_pembelian) walau journal_id-nya kebetulan bernilai sama.
            foreach ($request->details as $item) {
                if (floatval($item['debit']) == 0 && floatval($item['kredit']) == 0) {
                    continue;
                }

                DB::table('journal_items')->insert([
                    'journal_id'   => $jurnalId,
                    'journal_type' => 'jurnal_penjualan_pos', // [OPSI A]
                    'account_id'   => $item['account_id'],
                    'debit'        => $item['debit'],
                    'kredit'       => $item['kredit'],
                ]);
            }

            DB::commit();
            return redirect()
                ->route('jurnal-penjualanpos.index')
                ->with('success', 'Jurnal Penjualan POS No. Ref ' . $request->no_ref . ' berhasil dibukukan harian!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal memproses draf jurnal POS: ' . $e->getMessage())->withInput();
        }
    }

    public function penjualanposShow($id)
    {
        // 1. Ambil data induk/header jurnal khusus POS dari tabel jurnal_penjualan_pos
        $jurnal = DB::table('jurnal_penjualan_pos')
            ->where('id', $id)
            ->where('source_type', 'penjualan_pos') // Menyelaraskan token identitas asal pos
            ->first();

        if (!$jurnal) {
            return redirect()
                ->route('jurnal-penjualanpos.index')
                ->with('error', 'Data riwayat jurnal Penjualan POS tidak ditemukan.');
        }

        // --- VALIDASI ROLE / OUTLET AKSES ---
        $user = auth()->user();
        if ($user && $user->gudang_id) {
            $penjualan = DB::table('penjualan_pos')->where('id', $jurnal->source_id)->first();
            if ($penjualan && $penjualan->gudang_id != $user->gudang_id) {
                abort(403, 'Anda tidak memiliki akses ke data jurnal penjualan outlet lain.');
            }
        }

        // 2. Ambil rincian item debit/kredit yang terikat dengan ID jurnal POS ini
        // [OPSI A] Tambahan where journal_type mencegah baris journal_items milik
        // modul lain (mis. jurnal_pembelian) ikut tertarik hanya karena journal_id
        // mereka kebetulan sama dengan ID jurnal POS yang sedang dibuka ini.
        $details = DB::table('journal_items')
            ->leftJoin('chart_of_accounts', 'journal_items.account_id', '=', 'chart_of_accounts.id')
            ->where('journal_items.journal_id', $id)
            ->where('journal_items.journal_type', 'jurnal_penjualan_pos') // [OPSI A]
            ->select(
                'journal_items.debit',
                'journal_items.kredit',
                'chart_of_accounts.kode',
                'chart_of_accounts.nama'
            )
            ->orderBy('journal_items.debit', 'desc') // Mengurutkan akun Debit agar di atas demi estetika akuntansi
            ->get();

        // 3. Hitung total nilai saldo debit & kredit global untuk tfoot balance check
        $totalDebit = $details->sum('debit');
        $totalKredit = $details->sum('kredit');

        return view('jurnal-penjualanpos.show', compact('jurnal', 'details', 'totalDebit', 'totalKredit'));
    }

    public function penjualanb2bIndex()
    {
        // 1. Ambil ID Sumber yang sudah pernah dijurnal
        $pembayaranDijurnal = DB::table('jurnal_penjualan_b2b')->where('source_type', 'pembayaran')->pluck('source_id')->toArray();
        $pengirimanDijurnal = DB::table('jurnal_penjualan_b2b')->where('source_type', 'pengiriman')->pluck('source_id')->toArray();

        // 2. Antrean: Pembayaran Kas (Uang Muka / Pelunasan)
        $pembayaranBelum = \App\Models\Pembayaran::with(['pesanan.customer'])
            ->whereNotIn('id', $pembayaranDijurnal)
            ->get()
            ->map(function ($item) {
                $item->antrean_type = 'pembayaran';
                $item->label_antrean = 'Pembayaran Kas (DP/Pelunasan)';
                $item->tanggal_antrean = $item->tanggal_bayar;
                $item->no_transaksi = $item->pesanan->kode_pesanan ?? '-'; // Mengisi no_transaksi dengan kode pesanan
                $item->nominal_display = floatval($item->jumlah_bayar); // Angka murni untuk number_format
                return $item;
            });

        // 3. Antrean: Pengiriman Barang (Logistik & HPP)
        $pengirimanBelum = DB::table('pengiriman')
            ->join('pesanan', 'pengiriman.pesanan_id', '=', 'pesanan.id')
            ->join('customers', 'pesanan.customer_id', '=', 'customers.id')
            ->whereNotIn('pengiriman.id', $pengirimanDijurnal)
            ->select(
                'pengiriman.id',
                'pengiriman.no_pengiriman',
                'pengiriman.tanggal_pengiriman as tanggal_antrean',
                'pesanan.kode_pesanan',
                'pesanan.total_pesanan',
                'customers.nama as nama_customer'
            )
            ->get()
            ->map(function ($item) {
                $item->antrean_type = 'pengiriman';
                $item->label_antrean = 'Pengiriman Barang & HPP';
                $item->no_transaksi = $item->no_pengiriman; // Mengisi no_transaksi dengan nomor pengiriman surat jalan
                $item->nominal_display = floatval($item->total_pesanan); // Angka murni nilai kontrak B2B (DPP)
                return $item;
            });

        // Gabungkan kedua antrean dan urutkan berdasarkan tanggal terbaru
        $pesananBelum = $pembayaranBelum->concat($pengirimanBelum)->sortByDesc('tanggal_antrean');

        // 4. Ringkas data buku jurnal khusus penjualan B2B (Satu baris per No. Ref)
        $jurnalsSudah = DB::table('jurnal_penjualan_b2b')
            ->join('journal_items', function ($join) {
                $join->on('journal_items.journal_id', '=', 'jurnal_penjualan_b2b.id')
                    ->where('journal_type', '=', 'penjualan_b2b');
            })
            ->select(
                'jurnal_penjualan_b2b.id',
                'jurnal_penjualan_b2b.tanggal',
                'jurnal_penjualan_b2b.no_ref',
                'jurnal_penjualan_b2b.deskripsi',
                DB::raw('SUM(journal_items.debit) as total_debit'),
                DB::raw('SUM(journal_items.kredit) as total_kredit')
            )
            ->groupBy(
                'jurnal_penjualan_b2b.id',
                'jurnal_penjualan_b2b.tanggal',
                'jurnal_penjualan_b2b.no_ref',
                'jurnal_penjualan_b2b.deskripsi'
            )
            ->orderBy('jurnal_penjualan_b2b.tanggal', 'desc')
            ->get();

        return view('jurnal-penjualanb2b.index', compact('pesananBelum', 'jurnalsSudah'));
    }

    public function penjualanb2bCreate(Request $request, $id)
    {
        $type = $request->query('type', 'pembayaran');
        $coas = \App\Models\ChartOfAccount::orderBy('kode', 'asc')->get();

        // PENCATATAN ID COA SECARA DINAMIS BERDASARKAN KODE AKUN RESMI
        $idKasBank        = DB::table('chart_of_accounts')->where('kode', '1101')->value('id') ?? 1;
        $idUangMuka       = DB::table('chart_of_accounts')->where('kode', '2102')->value('id') ?? 2;
        $idPendapatan     = DB::table('chart_of_accounts')->where('kode', '4102')->value('id') ?? 4;
        $idPpnKeluaran    = DB::table('chart_of_accounts')->where('kode', '2201')->value('id') ?? 6;
        $idPersediaanJadi = DB::table('chart_of_accounts')->where('kode', '1301')->value('id') ?? 3;
        $idHPP            = DB::table('chart_of_accounts')->where('kode', '5102')->value('id') ?? 5;

        $defaultDetails = [];
        $pembayaran = null;
        $pengiriman = null;

        // PINTU LOGIKAA 1: JURNAL ALIRAN KAS MASUK (DP ATAU PELUNASAN KEDUA)
        if ($type === 'pembayaran') {
            $pembayaran = \App\Models\Pembayaran::with(['pesanan.customer'])->findOrFail($id);

            if (\App\Models\Journal::isPeriodClosed($pembayaran->tanggal_bayar ?? $pembayaran->tanggal ?? null)) {
                return redirect()->route('jurnal-penjualanb2b.index')->with('error', 'Periode akuntansi tanggal ' . date('d/m/Y', strtotime($pembayaran->tanggal_bayar ?? $pembayaran->tanggal)) . ' sudah ditutup buku. Tidak dapat memproses jurnal B2B pada periode yang sudah ditutup.');
            }

            $pesanan = $pembayaran->pesanan;

            if (!$pesanan) {
                return back()->with('error', 'Data induk pesanan untuk pembayaran ini tidak ditemukan.');
            }

            // PERBAIKAN 1: Ambil status pesanan secara eksplisit di Pintu Logika 1
            $statusPesanan = $pesanan->status_pesanan;

            $totalBayarSebelumnya = DB::table('pembayaran')
                ->where('pesanan_id', $pesanan->id)
                ->where('id', '<', $pembayaran->id)
                ->sum('jumlah_bayar');

            // Nilai dari tabel pembayaran diperlakukan sebagai DPP bersih (Belum PPN)
            $dppCurrent   = floatval($pembayaran->jumlah_bayar);
            $ppnCurrent   = round($dppCurrent * 0.10, 2);
            $totalKasBank = round($dppCurrent + $ppnCurrent, 2);

            $totalDppPenjualan = floatval($pesanan->total_pesanan);

            // DETEKSI KONDISI 3: Bayar Langsung Lunas 100% di Awal (Belum Dikirim)
            if ($totalBayarSebelumnya == 0 && abs($dppCurrent - $totalDppPenjualan) <= 0.05) {
                $defaultDetails = [
                    ['account_id' => $idKasBank, 'debit' => $totalKasBank, 'kredit' => 0],
                    ['account_id' => $idUangMuka, 'debit' => 0, 'kredit' => $dppCurrent],
                    ['account_id' => $idPpnKeluaran, 'debit' => 0, 'kredit' => $ppnCurrent]
                ];
            }
            // DETEKSI KONDISI 1: Pembayaran Uang Muka (DP) Berkala atau Angsuran Kedua
            else {
                $defaultDetails = [
                    ['account_id' => $idKasBank, 'debit' => $totalKasBank, 'kredit' => 0],
                    ['account_id' => $idUangMuka, 'debit' => 0, 'kredit' => $dppCurrent],
                    ['account_id' => $idPpnKeluaran, 'debit' => 0, 'kredit' => $ppnCurrent]
                ];
            }

            // PERBAIKAN 2: Pastikan variabel 'statusPesanan' ikut dilempar ke view
            return view('jurnal-penjualanb2b.create', compact('pembayaran', 'pesanan', 'coas', 'defaultDetails', 'type', 'statusPesanan'));
        }

        // PINTU LOGIKAA 2: JURNAL AKHIR PENGIRIMAN BARANG (PENGAKUAN OMZET & POTONG GUDANG HPP)
        else {
            $pengiriman = DB::table('pengiriman')->where('id', $id)->first();
            if (!$pengiriman) {
                return back()->with('error', 'Data pengiriman tidak ditemukan.');
            }

            if (\App\Models\Journal::isPeriodClosed($pengiriman->tanggal_pengiriman ?? $pengiriman->tanggal ?? null)) {
                return redirect()->route('jurnal-penjualanb2b.index')->with('error', 'Periode akuntansi tanggal ' . date('d/m/Y', strtotime($pengiriman->tanggal_pengiriman ?? $pengiriman->tanggal)) . ' sudah ditutup buku. Tidak dapat memproses jurnal B2B pada periode yang sudah ditutup.');
            }

            $pesanan = \App\Models\Pesanan::with('customer')->findOrFail($pengiriman->pesanan_id);

            // PERBAIKAN 3: Ambil status pesanan secara eksplisit di Pintu Logika 2
            $statusPesanan = $pesanan->status_pesanan;

            // Ambil biaya akumulasi HPP riil dari sistem manufaktur alokasi produksi
            $totalHppRiil = DB::table('alokasi_produksi_pesanan')->where('pesanan_id', $pesanan->id)->sum('total_hpp_alokasi') ?? 0;
            if ($totalHppRiil == 0) {
                $totalHppRiil = round(floatval($pesanan->total_pesanan) * 0.75, 2);
            }

            $totalDppPenjualan = floatval($pesanan->total_pesanan);

            // 1. Pengakuan Omzet komersial bersih: Balik Uang Muka Penjualan (Debet) vs Penjualan B2B (Kredit)
            $defaultDetails[] = ['account_id' => $idUangMuka, 'debit' => $totalDppPenjualan, 'kredit' => 0];
            $defaultDetails[] = ['account_id' => $idPendapatan, 'debit' => 0, 'kredit' => $totalDppPenjualan];

            // 2. Pengeluaran Fisik Barang: HPP Bertambah (Debet) vs Persediaan Barang Jadi Berkurang (Kredit)
            $defaultDetails[] = ['account_id' => $idHPP, 'debit' => $totalHppRiil, 'kredit' => 0];
            $defaultDetails[] = ['account_id' => $idPersediaanJadi, 'debit' => 0, 'kredit' => $totalHppRiil];

            // PERBAIKAN 4: Pastikan variabel 'statusPesanan' ikut dilempar ke view
            return view('jurnal-penjualanb2b.create', compact('pengiriman', 'pesanan', 'coas', 'defaultDetails', 'type', 'statusPesanan'));
        }
    }

    public function penjualanb2bStore(Request $request, $id)
    {
        $request->validate([
            'tanggal'              => 'required|date',
            'no_ref'               => 'required|string',
            'deskripsi'            => 'required|string',
            'source_type'          => 'required|in:pembayaran,pengiriman',
            'details'              => 'required|array|min:2',
            'details.*.account_id' => 'required|exists:chart_of_accounts,id',
            'details.*.debit'      => 'required|numeric|min:0',
            'details.*.kredit'     => 'required|numeric|min:0',
        ]);

        if (\App\Models\Journal::isPeriodClosed($request->tanggal)) {
            return back()->with('error', 'Periode akuntansi tanggal ' . date('d/m/Y', strtotime($request->tanggal)) . ' sudah ditutup buku. Tidak dapat menyimpan Jurnal Penjualan B2B pada periode yang sudah ditutup.')->withInput();
        }

        try {
            DB::beginTransaction();

            $totalDebit = 0;
            $totalKredit = 0;
            foreach ($request->details as $item) {
                $totalDebit += floatval($item['debit'] ?? 0);
                $totalKredit += floatval($item['kredit'] ?? 0);
            }

            if (abs(round($totalDebit, 2) - round($totalKredit, 2)) > 0.01) {
                throw new \Exception("Total Debit dan Kredit tidak seimbang!");
            }

            $jurnalsId = DB::table('jurnal_penjualan_b2b')->insertGetId([
                'tanggal'     => $request->tanggal,
                'no_ref'      => $request->no_ref,
                'deskripsi'   => $request->deskripsi,
                'source_type' => $request->source_type,
                'source_id'   => $id,
                'created_by'  => auth()->id() ?? 1,
            ]);

            foreach ($request->details as $item) {
                if (floatval($item['debit']) == 0 && floatval($item['kredit']) == 0) {
                    continue;
                }

                DB::table('journal_items')->insert([
                    'journal_id'   => $jurnalsId,
                    'journal_type' => 'penjualan_b2b',
                    'account_id'   => $item['account_id'],
                    'debit'        => $item['debit'],
                    'kredit'       => $item['kredit'],
                ]);
            }

            DB::commit();
            return redirect()
                ->route('jurnal-penjualanb2b.index')
                ->with('success', 'Transaksi No. Ref ' . $request->no_ref . ' berhasil dibukukan!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal memproses simpan jurnal: ' . $e->getMessage())->withInput();
        }
    }

    public function penjualanb2bShow($id)
    {
        $jurnal = DB::table('jurnal_penjualan_b2b')->where('id', $id)->first();

        if (!$jurnal) {
            return redirect()->route('jurnal-penjualanb2b.index')->with('error', 'Data riwayat jurnal B2B tidak ditemukan.');
        }

        $details = DB::table('journal_items')
            ->leftJoin('chart_of_accounts', 'journal_items.account_id', '=', 'chart_of_accounts.id')
            ->where('journal_items.journal_id', $id)
            ->where('journal_items.journal_type', 'penjualan_b2b')
            ->select('journal_items.debit', 'journal_items.kredit', 'chart_of_accounts.kode', 'chart_of_accounts.nama')
            ->orderBy('journal_items.debit', 'desc')
            ->get();

        $totalDebit = $details->sum('debit');
        $totalKredit = $details->sum('kredit');

        return view('jurnal-penjualanb2b.show', compact('jurnal', 'details', 'totalDebit', 'totalKredit'));
    }

    public function bukuPembantuUangMuka()
    {
        // Berdasarkan fungsi pembelianCreate, ID Uang Muka Pembelian dicari dinamis menggunakan kode 1202
        $idUangMukaPemb = DB::table('chart_of_accounts')->where('kode', '1202')->value('id') ?? 7;

        $bukuPembantuUangMuka = DB::table('pembelian')
            ->join('suppliers', 'pembelian.supplier_id', '=', 'suppliers.id')
            // Hubungkan ke header jurnal pembelian
            ->leftJoin('jurnal_pembelian', function ($join) {
                $join->on('jurnal_pembelian.source_id', '=', 'pembelian.id')
                    ->where('jurnal_pembelian.source_type', '=', 'pembelian');
            })
            // Hubungkan ke detail item jurnal dengan filter tipe polimorfik
            ->leftJoin('journal_items', function ($join) {
                $join->on('journal_items.journal_id', '=', 'jurnal_pembelian.id')
                    ->where('journal_items.journal_type', '=', 'jurnal_pembelian');
            })
            ->select(
                'suppliers.nama as nama_supplier',
                'pembelian.kode_pembelian',
                'pembelian.tanggal as tanggal_transaksi',
                // Uang muka bertambah di sisi DEBIT (saat Tahap: dp, pelunasan, gabungan)
                DB::raw("SUM(CASE WHEN journal_items.account_id = {$idUangMukaPemb} THEN journal_items.debit ELSE 0 END) as total_uang_muka_keluar"),
                // Uang muka berkurang/dibersihkan di sisi KREDIT (saat Tahap: reklas_lunas, gabungan)
                DB::raw("SUM(CASE WHEN journal_items.account_id = {$idUangMukaPemb} THEN journal_items.kredit ELSE 0 END) as total_uang_muka_direklas")
            )
            ->groupBy('suppliers.nama', 'pembelian.kode_pembelian', 'pembelian.tanggal')
            ->orderBy('suppliers.nama')
            ->orderBy('pembelian.tanggal')
            ->get();

        // Mengubah nama file view agar lebih relevan dengan konteks Uang Muka
        return view('bukupembantu-uangmuka.index', compact('bukuPembantuUangMuka'));
    }
}
