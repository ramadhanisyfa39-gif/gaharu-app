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
    Schema::table('pembelian', function (Blueprint $table) {
        $table->enum('metode_pembayaran', ['cod', 'termin', 'dp'])
              ->nullable()
              ->after('total');
        $table->date('tanggal_jatuh_tempo')->nullable()->after('metode_pembayaran'); // khusus termin
        $table->tinyInteger('persen_dp')->nullable()->after('tanggal_jatuh_tempo');  // khusus dp (1-99)
        $table->date('tanggal_pelunasan')->nullable()->after('persen_dp');           // dp & termin
        $table->text('catatan_pembayaran')->nullable()->after('tanggal_pelunasan');
        $table->unsignedBigInteger('dicatat_oleh')->nullable()->after('catatan_pembayaran');
        $table->timestamp('dicatat_pada')->nullable()->after('dicatat_oleh');
    });
}

public function down(): void
{
    Schema::table('pembelian', function (Blueprint $table) {
        $table->dropColumn([
            'metode_pembayaran', 'tanggal_jatuh_tempo', 'persen_dp',
            'tanggal_pelunasan', 'catatan_pembayaran', 'dicatat_oleh', 'dicatat_pada',
        ]);
    });
}
};
