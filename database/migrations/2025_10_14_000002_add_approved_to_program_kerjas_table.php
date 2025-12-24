<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('program_kerjas')) {
            Schema::table('program_kerjas', function (Blueprint $table) {
                if (!Schema::hasColumn('program_kerjas', 'approved')) {
                    $table->boolean('approved')->default(false)->after('dana');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('program_kerjas')) {
            Schema::table('program_kerjas', function (Blueprint $table) {
                if (Schema::hasColumn('program_kerjas', 'approved')) {
                    $table->dropColumn('approved');
                }
            });
        }
    }
};
