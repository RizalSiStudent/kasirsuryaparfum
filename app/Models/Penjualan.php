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
        'subtotal',          // <-- Tambahan baru
        'potongan_diskon',   // <-- Tambahan baru
        'uang_dibayar',      // <-- Tambahan baru
        'kembalian',         // <-- Tambahan baru
        'total_bayar',
        'metode_pembayaran',
        'status_pembayaran', 
        'snap_token',        
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