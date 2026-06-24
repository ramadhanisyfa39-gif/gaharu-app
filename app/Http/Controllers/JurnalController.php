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
        // 1. Ambil ID Pembelian yang sudah pernah dijurnal
        $sudahDijurnal = Journal::where('source_type', 'pembelian')->pluck('source_id')->toArray();

        // 2. Data untuk TABEL ATAS: Invoice yang BELUM dijurnal
        $pembeliansBelum = Pembelian::with(['supplier', 'gudang'])
            ->whereNotIn('id', $sudahDijurnal)
            ->orderBy('tanggal', 'desc')
            ->get();

        // 3. Data untuk TABEL BAWAH: Riwayat Jurnal Pembelian yang SUDAH disimpan
        $jurnalsSudah = Journal::with('details.coa')
            ->where('source_type', 'pembelian')
            ->orderBy('tanggal', 'desc')
            ->get();

        // Kirim kedua variabel ke satu halaman view index yang sama
        return view('jurnal-pembelian.index', compact('pembeliansBelum', 'jurnalsSudah'));
    }

    /*
    |--------------------------------------------------------------------------
    | 2. STORE LOGIC: EKSEKUSI JURNAL BERDASARKAN PILIHAN USER
    |--------------------------------------------------------------------------
    */
    public function prosesJurnalPembelian(Request $request, $id)

    {
        // 1. Validasi data form sesuai dengan input field dari view
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

        // 2. Cek apakah periode akuntansi sudah dikunci (Closing)
        $isClosed = Journal::where('source_type', 'closing')
            ->whereMonth('tanggal', date('m', strtotime($request->tanggal)))
            ->whereYear('tanggal', date('Y', strtotime($request->tanggal)))
            ->exists();

        if ($isClosed) {
            return back()->with('error', 'Gagal! Periode akuntansi untuk tanggal jurnal ini sudah ditutup (Closing).')->withInput();
        }

        try {
            DB::beginTransaction();

            // 3. Simpan Header Jurnal (Hubungkan ke ID Pembelian sebagai pengunci antrean)
            $jurnal = Journal::create([
                'tanggal'     => $request->tanggal,
                'deskripsi'   => $request->deskripsi,
                'no_ref'      => $request->no_ref ?? 'JR-' . time(),
                'source_type' => 'pembelian', // Disimpan sebagai tipe pembelian agar hilang dari antrean
                'source_id'   => $pembelian->id, // Mengunci ID pembelian ini
                'created_by'  => Auth::id(),
            ]);

            // 4. Simpan baris detail debit/kredit dari tabel form
            foreach ($request->details as $item) {
                // Lewati baris jika debit dan kredit sama-sama nol (opsional)
                if ($item['debit'] == 0 && $item['kredit'] == 0) {
                    continue;
                }

                $jurnal->details()->create([
                    'account_id' => $item['account_id'],
                    'debit'      => $item['debit'],
                    'kredit'     => $item['kredit'],
                ]);
            }

            // 5. Validasi keseimbangan saldo (Balance Check) di level backend
            if (round($jurnal->details->sum('debit'), 2) != round($jurnal->details->sum('kredit'), 2)) {
                throw new \Exception("Total Debit dan Kredit tidak seimbang!");
            }

            DB::table('jurnal_pembelian')->insert([
                'tanggal'     => $request->tanggal,
                'deskripsi'   => $request->deskripsi,
                'no_ref'      => $request->no_ref ?? 'JR-' . time(),
                'source_type' => 'pembelian', // Disimpan sebagai tipe pembelian agar hilang dari antrean
                'source_id'   => $pembelian->id, // Mengunci ID pembelian ini
                'created_by'  => Auth::id(),
            ]);

            DB::commit();
            return redirect()->route('jurnal-pembelian.index', $pembelian->id)->with('success', 'Invoice ' . $pembelian->kode_pembelian . ' berhasil dijurnal manual!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    public function pembelianCreate($id)
    {
        // Ambil daftar akun COA untuk dropdown di dalam tabel form view
        $coas = ChartOfAccount::orderBy('kode', 'asc')->get();

        // Langsung cari data pembelian menggunakan parameter $id dari rute URL
        $pembelian = Pembelian::findOrFail($id);

        return view('jurnal-pembelian.create', compact('coas', 'pembelian'));
    }

    public function penjualanposIndex()
    {
        // 1. Ambil ID PenjualanPos yang sudah pernah dijurnal
        $sudahDijurnal = Journal::where('source_type', 'penjualanpos')->pluck('source_id')->toArray();

        // 2. Data untuk TABEL ATAS: Invoice yang BELUM dijurnal
        $penjualanposBelum = PenjualanPos::with(['customer', 'gudang'])
            ->whereNotIn('id', $sudahDijurnal)
            ->orderBy('tanggal', 'desc')
            ->get();

        // 3. Data untuk TABEL BAWAH: Riwayat Jurnal PenjualanPos yang SUDAH disimpan
        $jurnalsSudah = Journal::with('details.coa')
            ->where('source_type', 'penjualanpos')
            ->orderBy('tanggal', 'desc')
            ->get();

        // Kirim kedua variabel ke satu halaman view index yang sama
        return view('jurnal-penjualanpos.index', compact('penjualanposBelum', 'jurnalsSudah'));
    }

    public function prosesJurnalPenjualanPos(Request $request, $id)

    {
        // 1. Validasi data form sesuai dengan input field dari view
        $request->validate([
            'tanggal'              => 'required|date',
            'deskripsi'            => 'required|string',
            'no_ref'               => 'nullable|string',
            'details'              => 'required|array|min:2',
            'details.*.account_id' => 'required|exists:chart_of_accounts,id',
            'details.*.debit'      => 'required|numeric|min:0',
            'details.*.kredit'     => 'required|numeric|min:0',
        ]);

        $penjualanpos = PenjualanPos::findOrFail($id);

        // 2. Cek apakah periode akuntansi sudah dikunci (Closing)
        $isClosed = Journal::where('source_type', 'closing')
            ->whereMonth('tanggal', date('m', strtotime($request->tanggal)))
            ->whereYear('tanggal', date('Y', strtotime($request->tanggal)))
            ->exists();

        if ($isClosed) {
            return back()->with('error', 'Gagal! Periode akuntansi untuk tanggal jurnal ini sudah ditutup (Closing).')->withInput();
        }

        try {
            DB::beginTransaction();

            // 3. Simpan Header Jurnal (Hubungkan ke ID PenjualanPos sebagai pengunci antrean)
            $jurnal = Journal::create([
                'tanggal'     => $request->tanggal,
                'deskripsi'   => $request->deskripsi,
                'no_ref'      => $request->no_ref ?? 'JR-' . time(),
                'source_type' => 'penjualanpos', // Disimpan sebagai tipe penjualanpos agar hilang dari antrean
                'source_id'   => $penjualanpos->id, // Mengunci ID penjualanpos ini
                'created_by'  => Auth::id(),
            ]);

            // 4. Simpan baris detail debit/kredit dari tabel form
            foreach ($request->details as $item) {
                // Lewati baris jika debit dan kredit sama-sama nol (opsional)
                if ($item['debit'] == 0 && $item['kredit'] == 0) {
                    continue;
                }

                $jurnal->details()->create([
                    'account_id' => $item['account_id'],
                    'debit'      => $item['debit'],
                    'kredit'     => $item['kredit'],
                ]);
            }

            // 5. Validasi keseimbangan saldo (Balance Check) di level backend
            if (round($jurnal->details->sum('debit'), 2) != round($jurnal->details->sum('kredit'), 2)) {
                throw new \Exception("Total Debit dan Kredit tidak seimbang!");
            }

            DB::table('jurnal_penjualanpos')->insert([
                'tanggal'     => $request->tanggal,
                'deskripsi'   => $request->deskripsi,
                'no_ref'      => $request->no_ref ?? 'JR-' . time(),
                'source_type' => 'penjualanpos', // Disimpan sebagai tipe penjualanpos agar hilang dari antrean
                'source_id'   => $penjualanpos->id, // Mengunci ID penjualanpos ini
                'created_by'  => Auth::id(),
            ]);

            DB::commit();
            return redirect()->route('jurnal-penjualanpos.index', $penjualanpos->id)->with('success', 'Invoice ' . $penjualanpos->kode_penjualanpos . ' berhasil dijurnal manual!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    public function penjualanposCreate($id)
    {
        // Ambil daftar akun COA untuk dropdown di dalam tabel form view
        $coas = ChartOfAccount::orderBy('kode', 'asc')->get();

        // Langsung cari data penjualanpos menggunakan parameter $id dari rute URL
        $penjualanpos = PenjualanPos::findOrFail($id);

        return view('jurnal-penjualanpos.create', compact('coas', 'penjualanpos'));
    }

    public function penjualanb2bIndex()
    {
        // 1. Ambil ID PenjualanB2B yang sudah pernah dijurnal
        $sudahDijurnal = Journal::where('source_type', 'pesanan')->pluck('source_id')->toArray();

        // 2. Data untuk TABEL ATAS: Invoice yang BELUM dijurnal
        $pesananBelum = Pesanan::with(['customer', 'gudang'])
            ->whereNotIn('id', $sudahDijurnal)
            ->orderBy('tanggal', 'desc')
            ->get();

        // 3. Data untuk TABEL BAWAH: Riwayat Jurnal PenjualanB2B yang SUDAH disimpan
        $jurnalsSudah = Journal::with('details.coa')
            ->where('source_type', 'penjualanb2b')
            ->orderBy('tanggal', 'desc')
            ->get();

        // Kirim kedua variabel ke satu halaman view index yang sama
        return view('jurnal-penjualanb2b.index', compact('pesananBelum', 'jurnalsSudah'));
    }

    public function prosesJurnalPenjualanB2B(Request $request, $id)

    {
        // 1. Validasi data form sesuai dengan input field dari view
        $request->validate([
            'tanggal'              => 'required|date',
            'deskripsi'            => 'required|string',
            'no_ref'               => 'nullable|string',
            'details'              => 'required|array|min:2',
            'details.*.account_id' => 'required|exists:chart_of_accounts,id',
            'details.*.debit'      => 'required|numeric|min:0',
            'details.*.kredit'     => 'required|numeric|min:0',
        ]);

        $pesanan = Pesanan::findOrFail($id);

        // 2. Cek apakah periode akuntansi sudah dikunci (Closing)
        $isClosed = Journal::where('source_type', 'closing')
            ->whereMonth('tanggal', date('m', strtotime($request->tanggal)))
            ->whereYear('tanggal', date('Y', strtotime($request->tanggal)))
            ->exists();

        if ($isClosed) {
            return back()->with('error', 'Gagal! Periode akuntansi untuk tanggal jurnal ini sudah ditutup (Closing).')->withInput();
        }

        try {
            DB::beginTransaction();

            // 3. Simpan Header Jurnal (Hubungkan ke ID PenjualanPos sebagai pengunci antrean)
            $jurnal = Journal::create([
                'tanggal'     => $request->tanggal,
                'deskripsi'   => $request->deskripsi,
                'no_ref'      => $request->no_ref ?? 'JR-' . time(),
                'source_type' => 'penjualanb2b', // Disimpan sebagai tipe penjualanb2b agar hilang dari antrean
                'source_id'   => $pesanan->id, // Mengunci ID pesanan ini
                'created_by'  => Auth::id(),
            ]);

            // 4. Simpan baris detail debit/kredit dari tabel form
            foreach ($request->details as $item) {
                // Lewati baris jika debit dan kredit sama-sama nol (opsional)
                if ($item['debit'] == 0 && $item['kredit'] == 0) {
                    continue;
                }

                $jurnal->details()->create([
                    'account_id' => $item['account_id'],
                    'debit'      => $item['debit'],
                    'kredit'     => $item['kredit'],
                ]);
            }

            // 5. Validasi keseimbangan saldo (Balance Check) di level backend
            if (round($jurnal->details->sum('debit'), 2) != round($jurnal->details->sum('kredit'), 2)) {
                throw new \Exception("Total Debit dan Kredit tidak seimbang!");
            }

            DB::table('jurnal_penjualanb2b')->insert([
                'tanggal'     => $request->tanggal,
                'deskripsi'   => $request->deskripsi,
                'no_ref'      => $request->no_ref ?? 'JR-' . time(),
                'source_type' => 'penjualanb2b', // Disimpan sebagai tipe penjualanb2b agar hilang dari antrean
                'source_id'   => $pesanan->id, // Mengunci ID pesanan ini
                'created_by'  => Auth::id(),
            ]);

            DB::commit();
            return redirect()->route('jurnal-penjualanb2b.index', $pesanan->id)->with('success', 'Invoice ' . $pesanan->kode_pesanan . ' berhasil dijurnal manual!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    public function penjualanb2bCreate($id)
    {
        // Ambil daftar akun COA untuk dropdown di dalam tabel form view
        $coas = ChartOfAccount::orderBy('kode', 'asc')->get();

        // Langsung cari data pesanan menggunakan parameter $id dari rute URL
        $pesanan = Pesanan::findOrFail($id);

        return view('jurnal-penjualanb2b.create', compact('coas', 'pesanan'));
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
