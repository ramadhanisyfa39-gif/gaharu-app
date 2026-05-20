<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('harga_barang_pos', function (Blueprint $table) {
            $table->id();
            
            // Pastikan 'master_barang' sesuai dengan nama tabel di database Anda
            $table->foreignId('barang_id')
                  ->constrained('master_barang') 
                  ->onDelete('cascade');
    
            $table->date('tgl_mulai');
            $table->date('tgl_selesai');
            $table->decimal('harga_pos', 15, 2);
            $table->string('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('harga_barang_pos');
    }
};