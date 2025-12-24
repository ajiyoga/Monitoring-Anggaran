<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Anggaran extends Model
{
    protected $fillable = ['nama', 'total', 'terserap', 'tersisa', 'tahun',];

    // Hook sebelum simpan
    protected static function booted()
    {
        static::saving(function ($anggaran) {
            $anggaran->tersisa = $anggaran->total - $anggaran->terserap;
        });
    }

    public function details()
    {
        return $this->hasMany(DetailAnggaran::class);
    }

    public function programKerjas()
    {
        return $this->hasMany(ProgramKerja::class, 'anggaran_id');
    }

    public function riwayatDana()
    {
        return $this->hasMany(\App\Models\RiwayatDana::class, 'anggaran_id');
    }

}
