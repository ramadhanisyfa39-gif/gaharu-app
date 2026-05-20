<?php

namespace App\Http\Controllers;

use App\Models\Penggajian;
use App\Models\Karyawan;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class PenggajianController extends Controller
{
    public function index()
    {
        // 1. Ambil data penggajian (ini sudah benar di tempatmu)
        $payrolls = Penggajian::with('karyawan')->orderBy('created_at', 'desc')->get();

        // 2. TAMBAHKAN INI: Ambil data semua karyawan
        $karyawans = Karyawan::all();

        // 3. Masukkan 'karyawans' ke dalam compact agar terkirim ke view index
        return view('penggajian.index', compact('payrolls', 'karyawans'));
    }

    public function create(): View
    {
        $karyawans = Karyawan::all();
        return view('penggajian.create', compact('karyawans'));
    }

    public function store(Request $request)
    {

        $cleanRupiah = function ($value) {
            return (int) preg_replace('/[^0-9]/', '', $value);
        };

        Penggajian::create([
            'karyawan_id'           => $request->karyawan_id,
            'periode_bulan_tahun'   => $request->periode,
            'gaji_pokok'            => $cleanRupiah($request->gaji_pokok),
            'tunjangan_transport'   => $cleanRupiah($request->tunjangan_transport),
            'tunjangan_makan'       => $cleanRupiah($request->tunjangan_makan),
            'lembur'                => $cleanRupiah($request->lembur),
            'bonus_target'          => $cleanRupiah($request->bonus_target),
            'bonus_tanggal_merah'   => $cleanRupiah($request->bonus_tanggal_merah),
            'bonus_birthday'        => $cleanRupiah($request->bonus_birthday),
            'bonus_dll'             => $cleanRupiah($request->bonus_dll),
            'potongan_inventaris'   => $cleanRupiah($request->potongan_inventaris),
            'potongan_terlambat'    => $cleanRupiah($request->potongan_terlambat),
            'total_gaji_bersih'     => $cleanRupiah($request->total_gaji_bersih),
        ]);

        // 1. Penerimaan Tetap
        $gaji_pokok = $cleanRupiah($request->gaji_pokok);
        $tunjangan_transport = $cleanRupiah($request->tunjangan_transport);
        $tunjangan_makan = $cleanRupiah($request->tunjangan_makan);
        $total_tetap = $gaji_pokok + $tunjangan_transport + $tunjangan_makan;

        // 2. Penerimaan Tidak Tetap
        $lembur = $cleanRupiah($request->lembur);
        $bonus_target = $cleanRupiah($request->bonus_target);
        $bonus_tanggal_merah = $cleanRupiah($request->bonus_tanggal_merah);
        $bonus_birthday = $cleanRupiah($request->bonus_birthday);
        $bonus_dll = $cleanRupiah($request->bonus_dll);
        $total_tidak_tetap = $lembur + $bonus_target + $bonus_tanggal_merah + $bonus_birthday + $bonus_dll;

        // 3. Potongan
        $potongan_inventaris = $cleanRupiah($request->potongan_inventaris);
        $potongan_terlambat = $cleanRupiah($request->potongan_terlambat);
        $total_potongan = $potongan_inventaris + $potongan_terlambat;

        // Total Gaji Bersih (Take Home Pay)
        $total_gaji_bersih = ($total_tetap + $total_tidak_tetap) - $total_potongan;

        // Simpan ke Database   ...
        return redirect()->route('penggajian.show', Penggajian::latest()->first()->id)
            ->with('success', 'Data penggajian berhasil disimpan dan siap dicetak.');
    }

    public function show($id)
    {
        $payroll = Penggajian::with('karyawan')->findOrFail($id);

        // Hitung Subtotal A
        $total_tetap = ($payroll->gaji_pokok ?? 0) +
            ($payroll->tunjangan_transport ?? 0) +
            ($payroll->tunjangan_makan ?? 0);

        // Hitung Subtotal B
        $total_tidak_tetap = ($payroll->lembur ?? 0) +
            ($payroll->bonus_target ?? 0) +
            ($payroll->bonus_tanggal_merah ?? 0) +
            ($payroll->bonus_birthday ?? 0) +
            ($payroll->bonus_dll ?? 0);

        // Hitung Subtotal C
        $total_potongan = ($payroll->potongan_inventaris ?? 0) +
            ($payroll->potongan_terlambat ?? 0);

        // Hitung THP Bersih
        $total_gaji_bersih = ($total_tetap + $total_tidak_tetap) - $total_potongan;

        // Pastikan semua variabel dipassing ke view!
        return view('penggajian.show', compact(
            'payroll',
            'total_tetap',
            'total_tidak_tetap',
            'total_potongan',
            'total_gaji_bersih'
        ));
    }

    public function destroy(Penggajian $penggajian): RedirectResponse
    {
        $penggajian->delete();
        return redirect()->route('penggajian.index')->with('success', 'Data gaji dihapus.');
    }
}
