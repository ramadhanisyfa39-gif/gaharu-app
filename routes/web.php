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


require __DIR__ . '/auth.php';
