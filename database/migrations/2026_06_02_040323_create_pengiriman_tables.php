<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel Induk Pengiriman (Mengarah ke Pesanan)
        Schema::create('pengiriman', function (Blueprint $table) {
            $table->id();
            $table->string('no_pengiriman')->unique();
            $table->foreignId('pesanan_id')->constrained('pesanan')->onDelete('cascade'); 
            $table->date('tanggal_pengiriman');
            $table->string('kurir')->nullable();
            $table->timestamps();
        });

        // Tabel Detail Pengiriman 
        Schema::create('pengiriman_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengiriman_id')->constrained('pengiriman')->onDelete('cascade');
            $table->foreignId('barang_id')->constrained('master_barang')->onDelete('cascade'); // Menggunakan barang_id sesuai tabel stok_gudang kamu
            $table->integer('qty_kirim');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengiriman_detail');
        Schema::dropIfExists('pengiriman');
    }
};