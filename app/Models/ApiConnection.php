<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApiConnection extends Model
{
    protected $fillable = [
        'nama_koneksi',
        'tipe_sistem',
        'api_url',
        'daily_yield_url',
        'api_method',
        'api_username',
        'api_password',
        'api_header',
        'response_path_kwh',
        'response_path_voltage',
        'response_path_current',
        'response_path_power',
        'response_path_daily_yield',
        'sync_interval',
        'is_active',
        'last_sync_at',
        'last_sync_status',
        'last_sync_message',
        'last_response_time',
        'last_http_code',
    ];

    protected $casts = [
        'is_active'          => 'boolean',
        'last_sync_at'       => 'datetime',
        'sync_interval'      => 'integer',
        'last_response_time' => 'integer',
        'last_http_code'     => 'integer',
        // ✅ Auto decrypt saat dibaca, auto encrypt saat disimpan
        'api_password'       => 'encrypted',
    ];

    // Relasi ke sync logs
    public function syncLogs(): HasMany
    {
        return $this->hasMany(ApiSynclog::class, 'api_connection_id');
    }

    // Property untuk ambil header sebagai array
    public function getApiHeaderArrayAttribute(): array
    {
        if (empty($this->api_header)) {
            return [];
        }

        $decoded = json_decode($this->api_header, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDueForSync($query)
    {
        return $query->active()
            ->where(function ($q) {
                $q->whereNull('last_sync_at')
                    ->orWhereRaw('TIMESTAMPDIFF(SECOND, last_sync_at, NOW()) >= sync_interval');
            });
    }
}