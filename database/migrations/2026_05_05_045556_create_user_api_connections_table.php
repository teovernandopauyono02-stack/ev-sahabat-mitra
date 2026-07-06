<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_api_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('api_directory_id')->nullable()->constrained('api_directories')->onDelete('set null');
            $table->string('nama_koneksi');
            $table->string('api_url');
            $table->string('api_method')->default('GET');
            $table->text('api_key')->nullable();
            $table->text('api_header')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_api_connections');
    }
};