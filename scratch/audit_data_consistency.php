<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== 1. AUDIT DUA TABEL STOK: stok_gudang vs stok_gudang_batch ===\n";
$stokGudang = DB::table('stok_gudang')->get();
foreach ($stokGudang as $sg) {
    $sumBatch = DB::table('stok_gudang_batch')
        ->where('gudang_id', $sg->gudang_id)
        ->where('barang_id', $sg->barang_id)
        ->sum('qty_sisa');
    
    $barang = DB::table('master_barang')->where('id', $sg->barang_id)->first();
    $gudang = DB::table('master_gudang')->where('id', $sg->gudang_id)->first();
    
    $namaBarang = $barang ? $barang->nama : "Barang #{$sg->barang_id}";
    $namaGudang = $gudang ? $gudang->nama : "Gudang #{$sg->gudang_id}";
    
    $diff = $sg->jumlah - $sumBatch;
    if (abs($diff) > 0.001) {
        echo "[BEDA STOK] Gudang: {$namaGudang} | Barang: {$namaBarang} => stok_gudang: {$sg->jumlah}, sum(batch.qty_sisa): {$sumBatch}, Selisih: {$diff}\n";
    } else {
        echo "[MATCH] Gudang: {$namaGudang} | Barang: {$namaBarang} => Total: {$sg->jumlah}\n";
    }
}

echo "\n=== 2. AUDIT MODUL PENGELUARAN BAHAN BAKU ===\n";
$pbks = DB::table('pengeluaran_bahan_baku')->get();
echo "Total Pengeluaran Bahan Baku: " . count($pbks) . "\n";
foreach ($pbks as $p) {
    echo "ID: {$p->id} | Kode: {$p->kode_pengeluaran} | Status: {$p->status} | Ket: {$p->keterangan}\n";
}

echo "\n=== 3. AUDIT JURNAL KEUANGAN ===\n";
$journalTypes = DB::table('journal_items')
    ->select('journal_type', DB::raw('count(*) as count'), DB::raw('sum(debit) as total_debit'), DB::raw('sum(kredit) as total_kredit'))
    ->groupBy('journal_type')
    ->get();

echo "Grouped Journal Items:\n";
foreach ($journalTypes as $jt) {
    echo "  - Type: '{$jt->journal_type}' | Count: {$jt->count} | Debit: {$jt->total_debit} | Kredit: {$jt->total_kredit}\n";
}

echo "\n=== 4. CEK RELASI POLIMORFIK AJP / JURNAL ===\n";
$ajpOrphans = DB::table('journal_items')
    ->where('journal_type', 'App\\Models\\JurnalPenyesuaian')
    ->whereNotExists(function($q) {
        $q->select(DB::raw(1))->from('jurnal_penyesuaian')->whereColumn('jurnal_penyesuaian.id', 'journal_items.journal_id');
    })->get();
echo "Orphan AJP Details (point to non-existent JurnalPenyesuaian): " . count($ajpOrphans) . "\n";

$journalOrphans = DB::table('journal_items')
    ->whereIn('journal_type', ['jurnal_umum', 'jurnal_pembelian', 'jurnal_penjualan_pos', 'jurnal_penjualan_b2b', 'closing', 'opening'])
    ->whereNotExists(function($q) {
        $q->select(DB::raw(1))->from('journals')->whereColumn('journals.id', 'journal_items.journal_id');
    })->get();
echo "Orphan Standard Journal Details (point to non-existent Journal): " . count($journalOrphans) . "\n";

echo "\nDone Audit.\n";
