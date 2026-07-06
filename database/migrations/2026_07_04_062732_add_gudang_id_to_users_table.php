<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Tambahkan gudang_id yang boleh kosong (nullable)
            $table->unsignedBigInteger('gudang_id')->nullable()->after('role_id');
            // (Opsional) Jika tabel master_gudang Anda sudah ada
            // $table->foreign('gudang_id')->references('id')->on('master_gudang')->onDelete('set null');
        });
    }
    
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('gudang_id');
        });
    }
};
