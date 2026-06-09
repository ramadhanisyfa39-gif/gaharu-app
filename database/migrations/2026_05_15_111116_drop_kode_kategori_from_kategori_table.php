<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up()
{
    if (
        Schema::hasColumn(
            'kategori',
            'kode_kategori'
        )
    ) {
        Schema::table('kategori', function (Blueprint $table) {
            $table->dropColumn('kode_kategori');
        });
    }
}

    public function down(): void
    {
        Schema::table('kategori', function (Blueprint $table) {

            $table->string('kode_kategori')->nullable();

        });
    }
};