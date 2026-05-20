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
        Schema::create('penjualanpos_detail', function (Blueprint $table) {
            $table->id();

            $table->foreignId('penjualan_id')
                  ->constrained('penjualan_pos')
                  ->cascadeOnDelete();

            $table->foreignId('produk_id')
                  ->constrained('master_barang');

            $table->decimal('qty', 15, 2);

            $table->decimal('harga', 15, 2);

            $table->decimal('hpp_satuan', 15, 2)
                  ->default(0);

            $table->decimal('subtotal', 15, 2);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penjualan_pos_detail');
    }
};