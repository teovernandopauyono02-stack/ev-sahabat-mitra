<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_integration', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100);
            $table->string('url_api', 500);
            $table->string('api_key', 500)->nullable();
            $table->string('metode', 10)->default('GET');
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->timestamp('last_sync')->nullable();
            $table->integer('total_data_sync')->default(0);
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_integration');
    }
};