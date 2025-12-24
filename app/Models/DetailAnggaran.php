<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetailAnggaran extends Model
{
    protected $fillable = ['anggaran_id', 'keterangan', 'jumlah'];

    public function anggaran()
    {
        return $this->belongsTo(Anggaran::class);
    }
}
