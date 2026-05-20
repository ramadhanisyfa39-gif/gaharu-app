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
        
            $table->foreignId('customer_id')
                  ->constrained('customers');
        
            $table->dateTime('tanggal');
        
            $table->dateTime('estimasi_kirim');
        
            $table->decimal('total_pesanan', 15, 2)
                  ->default(0);
        
            $table->string('status_pesanan')
                  ->default('pending');
        
            $table->foreignId('created_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
        
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pesanan');
    }
};
