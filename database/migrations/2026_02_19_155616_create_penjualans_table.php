<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penjualans', function (Blueprint $table) {
            $table->id('id_penjualan');
            $table->string('no_faktur', 50)->unique();
            
            // Relasi ke tabel users (kasir) dan pelanggans
            $table->unsignedBigInteger('id_pengguna');
            $table->unsignedBigInteger('id_pelanggan')->nullable(); // Boleh kosong jika pelanggan umum
            
            $table->decimal('total_bayar', 15, 2)->default(0);
            $table->string('metode_pembayaran', 20)->default('Tunai');
            $table->dateTime('waktu_transaksi')->useCurrent();
            $table->timestamps();

            // Constraint Foreign Key
            $table->foreign('id_pengguna')->references('id')->on('users');
            $table->foreign('id_pelanggan')->references('id_pelanggan')->on('pelanggans');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penjualans');
    }
};
