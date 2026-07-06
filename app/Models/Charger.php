<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Charger extends Model
{
    use HasFactory;

    protected $table = 'chargers';

    protected $fillable = [
        'stasiun_pengisian_id',
        'kode_unit',
        'tipe',
        'daya_kw',
        'status',
    ];

    public function stasiun()
    {
        return $this->belongsTo(StasiunPengisian::class, 'stasiun_pengisian_id');
    }
}