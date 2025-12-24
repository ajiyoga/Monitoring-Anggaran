<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('program_kerjas', function (Blueprint $table) {
            $table->unsignedBigInteger('riwayat_dana_id')->nullable()->after('anggaran_id');
            $table->decimal('dana_tambahan_dipakai', 15, 2)->nullable()->after('riwayat_dana_id');
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('program_kerjas', function (Blueprint $table) {
            $table->dropColumn(['riwayat_dana_id', 'dana_tambahan_dipakai']);
        });
    }

};
