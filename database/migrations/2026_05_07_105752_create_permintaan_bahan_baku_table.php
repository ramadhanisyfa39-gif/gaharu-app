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
        Schema::create('permintaan_bahan_baku', function (Blueprint $table) {

            $table->id();

            $table->string('kode_permintaan')->unique();

            $table->foreignId('produksi_id')
                ->nullable()
                ->constrained('produksi')
                ->nullOnDelete();

            $table->foreignId('gudang_id')
                ->constrained('master_gudang')
                ->cascadeOnDelete();

            $table->dateTime('tanggal');

            $table->enum('status', [
                'draft',
                'diajukan',
                'disetujui',
                'ditolak',
                'selesai'
            ])->default('draft');

            $table->text('catatan')->nullable();

            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permintaan_bahan_baku');
    }
};