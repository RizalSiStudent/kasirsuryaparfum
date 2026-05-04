<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parfums', function (Blueprint $table) {
            // Menggunakan id_parfum sebagai Primary Key sesuai ERD
            $table->id('id_parfum'); 
            $table->string('nama_parfum', 100);
            
            // Tipe data decimal untuk harga dan stok sesuai ERD
            // (15, 2) berarti maksimal 15 digit dengan 2 angka di belakang koma
            $table->decimal('harga_jual_per_ml', 15, 2);
            $table->decimal('harga_beli_per_ml', 15, 2);
            $table->decimal('stok_ml', 10, 2)->default(0);
            
            // Foto bisa kosong (nullable) pada saat pertama kali input
            $table->string('foto_parfum', 255)->nullable(); 
            
            $table->timestamps(); // otomatis membuat created_at dan updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parfums');
    }
};
