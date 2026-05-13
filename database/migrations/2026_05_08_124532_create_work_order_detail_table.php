<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_order_detail', function (Blueprint $table) {
            $table->id();
            
            // Relasi ke induk WO (cascade agar kalau WO dihapus, detail ikut terhapus)
            $table->foreignId('work_order_id')
                  ->constrained('work_order')
                  ->onDelete('cascade');

            // Referensi pesanan B2B dipindah ke sini
            $table->foreignId('pesanan_id')->constrained('pesanan');

            // Produk barang jadi yang harus diproduksi
            $table->foreignId('produk_id')->constrained('master_barang');

            // Qty yang harus diproduksi untuk pesanan ini
            $table->integer('qty_rencana');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_order_detail');
    }
};