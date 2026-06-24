<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pesanan;
use App\Models\Pengiriman;
use App\Models\PengirimanDetail;
use App\Models\MasterGudang;
use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Schema; // Dihapus karena sudah tidak dibutuhkan

class PengirimanController extends Controller
{
    public function index()
    {
        $pengirimans = Pengiriman::with('pesanan.customer')->orderBy('created_at', 'desc')->get();
        return view('pengiriman.index', compact('pengirimans'));
    }

    // 1. Tampilkan form kosong dengan list pesanan di dropdown
    public function create()
    {
        // Ambil pesanan yang statusnya belum 'Selesai' 
        // 💡 TIPS: Jika ingin pesanan yang belum Lunas TIDAK MUNCUL sama sekali di dropdown,
        // kamu bisa tambahkan: ->where('status_bayar', 'Lunas') di bawah ini.
        $pesanans = Pesanan::with('customer')
            ->where('status_pesanan', '!=', 'Selesai')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('pengiriman.create', compact('pesanans'));
    }

    // 2. Fungsi khusus AJAX 
    public function getPesananDetail($id)
    {
        $pesanan = DB::table('pesanan')
            ->leftJoin('customers', 'pesanan.customer_id', '=', 'customers.id')
            ->where('pesanan.id', $id)
            ->select('pesanan.*', 'customers.nama as customer_nama')
            ->first();
        
        if (!$pesanan) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        // Ambil detail dari tabel pesanan_detail dan hubungkan ke master_barang memakai produk_id
        $details = DB::table('pesanan_detail')
            ->leftJoin('master_barang', 'pesanan_detail.produk_id', '=', 'master_barang.id')
            ->where('pesanan_detail.pesanan_id', $id)
            ->select('pesanan_detail.*', 'master_barang.nama as barang_nama', 'master_barang.satuan as barang_satuan')
            ->get();

        // Format data agar serasi dengan variabel yang dibaca JavaScript di file Blade
        $formattedDetails = $details->map(function($item) {
            return [
                'barang_id' => $item->produk_id, 
                'qty' => $item->qty ?? 0,
                'barang' => [
                    'nama' => $item->barang_nama ?? 'Produk Tanpa Nama',
                    'satuan' => $item->barang_satuan ?? 'Unit'
                ]
            ];
        });

        return response()->json([
            'id' => $pesanan->id,
            'kode_pesanan' => $pesanan->kode_pesanan,
            'customer' => [
                'nama' => $pesanan->customer_nama
            ],
            'details' => $formattedDetails
        ]);
    }

    // 3. Simpan data pengiriman, potong stok, & update status pesanan
    public function store(Request $request)
    {
        $request->validate([
            'pesanan_id' => 'required',
            'tanggal_pengiriman' => 'required|date',
            'details' => 'required|array',
            'details.*.barang_id' => 'required',
            'details.*.qty_kirim' => 'required|numeric|min:0.01', 
        ]);

        // --- ATURAN BARU: CEK STATUS LUNAS ---
        $pesanan = DB::table('pesanan')->where('id', $request->pesanan_id)->first();
        
        if (!$pesanan) {
            return back()->with('error', 'Data pesanan tidak ditemukan.');
        }

        // Tolak jika status bayar bukan "Lunas" (Misal masih "DP 30%")
        if (strtolower($pesanan->status_pembayaran) !== 'lunas') {
            return back()->with('error', 'Pengiriman ditolak! Pesanan ini belum lunas, Silakahkan hubungi kepala outlet untuk konfirmasi!');
        }
        // --------------------------------------

        DB::beginTransaction();
        try {
            $gudangB2B = MasterGudang::where('kategori', 'Produksi')->first();
            if (!$gudangB2B) {
                throw new \Exception('Gudang kategori Produksi tidak ditemukan.');
            }

            $pengiriman = Pengiriman::create([
                'no_pengiriman' => 'SJ-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2))),
                'pesanan_id' => $request->pesanan_id,
                'tanggal_pengiriman' => $request->tanggal_pengiriman,
                'kurir' => $request->kurir,
            ]);

            foreach ($request->details as $detail) {
                PengirimanDetail::create([
                    'pengiriman_id' => $pengiriman->id,
                    'barang_id' => $detail['barang_id'],
                    'qty_kirim' => $detail['qty_kirim'],
                ]);

                // POTONG STOK DI TABEL stok_gudang
                $stok = DB::table('stok_gudang')
                    ->where('gudang_id', $gudangB2B->id)
                    ->where('barang_id', $detail['barang_id'])
                    ->first();

                if ($stok) {
                    DB::table('stok_gudang')
                        ->where('id', $stok->id)
                        ->decrement('jumlah', $detail['qty_kirim']);
                } else {
                    DB::table('stok_gudang')->insert([
                        'gudang_id' => $gudangB2B->id,
                        'barang_id' => $detail['barang_id'],
                        'jumlah' => -$detail['qty_kirim'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // UPDATE STATUS PESANAN (Kode lebih bersih & anti-error)
            DB::table('pesanan')->where('id', $request->pesanan_id)->update([
                'status_pesanan' => 'Selesai'
            ]);

            DB::commit();
            return redirect()->route('pengiriman.index')->with('success', 'Surat Jalan berhasil diterbitkan!');
        } catch (\Exception $e) {
            DB::rollBack();
            // dd() dimatikan agar user dikembalikan ke halaman form dengan pesan error rapi
            return back()->with('error', 'Gagal memproses pengiriman: ' . $e->getMessage());
        }
    }
}