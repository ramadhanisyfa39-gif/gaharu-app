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
        Schema::create('resep_btkl_bop', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produk_id')->constrained('master_barang');
            $table->decimal('output_qty', 15, 2);
            $table->string('satuan_output');
            $table->decimal('btkl_per_batch', 15, 2);
            $table->decimal('bop_per_batch', 15, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resep_btkl_bop');
    }
};
