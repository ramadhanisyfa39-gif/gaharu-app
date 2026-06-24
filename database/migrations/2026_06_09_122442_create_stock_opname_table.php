<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_opname', function (Blueprint $table) {

            $table->id();

            $table->string('kode_opname')->unique();

            $table->dateTime('tanggal');

            $table->foreignId('gudang_id')
                ->constrained('master_gudang');

            $table->enum('status', [
                'draft',
                'approved'
            ])->default('draft');

            $table->text('keterangan')
                ->nullable();

            $table->foreignId('created_by')
                ->constrained('users');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_opname');
    }
};