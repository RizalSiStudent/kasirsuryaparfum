<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('botols', function (Blueprint $table) {
            $table->id('id_botol'); 
            $table->string('nama_botol', 100);
            $table->integer('kapasitas_ml');
            $table->decimal('harga_jual_per_pcs', 15, 2);
            $table->decimal('harga_beli_per_pcs', 15, 2);
            $table->integer('stok_pcs')->default(0);
            $table->string('foto_botol', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('botols');
    }
};