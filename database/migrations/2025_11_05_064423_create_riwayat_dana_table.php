<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('riwayat_dana', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('anggaran_id');
            $table->bigInteger('jumlah');
            $table->timestamps();

            $table->foreign('anggaran_id')->references('id')->on('anggarans')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_dana');
    }
};
