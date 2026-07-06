<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserApiConnection extends Model
{
    protected $table = 'user_api_connection';

    protected $fillable = [
        'user_id', 'api_directory_id', 'is_active', 'connected_at',
    ];

    protected $casts = [
        'is_active'    => 'boolean',
        'connected_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function apiDirectory()
    {
        return $this->belongsTo(ApiDirectory::class, 'api_directory_id');
    }
}
