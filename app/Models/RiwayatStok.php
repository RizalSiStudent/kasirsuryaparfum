<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiwayatStok extends Model
{
    protected $primaryKey = 'id_riwayat';

    protected $fillable = [
        'id_parfum',
        'jenis_pergerakan',
        'jumlah_ml',
        'keterangan',
    ];

    // Relasi balik ke Parfum
    public function parfum()
    {
        return $this->belongsTo(Parfum::class, 'id_parfum', 'id_parfum');
    }
}