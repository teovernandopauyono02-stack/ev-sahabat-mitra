<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_config', function (Blueprint $table) {
            $table->id();
            $table->string('nama_api', 100);
            $table->text('url_endpoint');
            $table->string('api_key', 255)->nullable();
            $table->enum('metode', ['GET', 'POST'])->default('GET');
            $table->integer('interval_sync')->default(60);
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->dateTime('last_sync')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_config');
    }
};