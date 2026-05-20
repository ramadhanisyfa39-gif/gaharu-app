<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'pengeluaran_bahan_baku_fifo',
            function (Blueprint $table) {

                $table->id();

                /*
                |--------------------------------------------------------------------------
                | RELASI
                |--------------------------------------------------------------------------
                */

                $table->unsignedBigInteger(
                    'pengeluaran_id'
                );

                $table->unsignedBigInteger(
                    'detail_id'
                );

                $table->unsignedBigInteger(
                    'batch_id'
                );

                /*
                |--------------------------------------------------------------------------
                | FIFO
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'batch_number'
                );

                $table->decimal(
                    'qty_keluar',
                    15,
                    2
                );

                /*
                |--------------------------------------------------------------------------
                | HARGA FIFO
                |--------------------------------------------------------------------------
                */

                $table->decimal(
                    'harga_per_qty',
                    18,
                    2
                );

                $table->decimal(
                    'total_harga',
                    18,
                    2
                );

                $table->timestamps();
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'pengeluaran_bahan_baku_fifo'
        );
    }
};