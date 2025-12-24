<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('program_kerjas', function (Blueprint $table) {
            $table->unsignedBigInteger('anggaran_id')->nullable()->after('id');
            $table->foreign('anggaran_id')->references('id')->on('anggarans')->onDelete('cascade');
            $table->decimal('dana', 15, 2)->default(0)->after('kategori');
        });
    }

    public function down()
    {
        Schema::table('program_kerjas', function (Blueprint $table) {
            $table->dropForeign(['anggaran_id']);
            $table->dropColumn(['anggaran_id', 'dana']);
        });
    }

};
