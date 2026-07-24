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
        // 1. Modifikasi tabel pembayaran
        Schema::table('pembayaran', function (Blueprint $table) {
            // Drop foreign key pesanan_id terlebih dahulu
            $table->dropForeign(['pesanan_id']);
        });

        Schema::table('pembayaran', function (Blueprint $table) {
            // Ubah pesanan_id menjadi nullable
            $table->bigInteger('pesanan_id')->unsigned()->nullable()->change();
            
            // Tambahkan kolom baru
            $table->bigInteger('pembelian_id')->unsigned()->nullable()->after('pesanan_id');
            $table->bigInteger('penjualan_pos_id')->unsigned()->nullable()->after('pembelian_id');
            $table->string('kategori_pembayaran')->nullable()->after('penjualan_pos_id');
            $table->text('bukti_pembayaran')->nullable()->after('catatan');

            // Re-apply foreign keys
            $table->foreign('pesanan_id')->references('id')->on('pesanan')->onDelete('cascade');
            $table->foreign('pembelian_id')->references('id')->on('pembelian')->onDelete('cascade');
            $table->foreign('penjualan_pos_id')->references('id')->on('penjualan_pos')->onDelete('cascade');
        });

        // 2. Buat tabel penerimaan_pembelian
        Schema::create('penerimaan_pembelian', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('pembelian_id')->unsigned();
            $table->string('no_penerimaan')->unique();
            $table->datetime('tanggal');
            $table->bigInteger('created_by')->unsigned()->nullable();
            $table->timestamps();

            $table->foreign('pembelian_id')->references('id')->on('pembelian')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });

        // 3. Buat tabel penerimaan_pembelian_detail
        Schema::create('penerimaan_pembelian_detail', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('penerimaan_pembelian_id')->unsigned();
            $table->bigInteger('pembelian_detail_id')->unsigned();
            $table->bigInteger('barang_id')->unsigned();
            $table->decimal('qty', 15, 2);
            $table->decimal('harga_per_qty', 15, 2);
            $table->timestamps();

            $table->foreign('penerimaan_pembelian_id', 'fk_ppd_pp_id')->references('id')->on('penerimaan_pembelian')->onDelete('cascade');
            $table->foreign('pembelian_detail_id', 'fk_ppd_pd_id')->references('id')->on('pembelian_detail')->onDelete('cascade');
            $table->foreign('barang_id', 'fk_ppd_b_id')->references('id')->on('master_barang')->onDelete('cascade');
        });

        // 4. Tambahkan kolom tax_service ke tabel pembelian
        if (!Schema::hasColumn('pembelian', 'tax_service')) {
            Schema::table('pembelian', function (Blueprint $table) {
                $table->decimal('tax_service', 15, 2)->default(0)->after('total');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop tabel detail dan header penerimaan
        Schema::dropIfExists('penerimaan_pembelian_detail');
        Schema::dropIfExists('penerimaan_pembelian');

        // Drop kolom tax_service dari pembelian
        if (Schema::hasColumn('pembelian', 'tax_service')) {
            Schema::table('pembelian', function (Blueprint $table) {
                $table->dropColumn('tax_service');
            });
        }

        // Restore table pembayaran
        Schema::table('pembayaran', function (Blueprint $table) {
            $table->dropForeign(['pesanan_id']);
            $table->dropForeign(['pembelian_id']);
            $table->dropForeign(['penjualan_pos_id']);
        });

        Schema::table('pembayaran', function (Blueprint $table) {
            $table->dropColumn(['pembelian_id', 'penjualan_pos_id', 'kategori_pembayaran', 'bukti_pembayaran']);
            // Kembalikan pesanan_id menjadi not-nullable
            $table->bigInteger('pesanan_id')->unsigned()->change();
            $table->foreign('pesanan_id')->references('id')->on('pesanan')->onDelete('cascade');
        });
    }
};
