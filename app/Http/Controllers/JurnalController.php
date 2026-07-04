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

            // Simpan Header dengan source_type menjadi 'jurnal_umum'
            $jurnal = Journal::create([
                'tanggal' => $request->tanggal,
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
            return redirect()->route('jurnal.index')->with('success', 'Jurnal berhasil disimpan!');
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

        $bulan = $request->bulan;
        $tahun = $request->tahun;

        try {
            DB::beginTransaction();

            // 1. Validasi apakah periode ini sudah pernah dyclosing sebelumnya
            $alreadyClosed = Journal::where('source_type', 'closing')
                ->whereMonth('tanggal', $bulan)
                ->whereYear('tanggal', $tahun)
                ->exists();

            if ($alreadyClosed) {
                return back()->withErrors(['error' => 'Periode akuntansi ini sudah ditutup sebelumnya!']);
            }

            // 2. Ambil Akun Laba Ditahan (Muara Akhir Ekuitas)
            $labaDitahan = ChartOfAccount::where('kode', '3-9999')->firstOrFail();
            $tanggalClosing = date("Y-m-t", strtotime("$tahun-$bulan-01"));

            // 3. Buat Header Jurnal Penutup di tabel 'journals' (Meminjam Header)
            $journal = Journal::create([
                'tanggal'     => $tanggalClosing,
                'deskripsi'   => "Jurnal Penutup Periode " . date('F', mktime(0, 0, 0, $bulan, 1)) . " $tahun",
                'no_ref'      => 'CLS-' . time(),
                'source_type' => 'closing', // Papan nama pemisah
                'source_id'   => 0,
                'status'      => 'approved', // Langsung approved karena closing otomatis oleh sistem
                'created_by'  => Auth::id(),
            ]);

            $totalPendapatan = 0;
            $totalHppDanBeban = 0;
            $itemsToInsert = [];

            // 4. Hitung Saldo Pendapatan (Kepala 4) -> Filter mengecualikan 'closing' terdahulu
            $pendapatans = ChartOfAccount::where('kode', 'like', '4%')->get();
            foreach ($pendapatans as $coa) {
                $saldo = DB::table('journal_items')
                    ->join('journals', 'journal_items.journal_id', '=', 'journals.id')
                    ->where('journal_items.account_id', $coa->id)
                    ->whereMonth('journals.tanggal', $bulan)
                    ->whereYear('journals.tanggal', $tahun)
                    ->where('journal_items.journal_type', '!=', 'closing') // Filter krusial
                    ->select(DB::raw('SUM(kredit - debit) as neto'))
                    ->first()->neto ?? 0;

                if ($saldo != 0) {
                    $itemsToInsert[] = [
                        'journal_id'   => $journal->id,
                        'account_id'   => $coa->id,
                        'journal_type' => 'closing', // Menampung di journal_items bertipe closing
                        'debit'        => $saldo,
                        'kredit'       => 0,
                        'created_at'   => now(),
                        'updated_at'   => now()
                    ];
                    $totalPendapatan += $saldo;
                }
            }

            // 5. Hitung Saldo HPP (Kepala 5) & Beban (Kepala 6)
            $hppDanBebans = ChartOfAccount::where('kode', 'like', '5%')
                ->orWhere('kode', 'like', '6%')
                ->get();

            foreach ($hppDanBebans as $coa) {
                $saldo = DB::table('journal_items')
                    ->join('journals', 'journal_items.journal_id', '=', 'journals.id')
                    ->where('journal_items.account_id', $coa->id)
                    ->whereMonth('journals.tanggal', $bulan)
                    ->whereYear('journals.tanggal', $tahun)
                    ->where('journal_items.journal_type', '!=', 'closing')
                    ->select(DB::raw('SUM(debit - kredit) as neto'))
                    ->first()->neto ?? 0;

                if ($saldo != 0) {
                    $itemsToInsert[] = [
                        'journal_id'   => $journal->id,
                        'account_id'   => $coa->id,
                        'journal_type' => 'closing', // Menampung di journal_items bertipe closing
                        'debit'        => 0,
                        'kredit'       => $saldo,
                        'created_at'   => now(),
                        'updated_at'   => now()
                    ];
                    $totalHppDanBeban += $saldo;
                }
            }

            // 6. Hitung Laba/Rugi Bersih & Tembak ke Laba Ditahan
            $labaBersih = $totalPendapatan - $totalHppDanBeban;

            if ($labaBersih != 0) {
                $itemsToInsert[] = [
                    'journal_id'   => $journal->id,
                    'account_id'   => $labaDitahan->id,
                    'journal_type' => 'closing',
                    'debit'        => $labaBersih < 0 ? abs($labaBersih) : 0,
                    'kredit'       => $labaBersih > 0 ? $labaBersih : 0,
                    'created_at'   => now(),
                    'updated_at'   => now()
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
            // Tambahkan validasi action untuk menangkap tombol draft/post
            'action' => 'required|in:draft,post',
        ]);

        // Cek Periode Closing (Tetap mengecek ke tabel utama Journal)
        $isClosed = Journal::where('source_type', 'closing')
            ->whereMonth('tanggal', date('m', strtotime($request->tanggal)))
            ->whereYear('tanggal', date('Y', strtotime($request->tanggal)))
            ->exists();

        if ($isClosed) {
            return back()->with('error', 'Gagal! Periode ini sudah dikunci (Closing).')->withInput();
        }

        try {
            DB::beginTransaction();

            // 1. Simpan Header ke tabel: jurnal_penyesuaian dengan penambahan kolom status
            $jurnal = JurnalPenyesuaian::create([
                'tanggal'     => $request->tanggal,
                'deskripsi'   => "[AJP] " . $request->deskripsi,
                'no_ref'      => 'AJP-' . date('Ymd') . '-' . time(),
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

        $pembeliansBelum = $semuaPembelian->filter(function ($p) use ($tahapTersimpan) {
            $tahapSeharusnya = $this->tahapSeharusnyaAda($p);
            $sudahAda        = $tahapTersimpan->get($p->id, []);
            $tahapKurang     = array_diff($tahapSeharusnya, $sudahAda);

            return count($tahapKurang) > 0;
        })->values();

        $pembeliansBelum = $pembeliansBelum->map(function ($p) use ($tahapTersimpan) {
            $tahapSeharusnya     = $this->tahapSeharusnyaAda($p);
            $sudahAda            = $tahapTersimpan->get($p->id, []);
            $tahapKurang         = array_diff($tahapSeharusnya, $sudahAda);

            // Mengambil string murni elemen pertama dari sisa tahapan
            $p->tahap_selanjutnya = array_values($tahapKurang)[0] ?? null;

            // PERBAIKAN: Mengirimkan properti tahap_selanjutnya sebagai argumen kedua
            $p->total_keluar = $this->hitungTotalKeluar($p, $p->tahap_selanjutnya);

            return $p;
        });

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

            if (!$isDiterima) {
                if ($isLunas) {
                    $tahap[] = 'pelunasan';
                }
            } else {
                if ($isLunas) {
                    $tahap[] = 'pelunasan';
                    $tahap[] = 'reklas_lunas';
                } else {
                    $tahap[] = 'gabungan';
                }
            }
        } else {
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

    public function pembelianCreate($id)
    {
        $pembelian = \App\Models\Pembelian::with(['supplier'])->findOrFail($id);
        $coas = \App\Models\ChartOfAccount::orderBy('kode', 'asc')->get();

        $idKasBank        = 1;
        $idPersediaanBB   = 6;
        $idUangMukaPemb   = 7;
        $idPPNMasukan     = 8;

        $tarifPpn = 0.10;
        $defaultDetails = [];
        $tahap          = null;

        $dppTotal     = floatval($pembelian->total);
        $ppnTotal     = round($dppTotal * $tarifPpn, 0);
        $totalKontrak = $dppTotal + $ppnTotal;

        $persenDP   = floatval($pembelian->persen_dp ?? 0);
        $isDiterima = (bool) $pembelian->is_diterima;
        $isLunas    = (bool) $pembelian->is_lunas;

        $nominalDP        = floatval($pembelian->nominal_dp ?? ($totalKontrak * ($persenDP / 100)));
        $nominalPelunasan = $totalKontrak - $nominalDP;

        $tahapTersimpan = DB::table('jurnal_pembelian')
            ->where('source_type', 'pembelian')
            ->where('source_id', $pembelian->id)
            ->pluck('tahap')
            ->filter()
            ->toArray();

        $sudahAdaPelunasan = in_array('pelunasan', $tahapTersimpan);

        if (!$isDiterima) {
            if ($isLunas) {
                $tahap = 'pelunasan';
                $defaultDetails = [
                    ['account_id' => $idUangMukaPemb, 'debit' => $nominalPelunasan, 'kredit' => 0],
                    ['account_id' => $idKasBank, 'debit' => 0, 'kredit' => $nominalPelunasan]
                ];
            } else {
                $tahap = 'dp';
                $defaultDetails = [
                    ['account_id' => $idUangMukaPemb, 'debit' => $nominalDP, 'kredit' => 0],
                    ['account_id' => $idKasBank, 'debit' => 0, 'kredit' => $nominalDP]
                ];
            }
        } else {
            if ($isLunas && $sudahAdaPelunasan) {
                $tahap = 'reklas_lunas';
                $defaultDetails = [
                    ['account_id' => $idPersediaanBB, 'debit' => $dppTotal, 'kredit' => 0],
                    ['account_id' => $idPPNMasukan, 'debit' => $ppnTotal, 'kredit' => 0],
                    ['account_id' => $idUangMukaPemb, 'debit' => 0, 'kredit' => $totalKontrak]
                ];
            } elseif ($persenDP > 0 && $isLunas && !$sudahAdaPelunasan) {
                $tahap = 'gabungan';
                $defaultDetails = [
                    ['account_id' => $idPersediaanBB, 'debit' => $dppTotal, 'kredit' => 0],
                    ['account_id' => $idPPNMasukan, 'debit' => $ppnTotal, 'kredit' => 0],
                    ['account_id' => $idUangMukaPemb, 'debit' => 0, 'kredit' => $nominalDP],
                    ['account_id' => $idKasBank, 'debit' => 0, 'kredit' => $nominalPelunasan]
                ];
            } else {
                $tahap = 'cod';
                $defaultDetails = [
                    ['account_id' => $idPersediaanBB, 'debit' => $dppTotal, 'kredit' => 0],
                    ['account_id' => $idPPNMasukan, 'debit' => $ppnTotal, 'kredit' => 0],
                    ['account_id' => $idKasBank, 'debit' => 0, 'kredit' => $totalKontrak]
                ];
            }
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
        // 1. Ambil semua ID transaksi POS yang sudah pernah dijurnal
        $sudahDijurnal = DB::table('jurnal_penjualan_pos')
            ->where('source_type', 'penjualan_pos')
            ->pluck('source_id')
            ->toArray();

        // 2. Antrean Atas: Tarik data transaksi POS harian yang BELUM dijurnal
        $penjualanPosBelum = DB::table('penjualan_pos')
            ->whereNotIn('id', $sudahDijurnal)
            ->select(
                'id',
                'tanggal',
                'kode_transaksi',
                'total',
                DB::raw("'Gudang' as nama_outlet")
            )
            ->orderBy('tanggal', 'desc')
            ->get();

        // 3. Riwayat Bawah: Ringkas menjadi satu baris per dokumen (Group By No. Ref)
        // [OPSI A] Join ke journal_items kini memakai closure agar bisa menyaring
        // journal_type sekaligus di kondisi ON, sehingga baris journal_items milik
        // modul lain (mis. jurnal_pembelian) yang journal_id-nya kebetulan sama
        // TIDAK ikut tertarik dan tidak ikut ter-SUM ke total debit/kredit di sini.
        $jurnalsSudah = DB::table('jurnal_penjualan_pos')
            ->join('journal_items', function ($join) {
                $join->on('journal_items.journal_id', '=', 'jurnal_penjualan_pos.id')
                    ->where('journal_items.journal_type', '=', 'jurnal_penjualan_pos'); // [OPSI A]
            })
            ->where('jurnal_penjualan_pos.source_type', 'penjualan_pos')
            ->select(
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

        // 2. Ambil master data COA untuk dropdown penyesuaian di form view
        $coas = \App\Models\ChartOfAccount::orderBy('kode', 'asc')->get();

        // 3. Hitung akumulasi nilai total HPP riil dari tabel detail POS
        $totalHppRiil = DB::table('penjualanpos_detail')
            ->where('penjualan_id', $id)
            ->selectRaw('SUM(qty * hpp_satuan) as total_hpp')
            ->value('total_hpp') ?? 0;

        // ===================================================================
        // ID COA MODUL PENJUALAN POS (DENGAN TAMBAHAN PPN KELUARAN)
        // PENTING: ID di bawah ini BELUM diverifikasi ulang terhadap tabel
        // chart_of_accounts (lihat catatan sebelumnya soal kemungkinan ID 1
        // dipakai dobel untuk $idKasOutlet dan $idPersediaanJadi, serta ID 6
        // yang juga dipakai modul pembelian untuk akun berbeda). Opsi A yang
        // diterapkan di file ini HANYA memperbaiki percampuran data di
        // Index/Show, BUKAN memperbaiki ID akun COA ini. Mohon dicek manual
        // ke tabel chart_of_accounts sebelum deploy ke production.
        // ===================================================================
        $idKasOutlet      = 1;  // ID Akun Kas/Bank BRI (Debit)
        $idHppPos         = 5;  // ID Akun Harga Pokok Penjualan POS (Debit)
        $idPenjualanPos   = 4;  // ID Akun Penjualan POS (Kredit)
        $idPpnKeluaran    = 6;  // TENTUKAN: Isi dengan ID Akun Utang PPN / PPN Keluaran milikmu
        $idPersediaanJadi = 1;  // ID Akun Persediaan Barang Jadi (Kredit)
        // ===================================================================

        // Logika Pajak: Penjualan belum termasuk PPN, maka hitung nilai PPN di sini
        $nilaiPenjualan = floatval($penjualan->total);
        $tarifPpn       = 0.10; // Sesuaikan dengan tarif PPN yang berlaku (misal 11% atau 12%)
        $nilaiPpn       = $nilaiPenjualan * $tarifPpn;
        $totalKasMasuk  = $nilaiPenjualan + $nilaiPpn;

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
        $idKasBank        = DB::table('chart_of_accounts')->where('kode', '1-1101')->value('id') ?? 1;
        $idUangMuka       = DB::table('chart_of_accounts')->where('kode', '2-2102')->value('id') ?? 2;
        $idPendapatan     = DB::table('chart_of_accounts')->where('kode', '3-3302')->value('id') ?? 4;
        $idPpnKeluaran    = DB::table('chart_of_accounts')->where('kode', '2-2201')->value('id') ?? 6;
        $idPersediaanJadi = DB::table('chart_of_accounts')->where('kode', '1-1303')->value('id') ?? 3;
        $idHPP            = DB::table('chart_of_accounts')->where('kode', '5-5102')->value('id') ?? 5;

        $defaultDetails = [];
        $pembayaran = null;
        $pengiriman = null;

        // PINTU LOGIKAA 1: JURNAL ALIRAN KAS MASUK (DP ATAU PELUNASAN KEDUA)
        if ($type === 'pembayaran') {
            $pembayaran = \App\Models\Pembayaran::with(['pesanan.customer'])->findOrFail($id);
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
        // Berdasarkan fungsi pembelianCreate, ID Uang Muka Pembelian adalah 7
        $idUangMukaPemb = 7;

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
