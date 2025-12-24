<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('program_kerjas', function (Blueprint $table) {
            if (Schema::hasColumn('program_kerjas', 'status')) {
                // Ganti nama kolom lama agar tidak bentrok (misalnya ubah ke status_old)
                $table->renameColumn('status', 'status_old');
            }

            // Tambahkan kolom baru yang kamu inginkan
            $table->enum('status_baru', [
                'Belum Selesai',
                'Sedang Dikerjakan',
                'Tervalidasi',
                'Menunggu Validasi Kasi',
                'Menunggu Validasi Manajer'
            ])->default('Belum Selesai');
        });
    }

    public function down(): void
    {
        Schema::table('program_kerjas', function (Blueprint $table) {
            $table->dropColumn('status_baru');
            if (Schema::hasColumn('program_kerjas', 'status_old')) {
                $table->renameColumn('status_old', 'status');
            }
        });
    }
};

