<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Parfum extends Model
{
    protected $primaryKey = 'id_parfum';

    protected $fillable = [
        'id_supplier', // <-- Tambahan baru
        'nama_parfum',
        'harga_jual_per_ml',
        'harga_beli_per_ml',
        'stok_ml',
    ];

    // Relasi ke tabel Supplier
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'id_supplier', 'id_supplier');
    }
}