<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Menambahkan kolom journal_type ke journal_items untuk membedakan
     * baris ini berasal dari modul jurnal mana (penjualan_pos, pembelian, dst).
     * Ini menyelesaikan bug: journal_id tidak unik secara global karena setiap
     * tabel header (jurnal_penjualan_pos, jurnal_pembelian) punya auto-increment
     * sendiri-sendiri, sehingga bisa collision dan tercampur saat query JOIN/WHERE
     * hanya mengandalkan journal_id tanpa filter modul.
     */
    public function up(): void
    {
        Schema::table('journal_items', function (Blueprint $table) {
            $table->string('journal_type', 50)->nullable()->after('journal_id');
        });

        // Index terpisah (bukan composite) supaya tidak perlu menebak urutan kolom
        // yang dipakai di WHERE; MySQL/Postgres bisa pakai index gabungan otomatis
        // kalau query memang selalu menyertakan kedua kolom bersamaan.
        Schema::table('journal_items', function (Blueprint $table) {
            $table->index(['journal_id', 'journal_type'], 'journal_items_journal_id_type_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('journal_items', function (Blueprint $table) {
            $table->dropIndex('journal_items_journal_id_type_idx');
            $table->dropColumn('journal_type');
        });
    }
};
