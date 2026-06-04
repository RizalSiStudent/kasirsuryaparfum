<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diskons', function (Blueprint $table) {
            // Tambahkan kolom boolean khusus_pelanggan
            $table->boolean('khusus_pelanggan')->default(false)->after('minimal_belanja');
        });
    }

    public function down(): void
    {
        Schema::table('diskons', function (Blueprint $table) {
            $table->dropColumn('khusus_pelanggan');
        });
    }
};