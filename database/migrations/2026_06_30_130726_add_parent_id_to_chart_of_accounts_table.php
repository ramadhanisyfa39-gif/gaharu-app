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
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            // 1. Tambahkan kolom parent_id setelah kolom kode, tipenya unsignedBigInteger dan boleh kosong (nullable)
            $table->unsignedBigInteger('parent_id')->nullable()->after('kode');

            // 2. Buat relasi foreign key yang merujuk ke id di tabelnya sendiri
            $table->foreign('parent_id')
                ->references('id')
                ->on('chart_of_accounts')
                ->onDelete('cascade'); // Jika induk dihapus, anak otomatis ikut terhapus
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            // Hapus foreign key dan kolom jika migrasi di-rollback
            $table->dropForeign(['parent_id']);
            $table->dropColumn('parent_id');
        });
    }
};
