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
        Schema::create('kategori', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('prefix', 10)->nullable();
        });

        Schema::create('master_gudang', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('kategori');
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('jenis');
            $table->string('no_hp');
            $table->text('alamat');
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('no_hp');
            $table->text('alamat');
        });

        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->foreignId('parent_id')->nullable()->constrained('chart_of_accounts')->onDelete('cascade');
            $table->string('nama');
            $table->string('tipe');
            $table->string('saldo_normal');
            $table->timestamps();
        });

        Schema::create('master_barang', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kategori_id')->constrained('kategori');
            $table->unsignedBigInteger('resep_id')->nullable();
            $table->string('tipe_penjualan')->nullable();
            $table->string('kode_barang')->unique();
            $table->string('nama');
            $table->string('satuan');
            $table->boolean('is_bahan_baku');
            $table->boolean('is_barang_jadi');
            $table->boolean('is_operational');
            $table->boolean('is_direct_consumption');
            $table->decimal('harga_jual_b2b', 15, 2);
            $table->decimal('harga_jual_pos', 15, 2);
            $table->decimal('hpp_referensi', 15, 2);
            $table->boolean('is_active')->default(true);
            $table->integer('minimum_stock')->nullable();
            $table->decimal('minimum_order', 15, 2)->default(1.00);
            $table->timestamps();
        });

        Schema::create('resep_btkl_bop', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produk_id')->constrained('master_barang')->onDelete('cascade');
            $table->decimal('output_qty', 15, 2);
            $table->string('satuan_output');
            $table->decimal('btkl_per_batch', 15, 2);
            $table->decimal('bop_per_batch', 15, 2);
            $table->timestamps();
        });

        // Add foreign key constraint to master_barang referring to resep_btkl_bop
        Schema::table('master_barang', function (Blueprint $table) {
            $table->foreign('resep_id')->references('id')->on('resep_btkl_bop')->onDelete('set null');
        });

        Schema::create('resep_bahanbaku', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resep_id')->constrained('resep_btkl_bop')->onDelete('cascade');
            $table->foreignId('bahan_id')->constrained('master_barang')->onDelete('cascade');
            $table->decimal('qty_bahan', 15, 2);
            $table->string('satuan');
        });

        Schema::create('harga_barang_pos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barang_id')->constrained('master_barang')->onDelete('cascade');
            $table->date('tgl_mulai');
            $table->date('tgl_selesai');
            $table->decimal('harga_pos', 15, 2);
            $table->string('keterangan')->nullable();
            $table->timestamps();
        });

        Schema::create('karyawan', function (Blueprint $table) {
            $table->id();
            $table->string('nama_karyawan');
            $table->string('jabatan');
            $table->string('jenis_tenaga_kerja');
            $table->string('departemen');
            $table->decimal('gaji_pokok', 15, 2)->default(0.00);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('karyawan');
        Schema::dropIfExists('harga_barang_pos');
        Schema::dropIfExists('resep_bahanbaku');
        
        if (Schema::hasTable('master_barang')) {
            Schema::table('master_barang', function (Blueprint $table) {
                $table->dropForeign(['resep_id']);
            });
        }
        
        Schema::dropIfExists('resep_btkl_bop');
        Schema::dropIfExists('master_barang');
        Schema::dropIfExists('chart_of_accounts');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('master_gudang');
        Schema::dropIfExists('kategori');
    }
};
