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
    Schema::table('master_barang', function (Blueprint $table) {
        // Menambahkan kolom minimum_stock bertipe integer yang boleh kosong (nullable)
        $table->integer('minimum_stock')->nullable(); 
    });
}

public function down(): void
{
    Schema::table('master_barang', function (Blueprint $table) {
        $table->dropColumn('minimum_stock');
    });
}
};
