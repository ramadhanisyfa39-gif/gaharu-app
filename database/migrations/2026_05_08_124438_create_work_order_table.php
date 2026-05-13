<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_order', function (Blueprint $table) {
            $table->id();
            $table->string('kode_wo')->unique();
            $table->datetime('tanggal_wo');
            $table->enum('status_wo', [
                'Draft',
                'Diproses',
                'Selesai',
                'Batal'
            ])->default('Draft');
            $table->text('catatan')->nullable();
            
            // user pembuat WO
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_order');
    }
};