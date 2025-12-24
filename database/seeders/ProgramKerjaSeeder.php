<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProgramKerja;

class ProgramKerjaSeeder extends Seeder
{
    public function run(): void
    {
        ProgramKerja::create([
            'deskripsi' => 'Pengembangan Modul Pelaporan Keuangan Triwulan.',
            'penanggung_jawab' => 'Rina Wijaya',
            'target_waktu' => '2024-06-30',
            'status' => 'Belum Selesai'
        ]);

        ProgramKerja::create([
            'deskripsi' => 'Audit Internal Sistem Pengadaan Barang dan Jasa.',
            'penanggung_jawab' => 'Budi Santoso',
            'target_waktu' => '2024-07-15',
            'status' => 'Proses'
        ]);

        ProgramKerja::create([
            'deskripsi' => 'Review Kebijakan Pengelolaan Anggaran Tahun Fiskal 2025.',
            'penanggung_jawab' => 'Andi Pratama',
            'target_waktu' => '2024-09-30',
            'status' => 'Selesai'
        ]);
    }
}
