<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StasiunPengisian extends Model
{
    use HasFactory;

    protected $table = 'stasiun_pengisian';

    protected $fillable = [
        'nama_stasiun',
        'lokasi',
        'status',
        'latitude',
        'longitude',
    ];

    public function riwayatPengisian()
    {
        return $this->hasMany(RiwayatPengisian::class, 'stasiun_pengisian_id');
    }

    public function chargers()
    {
        return $this->hasMany(Charger::class, 'stasiun_pengisian_id');
    }
}