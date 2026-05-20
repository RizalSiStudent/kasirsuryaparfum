<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('detail_penjualans', function (Blueprint $table) {
            // Menambahkan harga beli (modal) per item dan total modalnya
            $table->integer('harga_beli_saat_transaksi')->after('harga_saat_transaksi')->default(0);
            $table->integer('subtotal_modal')->after('subtotal')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('detail_penjualans', function (Blueprint $table) {
            $table->dropColumn(['harga_beli_saat_transaksi', 'subtotal_modal']);
        });
    }
};