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
        Schema::create('pesanan', function (Blueprint $table) {
            $table->id();
            $table->string('kode_pesanan')->unique();
            $table->foreignId('customer_id')->constrained('customers');
            $table->dateTime('tanggal');
            $table->dateTime('estimasi_kirim');
            $table->date('estimasi_produksi')->nullable();
            $table->decimal('total_pesanan', 15, 2)->default(0.00);
            $table->string('status_pesanan')->default('pending');
            $table->enum('status_pembayaran', ['Belum Bayar', 'DP', 'Lunas'])->default('Belum Bayar');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        Schema::create('pesanan_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesanan_id')->constrained('pesanan')->onDelete('cascade');
            $table->foreignId('produk_id')->constrained('master_barang');
            $table->decimal('qty', 15, 2);
            $table->decimal('harga', 15, 2);
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();
        });

        Schema::create('pembayaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesanan_id')->constrained('pesanan')->onDelete('cascade');
            $table->date('tanggal_bayar');
            $table->decimal('jumlah_bayar', 15, 2);
            $table->string('metode_pembayaran')->nullable();
            $table->text('catatan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        Schema::create('penjualan_pos', function (Blueprint $table) {
            $table->id();
            $table->string('kode_transaksi')->unique();
            $table->dateTime('tanggal');
            $table->foreignId('gudang_id')->constrained('master_gudang');
            $table->decimal('total', 15, 2)->default(0.00);
            $table->string('status', 50)->default('Draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        Schema::create('penjualanpos_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penjualan_id')->references('id')->on('penjualan_pos')->onDelete('cascade');
            $table->foreignId('produk_id')->constrained('master_barang');
            $table->decimal('qty', 15, 2);
            $table->decimal('harga', 15, 2);
            $table->decimal('hpp_satuan', 15, 2)->default(0.00);
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();
        });

        Schema::create('work_order', function (Blueprint $table) {
            $table->id();
            $table->string('kode_wo')->unique();
            $table->dateTime('tanggal_wo');
            $table->enum('status_wo', ['Draft', 'Diproses', 'Selesai', 'Batal'])->default('Draft');
            $table->text('catatan')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('work_order_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained('work_order')->onDelete('cascade');
            $table->foreignId('pesanan_id')->constrained('pesanan');
            $table->foreignId('produk_id')->constrained('master_barang');
            $table->integer('qty_rencana');
            $table->timestamps();
        });

        Schema::create('produksi', function (Blueprint $table) {
            $table->id();
            $table->string('kode_produksi')->unique();
            $table->foreignId('pesanan_id')->constrained('pesanan');
            $table->dateTime('tanggal_mulai');
            $table->dateTime('tanggal_selesai')->nullable();
            $table->string('status_produksi', 50)->default('Draft');
            $table->foreignId('gudang_bahan_id')->constrained('master_gudang');
            $table->foreignId('gudang_hasil_id')->constrained('master_gudang');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('produksi_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produksi_id')->constrained('produksi');
            $table->foreignId('produk_id')->constrained('master_barang');
            $table->integer('qty');
            $table->decimal('hpp_total', 15, 2)->default(0.00);
            $table->timestamps();
        });

        Schema::create('alokasi_produksi_pesanan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produksi_id')->constrained('produksi')->onDelete('cascade');
            $table->foreignId('pesanan_id')->constrained('pesanan')->onDelete('cascade');
            $table->foreignId('produk_id')->constrained('master_barang')->onDelete('cascade');
            $table->decimal('qty_alokasi', 15, 2);
            $table->decimal('qty_terkirim', 15, 2)->default(0.00);
            $table->decimal('hpp_per_unit', 15, 2);
            $table->decimal('total_hpp_alokasi', 15, 2);
            $table->timestamps();

            $table->unique(['produksi_id', 'pesanan_id', 'produk_id'], 'alokasi_produksi_pesanan_unique');
        });

        Schema::create('pengiriman', function (Blueprint $table) {
            $table->id();
            $table->string('no_pengiriman')->unique();
            $table->foreignId('pesanan_id')->constrained('pesanan')->onDelete('cascade');
            $table->date('tanggal_pengiriman');
            $table->string('kurir')->nullable();
            $table->string('status_pengiriman', 50)->default('Draft');
            $table->timestamps();
        });

        Schema::create('pengiriman_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengiriman_id')->constrained('pengiriman')->onDelete('cascade');
            $table->foreignId('barang_id')->constrained('master_barang')->onDelete('cascade');
            $table->integer('qty_kirim');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengiriman_detail');
        Schema::dropIfExists('pengiriman');
        Schema::dropIfExists('alokasi_produksi_pesanan');
        Schema::dropIfExists('produksi_detail');
        Schema::dropIfExists('produksi');
        Schema::dropIfExists('work_order_detail');
        Schema::dropIfExists('work_order');
        Schema::dropIfExists('penjualanpos_detail');
        Schema::dropIfExists('penjualan_pos');
        Schema::dropIfExists('pembayaran');
        Schema::dropIfExists('pesanan_detail');
        Schema::dropIfExists('pesanan');
    }
};
