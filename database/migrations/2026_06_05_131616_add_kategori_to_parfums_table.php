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
        Schema::table('parfums', function (Blueprint $table) {
            $table->string('grade')->nullable()->after('nama_parfum');
        $table->enum('gender', ['Male', 'Female', 'Unisex'])->nullable()->after('grade');
        $table->text('deskripsi')->nullable()->after('gender');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parfums', function (Blueprint $table) {
            $table->dropColumn(['grade', 'gender', 'deskripsi']);
        });
    }
};
