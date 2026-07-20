<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\JurnalPenyesuaian;
use App\Models\StockOpname;

$jurnals = JurnalPenyesuaian::with('details')->get();
echo "Total Jurnal Penyesuaian: " . $jurnals->count() . "\n";

foreach ($jurnals as $j) {
    echo "\n----------------------------------------\n";
    echo "ID: " . $j->id . " | Ref: " . $j->no_ref . " | Date: " . $j->tanggal . "\n";
    echo "Desc: " . $j->deskripsi . "\n";
    $debit = $j->details->sum('debit');
    $kredit = $j->details->sum('kredit');
    echo "Total Debit: " . number_format($debit, 2) . " | Total Kredit: " . number_format($kredit, 2) . "\n";
    if ($debit != $kredit) {
        echo "!!! TIDAK BALANCE !!!\n";
    }
}

echo "\n========================================\n";
$so = StockOpname::with('details')->get();
echo "Total Stock Opname: " . $so->count() . "\n";
foreach ($so as $s) {
    echo "SO ID: " . $s->id . " | Ref: " . $s->kode_opname . "\n";
    foreach ($s->details as $d) {
        echo "  - Barang: " . $d->barang_id . " | Selisih: " . $d->selisih . " | Nilai Selisih: " . number_format($d->nilai_selisih, 2) . "\n";
    }
}

echo "\nDone.\n";
