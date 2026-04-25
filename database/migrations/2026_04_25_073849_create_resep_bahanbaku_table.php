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
        Schema::create('resep_bahanbaku', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resep_id')->constrained('resep_btkl_bop');
            $table->foreignId('bahan_id')->constrained('master_barang');
            $table->decimal('qty_bahan', 15, 2);
            $table->string('satuan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resep_bahanbaku');
    }
};
