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
        Schema::create('pembelian_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pembelian_id')->constrained('pembelian');
            $table->foreignId('barang_id')->constrained('master_barang');
            $table->decimal('qty', 15, 2);
            $table->decimal('harga', 15, 2);
            $table->string('batch_number')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pembelian_detail');
    }
};
