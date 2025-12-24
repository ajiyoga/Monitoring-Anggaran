<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('program_kerjas', function (Blueprint $table) {
            // Ubah jadi string yang lebih panjang
            $table->string('status', 100)->default('Belum Selesai')->change();
        });
    }

    public function down(): void
    {
        Schema::table('program_kerjas', function (Blueprint $table) {
            $table->string('status', 50)->default('Belum Selesai')->change();
        });
    }
};
