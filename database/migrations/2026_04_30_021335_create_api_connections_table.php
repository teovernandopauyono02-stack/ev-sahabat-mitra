<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_connections', function (Blueprint $table) {
            $table->id();
            $table->string('nama_koneksi');
            $table->enum('tipe_sistem', ['solar_panel', 'charging_station', 'energy_meter', 'other'])->default('other');
            $table->string('api_url');
            $table->enum('api_method', ['GET', 'POST'])->default('GET');
            $table->string('api_username')->nullable();
            $table->string('api_password')->nullable();
            $table->text('api_header')->nullable(); // JSON format
            
            // Response path mapping (dot notation)
            $table->string('response_path_kwh');
            $table->string('response_path_voltage')->nullable();
            $table->string('response_path_current')->nullable();
            $table->string('response_path_power')->nullable();
            
            // Sync settings
            $table->integer('sync_interval')->default(300); // dalam detik
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_sync_at')->nullable();
            $table->string('last_sync_status')->nullable(); // success, failed
            $table->text('last_sync_message')->nullable();
            $table->integer('last_response_time')->nullable(); // ms
            $table->integer('last_http_code')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_connections');
    }
};