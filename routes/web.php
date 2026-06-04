<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;
use App\Http\Controllers\PaymentCallbackController;

// Rute untuk Pemilik
Route::middleware(['auth', 'role:pemilik'])->group(function () {
    Volt::route('/pemilik/parfum', 'pemilik.parfum-crud')
        ->name('pemilik.parfum');
    Volt::route('/pemilik/botol', 'pemilik.botol-crud')
        ->name('pemilik.botol');
    Volt::route('/pemilik/supplier', 'pemilik.supplier-crud')
        ->name('pemilik.supplier');
    Volt::route('/pemilik/pelanggan', 'pemilik.pelanggan-crud')
        ->name('pemilik.pelanggan');
    Volt::route('/pemilik/karyawan', 'pemilik.karyawan-crud')
        ->name('pemilik.karyawan');
    Volt::route('/pemilik/parfum-jadi', 'pemilik.parfum-jadi-crud')
        ->name('pemilik.parfum-jadi');
    Volt::route('/pemilik/laporan', 'pemilik.laporan') 
        ->name('pemilik.laporan');
    Volt::route('/pemilik/dashboard', 'pemilik.dashboard')->name('pemilik.dashboard');
    // Tambahkan ini di dalam group route pemilik
    Volt::route('/pemilik/diskon', 'pemilik.diskon-crud')->name('pemilik.diskon');
});

// RUTE KASIR & PEMILIK
Route::middleware(['auth', 'role:kasir,pemilik'])->group(function () {
    Volt::route('/kasir/penjualan', 'kasir.penjualan')->name('kasir.penjualan');
    Volt::route('/kasir/struk/{no_faktur}', 'kasir.struk')->name('kasir.struk');
    // Tambahkan baris ini di dalam grup middleware role:kasir atau kasir, pemilik
Volt::route('/kasir/dashboard', 'kasir.dashboard')->name('kasir.dashboard');    
});

// RUTE ADMIN STOK & PEMILIK
Route::middleware(['auth', 'role:admin_stok,pemilik'])->group(function () {
    Volt::route('/admin-stok/kelola', 'admin-stok.kelola-stok')->name('admin-stok.kelola');
    // Tambahkan baris ini di dalam grup middleware role:admin_stok atau admin_stok, pemilik
Volt::route('/admin-stok/dashboard', 'admin-stok.dashboard')->name('admin-stok.dashboard');
});

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/dashboard', function () {
    $peran = auth()->user()->peran;
    
    if ($peran === 'pemilik') {
        return redirect()->route('pemilik.dashboard');
    } elseif ($peran === 'kasir') {
        return redirect()->route('kasir.dashboard');
    } elseif ($peran === 'admin_stok') {
        return redirect()->route('admin-stok.dashboard');
    }
    
    return view('dashboard'); // Cadangan jika role tidak dikenali
})->middleware(['auth', 'verified'])->name('dashboard');

Route::post('/midtrans/callback', [PaymentCallbackController::class, 'receive']);

require __DIR__.'/settings.php';
