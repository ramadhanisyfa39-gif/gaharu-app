<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\BarangController;
use App\Http\Controllers\ResepBtklBopController;
use App\Http\Controllers\ResepBahanBakuController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\GudangController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\KaryawanController;
use App\Http\Controllers\CoaController;
use App\Http\Controllers\PenggajianController;
use App\Http\Controllers\JurnalController;
use App\Http\Controllers\PembelianController;
use App\Http\Controllers\StokGudangController;
use App\Http\Controllers\PesananController;
use App\Http\Controllers\PesananDetailController;
use App\Http\Controllers\PenjualanPosController;
use App\Http\Controllers\PenjualanPosDetailController;
use App\Http\Controllers\WorkOrderController;
use App\Http\Controllers\PengeluaranBahanBakuController;
use App\Http\Controllers\ProduksiController;
use App\Http\Controllers\HargaBarangPosController;
use App\Http\Controllers\StokGudangBatchController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\LaporanProduksiController;
use App\Http\Controllers\PengirimanController;
use App\Http\Controllers\LaporanPenjualanController;
use App\Http\Controllers\LaporanPenjualanPosController;

/*
|--------------------------------------------------------------------------
| Halaman Awal
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

/*
|--------------------------------------------------------------------------
| Route yang Memerlukan Login
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Profile
    |--------------------------------------------------------------------------
    */
    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');

    /*
    |--------------------------------------------------------------------------
    | Master Data
    |--------------------------------------------------------------------------
    */
    Route::resource('kategori', KategoriController::class)
        ->names('kategori');

    Route::resource('customer', CustomerController::class)
        ->names('customer');

    Route::resource('barang', BarangController::class)
        ->names('barang');

    Route::resource('suppliers', SupplierController::class)
        ->names('suppliers');

    Route::resource('gudangs', GudangController::class)
        ->names('gudangs');

    Route::resource('roles', RoleController::class);

    Route::resource('users', UserController::class);

    Route::resource('karyawan', KaryawanController::class)
        ->names('karyawan');

    Route::resource('coa', CoaController::class)
        ->names('coa');

    Route::get('/barang/generate-kode/{kategori}', [BarangController::class, 'generateKode'])
        ->name('barang.generate-kode');

    /*
    |--------------------------------------------------------------------------
    | Resep
    |--------------------------------------------------------------------------
    */
    Route::resource('resep', ResepBtklBopController::class);

    Route::get('/resep-bahan/{id}', [ResepBahanBakuController::class, 'show'])
        ->name('resep.bahan.show');

    Route::post('/resep-bahan/{id}', [ResepBahanBakuController::class, 'store'])
        ->name('resep.bahan.store');

    Route::delete('/resep-bahan/{id}', [ResepBahanBakuController::class, 'destroy'])
        ->name('resep.bahan.destroy');

    /*
    |--------------------------------------------------------------------------
    | Penggajian dan Jurnal Umum
    |--------------------------------------------------------------------------
    */
    Route::resource('penggajian', PenggajianController::class);

    Route::get('/closing', [JurnalController::class, 'closingPage'])
        ->name('closing.index');

    Route::post('/closing', [JurnalController::class, 'closePeriod'])
        ->name('closing.create');

    Route::resource('jurnal', JurnalController::class);

    /*
    |--------------------------------------------------------------------------
    | Pembelian
    |--------------------------------------------------------------------------
    */
    Route::resource('pembelian', PembelianController::class)->only([
        'index',
        'create',
        'store',
        'show',
        'edit',
        'update',
        'destroy',
    ]);

    /*
    |--------------------------------------------------------------------------
    | Stok Gudang
    |--------------------------------------------------------------------------
    */
    Route::get('/stok-gudang', [StokGudangController::class, 'index'])
        ->name('stok-gudang.index');

    Route::get('/stok-gudang-batch', [StokGudangBatchController::class, 'index'])
        ->name('stok-gudang-batch.index');

    /*
    |--------------------------------------------------------------------------
    | Pesanan B2B
    |--------------------------------------------------------------------------
    */
    Route::resource('pesanan', PesananController::class)
        ->names('pesanan');

    Route::resource('pesanan-detail', PesananDetailController::class);

    Route::post('/pesanan/{id}/pembayaran', [PesananController::class, 'simpanPembayaran'])
        ->name('pesanan.bayar');

    Route::get('/pesanan/{id}/kwitansi', [PesananController::class, 'kwitansi'])
        ->name('pesanan.kwitansi');

    /*
    |--------------------------------------------------------------------------
    | Penjualan POS
    |--------------------------------------------------------------------------
    */
    Route::resource('penjualan_pos', PenjualanPosController::class);

    Route::resource('penjualanpos-detail', PenjualanPosDetailController::class);

    Route::get('/penjualan_pos/get-harga/{produk_id}', [PenjualanPosController::class, 'getHargaAktif'])
        ->name('penjualan_pos.get-harga');

    Route::get('/laporan-penjualan-pos', [LaporanPenjualanPosController::class, 'index'])
        ->name('penjualan_pos.laporan');

    /*
    |--------------------------------------------------------------------------
    | Work Order
    |--------------------------------------------------------------------------
    */
    Route::prefix('work-order')->name('wo.')->group(function () {

        Route::get('/', [WorkOrderController::class, 'index'])
            ->name('index');

        Route::get('/create/{id}', [WorkOrderController::class, 'create'])
            ->name('create');

        Route::post('/store', [WorkOrderController::class, 'store'])
            ->name('store');

        Route::get('/show/{id}', [WorkOrderController::class, 'show'])
            ->name('show');

        Route::post('/massal/review', [WorkOrderController::class, 'reviewMassal'])
            ->name('review_massal');

        Route::post('/massal/store', [WorkOrderController::class, 'storeMassal'])
            ->name('store_massal');

        Route::get('/massal/review', function () {
            return redirect()
                ->route('wo.index')
                ->with('error', 'Sesi tidak valid, silakan ulangi.');
        });

        Route::post('/{id}/kirim-produksi', [WorkOrderController::class, 'kirimKeProduksi'])
            ->name('kirim_produksi');

        Route::get('/stok-gudang-batch', [StokGudangBatchController::class, 'index'])
            ->name('stok-gudang-batch.index');
    });

    /*
    |--------------------------------------------------------------------------
    | Input Produksi
    |--------------------------------------------------------------------------
    */
    Route::resource('produksi', ProduksiController::class);

    /*
    |--------------------------------------------------------------------------
    | Pengeluaran Bahan Baku
    |--------------------------------------------------------------------------
    */
    Route::resource('pengeluaran-bahan-baku', PengeluaranBahanBakuController::class);

    Route::get('/pengeluaran-bahan-baku/{id}/approve', [PengeluaranBahanBakuController::class, 'approve'])
        ->name('pengeluaran-bahan-baku.approve');

    /*
    |--------------------------------------------------------------------------
    | Harga Barang POS
    |--------------------------------------------------------------------------
    */
    Route::get('/harga-barang-pos/{id?}', [HargaBarangPosController::class, 'index'])
        ->name('harga.index');

    Route::post('/harga-barang-pos/store', [HargaBarangPosController::class, 'store'])
        ->name('harga.store');

    Route::put('/harga-barang-pos/{id}', [HargaBarangPosController::class, 'update'])
        ->name('harga.update');

    Route::delete('/harga-barang-pos/{id}', [HargaBarangPosController::class, 'destroy'])
        ->name('harga.destroy');

    /*
    |--------------------------------------------------------------------------
    | Pengiriman
    |--------------------------------------------------------------------------
    */
    Route::get('/pengiriman', [PengirimanController::class, 'index'])
        ->name('pengiriman.index');

    Route::get('/pengiriman/create', [PengirimanController::class, 'create'])
        ->name('pengiriman.create');

    Route::post('/pengiriman/store', [PengirimanController::class, 'store'])
        ->name('pengiriman.store');

    Route::get('/pengiriman/pesanan-detail/{id}', [PengirimanController::class, 'getPesananDetail'])
        ->name('pengiriman.pesanan-detail');

    /*
    |--------------------------------------------------------------------------
    | Adjustment
    |--------------------------------------------------------------------------
    */
    Route::get('/adjustment', [JurnalController::class, 'adjustmentIndex'])
        ->name('adjustment.index');

    Route::get('/adjustment/create', [JurnalController::class, 'adjustmentPage'])
        ->name('adjustment.create');

    Route::post('/adjustment', [JurnalController::class, 'adjustmentStore'])
        ->name('adjustment.store');

    /*
    |--------------------------------------------------------------------------
    | Laporan Penjualan
    |--------------------------------------------------------------------------
    */
    Route::get('/laporan-penjualan', [LaporanPenjualanController::class, 'index'])
        ->name('laporan.penjualan');

    /*
    |--------------------------------------------------------------------------
    | Laporan Keuangan
    |--------------------------------------------------------------------------
    */
    Route::prefix('laporan')->name('laporan.')->group(function () {

        Route::get('/', [LaporanController::class, 'labaRugiIndex'])
            ->name('index');

        Route::get('/laba-rugi', [LaporanController::class, 'labaRugiIndex'])
            ->name('laba-rugi.index');

        Route::get('/laba-rugi/show', [LaporanController::class, 'labaRugiShow'])
            ->name('laba-rugi.show');

        Route::get('/neraca', [LaporanController::class, 'neracaIndex'])
            ->name('neraca.index');

        Route::get('/neraca/show', [LaporanController::class, 'neracaShow'])
            ->name('neraca.show');

        Route::get('/arus-kas', [LaporanController::class, 'arusKasIndex'])
            ->name('arus-kas.index');

        Route::get('/arus-kas/show', [LaporanController::class, 'arusKasShow'])
            ->name('arus-kas.show');

        Route::get('/buku-besar', [LaporanController::class, 'bukuBesar'])
            ->name('buku-besar.index');

        Route::get('/neraca-saldo', [LaporanController::class, 'neracaSaldo'])
            ->name('neraca-saldo.index');
    });

    /*
    |--------------------------------------------------------------------------
    | Laporan Produksi
    |--------------------------------------------------------------------------
    */
    Route::prefix('laporan-produksi')->group(function () {

        Route::get('/rekapitulasi', [LaporanProduksiController::class, 'rekapitulasi'])
            ->name('laporan.rekapitulasi');

        Route::get('/hpp', [LaporanProduksiController::class, 'hpp'])
            ->name('laporan.hpp');
    });

    /*
    |--------------------------------------------------------------------------
    | Jurnal Pembelian
    |--------------------------------------------------------------------------
    */
    Route::get('/jurnal-pembelian', [JurnalController::class, 'pembelianIndex'])
        ->name('jurnal-pembelian.index');

    Route::get('/jurnal-pembelian/create/{id}', [JurnalController::class, 'pembelianCreate'])
        ->name('jurnal-pembelian.create');

    Route::post('/jurnal-pembelian/store/{id}', [JurnalController::class, 'prosesJurnalPembelian'])
        ->name('jurnal-pembelian.store');

    /*
    |--------------------------------------------------------------------------
    | Jurnal Penjualan POS
    |--------------------------------------------------------------------------
    */
    Route::get('/jurnal-penjualanpos', [JurnalController::class, 'penjualanposIndex'])
        ->name('jurnal-penjualanpos.index');

    Route::get('/jurnal-penjualanpos/create/{id}', [JurnalController::class, 'penjualanposCreate'])
        ->name('jurnal-penjualanpos.create');

    Route::post('/jurnal-penjualanpos/store/{id}', [JurnalController::class, 'penjualanposStore'])
        ->name('jurnal-penjualanpos.store');

    /*
    |--------------------------------------------------------------------------
    | Jurnal Penjualan B2B
    |--------------------------------------------------------------------------
    */
    Route::get('/jurnal-penjualanb2b', [JurnalController::class, 'penjualanb2bIndex'])
        ->name('jurnal-penjualanb2b.index');

    Route::get('/jurnal-penjualanb2b/create/{id}', [JurnalController::class, 'penjualanb2bCreate'])
        ->name('jurnal-penjualanb2b.create');

    Route::post('/jurnal-penjualanb2b/store/{id}', [JurnalController::class, 'penjualanB2BStore'])
        ->name('jurnal-penjualanb2b.store');

    /*
    |--------------------------------------------------------------------------
    | Buku Pembantu Utang
    |--------------------------------------------------------------------------
    */
    Route::get('/buku-pembantu-utang', [JurnalController::class, 'bukuPembantuUtang'])
        ->name('bukupembantu-utang.index');
});

require __DIR__ . '/auth.php';