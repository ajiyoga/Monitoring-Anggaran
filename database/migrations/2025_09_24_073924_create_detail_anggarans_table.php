<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('detail_anggarans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('anggaran_id')->constrained('anggarans')->onDelete('cascade');
            $table->string('keterangan'); // contoh: Pembelian laptop
            $table->bigInteger('jumlah')->default(0); // nominal penggunaan
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_anggarans');
    }
};
