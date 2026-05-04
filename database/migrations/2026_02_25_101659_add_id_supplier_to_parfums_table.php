<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parfums', function (Blueprint $table) {
            // Tambahkan kolom id_supplier, kita buat nullable() berjaga-jaga jika ada parfum lama
            $table->unsignedBigInteger('id_supplier')->nullable()->after('id_parfum');
            
            // Buat relasi Foreign Key ke tabel suppliers
            $table->foreign('id_supplier')->references('id_supplier')->on('suppliers')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('parfums', function (Blueprint $table) {
            $table->dropForeign(['id_supplier']);
            $table->dropColumn('id_supplier');
        });
    }
};