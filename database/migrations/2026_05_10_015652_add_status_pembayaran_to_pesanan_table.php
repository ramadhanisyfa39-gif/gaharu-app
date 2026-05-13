<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('pesanan', function (Blueprint $table) {
            // Menambahkan kolom status_pembayaran setelah status_pesanan
            $table->enum('status_pembayaran', ['Belum Bayar', 'DP', 'Lunas'])
                  ->default('Belum Bayar')
                  ->after('status_pesanan');
        });
    }

    public function down()
    {
        Schema::table('pesanan', function (Blueprint $table) {
            $table->dropColumn('status_pembayaran');
        });
    }
};