<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alokasi_produksi_pesanan', function (Blueprint $table) {
            $table->id();

            $table->foreignId('produksi_id')
                ->constrained('produksi')
                ->cascadeOnDelete();

            $table->foreignId('pesanan_id')
                ->constrained('pesanan')
                ->cascadeOnDelete();

            $table->foreignId('produk_id')
                ->constrained('master_barang')
                ->cascadeOnDelete();

            $table->decimal('qty_alokasi', 15, 2);

            $table->decimal('qty_terkirim', 15, 2)
                ->default(0);

            $table->decimal('hpp_per_unit', 15, 2);

            $table->decimal('total_hpp_alokasi', 15, 2);

            $table->timestamps();

            $table->unique(
                ['produksi_id', 'pesanan_id', 'produk_id'],
                'alokasi_produksi_pesanan_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alokasi_produksi_pesanan');
    }
};