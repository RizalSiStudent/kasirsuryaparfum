<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Diskon extends Model
{
    use HasFactory;

    protected $primaryKey = 'id_diskon';
    
    // Mengizinkan semua kolom diisi secara massal
    protected $guarded = [];

    // Fungsi tambahan untuk mengecek apakah promo ini sedang valid HARI INI
    public function getIsValidAttribute()
    {
        $hariIni = Carbon::now()->startOfDay();
        $mulai = Carbon::parse($this->tanggal_mulai)->startOfDay();
        $akhir = Carbon::parse($this->tanggal_akhir)->endOfDay();

        return $this->is_active && $hariIni->between($mulai, $akhir);
    }
}