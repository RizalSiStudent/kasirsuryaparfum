<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detail_penjualans', function (Blueprint $table) {
            $table->id('id_detail_penjualan');
            $table->unsignedBigInteger('id_penjualan');
            $table->unsignedBigInteger('id_parfum');
            $table->unsignedBigInteger('id_botol');
            
            $table->decimal('jumlah_ml', 10, 2);
            $table->decimal('harga_saat_transaksi', 15, 2);
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();

            // Constraint Foreign Key
            $table->foreign('id_penjualan')->references('id_penjualan')->on('penjualans')->onDelete('cascade');
            $table->foreign('id_parfum')->references('id_parfum')->on('parfums');
            $table->foreign('id_botol')->references('id_botol')->on('botols');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_penjualans');
    }
};
