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
        Schema::create('journals', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->text('deskripsi');
            $table->string('no_ref');
            $table->string('source_type');
            $table->integer('source_id');
            $table->foreignId('created_by')->constrained('users');
            $table->string('status')->default('draft');
        });

        Schema::create('journal_items', function (Blueprint $table) {
            $table->id();
            $table->integer('journal_id');
            $table->string('journal_type', 50)->nullable();
            $table->foreignId('account_id')->constrained('chart_of_accounts');
            $table->decimal('debit', 15, 2)->default(0.00);
            $table->decimal('kredit', 15, 2)->default(0.00);

            $table->index(['journal_id', 'journal_type'], 'journal_items_journal_id_type_idx');
        });

        Schema::create('jurnal_pembelian', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->text('deskripsi');
            $table->string('no_ref');
            $table->string('source_type');
            $table->integer('source_id');
            $table->string('tahap', 20)->nullable();
            $table->foreignId('created_by')->constrained('users');
        });

        Schema::create('jurnal_penjualan_pos', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->text('deskripsi');
            $table->string('no_ref');
            $table->string('source_type');
            $table->integer('source_id');
            $table->foreignId('created_by')->constrained('users');
        });

        Schema::create('jurnal_penjualan_b2b', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->text('deskripsi');
            $table->string('no_ref');
            $table->string('source_type');
            $table->integer('source_id');
            $table->foreignId('created_by')->constrained('users');
        });

        Schema::create('jurnal_penyesuaian', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->text('deskripsi');
            $table->string('no_ref');
            $table->string('source_type');
            $table->integer('source_id');
            $table->foreignId('created_by')->constrained('users');
            $table->string('status')->default('draft');
        });

        Schema::create('penggajian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('karyawan_id')->constrained('karyawan');
            $table->string('periode_bulan_tahun');
            $table->decimal('gaji_pokok', 15, 2);
            $table->decimal('tunjangan_transport', 15, 2)->default(0.00);
            $table->decimal('tunjangan_makan', 15, 2)->default(0.00);
            $table->decimal('lembur', 15, 2)->default(0.00);
            $table->decimal('bonus_target', 15, 2)->default(0.00);
            $table->decimal('bonus_tanggal_merah', 15, 2)->default(0.00);
            $table->decimal('bonus_birthday', 15, 2)->default(0.00);
            $table->decimal('bonus_dll', 15, 2)->default(0.00);
            $table->decimal('potongan_inventaris', 15, 2)->default(0.00);
            $table->decimal('potongan_terlambat', 15, 2)->default(0.00);
            $table->decimal('total_gaji_bersih', 15, 2);
            $table->string('status')->default('draft');
            $table->boolean('status_jurnal')->default(false);
            $table->foreignId('journal_id')->nullable()->constrained('journals')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penggajian');
        Schema::dropIfExists('jurnal_penyesuaian');
        Schema::dropIfExists('jurnal_penjualan_b2b');
        Schema::dropIfExists('jurnal_penjualan_pos');
        Schema::dropIfExists('jurnal_pembelian');
        Schema::dropIfExists('journal_items');
        Schema::dropIfExists('journals');
    }
};
