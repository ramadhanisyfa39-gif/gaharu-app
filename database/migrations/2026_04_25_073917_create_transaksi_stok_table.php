<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transaksi_stok', function (Blueprint $table) {
            $table->id();
            $table->datetime('tanggal');
            $table->string('tipe'); // masuk, keluar, dll
            $table->string('source_type'); // pembelian, produksi, dll
            $table->integer('source_id');
            $table->foreignId('gudang_asal_id')->nullable()->constrained('master_gudang');
            $table->foreignId('gudang_tujuan_id')->nullable()->constrained('master_gudang');
            $table->foreignId('barang_id')->constrained('master_barang');
            $table->decimal('qty', 15, 2);
            $table->decimal('total_harga', 15, 2);
            $table->foreignId('created_by')->constrained('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaksi_stok');
    }
};
