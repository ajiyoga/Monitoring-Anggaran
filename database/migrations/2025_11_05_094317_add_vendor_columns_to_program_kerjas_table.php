<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('program_kerjas', function (Blueprint $table) {
            if (!Schema::hasColumn('program_kerjas', 'vendor')) {
                $table->string('vendor')->nullable()->after('penanggung_jawab');
            }
            if (!Schema::hasColumn('program_kerjas', 'nama_vendor')) {
                $table->string('nama_vendor')->nullable()->after('vendor');
            }
        });
    }

    public function down(): void
    {
        Schema::table('program_kerjas', function (Blueprint $table) {
            if (Schema::hasColumn('program_kerjas', 'vendor')) {
                $table->dropColumn('vendor');
            }
            if (Schema::hasColumn('program_kerjas', 'nama_vendor')) {
                $table->dropColumn('nama_vendor');
            }
        });
    }
};
