<?php

namespace App\Services;

use App\Models\StasiunPengisian;
use App\Models\RiwayatPengisian;
use App\Models\Charger;

class DashboardDataService
{
    public function getDashboardData($userId)
    {
        return $this->getLocalData();
    }

    public function getActiveCompanyInfo($userId)
    {
        return [
            'is_connected' => false,
            'company_name' => 'EV Sahabat',
            'company_code' => 'LOCAL',
            'last_sync'    => now(),
            'api_url'      => null
        ];
    }

    private function getLocalData()
    {
        try { $totalStasiun   = StasiunPengisian::count(); } catch (\Exception $e) { $totalStasiun = 0; }
        try { $totalPengisian = RiwayatPengisian::count(); } catch (\Exception $e) { $totalPengisian = 0; }
        try { $totalEnergi    = RiwayatPengisian::sum('energi_kwh') ?? 0; } catch (\Exception $e) { $totalEnergi = 0; }
        try { $pengisianAktif = RiwayatPengisian::whereNull('waktu_selesai')->count(); } catch (\Exception $e) { $pengisianAktif = 0; }
        try { $recentSessions = RiwayatPengisian::latest('waktu_mulai')->limit(10)->get(); } catch (\Exception $e) { $recentSessions = collect([]); }

        // Catatan: kolom 'stations' lengkap tidak dipakai di view (DashboardController ambil sendiri).
        // Kirim collection kosong supaya tidak query 510+ baris percuma.
        $stations = collect([]);

        return [
            'source'          => 'local',
            'company_name'    => 'EV Sahabat',
            'total_stasiun'   => $totalStasiun,
            'total_pengisian' => $totalPengisian,
            'total_energi'    => $totalEnergi,
            'pengisian_aktif' => $pengisianAktif,
            'recent_sessions' => $recentSessions,
            'stations'        => $stations,
            'sync_time'       => now(),
            'raw_data'        => null
        ];
    }
}