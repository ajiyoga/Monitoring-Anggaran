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
        Schema::create('program_kerjas', function (Blueprint $table) {
            $table->id();
            $table->string('deskripsi'); // nama / deskripsi singkat program
            $table->string('penanggung_jawab'); // nama orang yang bertanggung jawab
            $table->date('waktu_dimulai'); // tanggal mulai program
            $table->date('target_waktu'); // deadline program

            // ✅ Gunakan satu kolom status saja (enum)
            $table->enum('status', [
                'Belum Selesai',
                'Sedang Dikerjakan',
                'Tervalidasi',
                'Menunggu Validasi Kasi',
                'Menunggu Validasi Manajer'
            ])->default('Belum Selesai');

            $table->string('kategori')->nullable(); // kategori
            $table->text('deskripsi_lengkap')->nullable(); // penjelasan detail
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('program_kerjas');
    }
};
