<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->default('user'); // admin, manajer, user
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('anggarans', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->decimal('total', 15, 2);
            $table->decimal('terserap', 15, 2)->default(0);
            $table->decimal('tersisa', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('program_kerjas', function (Blueprint $table) {
            $table->id();
            $table->string('deskripsi');
            $table->text('deskripsi_lengkap')->nullable();
            $table->string('penanggung_jawab');
            $table->string('vendor')->nullable();
            $table->dateTime('waktu_dimulai')->nullable();
            $table->date('target_waktu');
            $table->string('status');
            $table->string('kategori');
            $table->foreignId('anggaran_id')->constrained('anggarans');
            $table->decimal('dana', 15, 2);
            $table->boolean('approved')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_kerjas');
        Schema::dropIfExists('anggarans');
        Schema::dropIfExists('users');
    }
};
