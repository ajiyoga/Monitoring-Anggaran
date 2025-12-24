<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('progress_kerjas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('program_kerja_id'); // relasi ke program kerja
            $table->date('tanggal'); // tanggal progress
            $table->text('catatan'); // catatan progress harian
            $table->integer('persentase')->default(0); // persentase progress

            // ✅ Tambahkan kolom status di sini
            $table->string('status', 50)->default('Belum Selesai');

            // Bukti dokumen opsional
            $table->string('bukti_dokumen')->nullable();

            // Status verifikasi manajer
            $table->enum('status_verifikasi', ['pending', 'approved', 'rejected'])->default('pending');

            $table->timestamps();

            // Relasi foreign key
            $table->foreign('program_kerja_id')
                ->references('id')
                ->on('program_kerjas')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('progress_kerjas');
    }
};
