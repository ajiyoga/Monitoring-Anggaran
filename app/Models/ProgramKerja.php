<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgramKerja extends Model
{
    use HasFactory;

    protected $table = 'program_kerjas';

protected $fillable = [
    'deskripsi',
    'deskripsi_lengkap',
    'penanggung_jawab',
    'vendor',
    'nama_vendor',
    'waktu_dimulai',
    'target_waktu',
    'status',
    'kategori',
    'anggaran_id',
    'riwayat_dana_id',
    'dana',
    'dana_tambahan_dipakai',
    'tahun',
];



    // Relasi ke progress_kerjas
    public function progressKerjas()
    {
        return $this->hasMany(ProgressKerja::class, 'program_kerja_id');
    }

    public function anggaran()
    {
        return $this->belongsTo(Anggaran::class, 'anggaran_id');
    }
}
