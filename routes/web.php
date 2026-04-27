<?php


use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\BarangController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\GudangController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\KaryawanController;
use App\Http\Controllers\CoaController;
use App\Http\Controllers\PenggajianController;
use App\Http\Controllers\JurnalController;

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

    Route::resource('kategori', KategoriController::class);
    Route::resource('customer', CustomerController::class);
    Route::resource('barang', BarangController::class);


    Route::resource('suppliers', SupplierController::class);
    Route::resource('gudangs', GudangController::class);
    Route::resource('roles', RoleController::class);
    Route::resource('users', UserController::class);

    // --- ROUTE CRUD KAMU ---
    Route::resource('karyawan', KaryawanController::class);
    Route::resource('coa', CoaController::class);
    Route::resource('penggajian', PenggajianController::class);
    Route::resource('jurnal', JurnalController::class);
});


require __DIR__ . '/auth.php';
