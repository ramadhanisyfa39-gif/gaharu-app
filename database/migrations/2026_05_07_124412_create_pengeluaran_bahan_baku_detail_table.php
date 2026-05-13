<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengeluaran_bahan_baku_detail', function (Blueprint $table) {

            $table->id();

            $table->foreignId('pengeluaran_id')
                ->constrained('pengeluaran_bahan_baku')
                ->cascadeOnDelete();

            $table->foreignId('barang_id')
                ->constrained('master_barang');

            $table->decimal('qty', 15, 2);

            $table->string('satuan');

            $table->decimal('harga_satuan', 15, 2)
                ->default(0);

            $table->decimal('total_harga', 15, 2)
                ->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengeluaran_bahan_baku_detail');
    }
};