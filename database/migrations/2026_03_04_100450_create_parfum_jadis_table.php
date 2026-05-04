<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parfum_jadis', function (Blueprint $table) {
            $table->id('id_parfum_jadi');
            $table->string('nama_parfum', 100); // Misal: Surya Signature Baccarat 30ml
            $table->decimal('harga_beli_per_pcs', 15, 2);
            $table->decimal('harga_jual_per_pcs', 15, 2);
            $table->integer('stok_pcs')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parfum_jadis');
    }
};