<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * FIX: Perbesar kolom api_password dari VARCHAR ke TEXT
     * karena encrypted value sangat panjang (> 255 karakter)
     *
     * Jalankan: php artisan migrate
     */
    public function up(): void
    {
        Schema::table('api_connections', function (Blueprint $table) {
            // Ubah api_password menjadi LONGTEXT agar cukup untuk encrypted string
            $table->longText('api_password')->nullable()->change();

            // Sekalian pastikan api_header juga LONGTEXT
            $table->longText('api_header')->nullable()->change();

            // Pastikan api_url juga TEXT (URL bisa panjang)
            $table->text('api_url')->change();
        });
    }

    public function down(): void
    {
        Schema::table('api_connections', function (Blueprint $table) {
            $table->string('api_password', 255)->nullable()->change();
            $table->string('api_header', 500)->nullable()->change();
            $table->string('api_url', 255)->change();
        });
    }
};