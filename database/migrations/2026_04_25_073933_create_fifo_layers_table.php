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
        Schema::create('fifo_layers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barang_id')->constrained('master_barang');
            $table->foreignId('gudang_id')->constrained('master_gudang');
            $table->decimal('qty_masuk', 15, 2);
            $table->decimal('qty_sisa', 15, 2);
            $table->decimal('harga_per_unit', 15, 2);
            $table->string('batch_number')->nullable();
            $table->datetime('tanggal_masuk');
            $table->foreignId('referensi_transaksi')->constrained('transaksi_stok');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fifo_layers');
    }
};
