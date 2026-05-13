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

            // relasi ke pesanan B2B
            $table->foreignId('pesanan_id')
                  ->constrained('pesanan');

            $table->datetime('tanggal_mulai');

            $table->datetime('tanggal_selesai')
                  ->nullable();

            $table->enum('status_produksi', [
                'Pending',
                'Diproses',
                'Selesai',
                'Batal'
            ])->default('Pending');

            // gudang bahan baku
            $table->foreignId('gudang_bahan_id')
                  ->constrained('master_gudang');

            // gudang hasil produksi
            $table->foreignId('gudang_hasil_id')
                  ->constrained('master_gudang');

            // user yang membuat produksi
            $table->foreignId('created_by')
                  ->constrained('users');

            $table->timestamps();
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