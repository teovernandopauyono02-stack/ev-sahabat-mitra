<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class AccountLock extends Model
{
    protected $fillable = [
        'email', 'locked_until', 'lock_count', 'reason', 'last_ip',
    ];

    protected $casts = [
        'locked_until' => 'datetime',
    ];

    /** Apakah email ini sedang dikunci? */
    public static function isLocked(string $email): bool
    {
        $lock = self::where('email', $email)->first();
        return $lock && $lock->locked_until && $lock->locked_until->isFuture();
    }

    /** Sisa detik sebelum akun bisa login lagi */
    public static function secondsUntilUnlock(string $email): int
    {
        $lock = self::where('email', $email)->first();
        if (!$lock || !$lock->locked_until || $lock->locked_until->isPast()) {
            return 0;
        }
        return (int) ceil(now()->diffInSeconds($lock->locked_until, false));
    }

    /**
     * Lock email selama X detik. Default 30 detik sesuai kebutuhan sistem.
     * Akumulasi lock_count untuk deteksi serangan persistent.
     */
    public static function lockEmail(string $email, int $seconds = 30, ?string $ip = null, string $reason = '5x percobaan login gagal'): self
    {
        $existing = self::where('email', $email)->first();

        if ($existing) {
            $existing->update([
                'locked_until' => Carbon::now()->addSeconds($seconds),
                'lock_count'   => $existing->lock_count + 1,
                'reason'       => $reason,
                'last_ip'      => $ip,
            ]);
            return $existing;
        }

        return self::create([
            'email'        => $email,
            'locked_until' => Carbon::now()->addSeconds($seconds),
            'lock_count'   => 1,
            'reason'       => $reason,
            'last_ip'      => $ip,
        ]);
    }

    /** Cek apakah ini email yang sering dilock (lebih dari 3x) → tandai mencurigakan */
    public function isSuspicious(): bool
    {
        return $this->lock_count >= 3;
    }
}
