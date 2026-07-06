<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel audit_logs — mencatat seluruh aktivitas user di sistem.
     * Bukti: Audit Teknologi Informasi (Makul No.1)
     * - Evaluasi tata kelola TI
     * - IT control & audit proses kerja
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('user_name')->nullable();          // nama user saat log dibuat
            $table->string('user_role')->nullable();          // role user saat log dibuat
            $table->string('action');                          // aksi yang dilakukan
            $table->string('module')->nullable();              // modul/fitur (Dashboard, Station, dst)
            $table->text('description')->nullable();           // deskripsi detail aksi
            $table->string('ip_address', 45)->nullable();      // IP address
            $table->string('user_agent')->nullable();          // browser/device
            $table->json('old_data')->nullable();              // data sebelum perubahan
            $table->json('new_data')->nullable();              // data sesudah perubahan
            $table->enum('severity', ['info', 'warning', 'critical'])->default('info');
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['module', 'created_at']);
            $table->index('severity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
