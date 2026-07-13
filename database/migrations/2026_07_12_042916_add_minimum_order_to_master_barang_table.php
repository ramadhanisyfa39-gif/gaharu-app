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
        Schema::table('master_barang', function (Blueprint $table) {
            $table->decimal('minimum_order', 15, 2)->default(1.00)->after('minimum_stock');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_barang', function (Blueprint $table) {
            $table->dropColumn('minimum_order');
        });
    }
};
