<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn(
            'pembelian_detail',
            'harga_per_qty'
        )) {

            Schema::table(
                'pembelian_detail',
                function (Blueprint $table) {

                    $table->decimal(
                        'harga_per_qty',
                        15,
                        2
                    )->nullable()->after('harga');
                }
            );
        }
    }

    public function down()
    {
        if (Schema::hasColumn(
            'pembelian_detail',
            'harga_per_qty'
        )) {

            Schema::table(
                'pembelian_detail',
                function (Blueprint $table) {

                    $table->dropColumn(
                        'harga_per_qty'
                    );
                }
            );
        }
    }
};