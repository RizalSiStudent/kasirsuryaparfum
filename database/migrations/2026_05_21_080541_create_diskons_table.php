<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diskons', function (Blueprint $table) {
            $table->id('id_diskon');
            $table->string('nama_event');
            $table->date('tanggal_mulai');
            $table->date('tanggal_akhir');
            $table->enum('jenis_diskon', ['persentase', 'nominal']);
            $table->integer('nilai_diskon'); // Menyimpan nilai % (misal: 10) atau nominal Rp (misal: 5000)
            $table->integer('minimal_belanja')->default(0); // Syarat minimal belanja agar diskon aktif
            $table->boolean('is_active')->default(true); // Status apakah event ini diaktifkan/dimatikan manual
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diskons');
    }
};