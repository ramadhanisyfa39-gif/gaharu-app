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
        Schema::create('master_barang', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kategori_id')->constrained('kategori');
            $table->string('kode_barang')->unique();
            $table->string('nama');
            $table->string('satuan');
            $table->boolean('is_bahan_baku');
            $table->boolean('is_barang_jadi');
            $table->boolean('is_operational');
            $table->boolean('is_direct_consumption');
            $table->decimal('harga_jual_b2b', 15, 2);
            $table->decimal('harga_jual_pos', 15, 2);
            $table->decimal('hpp_referensi', 15, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_barang');
    }
};
