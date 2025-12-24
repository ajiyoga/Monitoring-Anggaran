<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('program_kerjas', function (Blueprint $table) {
            if (!Schema::hasColumn('program_kerjas', 'tahun')) {
                $table->year('tahun')->default(date('Y'))->after('id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('program_kerjas', function (Blueprint $table) {
            if (Schema::hasColumn('program_kerjas', 'tahun')) {
                $table->dropColumn('tahun');
            }
        });
        ;
    }
};
