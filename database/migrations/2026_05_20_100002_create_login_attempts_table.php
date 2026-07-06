<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel login_attempts — mencatat percobaan login.
     * Bukti: Keamanan Data & Jaringan (Makul No.2)
     * - Manajemen akses
     * - Hardening sistem (deteksi brute-force)
     * - SOP keamanan
     */
    public function up(): void
    {
        Schema::create('login_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->enum('status', ['success', 'failed'])->default('failed');
            $table->string('failure_reason')->nullable();     // misal: "Email tidak ditemukan", "Password salah"
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['email', 'created_at']);
            $table->index(['ip_address', 'created_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_attempts');
    }
};
