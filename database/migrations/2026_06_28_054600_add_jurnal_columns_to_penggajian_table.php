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
        Schema::table('penggajian', function (Blueprint $table) {
            // 1. Tambah kolom status_jurnal (0 = belum dijurnal, 1 = sudah)
            // Diletakkan setelah kolom status agar rapi
            $table->boolean('status_jurnal')->default(false)->after('status');

            // 2. Tambah kolom journal_id sebagai foreign key (nullable karena diisi belakangan)
            $table->foreignId('journal_id')
                ->nullable()
                ->after('status_jurnal')
                ->constrained('journals')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('penggajian', function (Blueprint $table) {
            // Drop foreign key terlebih dahulu sebelum drop kolomnya
            $table->dropForeign(['journal_id']);

            // Drop kolom yang tadi ditambahkan
            $table->dropColumn(['status_jurnal', 'journal_id']);
        });
    }
};
