<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('anggarans', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->bigInteger('total')->default(0);
            $table->bigInteger('terserap')->default(0);
            $table->bigInteger('tersisa')->default(0);
            $table->integer('tahun')->default(date('Y')); // ➕ Tambahkan ini
            $table->timestamps();
        });

    }

    public function down(): void
    {
        Schema::table('anggarans', function (Blueprint $table) {
            $table->dropColumn('tahun');
        });
    }
};
