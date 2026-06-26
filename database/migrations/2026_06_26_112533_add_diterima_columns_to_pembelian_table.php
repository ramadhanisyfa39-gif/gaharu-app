<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pembelian', function (Blueprint $table) {
            $table->timestamp('diterima_at')->nullable()->after('catatan_pelunasan');
            $table->unsignedBigInteger('diterima_oleh')->nullable()->after('diterima_at');
        });
    }

    public function down(): void
    {
        Schema::table('pembelian', function (Blueprint $table) {
            $table->dropColumn(['diterima_at', 'diterima_oleh']);
        });
    }
};