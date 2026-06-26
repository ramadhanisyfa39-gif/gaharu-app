<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pembelian', function (Blueprint $table) {
            $table->decimal('nominal_pelunasan', 15, 2)->nullable()->after('lunas_at');
            $table->text('catatan_pelunasan')->nullable()->after('nominal_pelunasan');
        });
    }

    public function down(): void
    {
        Schema::table('pembelian', function (Blueprint $table) {
            $table->dropColumn(['nominal_pelunasan', 'catatan_pelunasan']);
        });
    }
};