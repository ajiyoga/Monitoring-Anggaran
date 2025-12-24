<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('progress_kerjas', function (Blueprint $table) {
            if (!Schema::hasColumn('progress_kerjas', 'bukti_dokumen')) {
                $table->string('bukti_dokumen')->nullable()->after('persentase');
            }
            if (!Schema::hasColumn('progress_kerjas', 'status_verifikasi')) {
                $table->string('status_verifikasi')->default('pending')->after('bukti_dokumen');
            }
        });
    }

    public function down(): void
    {
        Schema::table('progress_kerjas', function (Blueprint $table) {
            if (Schema::hasColumn('progress_kerjas', 'status_verifikasi')) {
                $table->dropColumn('status_verifikasi');
            }
            if (Schema::hasColumn('progress_kerjas', 'bukti_dokumen')) {
                $table->dropColumn('bukti_dokumen');
            }
        });
    }
};
