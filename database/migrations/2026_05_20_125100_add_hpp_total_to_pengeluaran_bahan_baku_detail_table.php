<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'pengeluaran_bahan_baku_detail',
            function (Blueprint $table) {

                $table->decimal(
                    'hpp_total',
                    18,
                    2
                )
                ->default(0)
                ->after('total_harga');
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'pengeluaran_bahan_baku_detail',
            function (Blueprint $table) {

                $table->dropColumn(
                    'hpp_total'
                );
            }
        );
    }
};