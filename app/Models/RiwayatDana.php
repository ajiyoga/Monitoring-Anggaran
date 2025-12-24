<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RiwayatDana extends Model
{
    use HasFactory;

    protected $table = 'riwayat_danas';

    protected $fillable = [
        'anggaran_id',
        'tanggal',
        'jumlah',
        'sumber',
        'keterangan',
        'deskripsi',
        'tahun',
    ];

    public function anggaran()
    {
        return $this->belongsTo(Anggaran::class);
    }

    public static function totalTambahan($anggaranId)
    {
        return self::where('anggaran_id', $anggaranId)->sum('jumlah');
    }
}
