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
        Schema::table('kategori', function (Blueprint $table) {
            if (!Schema::hasColumn('kategori', 'prefix')) {
                $table->string('prefix', 10)->nullable()->after('nama');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kategori', function (Blueprint $table) {
            if (Schema::hasColumn('kategori', 'prefix')) {
                $table->dropColumn('prefix');
            }
        });
    }
};
