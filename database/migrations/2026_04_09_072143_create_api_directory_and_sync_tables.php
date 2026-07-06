<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApiDirectoryAndSyncTables extends Migration
{
    public function up()
    {
        // Tabel untuk menyimpan daftar API perusahaan yang tersedia
        Schema::create('api_directory', function (Blueprint $table) {
            $table->id();
            $table->string('nama_perusahaan');
            $table->string('kode_perusahaan')->unique();
            $table->text('deskripsi')->nullable();
            $table->string('api_url');
            $table->string('api_key')->nullable();
            $table->enum('metode', ['GET', 'POST', 'PUT', 'DELETE'])->default('GET');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->string('kategori')->nullable();
            $table->json('field_mapping')->nullable();
            $table->timestamp('last_sync')->nullable();
            $table->integer('total_sync')->default(0);
            $table->timestamps();
        });

        // Tabel untuk menyimpan data hasil sync dari API eksternal
        Schema::create('api_sync_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_directory_id')->constrained('api_directory')->onDelete('cascade');
            $table->string('entity_type');
            $table->json('raw_data');
            $table->json('processed_data')->nullable();
            $table->timestamp('sync_time');
            $table->enum('sync_status', ['success', 'failed', 'partial'])->default('success');
            $table->text('sync_message')->nullable();
            $table->timestamps();
        });

        // Tabel untuk tracking active API connection per user
        Schema::create('user_api_connection', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('api_directory_id')->constrained('api_directory')->onDelete('cascade');
            $table->boolean('is_active')->default(false);
            $table->timestamp('connected_at')->nullable();
            $table->timestamps();
        });

        // Update tabel api_integration yang sudah ada (jika belum ada kolom)
        if (Schema::hasTable('api_integration')) {
            Schema::table('api_integration', function (Blueprint $table) {
                if (!Schema::hasColumn('api_integration', 'auto_sync')) {
                    $table->boolean('auto_sync')->default(false)->after('status');
                }
                if (!Schema::hasColumn('api_integration', 'sync_interval')) {
                    $table->integer('sync_interval')->default(300)->after('auto_sync');
                }
                if (!Schema::hasColumn('api_integration', 'next_sync')) {
                    $table->timestamp('next_sync')->nullable()->after('sync_interval');
                }
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('api_integration')) {
            Schema::table('api_integration', function (Blueprint $table) {
                if (Schema::hasColumn('api_integration', 'auto_sync')) {
                    $table->dropColumn(['auto_sync', 'sync_interval', 'next_sync']);
                }
            });
        }
        
        Schema::dropIfExists('user_api_connection');
        Schema::dropIfExists('api_sync_data');
        Schema::dropIfExists('api_directory');
    }
}