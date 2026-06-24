<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_opname_detail', function (Blueprint $table) {

            $table->id();

            $table->foreignId('stock_opname_id')
                ->constrained('stock_opname')
                ->cascadeOnDelete();

            $table->foreignId('barang_id')
                ->constrained('master_barang');

            $table->decimal(
                'stok_sistem',
                15,
                2
            )->default(0);

            $table->decimal(
                'stok_fisik',
                15,
                2
            )->default(0);

            $table->decimal(
                'selisih',
                15,
                2
            )->default(0);

            $table->decimal(
                'nilai_selisih',
                18,
                2
            )->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_opname_detail');
    }
};