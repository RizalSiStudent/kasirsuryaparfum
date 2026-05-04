<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Botol extends Model
{
    protected $primaryKey = 'id_botol';

    protected $fillable = [
        'nama_botol',
        'kapasitas_ml',
        'harga_jual_per_pcs',
        'harga_beli_per_pcs',
        'stok_pcs',
        'foto_botol',
    ];
}