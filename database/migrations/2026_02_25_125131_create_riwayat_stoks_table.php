<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('riwayat_stoks', function (Blueprint $table) {
            $table->id('id_riwayat');
            $table->unsignedBigInteger('id_parfum');
            
            // Membedakan apakah ini barang masuk dari supplier, atau retur keluar ke supplier
            $table->enum('jenis_pergerakan', ['Stok Masuk', 'Retur Keluar']);
            
            $table->decimal('jumlah_ml', 10, 2);
            $table->text('keterangan')->nullable(); // Alasan retur atau catatan nota pembelian
            $table->timestamps();

            // Relasi ke tabel parfums
            $table->foreign('id_parfum')->references('id_parfum')->on('parfums')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_stoks');
    }
};