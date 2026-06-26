<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pesanan;
use App\Models\Pengiriman;
use App\Models\PengirimanDetail;
use App\Models\MasterGudang;
use App\Models\ProduksiPesanan;
use Illuminate\Support\Facades\DB;

class PengirimanController extends Controller
{
    public function index()
    {
        $pengirimans = Pengiriman::with('pesanan.customer')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('pengiriman.index', compact('pengirimans'));
    }

    /**
     * Tampilkan form pengiriman
     */
    public function create()
    {
        $pesanans = Pesanan::with('customer')
            ->where('status_pesanan', '!=', 'Selesai')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('pengiriman.create', compact('pesanans'));
    }

    /**
     * Ambil detail pesanan melalui AJAX
     */
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

        $details = DB::table('pesanan_detail')
            ->leftJoin('master_barang', 'pesanan_detail.produk_id', '=', 'master_barang.id')
            ->where('pesanan_detail.pesanan_id', $id)
            ->select(
                'pesanan_detail.*',
                'master_barang.nama as barang_nama',
                'master_barang.satuan as barang_satuan'
            )
            ->get();

        $formattedDetails = $details->map(function ($item) {
            return [
                'barang_id' => $item->produk_id,
                'qty' => $item->qty ?? 0,
                'barang' => [
                    'nama' => $item->barang_nama ?? 'Produk Tanpa Nama',
                    'satuan' => $item->barang_satuan ?? 'Unit',
                ],
            ];
        });

        return response()->json([
            'id' => $pesanan->id,
            'kode_pesanan' => $pesanan->kode_pesanan,
            'customer' => [
                'nama' => $pesanan->customer_nama,
            ],
            'details' => $formattedDetails,
        ]);
    }

    /**
     * Simpan pengiriman, potong stok, update alokasi, dan status pesanan
     */
    public function store(Request $request)
    {
        $request->validate([
            'pesanan_id' => 'required',
            'tanggal_pengiriman' => 'required|date',
            'details' => 'required|array',
            'details.*.barang_id' => 'required',
            'details.*.qty_kirim' => 'required|numeric|min:0.01',
        ]);

        $pesanan = DB::table('pesanan')
            ->where('id', $request->pesanan_id)
            ->first();

        if (!$pesanan) {
            return back()->with('error', 'Data pesanan tidak ditemukan.');
        }

        if (strtolower($pesanan->status_pembayaran) !== 'lunas') {
            return back()->with(
                'error',
                'Pengiriman ditolak! Pesanan ini belum lunas. Silakan hubungi kepala outlet untuk konfirmasi.'
            );
        }

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
                $barangId = $detail['barang_id'];
                $qtyKirim = floatval($detail['qty_kirim']);

                /*
                |--------------------------------------------------------------------------
                | 1. Cari alokasi produksi untuk pesanan dan produk yang dikirim
                |--------------------------------------------------------------------------
                */
                $alokasi = ProduksiPesanan::where('pesanan_id', $request->pesanan_id)
                    ->where('produk_id', $barangId)
                    ->whereRaw('qty_terkirim < qty_alokasi')
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->first();

                if (!$alokasi) {
                    throw new \Exception(
                        'Alokasi produksi untuk produk yang dikirim belum tersedia atau sudah habis.'
                    );
                }

                $sisaAlokasi = floatval($alokasi->qty_alokasi) - floatval($alokasi->qty_terkirim);

                if ($qtyKirim > $sisaAlokasi) {
                    throw new \Exception(
                        'Qty kirim melebihi sisa alokasi produksi. Sisa alokasi: ' . $sisaAlokasi
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | 2. Cek stok barang jadi
                |--------------------------------------------------------------------------
                */
                $stok = DB::table('stok_gudang')
                    ->where('gudang_id', $gudangB2B->id)
                    ->where('barang_id', $barangId)
                    ->lockForUpdate()
                    ->first();

                if (!$stok || floatval($stok->jumlah) < $qtyKirim) {
                    throw new \Exception(
                        'Stok barang jadi tidak mencukupi untuk pengiriman.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | 3. Simpan detail pengiriman
                |--------------------------------------------------------------------------
                */
                PengirimanDetail::create([
                    'pengiriman_id' => $pengiriman->id,
                    'barang_id' => $barangId,
                    'qty_kirim' => $qtyKirim,
                ]);

                /*
                |--------------------------------------------------------------------------
                | 4. Update qty terkirim pada alokasi produksi
                |--------------------------------------------------------------------------
                */
                $alokasi->increment('qty_terkirim', $qtyKirim);

                /*
                |--------------------------------------------------------------------------
                | 5. Kurangi stok barang jadi
                |--------------------------------------------------------------------------
                */
                DB::table('stok_gudang')
                    ->where('id', $stok->id)
                    ->decrement('jumlah', $qtyKirim);
            }

            /*
            |--------------------------------------------------------------------------
            | 6. Cek apakah seluruh produk dalam pesanan sudah terkirim
            |--------------------------------------------------------------------------
            */
            $detailPesanan = DB::table('pesanan_detail')
                ->where('pesanan_id', $request->pesanan_id)
                ->get();

            $semuaSudahTerkirim = true;

            foreach ($detailPesanan as $detailPesananItem) {
                $qtySudahKirim = DB::table('pengiriman_detail')
                    ->join('pengiriman', 'pengiriman_detail.pengiriman_id', '=', 'pengiriman.id')
                    ->where('pengiriman.pesanan_id', $request->pesanan_id)
                    ->where('pengiriman_detail.barang_id', $detailPesananItem->produk_id)
                    ->sum('pengiriman_detail.qty_kirim');

                if (floatval($qtySudahKirim) < floatval($detailPesananItem->qty)) {
                    $semuaSudahTerkirim = false;
                    break;
                }
            }

            /*
            |--------------------------------------------------------------------------
            | 7. Update status pesanan
            |--------------------------------------------------------------------------
            */
            DB::table('pesanan')
                ->where('id', $request->pesanan_id)
                ->update([
                    'status_pesanan' => $semuaSudahTerkirim ? 'Selesai' : 'Siap kirim',
                    'updated_at' => now(),
                ]);

            DB::commit();

            return redirect()
                ->route('pengiriman.index')
                ->with(
                    'success',
                    'Surat Jalan berhasil diterbitkan, stok dikurangi, dan alokasi produksi diperbarui.'
                );
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with(
                'error',
                'Gagal memproses pengiriman: ' . $e->getMessage()
            );
        }
    }
}