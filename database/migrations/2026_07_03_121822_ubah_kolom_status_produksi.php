<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Mengubah tipe kolom menjadi VARCHAR agar lebih fleksibel
        DB::statement("ALTER TABLE produksi MODIFY status_produksi VARCHAR(50) DEFAULT 'Draft'");
    }
    
    public function down(): void
    {
        // Kembalikan ke enum semula jika di-rollback (sesuaikan dengan nilai awal Anda)
        DB::statement("ALTER TABLE produksi MODIFY status_produksi ENUM('Diproses', 'Selesai') DEFAULT 'Diproses'");
    }
};
