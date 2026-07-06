<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RiwayatPengisian extends Model
{
    use HasFactory;

    protected $table = 'riwayat_pengisian';

    protected $fillable = [
        'stasiun_pengisian_id',
        'energi_kwh',
        'waktu_mulai',
        'waktu_selesai',
        'lokasi',
    ];

    protected $casts = [
        'waktu_mulai'   => 'datetime',
        'waktu_selesai' => 'datetime',
        'energi_kwh'    => 'float',
    ];

    public function stasiun()
    {
        return $this->belongsTo(StasiunPengisian::class, 'stasiun_pengisian_id');
    }
}