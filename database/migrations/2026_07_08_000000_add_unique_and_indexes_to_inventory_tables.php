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
        // 1. Tambahkan Unique Composite Constraint pada stok_gudang
        Schema::table('stok_gudang', function (Blueprint $table) {
            // Mencegah duplikasi barang_id & gudang_id
            $table->unique(['gudang_id', 'barang_id'], 'stok_gudang_gudang_barang_unique');
        });

        // 2. Tambahkan Indexes pada stok_gudang_batch
        Schema::table('stok_gudang_batch', function (Blueprint $table) {
            $table->index(['gudang_id', 'barang_id'], 'stok_batch_gudang_barang_idx');
            $table->index('is_habis', 'stok_batch_is_habis_idx');
            $table->index('qty_sisa', 'stok_batch_qty_sisa_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stok_gudang', function (Blueprint $table) {
            $table->dropUnique('stok_gudang_gudang_barang_unique');
        });

        Schema::table('stok_gudang_batch', function (Blueprint $table) {
            $table->dropIndex('stok_batch_gudang_barang_idx');
            $table->dropIndex('stok_batch_is_habis_idx');
            $table->dropIndex('stok_batch_qty_sisa_idx');
        });
    }
};
