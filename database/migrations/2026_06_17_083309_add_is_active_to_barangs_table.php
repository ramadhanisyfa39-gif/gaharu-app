<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    // Ubah 'barangs' menjadi 'master_barang'
    Schema::table('master_barang', function (Blueprint $table) {
        $table->boolean('is_active')->default(true);
    });
}

public function down()
{
    // Lakukan hal yang sama di fungsi down
    Schema::table('master_barang', function (Blueprint $table) {
        $table->dropColumn('is_active');
    });
}
};
