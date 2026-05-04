<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParfumJadi extends Model
{
    protected $primaryKey = 'id_parfum_jadi';

    protected $fillable = [
        'nama_parfum',
        'harga_beli_per_pcs',
        'harga_jual_per_pcs',
        'stok_pcs',
    ];
}