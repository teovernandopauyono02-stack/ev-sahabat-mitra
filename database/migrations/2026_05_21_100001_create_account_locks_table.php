<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel account_locks — kunci akun (per-email) setelah 5x gagal login.
 * Lebih kuat dari sekadar IP block karena penyerang yang ganti IP
 * tetap tidak bisa coba akun yang sama.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('account_locks', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->timestamp('locked_until');
            $table->integer('lock_count')->default(1);   // berapa kali pernah di-lock
            $table->string('reason')->nullable();
            $table->string('last_ip', 45)->nullable();
            $table->timestamps();

            $table->index('locked_until');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_locks');
    }
};
