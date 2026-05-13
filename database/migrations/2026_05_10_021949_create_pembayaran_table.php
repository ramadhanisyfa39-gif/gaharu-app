<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('pembayaran', function (Blueprint $table) {
            $table->id();
            // Relasi ke tabel pesanan
            $table->foreignId('pesanan_id')->constrained('pesanan')->onDelete('cascade');
            
            $table->date('tanggal_bayar');
            $table->decimal('jumlah_bayar', 15, 2);
            $table->string('metode_pembayaran')->nullable(); // Contoh: Cash, Transfer BCA, dll
            $table->text('catatan')->nullable();
            
            // Opsional: untuk mencatat siapa kasir/admin yang menerima pembayaran
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('pembayaran');
    }
};