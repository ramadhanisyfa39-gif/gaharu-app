<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengeluaran_bahan_baku', function (Blueprint $table) {

            $table->id();

            $table->string('kode_pengeluaran')->unique();

            $table->dateTime('tanggal');

            $table->foreignId('gudang_id')
                ->constrained('master_gudang');

            $table->string('status')
                ->default('draft');

            $table->text('keterangan')
                ->nullable();

            $table->foreignId('created_by')
                ->constrained('users');

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users');

            $table->dateTime('approved_at')
                ->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengeluaran_bahan_baku');
    }
};