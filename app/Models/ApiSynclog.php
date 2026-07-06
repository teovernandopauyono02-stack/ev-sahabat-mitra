<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiSynclog extends Model
{
    protected $fillable = [
        'api_connection_id',
        'status',
        'response_time',
        'http_code',
        'response_data',
        'error_message',
        'kwh_value',
        'voltage_value',
        'current_value',
        'power_value',
        'daily_yield_kwh',
    ];

    protected $casts = [
        'response_time'   => 'integer',
        'http_code'       => 'integer',
        'kwh_value'       => 'decimal:2',
        'voltage_value'   => 'decimal:2',
        'current_value'   => 'decimal:2',
        'power_value'     => 'decimal:2',
        'daily_yield_kwh' => 'decimal:2',
    ];

    // ✅ Relasi ke ApiConnection
    public function connection(): BelongsTo
    {
        return $this->belongsTo(ApiConnection::class, 'api_connection_id');
    }
}