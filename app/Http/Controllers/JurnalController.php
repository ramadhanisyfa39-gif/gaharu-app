<?php

namespace App\Http\Controllers;

use App\Models\Journal;
use App\Models\ChartOfAccount;
use App\Models\JournalItem;
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
                $pendapatans = ChartOfAccount::where('kode', 'like', '4%')->get();
                foreach ($pendapatans as $coa) {
                    // Query diarahkan ke JournalItem, bukan Journal
                    $saldo = DB::table('journal_items')
                        ->join('journals', 'journal_items(--taruh_foreign_key_disini_jika_bukan_journal_id--)_journal_id', '=', 'journals.id')
                        ->where('journal_items.account_id', $coa->id)
                        ->whereMonth('journals.tanggal', $bulan)
                        ->whereYear('journals.tanggal', $tahun)
                        ->select(DB::raw('SUM(kredit - debit) as neto'))
                        ->first()->neto ?? 0;

                    if ($saldo != 0) {
                        // Simpan detail item jurnal penutup untuk menolkan akun pendapatan
                        DB::table('journal_items')->insert([
                            'journal_id' => $journal->id,
                            'account_id' => $coa->id,
                            'debit'      => $saldo,
                            'kredit'     => 0,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                        $totalPendapatan += $saldo;
                    }
                }

                // 4. Ambil Saldo Beban (Debit - Kredit)
                $bebans = ChartOfAccount::where('kode', 'like', '5%')->get();
                foreach ($bebans as $coa) {
                    $saldo = DB::table('journal_items')
                        ->join('journals', 'journal_items.journal_id', '=', 'journals.id')
                        ->where('journal_items.account_id', $coa->id)
                        ->whereMonth('journals.tanggal', $bulan)
                        ->whereYear('journals.tanggal', $tahun)
                        ->select(DB::raw('SUM(debit - kredit) as neto'))
                        ->first()->neto ?? 0;

                    if ($saldo != 0) {
                        // Simpan detail item jurnal penutup untuk menolkan akun beban
                        DB::table('journal_items')->insert([
                            'journal_id' => $journal->id,
                            'account_id' => $coa->id,
                            'debit'      => 0,
                            'kredit'     => $saldo,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                        $totalBeban += $saldo;
                    }
                }
                $journal->load('details');

                // 5. Tutup ke Ikhtisar & Laba Ditahan
                $labaBersih = $totalPendapatan - $totalBeban;

                if ($totalPendapatan != 0 || $totalBeban != 0) {

                    // A. Masukkan total Pendapatan ke Kredit Ikhtisar
                    DB::table('journal_items')->insert([
                        'journal_id' => $journal->id,
                        'account_id' => $ikhtisar->id,
                        'debit'      => 0,
                        'kredit'     => $totalPendapatan,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);

                    // B. Masukkan total Beban ke Debit Ikhtisar
                    DB::table('journal_items')->insert([
                        'journal_id' => $journal->id,
                        'account_id' => $ikhtisar->id,
                        'debit'      => $totalBeban,
                        'kredit'     => 0,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }

                // C. Pindahkan saldo Ikhtisar (Laba Bersih) ke Laba Ditahan
                // Jika Laba: Debit Ikhtisar, Kredit Laba Ditahan
                // Jika Rugi: Kredit Ikhtisar, Debit Laba Ditahan
                if ($labaBersih != 0) {
                    // Baris Ikhtisar untuk meng-nolkan dirinya sendiri
                    DB::table('journal_items')->insert([
                        'journal_id' => $journal->id,
                        'account_id' => $ikhtisar->id,
                        'debit'      => $labaBersih > 0 ? $labaBersih : 0,
                        'kredit'     => $labaBersih < 0 ? abs($labaBersih) : 0,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);

                    // Pindahkan ke laba ditahan
                    DB::table('journal_items')->insert([
                        'journal_id' => $journal->id,
                        'account_id' => $labaDitahan->id,
                        'debit'      => $labaBersih < 0 ? abs($labaBersih) : 0,
                        'kredit'     => $labaBersih > 0 ? $labaBersih : 0,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
                return redirect()->route('closing.index')->with('success', 'Periode berhasil ditutup!');
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

    public function pembelianIndex()
    {
        // 1. Ambil semua ID Pembelian yang sudah pernah dijurnal
        $sudahDijurnal = DB::table('jurnal_pembelian')
            ->where('source_type', 'pembelian')
            ->pluck('source_id')
            ->toArray();

        // 2. Data untuk TABEL ATAS: Invoice yang BELUM dijurnal
        $pembeliansBelum = Pembelian::with(['supplier', 'gudang'])
            ->whereNotIn('id', $sudahDijurnal)
            ->orderBy('tanggal', 'desc')
            ->get();

        // 3. Data untuk TABEL BAWAH: Hubungkan jurnal_pembelian -> journal_items -> pembelian
        $jurnalsSudah = DB::table('jurnal_pembelian')
            ->join('journal_items', 'journal_items.journal_id', '=', 'jurnal_pembelian.id')
            ->join('pembelian', 'jurnal_pembelian.source_id', '=', 'pembelian.id')
            ->leftJoin('chart_of_accounts', 'journal_items.account_id', '=', 'chart_of_accounts.id')
            ->where('jurnal_pembelian.source_type', 'pembelian') // Filter hanya data bertipe pembelian
            ->select(
                'jurnal_pembelian.tanggal',
                'jurnal_pembelian.no_ref',
                'jurnal_pembelian.deskripsi',
                'journal_items.debit',
                'journal_items.kredit',
                'chart_of_accounts.nama as nama',
                'chart_of_accounts.kode as kode',
                'pembelian.total as total_belanja'
            )
            ->orderBy('jurnal_pembelian.tanggal', 'desc')
            ->get();

        return view('jurnal-pembelian.index', compact('pembeliansBelum', 'jurnalsSudah'));
    }

    /*
    |--------------------------------------------------------------------------
    | 2. STORE LOGIC: EKSEKUSI JURNAL BERDASARKAN PILIHAN USER
    |--------------------------------------------------------------------------
    */
    public function prosesJurnalPembelian(Request $request, $id)
    {
        // 1. Validasi data form
        $request->validate([
            'tanggal'              => 'required|date',
            'deskripsi'            => 'required|string',
            'no_ref'               => 'nullable|string',
            'details'              => 'required|array|min:2',
            'details.*.account_id' => 'required|exists:chart_of_accounts,id',
            'details.*.debit'      => 'required|numeric|min:0',
            'details.*.kredit'     => 'required|numeric|min:0',
        ]);

        $pembelian = Pembelian::findOrFail($id);

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
                'created_by'  => Auth::id() ?? 1,
            ]);

            // 3. Simpan baris detail debit/kredit ke tabel journal_items
            foreach ($request->details as $item) {
                if ($item['debit'] == 0 && $item['kredit'] == 0) {
                    continue;
                }

                DB::table('journal_items')->insert([
                    'journal_id' => $jurnalPembelianId, // Sekarang aman diisi ID dari jurnal_pembelian karena FK sudah dilepas
                    'account_id' => $item['account_id'],
                    'debit'      => $item['debit'],
                    'kredit'     => $item['kredit'],
                ]);
            }

            DB::commit();
            return redirect()
                ->route('laporan.jurnal-pembelian.index')
                ->with('success', 'Invoice ' . $pembelian->kode_pembelian . ' berhasil disimpan ke Jurnal Pembelian!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    public function pembelianCreate($id)
    {
        // 1. Ambil data master transaksi pembelian beserta relasi suppliernya
        $pembelian = \App\Models\Pembelian::with(['supplier'])->findOrFail($id);

        // 2. Ambil semua daftar akun COA untuk dropdown penyesuaian jika akuntan mau edit akun
        $coas = \App\Models\ChartOfAccount::orderBy('kode', 'asc')->get();

        // 3. Tentukan ID default Chart of Account (COA) sesuai master data di database-mu
        $idPersediaanBahan  = 4;  // Contoh ID COA: Persediaan Bahan Baku
        $idUtangUsaha       = 7;  // Contoh ID COA: Utang Usaha (jika pembelian kredit)

        // 4. Susun struktur baris jurnal otomatis seimbang (Debit & Kredit)
        // Nilai nominal diambil langsung dari kolom total di tabel pembelian kamu
        $defaultDetails = [
            ['account_id' => $idPersediaanBahan, 'debit' => $pembelian->total, 'kredit' => 0],
            ['account_id' => $idUtangUsaha, 'debit' => 0, 'kredit' => $pembelian->total]
        ];

        // 5. Kirim data ke view jurnal-pembelian/create dengan aman
        return view('jurnal-pembelian.create', compact('pembelian', 'coas', 'defaultDetails'));
    }

    /*
    |--------------------------------------------------------------------------
    | 1. INDEKS ANTREAN JURNAL PENJUALAN POS
    |--------------------------------------------------------------------------
    */
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
                DB::raw("'Outlet Utama' as nama_outlet")
            )
            ->orderBy('tanggal', 'desc')
            ->get();

        // 3. Riwayat Bawah: Ringkas menjadi satu baris per dokumen (Group By No. Ref)
        $jurnalsSudah = DB::table('jurnal_penjualan_pos')
            ->join('journal_items', 'journal_items.journal_id', '=', 'jurnal_penjualan_pos.id')
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

    public function penjualanposCreate($id)
    {
        // 1. Ambil data induk penjualan POS berdasarkan ID antrean
        $penjualan = DB::table('penjualan_pos')->where('id', $id)->first();

        if (!$penjualan) {
            return back()->with('error', 'Data induk penjualan POS tidak ditemukan.');
        }

        // 2. Ambil master data COA untuk dropdown penyesuaian di form view
        $coas = \App\Models\ChartOfAccount::orderBy('kode', 'asc')->get();

        // 3. SOLUSI PRESTASI: Hitung akumulasi nilai total HPP riil dari tabel detail POS
        $totalHppRiil = DB::table('penjualanpos_detail')
            ->where('penjualan_id', $id)
            ->selectRaw('SUM(qty * hpp_satuan) as total_hpp')
            ->value('total_hpp') ?? 0;

        // ===================================================================
        // ID COA DUMMY UNTUK MODUL PENJUALAN POS (Sesuai Pertanyaan Kedua)
        // ===================================================================
        $idKasOutlet      = 1;  // ID Dummy Akun Kas/Bank (Debit)
        $idHppPos         = 5; // ID Dummy Akun Harga Pokok Penjualan POS (Debit)
        $idPenjualanPos   = 4;  // ID Dummy Akun Penjualan POS (Kredit)
        $idPersediaanJadi = 1;  // ID Dummy Akun Persediaan Barang Jadi (Kredit)
        // ===================================================================

        // 4. Susun Draf Jurnal Otomatis (4 Baris Berpasangan - Pastikan Seimbang)
        $defaultDetails = [
            // --- Kelompok Penerimaan Arus Kas Omzet ---
            [
                'account_id' => $idKasOutlet,
                'debit'      => floatval($penjualan->total),
                'kredit'     => 0
            ],
            [
                'account_id' => $idPenjualanPos,
                'debit'      => 0,
                'kredit'     => floatval($penjualan->total)
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
            foreach ($request->details as $item) {
                if (floatval($item['debit']) == 0 && floatval($item['kredit']) == 0) {
                    continue;
                }

                DB::table('journal_items')->insert([
                    'journal_id' => $jurnalId,
                    'account_id' => $item['account_id'],
                    'debit'      => $item['debit'],
                    'kredit'     => $item['kredit'],
                ]);
            }

            DB::commit();
            return redirect()
                ->route('laporan.jurnal-penjualanpos.index')
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
                ->route('laporan.jurnal-penjualanpos.index')
                ->with('error', 'Data riwayat jurnal Penjualan POS tidak ditemukan.');
        }

        // 2. Ambil rincian item debit/kredit yang terikat dengan ID jurnal POS ini
        $details = DB::table('journal_items')
            ->leftJoin('chart_of_accounts', 'journal_items.account_id', '=', 'chart_of_accounts.id')
            ->where('journal_items.journal_id', $id)
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
        // 1. Ambil semua ID PEMBAYARAN yang sudah pernah dijurnal
        $sudahDijurnal = DB::table('jurnal_penjualan_b2b')
            ->where('source_type', 'pembayaran')
            ->pluck('source_id')
            ->toArray();

        // 2. Antrean Atas: Ambil daftar transaksi PEMBAYARAN yang BELUM dijurnal
        $pesananBelum = \App\Models\Pembayaran::with(['pesanan.customer'])
            ->whereNotIn('id', $sudahDijurnal)
            ->orderBy('tanggal_bayar', 'desc')
            ->get();

        // 3. PERBAIKAN: Ringkas data buku jurnal (Satu baris per No. Ref)
        $jurnalsSudah = DB::table('jurnal_penjualan_b2b')
            ->join('journal_items', 'journal_items.journal_id', '=', 'jurnal_penjualan_b2b.id')
            ->where('jurnal_penjualan_b2b.source_type', 'pembayaran')
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
            ->orderBy('jurnal_penjualan_b2b.id', 'desc')
            ->get();

        return view('jurnal-penjualanb2b.index', compact('pesananBelum', 'jurnalsSudah'));
    }

    public function penjualanb2bCreate($id)
    {
        // 1. Ambil data Pembayaran saat ini beserta relasi induk pesanan dan customernya
        $pembayaran = \App\Models\Pembayaran::with(['pesanan.customer'])->findOrFail($id);
        $pesanan = $pembayaran->pesanan;

        if (!$pesanan) {
            return back()->with('error', 'Data induk pesanan untuk pembayaran ini tidak ditemukan.');
        }

        // 2. Ambil daftar COA untuk kebutuhan dropdown di view screen
        $coas = \App\Models\ChartOfAccount::orderBy('kode', 'asc')->get();

        // 3. Ambil data akumulasi nominal DP yang dibayar SEBELUM baris pembayaran pelunasan saat ini
        $totalBayarSebelumnya = DB::table('pembayaran')
            ->where('pesanan_id', $pesanan->id)
            ->where('id', '<', $pembayaran->id)
            ->sum('jumlah_bayar');

        $statusPesanan = $pesanan->status_pesanan; // Membaca status_pesanan ('pending' / 'Selesai')

        // ===================================================================
        // ID COA DUMMY UNTUK KEBUTUHAN UJI COBA SISTEM (Dibuat unik agar tidak bentrok)
        // ===================================================================
        $idKasBank        = 1;  // ID Dummy Akun Kas
        $idUangMuka       = 2;  // ID Dummy Akun Uang Muka Penjualan
        $idPendapatan     = 4;  // ID Dummy Akun Pendapatan Penjualan B2B
        $idHPP            = 5;  // ID Dummy Akun Harga Pokok Penjualan
        $idPersediaanJadi = 3;  // ID Dummy Akun Persediaan Barang Jadi (Diubah dari 1 ke 3 agar tidak kembar dengan Kas)
        // ===================================================================

        $defaultDetails = [];

        // KONDISI A: JIKA BARANG BELUM DIKIRIM (STATUS MASIH PENDING / BUKAN SELESAI) -> JURNAL DP
        if (strtolower($statusPesanan) !== 'selesai') {
            $defaultDetails = [
                [
                    'account_id' => $idKasBank,
                    'debit'      => floatval($pembayaran->jumlah_bayar),
                    'kredit'     => 0
                ],
                [
                    'account_id' => $idUangMuka,
                    'debit'      => 0,
                    'kredit'     => floatval($pembayaran->jumlah_bayar)
                ]
            ];
        }
        // KONDISI B: JIKA BARANG SUDAH DIKIRIM (STATUS SELESAI) -> GABUNGAN PELUNASAN, REALISASI REVENUE & HPP
        else {
            // PERBAIKAN EMAS: Mengganti tabel pesanan_detail ke alokasi_produksi_pesanan untuk menarik total HPP riil
            $totalHppRiil = DB::table('alokasi_produksi_pesanan')
                ->where('pesanan_id', $pesanan->id)
                ->sum('total_hpp_alokasi') ?? 0;

            // --- PART JURNAL 1: REALISASI PENDAPATAN & ARUS KAS ---

            // 1. Debit: Terima sisa Kas dari nominal transaksi pembayaran saat ini
            $defaultDetails[] = [
                'account_id' => $idKasBank,
                'debit'      => floatval($pembayaran->jumlah_bayar),
                'kredit'     => 0
            ];

            // 2. Debit: Balik / Tutup saldo Uang Muka Penjualan sebesar nilai DP masa lalu
            if ($totalBayarSebelumnya > 0) {
                $defaultDetails[] = [
                    'account_id' => $idUangMuka,
                    'debit'      => floatval($totalBayarSebelumnya),
                    'kredit'     => 0
                ];
            }

            // 3. Kredit: Akui total Penjualan B2B 100% utuh dari kolom total_pesanan milik model pesanan
            $defaultDetails[] = [
                'account_id' => $idPendapatan,
                'debit'      => 0,
                'kredit'     => floatval($pesanan->total_pesanan)
            ];

            // --- PART JURNAL 2: MATCHING COST PENGURANGAN BARANG GUDANG ---

            // 4. Debit: Akui Beban Harga Pokok Penjualan B2B dari akumulasi nilai alokasi produksi
            $defaultDetails[] = [
                'account_id' => $idHPP,
                'debit'      => floatval($totalHppRiil),
                'kredit'     => 0
            ];

            // 5. Kredit: Kurangi Aset Lancar Persediaan Barang Jadi karena barang keluar dikirim
            $defaultDetails[] = [
                'account_id' => $idPersediaanJadi,
                'debit'      => 0,
                'kredit'     => floatval($totalHppRiil)
            ];
        }

        return view('jurnal-penjualanb2b.create', compact('pembayaran', 'pesanan', 'coas', 'defaultDetails', 'statusPesanan'));
    }

    public function penjualanb2bStore(Request $request, $id)
    {
        $pembayaran = \App\Models\Pembayaran::findOrFail($id);

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

            // Cek Keseimbangan Debit & Kredit sebelum simpan
            $totalDebit = 0;
            $totalKredit = 0;
            foreach ($request->details as $item) {
                $totalDebit += floatval($item['debit'] ?? 0);
                $totalKredit += floatval($item['kredit'] ?? 0);
            }

            if (round($totalDebit, 2) != round($totalKredit, 2)) {
                throw new \Exception("Total Debit dan Kredit tidak seimbang!");
            }

            // Simpan Header Jurnal murni dari form review screen
            $jurnalsId = DB::table('jurnal_penjualan_b2b')->insertGetId([
                'tanggal'     => $request->tanggal,
                'no_ref'      => $request->no_ref,
                'deskripsi'   => $request->deskripsi,
                'source_type' => 'pembayaran',
                'source_id'   => $pembayaran->id,
                'created_by'  => auth()->id() ?? 1,
            ]);

            // Simpan Detail Jurnal secara dinamis berdasarkan pilihan form user
            foreach ($request->details as $item) {
                if (floatval($item['debit']) == 0 && floatval($item['kredit']) == 0) {
                    continue;
                }

                DB::table('journal_items')->insert([
                    'journal_id' => $jurnalsId,
                    'account_id' => $item['account_id'],
                    'debit'      => $item['debit'],
                    'kredit'     => $item['kredit'],
                ]);
            }

            DB::commit();
            return redirect()
                ->route('laporan.jurnal-penjualanb2b.index')
                ->with('success', 'Transaksi No. Ref ' . $request->no_ref . ' berhasil dibukukan!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal memproses simpan jurnal: ' . $e->getMessage())->withInput();
        }
    }

    public function penjualanb2bShow($id)
    {
        $jurnal = DB::table('jurnal_penjualan_b2b')
            ->where('id', $id)
            ->where('source_type', 'pembayaran')
            ->first();

        if (!$jurnal) {
            return redirect()
                ->route('laporan.jurnal-penjualanb2b.index')
                ->with('error', 'Data riwayat jurnal B2B tidak ditemukan.');
        }

        $details = DB::table('journal_items')
            ->leftJoin('chart_of_accounts', 'journal_items.account_id', '=', 'chart_of_accounts.id')
            ->where('journal_items.journal_id', $id)
            ->select(
                'journal_items.debit',
                'journal_items.kredit',
                'chart_of_accounts.kode',
                'chart_of_accounts.nama'
            )
            ->orderBy('journal_items.debit', 'desc')
            ->get();

        $totalDebit = $details->sum('debit');
        $totalKredit = $details->sum('kredit');

        return view('jurnal-penjualanb2b.show', compact('jurnal', 'details', 'totalDebit', 'totalKredit'));
    }

    public function bukuPembantuUtang()
    {
        $kodeAkunUtang = '21100'; // Sesuaikan dengan kode akun utang usaha kamu

        $bukuPembantuUtang = DB::table('pembelian')
            ->join('suppliers', 'pembelian.supplier_id', '=', 'suppliers.id')
            ->leftJoin('journals', function ($join) {
                $join->on('journals.source_id', '=', 'pembelian.id')
                    ->where('journals.source_type', '=', 'pembelian');
            })
            ->leftJoin('journal_items', 'journal_items.journal_id', '=', 'journals.id')
            ->leftJoin('chart_of_accounts', 'journal_items.account_id', '=', 'chart_of_accounts.id')
            ->select(
                'suppliers.nama as nama_supplier',
                'pembelian.kode_pembelian',
                'pembelian.tanggal as tanggal_transaksi',
                DB::raw("SUM(CASE WHEN chart_of_accounts.kode = '{$kodeAkunUtang}' THEN journal_items.kredit ELSE 0 END) as total_utang"),
                DB::raw("SUM(CASE WHEN chart_of_accounts.kode = '{$kodeAkunUtang}' THEN journal_items.debit ELSE 0 END) as total_cicilan")
            )
            ->groupBy('suppliers.nama', 'pembelian.kode_pembelian', 'pembelian.tanggal')
            ->orderBy('suppliers.nama')
            ->orderBy('pembelian.tanggal')
            ->get();

        // Ambil nama folder view berdasarkan rute kamu: bukupembantu-utang/index.blade.php
        return view('bukupembantu-utang.index', compact('bukuPembantuUtang'));
    }
}
