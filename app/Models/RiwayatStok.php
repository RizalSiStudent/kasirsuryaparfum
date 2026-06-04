<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiwayatStok extends Model
{
    protected $primaryKey = 'id_riwayat';
    protected $guarded = [];

    public function parfum() {
        return $this->belongsTo(Parfum::class, 'id_parfum', 'id_parfum');
    }
    public function botol() {
        return $this->belongsTo(Botol::class, 'id_botol', 'id_botol');
    }
    public function parfumJadi() {
        return $this->belongsTo(ParfumJadi::class, 'id_parfum_jadi', 'id_parfum_jadi');
    }
}