<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiSyncData extends Model
{
    protected $table = 'api_sync_data';

    protected $fillable = [
        'api_directory_id', 'entity_type', 'raw_data',
        'processed_data', 'sync_time', 'sync_status', 'sync_message',
    ];

    protected $casts = [
        'raw_data'       => 'array',
        'processed_data' => 'array',
        'sync_time'      => 'datetime',
    ];

    public function apiDirectory()
    {
        return $this->belongsTo(ApiDirectory::class, 'api_directory_id');
    }
}
