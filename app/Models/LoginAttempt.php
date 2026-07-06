<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginAttempt extends Model
{
    protected $fillable = [
        'email', 'ip_address', 'user_agent',
        'status', 'failure_reason', 'user_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Apakah IP ini brute-force? (>5 gagal dalam 5 menit) */
    public static function isBruteForce(string $ip): bool
    {
        return self::where('ip_address', $ip)
            ->where('status', 'failed')
            ->where('created_at', '>=', now()->subSeconds(300))
            ->count() >= 5;
    }
}
