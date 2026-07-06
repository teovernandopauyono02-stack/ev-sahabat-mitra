<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id', 'user_name', 'user_role',
        'action', 'module', 'description',
        'ip_address', 'user_agent',
        'old_data', 'new_data', 'severity',
    ];

    protected $casts = [
        'old_data' => 'array',
        'new_data' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Helper statis — catat aktivitas dari mana saja di sistem.
     * Contoh: AuditLog::record('Tambah Stasiun', 'Station', 'Menambahkan EV-13')
     */
    public static function record(
        string $action,
        string $module = '',
        string $description = '',
        string $severity = 'info',
        array  $oldData = [],
        array  $newData = []
    ): void {
        try {
            $user = Auth::user();
            self::create([
                'user_id'      => $user?->id,
                'user_name'    => $user?->name ?? 'System',
                'user_role'    => $user?->role ?? '-',
                'action'       => $action,
                'module'       => $module,
                'description'  => $description,
                'ip_address'   => Request::ip(),
                'user_agent'   => substr(Request::userAgent() ?? '', 0, 255),
                'old_data'     => $oldData ?: null,
                'new_data'     => $newData ?: null,
                'severity'     => $severity,
            ]);
        } catch (\Exception $e) {
            // Jangan sampai error audit menghentikan proses utama
            \Log::warning('AuditLog::record failed: ' . $e->getMessage());
        }
    }
}
