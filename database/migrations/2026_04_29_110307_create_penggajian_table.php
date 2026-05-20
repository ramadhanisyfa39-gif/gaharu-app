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
        Schema::create('penggajian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('karyawan_id')->constrained('karyawan');
            $table->string('periode_bulan_tahun'); // Contoh: "04-2026"
            $table->decimal('gaji_pokok', 15, 2);
            $table->decimal('tunjangan_transport', 15, 2)->default(0);
            $table->decimal('tunjangan_makan', 15, 2)->default(0);
            $table->decimal('lembur', 15, 2)->default(0);
            $table->decimal('bonus_target', 15, 2)->default(0);
            $table->decimal('bonus_tanggal_merah', 15, 2)->default(0);
            $table->decimal('bonus_birthday', 15, 2)->default(0);
            $table->decimal('bonus_dll', 15, 2)->default(0);
            $table->decimal('potongan_inventaris', 15, 2)->default(0);
            $table->decimal('potongan_terlambat', 15, 2)->default(0);
            $table->decimal('total_gaji_bersih', 15, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penggajian');
    }
};
