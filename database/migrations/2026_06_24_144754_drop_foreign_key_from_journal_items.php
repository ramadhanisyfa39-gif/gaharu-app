<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journal_items', function (Blueprint $table) {
            // 1. Hapus aturan foreign key yang mengunci ke tabel journals
            $table->dropForeign('journal_items_journal_id_foreign');

            // 2. Ubah tipe data journal_id dari foreignId menjadi integer biasa agar bebas diisi
            $table->integer('journal_id')->change();
        });
    }

    public function down(): void
    {
        Schema::table('journal_items', function (Blueprint $table) {
            // Jika ingin dikembalikan seperti semula (opsional)
            $table->foreignId('journal_by')->constrained('journals');
        });
    }
};
