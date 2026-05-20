<?php

use Illuminate\Support\Facades\Route;

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

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Profile
    |--------------------------------------------------------------------------
    */
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    /*
    |--------------------------------------------------------------------------
    | Master Data
    |--------------------------------------------------------------------------
    */
    Route::resource('kategori', KategoriController::class)->names('kategori');
    Route::resource('customer', CustomerController::class)->names('customer');
    Route::resource('barang', BarangController::class)->names('barang');
    Route::resource('suppliers', SupplierController::class)->names('suppliers');
    Route::resource('gudangs', GudangController::class)->names('gudangs');
    Route::resource('roles', RoleController::class);
    Route::resource('users', UserController::class);
    Route::resource('karyawan', KaryawanController::class)->names('karyawan');
    Route::resource('coa', CoaController::class)->names('coa');

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
    | Penggajian & Jurnal
    |--------------------------------------------------------------------------
    */
    Route::resource('penggajian', PenggajianController::class);
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
        'destroy'
    ]);

    /*
    |--------------------------------------------------------------------------
    | Stok Gudang
    |--------------------------------------------------------------------------
    */
    Route::get('/stok-gudang', [StokGudangController::class, 'index'])
        ->name('stok-gudang.index');

    /*
    |--------------------------------------------------------------------------
    | Pesanan
    |--------------------------------------------------------------------------
    */
    Route::resource('pesanan', PesananController::class)->names('pesanan');
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
    });

    /*
    |--------------------------------------------------------------------------
    | Input Produksi
    |--------------------------------------------------------------------------
    */
    Route::resource('produksi', ProduksiController::class)->middleware('auth');

    /*
    |--------------------------------------------------------------------------
    | Pengeluaran Bahan Baku
    |--------------------------------------------------------------------------
    */
    Route::resource('pengeluaran-bahan-baku', PengeluaranBahanBakuController::class);

    Route::get(
        'pengeluaran-bahan-baku/{id}/approve',
        [PengeluaranBahanBakuController::class, 'approve']
    )->name('pengeluaran-bahan-baku.approve');


    // Halaman form & riwayat (Butuh ID barang)
    Route::get('/harga-barang-pos/{id?}', [HargaBarangPosController::class, 'index'])->name('harga.index');

    // Proses simpan data
    Route::post('/harga-barang-pos/store', [HargaBarangPosController::class, 'store'])->name('harga.store');

    // TAMBAHKAN DUA BARIS BARU INI:
    Route::put('/harga-barang-pos/{id}', [HargaBarangPosController::class, 'update'])->name('harga.update');
    Route::delete('/harga-barang-pos/{id}', [HargaBarangPosController::class, 'destroy'])->name('harga.destroy');
    });

    Route::get('/penjualan_pos/get-harga/{produk_id}', [\App\Http\Controllers\PenjualanPosController::class, 'getHargaAktif']);

require __DIR__ . '/auth.php';
