<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Penjualan extends Model
{
    protected $primaryKey = 'id_penjualan';

    protected $fillable = [
        'no_faktur',
        'id_pengguna',
        'id_pelanggan',
        'total_bayar',
        'metode_pembayaran',
        'status_pembayaran', // Wajib ditambahkan
        'snap_token',        // Wajib ditambahkan
        'waktu_transaksi',
    ];

    public function pengguna() {
        return $this->belongsTo(User::class, 'id_pengguna');
    }
    
    public function pelanggan() {
        return $this->belongsTo(Pelanggan::class, 'id_pelanggan', 'id_pelanggan');
    }
    
    public function details()
{
    return $this->hasMany(DetailPenjualan::class, 'id_penjualan', 'id_penjualan');
}
}