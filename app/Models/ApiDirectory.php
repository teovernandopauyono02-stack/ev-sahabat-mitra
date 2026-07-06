<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiDirectory extends Model
{
    protected $table = 'api_directory';

    protected $fillable = [
        'nama_perusahaan', 'kode_perusahaan', 'deskripsi',
        'api_url', 'api_key', 'metode', 'status',
        'kategori', 'field_mapping', 'last_sync', 'total_sync',
    ];

    protected $casts = [
        'field_mapping' => 'array',
        'last_sync'     => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeSearch($query, $keyword)
    {
        return $query->where(function ($q) use ($keyword) {
            $q->where('nama_perusahaan', 'like', "%{$keyword}%")
              ->orWhere('kode_perusahaan', 'like', "%{$keyword}%")
              ->orWhere('kategori', 'like', "%{$keyword}%");
        });
    }

    public function latestSync()
    {
        return $this->hasOne(ApiSyncData::class, 'api_directory_id')->latest('sync_time');
    }

    public function syncData()
    {
        return $this->hasMany(ApiSyncData::class, 'api_directory_id');
    }
}
