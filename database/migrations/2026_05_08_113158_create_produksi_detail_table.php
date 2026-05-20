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
        Schema::create('produksi_detail', function (Blueprint $table) {
            $table->id();

            $table->foreignId('produksi_id')
                  ->constrained('produksi');

            $table->foreignId('produk_id')
                  ->constrained('master_barang');

            $table->integer('qty');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produksi_detail');
    }
};