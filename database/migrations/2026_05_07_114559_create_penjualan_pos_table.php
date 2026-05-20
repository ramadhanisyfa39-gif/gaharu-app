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
        Schema::create('penjualan_pos', function (Blueprint $table) {
            $table->id();

            $table->string('kode_transaksi')->unique();

            $table->dateTime('tanggal');

            $table->foreignId('gudang_id')
                  ->constrained('master_gudang');

            $table->decimal('total', 15, 2)
                  ->default(0);

            $table->foreignId('created_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penjualan_pos');
    }
};