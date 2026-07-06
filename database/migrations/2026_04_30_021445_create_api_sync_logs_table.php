<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_connection_id')->constrained('api_connections')->onDelete('cascade');
            $table->enum('status', ['success', 'failed', 'timeout'])->default('failed');
            $table->integer('response_time')->nullable(); // milliseconds
            $table->integer('http_code')->nullable();
            $table->text('response_data')->nullable(); // raw response untuk debugging
            $table->text('error_message')->nullable();
            $table->decimal('kwh_value', 10, 2)->nullable();
            $table->decimal('voltage_value', 10, 2)->nullable();
            $table->decimal('current_value', 10, 2)->nullable();
            $table->decimal('power_value', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_sync_logs');
    }
};