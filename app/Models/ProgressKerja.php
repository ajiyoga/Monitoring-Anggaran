<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgressKerja extends Model
{
    protected $table = 'progress_kerjas'; // ⬅️ sesuai database kamu

    protected $fillable = [
        'program_kerja_id',
        'tanggal',
        'catatan',
        'persentase',
        'status',
        'bukti_dokumen',
        'status_verifikasi',
    ];



    public function programKerja()
    {
        return $this->belongsTo(ProgramKerja::class, 'program_kerja_id');
    }
}
