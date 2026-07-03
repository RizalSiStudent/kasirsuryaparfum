<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $primaryKey = 'id_supplier';

    protected $fillable = [
        'nama_perusahaan',
        'nama_supplier',
        'no_telepon',
        'alamat',
    ];
}