<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_barang', function (Blueprint $table) {
            // Menambahkan kolom resep_id setelah kategori_id
            // nullable() agar barang lama yang belum punya resep tidak error
            $table->unsignedBigInteger('resep_id')->nullable()->after('kategori_id');
            
            // Opsional: Jika ingin menambahkan relasi resmi ke tabel reseps
            // $table->foreign('resep_id')->references('id')->on('resep_btkl_bops')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('master_barang', function (Blueprint $table) {
            $table->dropColumn('resep_id');
        });
    }
};