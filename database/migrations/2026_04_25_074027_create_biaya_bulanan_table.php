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
        Schema::create('biaya_bulanan', function (Blueprint $table) {
            $table->id();
            $table->string('periode_bulan_tahun');
            $table->string('jenis_biaya');
            $table->decimal('total_biaya', 15, 2);
            $table->decimal('persen_hpp', 5, 2);
            $table->decimal('persen_beban', 5, 2);
            $table->decimal('nilai_hpp', 15, 2);
            $table->decimal('nilai_beban', 15, 2);
            $table->date('tanggal_bayar');
            $table->text('keterangan')->nullable();
            $table->foreignId('created_by')->constrained('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('biaya_bulanan');
    }
};
