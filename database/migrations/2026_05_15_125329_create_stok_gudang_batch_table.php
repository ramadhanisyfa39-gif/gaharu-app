<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up(): void
{
    if (!Schema::hasTable('stok_gudang_batch')) {

        Schema::create('stok_gudang_batch', function (Blueprint $table) {

            $table->id();

            $table->unsignedBigInteger('gudang_id');

            $table->unsignedBigInteger('supplier_id');

            $table->unsignedBigInteger('barang_id');

            $table->unsignedBigInteger('pembelian_id');

            $table->unsignedBigInteger('pembelian_detail_id');

            $table->string('batch_number');

            $table->decimal('qty_masuk',15,2);

            $table->decimal('qty_keluar',15,2)
                ->default(0);

            $table->decimal('qty_sisa',15,2);

            $table->decimal('harga_per_qty',15,2);

            $table->boolean('is_habis')
                ->default(false);

            $table->timestamps();
        });
    }
}

    public function down(): void
    {
        Schema::dropIfExists(
            'stok_gudang_batch'
        );
    }
};