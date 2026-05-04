<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Akun Pemilik
        User::create([
            'name' => 'Rizal Arilya',
            'email' => 'owner@suryaparfum.com',
            'password' => Hash::make('owner123'),
            'peran' => 'pemilik',
            'aktif' => true,
        ]);

        // Akun Admin Stok
        User::create([
            'name' => 'Admin Stok',
            'email' => 'adminstok@suryaparfum.com',
            'password' => Hash::make('adminstok123'),
            'peran' => 'admin_stok',
            'aktif' => true,
        ]);

        // Akun Kasir
        User::create([
            'name' => 'Kasir',
            'email' => 'kasir@suryaparfum.com',
            'password' => Hash::make('kasir123'),
            'peran' => 'kasir',
            'aktif' => true,
        ]);
    }
}