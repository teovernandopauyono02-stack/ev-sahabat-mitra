<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PasswordResetOtp extends Model
{
    protected $fillable = [
        'email', 'otp_code', 'channel', 'expires_at',
        'is_used', 'ip_address',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_used'    => 'boolean',
    ];

    /** Apakah OTP masih valid (belum expired & belum dipakai)? */
    public function isValid(): bool
    {
        return !$this->is_used && $this->expires_at && $this->expires_at->isFuture();
    }

    /** Generate kode OTP 6 digit */
    public static function generateCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Buat OTP baru untuk email tertentu.
     * Hapus OTP lama yang belum dipakai untuk email yang sama.
     */
    public static function createForEmail(string $email, string $channel = 'email', ?string $ip = null): self
    {
        // Invalidasi OTP lama email ini
        self::where('email', $email)->where('is_used', false)->update(['is_used' => true]);

        return self::create([
            'email'      => $email,
            'otp_code'   => self::generateCode(),
            'channel'    => $channel,
            'expires_at' => Carbon::now()->addMinutes(10),
            'is_used'    => false,
            'ip_address' => $ip,
        ]);
    }
}
