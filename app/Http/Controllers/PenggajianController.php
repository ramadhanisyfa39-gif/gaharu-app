<?php

namespace App\Http\Controllers;

use App\Models\Penggajian;
use App\Models\Karyawan;
use App\Models\Journal;
use App\Models\JournalItem;
use App\Models\ChartOfAccount;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class PenggajianController extends Controller
{
    // 1. MENAMPILKAN DAFTAR PERIODE (Halaman Utama / Index)
    public function index(): View
    {
        // Mengelompokkan data berdasarkan periode untuk melihat resume total per bulan
        // Menggunakan ignore null pada sum agar baris inisiasi awal yang bernilai 0 tidak mengacaukan hitungan
        $groupedPayrolls = Penggajian::select(
            'periode_bulan_tahun',
            'status',
            DB::raw('COUNT(CASE WHEN karyawan_id IS NOT NULL THEN 1 END) as total_karyawan'),
            DB::raw('SUM(total_gaji_bersih) as total_nominal')
        )
            ->groupBy('periode_bulan_tahun', 'status')
            ->orderBy('periode_bulan_tahun', 'desc')
            ->get();

        return view('penggajian.index', compact('groupedPayrolls'));
    }

    // 2. MEMBUAT WADAH PERIODE OTOMATIS (Aksi Tombol Utama di Index)
    public function create(): RedirectResponse
    {
        // Otomatis mengambil tahun dan bulan sekarang (Format: YYYY-MM)
        $periodeOtomatis = now()->format('Y-m');

        // Proteksi: Cek apakah periode bulan ini sudah pernah dibuat sebelumnya
        $exists = Penggajian::where('periode_bulan_tahun', $periodeOtomatis)->exists();
        if ($exists) {
            return redirect()->route('penggajian.index')
                ->with('error', "Periode $periodeOtomatis sudah ada. Silakan kelola di menu detail.");
        }

        // Buat satu baris draf awal dengan karyawan_id null sebagai wadah induk
        Penggajian::create([
            'karyawan_id'           => null,
            'periode_bulan_tahun'   => $periodeOtomatis,
            'gaji_pokok'            => 0,
            'tunjangan_transport'   => 0,
            'tunjangan_makan'       => 0,
            'lembur'                => 0,
            'bonus_target'          => 0,
            'bonus_tanggal_merah'   => 0,
            'bonus_birthday'        => 0,
            'bonus_dll'             => 0,
            'potongan_inventaris'   => 0,
            'potongan_terlambat'    => 0,
            'total_gaji_bersih'     => 0,
            'status'                => 'draft'
        ]);

        return redirect()->route('penggajian.index')
            ->with('success', "Periode baru $periodeOtomatis berhasil dibuat secara otomatis.");
    }

    // 3. MENAMPILKAN DETAIL GAJI BERSIH (Halaman Ringkas Karyawan)
    public function showPeriode($periode): View
    {
        // Ambil data penggajian yang valid (termasuk relasi karyawan)
        $payrolls = Penggajian::with('karyawan')
            ->where('periode_bulan_tahun', $periode)
            ->whereNotNull('karyawan_id') // Hanya tampilkan baris yang sudah ada karyawannya
            ->get();

        // Cari tahu status asli periode dari database
        $checkStatus = Penggajian::where('periode_bulan_tahun', $periode)->first();
        $status = $checkStatus->status ?? 'draft';

        return view('penggajian.show_periode', compact('payrolls', 'periode', 'status'));
    }

    // 4. MENAMPILKAN FORM INPUT GAJI ASLI MILIKMU
    public function createKaryawan(Request $request): View
    {
        $periode = $request->query('periode');
        $karyawans = Karyawan::all();

        return view('penggajian.create', compact('karyawans', 'periode'));
    }

    // 5. MEMPROSES SIMPAN NOMINAL GAJI KARYAWAN (MANUAL SATU PER SATU)
    public function storeKaryawan(Request $request): RedirectResponse
    {
        $cleanRupiah = function ($value) {
            return (int) preg_replace('/[^0-9]/', '', $value);
        };

        // Hitung Komponen Gaji Bersih
        $gaji_pokok = $cleanRupiah($request->gaji_pokok);
        $tunjangan_transport = $cleanRupiah($request->tunjangan_transport);
        $tunjangan_makan = $cleanRupiah($request->tunjangan_makan);
        $total_tetap = $gaji_pokok + $tunjangan_transport + $tunjangan_makan;

        $lembur = $cleanRupiah($request->lembur);
        $bonus_target = $cleanRupiah($request->bonus_target);
        $bonus_tanggal_merah = $cleanRupiah($request->bonus_tanggal_merah);
        $bonus_birthday = $cleanRupiah($request->bonus_birthday);
        $bonus_dll = $cleanRupiah($request->bonus_dll);
        $total_tidak_tetap = $lembur + $bonus_target + $bonus_tanggal_merah + $bonus_birthday + $bonus_dll;

        $potongan_inventaris = $cleanRupiah($request->potongan_inventaris);
        $potongan_terlambat = $cleanRupiah($request->potongan_terlambat);
        $total_potongan = $potongan_inventaris + $potongan_terlambat;

        $total_gaji_bersih = ($total_tetap + $total_tidak_tetap) - $total_potongan;

        // Cek apakah wadah draf kosong hasil inisiasi awal masih ada
        $dummyRow = Penggajian::where('periode_bulan_tahun', $request->periode)
            ->whereNull('karyawan_id')
            ->first();

        if ($dummyRow) {
            // Jika ada baris kosong, pakai baris tersebut untuk data karyawan pertama
            $dummyRow->update([
                'karyawan_id'           => $request->karyawan_id,
                'gaji_pokok'            => $gaji_pokok,
                'tunjangan_transport'   => $tunjangan_transport,
                'tunjangan_makan'       => $tunjangan_makan,
                'lembur'                => $lembur,
                'bonus_target'          => $bonus_target,
                'bonus_tanggal_merah'   => $bonus_tanggal_merah,
                'bonus_birthday'        => $bonus_birthday,
                'bonus_dll'             => $bonus_dll,
                'potongan_inventaris'   => $potongan_inventaris,
                'potongan_terlambat'    => $potongan_terlambat,
                'total_gaji_bersih'     => $total_gaji_bersih,
            ]);
        } else {
            // Jika baris kosong sudah habis digunakan, buat baris baru untuk karyawan selanjutnya
            Penggajian::create([
                'karyawan_id'           => $request->karyawan_id,
                'periode_bulan_tahun'   => $request->periode,
                'gaji_pokok'            => $gaji_pokok,
                'tunjangan_transport'   => $tunjangan_transport,
                'tunjangan_makan'       => $tunjangan_makan,
                'lembur'                => $lembur,
                'bonus_target'          => $bonus_target,
                'bonus_tanggal_merah'   => $bonus_tanggal_merah,
                'bonus_birthday'        => $bonus_birthday,
                'bonus_dll'             => $bonus_dll,
                'potongan_inventaris'   => $potongan_inventaris,
                'potongan_terlambat'    => $potongan_terlambat,
                'total_gaji_bersih'     => $total_gaji_bersih,
                'status'                => 'draft'
            ]);
        }

        return redirect()->route('penggajian.periode', $request->periode)
            ->with('success', 'Gaji karyawan berhasil ditambahkan.');
    }

    // 6. ACTION: AJUKAN KE DIREKTUR KEUANGAN
    public function submitToDirector($periode): RedirectResponse
    {
        Penggajian::where('periode_bulan_tahun', $periode)->update(['status' => 'pending_approval']);
        return redirect()->back()->with('success', 'Data penggajian berhasil diajukan ke Direktur Keuangan.');
    }

    // 7. ACTION: APPROVAL DIREKTUR KEUANGAN
    public function approveByDirector($periode): RedirectResponse
    {
        Penggajian::where('periode_bulan_tahun', $periode)->update(['status' => 'approved']);
        return redirect()->back()->with('success', 'Data penggajian disetujui.');
    }

    // 8. ACTION: OTOMATISASI PENJURNALAN (AKUNTANSI DOUBLE-ENTRY)
    public function sendToJournal($periode): RedirectResponse
    {
        $payrolls = Penggajian::where('periode_bulan_tahun', $periode)->whereNotNull('karyawan_id')->get();

        if ($payrolls->isEmpty() || $payrolls->first()->status !== 'approved') {
            return redirect()->back()->with('error', 'Gagal memproses jurnal. Status harus Approved.');
        }

        // Akumulasi data nominal dari seluruh karyawan untuk keperluan jurnal umum
        $total_gaji_kotor = $payrolls->sum(function ($p) {
            return $p->gaji_pokok + $p->tunjangan_transport + $p->tunjangan_makan + $p->lembur + $p->bonus_target + $p->bonus_tanggal_merah + $p->bonus_birthday + $p->bonus_dll;
        });
        $total_potongan = $payrolls->sum('potongan_inventaris') + $payrolls->sum('potongan_terlambat');
        $total_gaji_bersih = $payrolls->sum('total_gaji_bersih');

        // ID Akun COA (Sesuaikan dengan data di database chart_of_accounts-mu)
        $akun_beban_gaji = 20;
        $akun_potongan   = 21;
        $akun_kas_bank   = 1;

        DB::transaction(function () use ($periode, $total_gaji_kotor, $total_potongan, $total_gaji_bersih, $akun_beban_gaji, $akun_potongan, $akun_kas_bank) {

            // Simpan Data Induk Jurnal
            $journal = Journal::create([
                'tanggal'     => now()->format('Y-m-d'),
                'deskripsi'   => "Pencatatan Gaji Karyawan Periode $periode",
                'no_ref'      => 'PYR-' . strtoupper($periode),
                'source_type' => 'Penggajian',
                'source_id'   => 0,
                'created_by'  => auth()->id() ?? 1
            ]);

            // Item Jurnal: DEBIT Beban Gaji
            JournalItem::create([
                'journal_id' => $journal->id,
                'account_id' => $akun_beban_gaji,
                'debit'      => $total_gaji_kotor,
                'kredit'     => 0
            ]);

            // Item Jurnal: KREDIT Potongan Gaji (Jika ada nominalnya)
            if ($total_potongan > 0) {
                JournalItem::create([
                    'journal_id' => $journal->id,
                    'account_id' => $akun_potongan,
                    'debit'      => 0,
                    'kredit'     => $total_potongan
                ]);
            }

            // Item Jurnal: KREDIT Kas / Bank
            JournalItem::create([
                'journal_id' => $journal->id,
                'account_id' => $akun_kas_bank,
                'debit'      => 0,
                'kredit'     => $total_gaji_bersih
            ]);

            // Ubah status agar terkunci permanen
            Penggajian::where('periode_bulan_tahun', $periode)->update(['status' => 'posted']);
        });

        return redirect()->back()->with('success', 'Jurnal otomatis penggajian berhasil disimpan ke Jurnal Umum.');
    }

    // 9. MENGHAPUS KARYAWAN DARI DAFTAR PERIODE (Hanya saat status Draft)
    public function destroy(Penggajian $penggajian): RedirectResponse
    {
        if ($penggajian->status !== 'draft') {
            return redirect()->back()->with('error', 'Gagal menghapus. Data sudah masuk proses review.');
        }

        $periode = $penggajian->periode_bulan_tahun;
        $penggajian->delete();

        return redirect()->route('penggajian.periode', $periode)->with('success', 'Data gaji karyawan dihapus.');
    }
}
