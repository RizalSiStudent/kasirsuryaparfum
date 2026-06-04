<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penjualans', function (Blueprint $table) {
            $table->integer('subtotal')->default(0)->after('id_pelanggan');
            $table->integer('potongan_diskon')->default(0)->after('subtotal');
            $table->integer('uang_dibayar')->nullable()->after('metode_pembayaran');
            $table->integer('kembalian')->nullable()->after('uang_dibayar');
        });
    }

    public function down(): void
    {
        Schema::table('penjualans', function (Blueprint $table) {
            $table->dropColumn(['subtotal', 'potongan_diskon', 'uang_dibayar', 'kembalian']);
        });
    }
};