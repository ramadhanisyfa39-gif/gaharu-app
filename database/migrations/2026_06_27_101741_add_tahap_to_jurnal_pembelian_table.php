<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Kolom 'tahap' membedakan jenis jurnal yang tersimpan untuk 1 invoice
     * pembelian yang sama, karena 1 invoice bisa melewati beberapa kali
     * proses input jurnal (DP -> Pelunasan -> Reklas Persediaan), atau
     * langsung 1 kali saja untuk skenario gabungan/COD.
     *
     * Nilai yang dipakai:
     * - 'dp'         : Jurnal pembayaran DP (Skenario 1 & 2, Tahap A)
     * - 'pelunasan'  : Jurnal pelunasan SEBELUM barang datang (Skenario 1, Tahap B)
     * - 'reklas'     : Jurnal reklas Uang Muka -> Persediaan SETELAH lunas duluan,
     *                  barang baru datang menyusul (Skenario 1, Tahap C)
     * - 'gabungan'   : Jurnal pelunasan + terima barang bersamaan (Skenario 2)
     * - 'cod'        : Jurnal COD, lunas total saat barang datang (Skenario 3)
     */
    public function up(): void
    {
        Schema::table('jurnal_pembelian', function (Blueprint $table) {
            $table->string('tahap', 20)
                ->nullable()
                ->after('source_id')
                ->comment("Tahap jurnal: dp, pelunasan, reklas, gabungan, cod");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jurnal_pembelian', function (Blueprint $table) {
            $table->dropColumn('tahap');
        });
    }
};
