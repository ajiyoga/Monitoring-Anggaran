<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('riwayat_dana', function (Blueprint $table) {
            if (!Schema::hasColumn('riwayat_dana', 'keterangan')) {
                $table->string('keterangan')->nullable();
            }
            if (!Schema::hasColumn('riwayat_dana', 'deskripsi')) {
                $table->text('deskripsi')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('riwayat_dana', function (Blueprint $table) {
            if (Schema::hasColumn('riwayat_dana', 'keterangan')) {
                $table->dropColumn('keterangan');
            }
            if (Schema::hasColumn('riwayat_dana', 'deskripsi')) {
                $table->dropColumn('deskripsi');
            }
        });
    }
};
