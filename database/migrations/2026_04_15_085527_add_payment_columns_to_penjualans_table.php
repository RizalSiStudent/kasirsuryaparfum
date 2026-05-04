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
    Schema::table('penjualans', function (Blueprint $table) {
        // Tambahkan kolom status dan snap_token setelah metode_pembayaran
        $table->string('status_pembayaran')->default('pending')->after('metode_pembayaran');
        $table->string('snap_token')->nullable()->after('status_pembayaran');
    });
}

public function down(): void
{
    Schema::table('penjualans', function (Blueprint $table) {
        $table->dropColumn(['status_pembayaran', 'snap_token']);
    });
}
};
