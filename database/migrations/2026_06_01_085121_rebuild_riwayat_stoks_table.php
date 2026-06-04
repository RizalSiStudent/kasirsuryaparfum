<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Bersihkan tabel riwayat lama jika ada
        Schema::dropIfExists('riwayat_botols');
        Schema::dropIfExists('riwayat_parfum_jadis');
        Schema::dropIfExists('riwayat_stoks');

        // 2. Buat tabel riwayat_stoks universal
        Schema::create('riwayat_stoks', function (Blueprint $table) {
            $table->id('id_riwayat');
            $table->string('kategori'); // Untuk penanda: 'Bibit Parfum', 'Botol', atau 'Parfum Jadi'
            
            // Kolom ID dibuat nullable agar bisa diisi salah satu saja sesuai barangnya
            $table->unsignedBigInteger('id_parfum')->nullable();
            $table->unsignedBigInteger('id_botol')->nullable();
            $table->unsignedBigInteger('id_parfum_jadi')->nullable();
            
            $table->enum('jenis_pergerakan', ['Stok Masuk', 'Retur Keluar']);
            $table->decimal('jumlah', 10, 2); // Diubah jadi 'jumlah' (universal) bukan 'jumlah_ml'
            $table->text('keterangan')->nullable();
            $table->timestamps();

            // Relasi ke masing-masing master barang
            $table->foreign('id_parfum')->references('id_parfum')->on('parfums')->onDelete('cascade');
            $table->foreign('id_botol')->references('id_botol')->on('botols')->onDelete('cascade');
            $table->foreign('id_parfum_jadi')->references('id_parfum_jadi')->on('parfum_jadis')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_stoks');
    }
};