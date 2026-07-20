<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\MasterBarang;
use App\Models\ResepBtklBop;
use App\Models\ResepBahanBaku;
use App\Models\StokGudangBatch;
use App\Models\StokGudang;
use App\Models\PenjualanPos;
use App\Models\PenjualanPosDetail;
use App\Models\StockOpname;
use App\Models\StockOpnameDetail;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\PenjualanPosController;
use App\Http\Controllers\StockOpnameController;

echo "--- MOCKING DATA UNTUK TEST ---\n";
// Buat bahan baku dummy
$bahanId = DB::table('master_barang')->insertGetId([
    'kode_barang' => 'BB-TEST-1',
    'nama' => 'Bahan Baku Test', 
    'satuan' => 'g', 
    'is_bahan_baku' => 1, 
    'is_barang_jadi' => 0, 
    'is_operational' => 0, 
    'is_direct_consumption' => 0,
    'kategori_id' => 1
]);

// Tambah stok FIFO untuk bahan baku
StokGudangBatch::firstOrCreate(
    ['batch_number' => 'BATCH-TEST-1', 'barang_id' => $bahanId, 'gudang_id' => 2],
    ['qty_masuk' => 5000, 'qty_keluar' => 0, 'qty_sisa' => 5000, 'harga_per_qty' => 10, 'is_habis' => 0]
);
StokGudang::updateOrCreate(
    ['gudang_id' => 2, 'barang_id' => $bahanId],
    ['jumlah' => 5000]
);

// Buat barang jadi dummy (POS)
$produkId = DB::table('master_barang')->insertGetId([
    'kode_barang' => 'BJ-TEST-1',
    'nama' => 'Produk Jadi Test', 
    'satuan' => 'Pcs', 
    'is_barang_jadi' => 1, 
    'is_bahan_baku' => 0, 
    'is_operational' => 0, 
    'is_direct_consumption' => 0,
    'harga_jual_pos' => 15000, 
    'tipe_penjualan' => 'POS Gaharu', 
    'kategori_id' => 1
]);

// Buat Resep dengan output_qty = 10 dan qty_bahan = 1000
$resep = ResepBtklBop::firstOrCreate(
    ['produk_id' => $produkId],
    ['output_qty' => 10, 'btkl_per_batch' => 0, 'bop_per_batch' => 0]
);
DB::table('master_barang')->where('id', $produkId)->update(['resep_id' => $resep->id]);

ResepBahanBaku::firstOrCreate(
    ['resep_id' => $resep->id, 'bahan_id' => $bahanId],
    ['qty_bahan' => 1000, 'satuan' => 'g']
);

echo "--- TEST POS SALES (1 Pcs) ---\n";
// Seharusnya deduct: 1000 / 10 * 1 = 100g.
// Harga FIFO: 10/g. HPP = 100 * 10 = 1000.

$penjualan = PenjualanPos::create([
    'kode_transaksi' => 'POS-TEST-' . time(),
    'tanggal' => now(),
    'status' => 'Draft',
    'gudang_id' => 2,
    'total' => 15000,
    'created_by' => 1
]);
$detail = PenjualanPosDetail::create([
    'penjualan_id' => $penjualan->id,
    'produk_id' => $produkId,
    'qty' => 1,
    'harga' => 15000,
    'hpp_satuan' => 0,
    'subtotal' => 15000
]);

$controller = new PenjualanPosController();
$controller->approve($penjualan->id);

$detail->refresh();
echo "HPP Produk (Harapan 1000): " . $detail->hpp_satuan . "\n";
$stokBatch = StokGudangBatch::where('batch_number', 'BATCH-TEST-1')->first();
echo "Qty Keluar dari Batch (Harapan 100): " . $stokBatch->qty_keluar . "\n";

echo "--- TEST STOCK OPNAME (Surplus) ---\n";
// System stok bahan = 4900 (karena kepotong 100)
// Fisik stok bahan = 5000 (Surplus 100)
$so = StockOpname::create([
    'kode_opname' => 'SO-TEST-' . time(),
    'tanggal' => now(),
    'gudang_id' => 2,
    'status' => 'draft',
    'created_by' => 1
]);

$detailSO = StockOpnameDetail::create([
    'stock_opname_id' => $so->id,
    'barang_id' => $bahanId,
    'stok_sistem' => 4900,
    'stok_fisik' => 5000,
    'selisih' => 100,
    'nilai_selisih' => 100 * 10
]);

$soController = new StockOpnameController();
$soController->approve($so->id);

$jurnal = \App\Models\JurnalPenyesuaian::where('source_type', 'stock_opname')->where('source_id', $so->id)->with('details')->first();
if ($jurnal) {
    echo "Jurnal Terbentuk! ID: " . $jurnal->id . "\n";
    echo "Total Debit: " . $jurnal->details->sum('debit') . "\n";
    echo "Total Kredit: " . $jurnal->details->sum('kredit') . "\n";
} else {
    echo "Jurnal Tidak Terbentuk!\n";
}

echo "Done.\n";
