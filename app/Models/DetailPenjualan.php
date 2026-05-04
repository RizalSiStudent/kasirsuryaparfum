<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetailPenjualan extends Model
{
    protected $primaryKey = 'id_detail_penjualan';

    protected $fillable = [
        'id_penjualan',
        'id_parfum',
        'id_botol',
        'id_parfum_jadi', // <-- Tambahan
        'jumlah_ml',
        'jumlah_pcs',     // <-- Tambahan
        'harga_saat_transaksi',
        'subtotal',
    ];
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