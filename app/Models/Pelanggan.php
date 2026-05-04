<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pelanggan extends Model
{
    protected $primaryKey = 'id_pelanggan';

    protected $fillable = [
        'nama_pelanggan',
        'jenis_kelamin',
        'no_telepon',
        'alamat',
        'tanggal_gabung',
    ];
}