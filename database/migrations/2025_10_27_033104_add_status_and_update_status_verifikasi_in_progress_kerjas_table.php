<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('progress_kerjas', function (Blueprint $table) {
            // Tambahkan kolom status jika belum ada
            if (!Schema::hasColumn('progress_kerjas', 'status')) {
                $table->string('status', 50)->default('Belum Selesai')->after('persentase');
            }

            // Ubah default status_verifikasi jadi 'pending'
            $table->enum('status_verifikasi', ['pending', 'approved', 'rejected'])
                ->default('pending')
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('progress_kerjas', function (Blueprint $table) {
            if (Schema::hasColumn('progress_kerjas', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
