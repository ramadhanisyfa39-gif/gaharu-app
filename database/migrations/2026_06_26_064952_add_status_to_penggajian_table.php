<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penggajian', function (Blueprint $table) {
            // Mengubah default menjadi 'draft' (huruf kecil) agar sinkron dengan controller
            $table->string('status')->default('draft')->after('total_gaji_bersih');
        });
    }

    public function down(): void
    {
        Schema::table('penggajian', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
