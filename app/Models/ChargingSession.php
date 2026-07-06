<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChargingSession extends Model
{
    protected $table = 'charging_sessions';

    protected $fillable = [
        'charger_id', 'user_id', 'start_time', 'end_time',
        'energy_kwh', 'cost', 'status',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time'   => 'datetime',
    ];

    public function charger()
    {
        return $this->belongsTo(Charger::class);
    }
}
