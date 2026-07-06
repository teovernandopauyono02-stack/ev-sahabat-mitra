<?php

namespace Database\Seeder;

use Illuminate\Database\Seeder;
use App\Models\StasiunPengisian;
use App\Models\RiwayatPengisian;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin user
        User::updateOrCreate(
            ['email' => 'admin@ev-sahabat.com'],
            [
                'name'     => 'Administrator',
                'email'    => 'admin@ev-sahabat.com',
                'password' => Hash::make('admin309'),
            ]
        );

        // Sample station
        $station = [
            ['nama_stasiun' => 'EV-01', 'lokasi' => 'Jakarta',  'status' => 'Active',      'latitude' => -6.2088,  'longitude' => 106.8456],
            ['nama_stasiun' => 'EV-02', 'lokasi' => 'Bandung',  'status' => 'Active',      'latitude' => -6.9175,  'longitude' => 107.6191],
            ['nama_stasiun' => 'EV-03', 'lokasi' => 'Surabaya', 'status' => 'Maintenance', 'latitude' => -7.2575,  'longitude' => 112.7521],
            ['nama_stasiun' => 'EV-04', 'lokasi' => 'Lampung',  'status' => 'Active',      'latitude' => -5.4500,  'longitude' => 105.2667],
            ['nama_stasiun' => 'EV-05', 'lokasi' => 'Semarang', 'status' => 'Active',      'latitude' => -6.9932,  'longitude' => 110.4203],
        ];

        foreach ($station as $station) {
            StasiunPengisian::updateOrCreate(
                ['nama_stasiun' => $station['nama_stasiun']],
                $station
            );
        }

        // Sample energy log
        $stasiunId = StasiunPengisian::pluck('id')->toArray();
        $sampleLog = [
            ['energi_kwh' => 36, 'hour_ago' => 1],
            ['energi_kwh' => 38, 'hour_ago' => 2],
            ['energi_kwh' => 29, 'hour_ago' => 3],
            ['energi_kwh' => 40, 'hour_ago' => 4],
            ['energi_kwh' => 29, 'hour_ago' => 5],
            ['energi_kwh' => 45, 'hour_ago' => 6],
            ['energi_kwh' => 32, 'hour_ago' => 7],
            ['energi_kwh' => 50, 'hour_ago' => 8],
            ['energi_kwh' => 41, 'hour_ago' => 9],
            ['energi_kwh' => 55, 'hour_ago' => 10],
            ['energi_kwh' => 48, 'hour_ago' => 11],
        ];

        foreach ($sampleLog as $log) {
            $start = Carbon::now()->subHours($log['hours_ago']);
            $end   = $start->copy()->addMinutes(rand(30, 90));

            RiwayatPengisian::create([
                'stasiun_pengisian_id' => $stasiunId[array_rand($stasiunId)],
                'energi_kwh'           => $log['energi_kwh'],
                'waktu_mulai'          => $start,
                'waktu_selesai'        => $end,
            ]);
        }
    }
}
