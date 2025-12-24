<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArsipProgramKerja extends Model
{
    protected $fillable = [
        'nama_program',
        'anggaran_total',
        'anggaran_terserap',
        'status',
        'tahun',
    ];
}
