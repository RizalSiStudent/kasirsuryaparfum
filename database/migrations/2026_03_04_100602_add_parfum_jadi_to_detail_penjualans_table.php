<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detail_penjualans', function (Blueprint $table) {
            // Ubah kolom racikan lama menjadi nullable (boleh kosong jika beli parfum jadi)
            $table->unsignedBigInteger('id_parfum')->nullable()->change();
            $table->unsignedBigInteger('id_botol')->nullable()->change();
            $table->decimal('jumlah_ml', 10, 2)->nullable()->change();
            
            // Tambahkan kolom khusus untuk Parfum Jadi
            $table->unsignedBigInteger('id_parfum_jadi')->nullable()->after('id_botol');
            $table->integer('jumlah_pcs')->default(1)->after('jumlah_ml');

            // Daftarkan relasinya
            $table->foreign('id_parfum_jadi')->references('id_parfum_jadi')->on('parfum_jadis')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('detail_penjualans', function (Blueprint $table) {
            $table->dropForeign(['id_parfum_jadi']);
            $table->dropColumn(['id_parfum_jadi', 'jumlah_pcs']);
        });
    }
};