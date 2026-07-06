<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel password_reset_otps — menyimpan OTP untuk Lupa Password.
 * OTP dikirim via email dan berlaku selama 10 menit.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('password_reset_otps', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('otp_code', 10);                 // 6 digit OTP
            $table->string('channel', 20)->default('email'); // email | whatsapp
            $table->timestamp('expires_at');
            $table->boolean('is_used')->default(false);
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['email', 'otp_code']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_otps');
    }
};
