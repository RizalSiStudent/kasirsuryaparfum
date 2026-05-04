<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pelanggans', function (Blueprint $table) {
            $table->id('id_pelanggan'); 
            $table->string('nama_pelanggan', 100);
            $table->enum('jenis_kelamin', ['Laki-laki', 'Perempuan']);
            $table->string('no_telepon', 15)->nullable();
            $table->text('alamat')->nullable();
            // Gunakan useCurrent() agar otomatis terisi saat data ditambah
            $table->dateTime('tanggal_gabung')->useCurrent(); 
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pelanggans');
    }
};
