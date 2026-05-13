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
        Schema::create('permintaan_bahan_baku_detail', function (Blueprint $table) {

            $table->id();

            $table->foreignId('permintaan_id')
                ->constrained('permintaan_bahan_baku')
                ->cascadeOnDelete();

            $table->foreignId('barang_id')
                ->constrained('master_barang')
                ->cascadeOnDelete();

            $table->decimal('qty_permintaan', 18, 4);

            $table->decimal('qty_disetujui', 18, 4)
                ->default(0);

            $table->decimal('qty_dikeluarkan', 18, 4)
                ->default(0);

            $table->text('keterangan')
                ->nullable();

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permintaan_bahan_baku_detail');
    }
};