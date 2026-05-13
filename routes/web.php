<?php


use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
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
use App\Http\Controllers\PenjualanPosController;
use App\Http\Controllers\WorkOrderController;



Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('kategori', KategoriController::class)->names('kategori');
    Route::resource('customer', CustomerController::class)->names('customer');
    Route::resource('barang', BarangController::class)->names('barang');
    Route::resource('resep', ResepBtklBopController::class);

    Route::get('/resep-bahan/{id}', [ResepBahanBakuController::class, 'show'])
        ->name('resep.bahan.show');

    Route::post('/resep-bahan/{id}', [ResepBahanBakuController::class, 'store'])
        ->name('resep.bahan.store');

    Route::delete('/resep-bahan/{id}', [ResepBahanBakuController::class, 'destroy'])
        ->name('resep.bahan.destroy');

    Route::put('/resep-bahan/{id}', [ResepBahanBakuController::class, 'update'])
    ->name('resep.bahan.update');
    
    Route::put('/resep-bahan/{id}', [ResepBahanBakuController::class, 'update'])
    ->name('resep.bahan.update'); //update bahan di form detail

    Route::resource('suppliers', SupplierController::class)->names('suppliers');
    Route::resource('gudangs', GudangController::class)->names('gudangs');
    Route::resource('roles', RoleController::class);
    Route::resource('users', UserController::class);

    Route::resource('karyawan', KaryawanController::class)->names('karyawan');
    Route::resource('coa', CoaController::class)->names('coa');
    Route::resource('penggajian', PenggajianController::class);
    Route::resource('jurnal', JurnalController::class);
    Route::resource('pembelian', PembelianController::class) ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
    Route::get('/stok-gudang', [StokGudangController::class, 'index'])->name('stok-gudang.index');
    });


    Route::resource('pesanan', PesananController::class)->names('pesanan');
    Route::resource('pesanan-detail', PesananDetailController::class);
    Route::post('/pesanan/{id}/pembayaran', [PesananController::class, 'simpanPembayaran'])->name('pesanan.bayar');
    Route::get('/pesanan/{id}/kwitansi', [App\Http\Controllers\PesananController::class, 'kwitansi'])->name('pesanan.kwitansi');

    Route::resource('penjualan_pos', PenjualanPosController::class);
    Route::resource('penjualanpos-detail', PenjualanPosDetailController::class);


    Route::get('/work-order', [WorkOrderController::class, 'index'])
    ->name('wo.index');

    Route::get('/work-order/create/{id}', [WorkOrderController::class, 'create'])
        ->name('wo.create');

    Route::post('/work-order/store', [WorkOrderController::class, 'store'])
        ->name('wo.store');

    Route::get('/work-order/show/{id}', [WorkOrderController::class, 'show'])
        ->name('wo.show');

    // 1. Route untuk menampilkan halaman review (Ini sepertinya sudah ada)
    Route::post('/work-order/massal/review', [App\Http\Controllers\WorkOrderController::class, 'reviewMassal'])->name('wo.review_massal');

    // 2. TAMBAHKAN ROUTE INI UNTUK MENYIMPAN DATA (Ini yang membuat error)
    Route::post('/work-order/massal/store', [App\Http\Controllers\WorkOrderController::class, 'storeMassal'])->name('wo.store_massal');

    // (Opsional) Jaring pengaman yang kita bahas sebelumnya jika user ter-refresh
    Route::get('/work-order/massal/review', function() {
        return redirect()->route('wo.index')->with('error', 'Sesi tidak valid, silakan ulangi.');
    });
    

    // Route untuk mengirim data permintaan bahan ke gudang secara otomatis
    Route::post('/work-order/{id}/kirim-produksi', [WorkOrderController::class, 'kirimKeProduksi'])->name('wo.kirim_produksi');

    require __DIR__ . '/auth.php';
