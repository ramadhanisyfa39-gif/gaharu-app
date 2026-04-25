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
        Schema::create('produksi', function (Blueprint $table) {
            $table->id();
            $table->string('kode_produksi')->unique();
            $table->datetime('tanggal_mulai');
            $table->datetime('tanggal_selesai')->nullable();
            $table->string('status_produksi');
            $table->foreignId('gudang_bahan_id')->constrained('master_gudang');
            $table->foreignId('gudang_hasil_id')->constrained('master_gudang');
            $table->foreignId('created_by')->constrained('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produksi');
    }
};
