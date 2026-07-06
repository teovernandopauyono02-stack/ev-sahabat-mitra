<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiIntegration extends Model
{
    protected $table = 'api_integration';

    protected $fillable = [
        'nama', 'url', 'method', 'headers', 'body',
        'status', 'auto_sync', 'sync_interval', 'next_sync',
    ];

    protected $casts = [
        'headers'   => 'array',
        'next_sync' => 'datetime',
    ];
}
