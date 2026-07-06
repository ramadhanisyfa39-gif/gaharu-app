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
        Schema::table('penjualan_pos', function (Blueprint $table) {
            // Menambahkan kolom status setelah kolom total
            $table->string('status', 50)->default('Draft')->after('total');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('penjualan_pos', function (Blueprint $table) {
            // Menghapus kolom jika migration di-rollback
            $table->dropColumn('status');
        });
    }
};