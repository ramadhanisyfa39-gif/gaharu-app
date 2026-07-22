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
        if (!Schema::hasColumn('pembelian_detail', 'qty_diterima')) {
            Schema::table('pembelian_detail', function (Blueprint $table) {
                $table->decimal('qty_diterima', 15, 2)->nullable()->after('qty');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('pembelian_detail', 'qty_diterima')) {
            Schema::table('pembelian_detail', function (Blueprint $table) {
                $table->dropColumn('qty_diterima');
            });
        }
    }
};
