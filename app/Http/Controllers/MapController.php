<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\StasiunPengisian;
use App\Models\RiwayatPengisian;
use App\Models\AuditLog;

class MapController extends Controller
{
    public function index()
    {
        $stations = StasiunPengisian::with(['chargers' => function ($q) {
            $q->select('id', 'stasiun_pengisian_id', 'kode_unit', 'tipe', 'daya_kw', 'status');
        }])->get();

        // Hitung jumlah charger per stasiun (handle kalau model Chargers belum punya relasi)
        $stations->each(function ($st) {
            try {
                $st->total_unit  = $st->chargers->count();
                $st->unit_aktif  = $st->chargers->where('status', 'Available')->count();
                $st->unit_in_use = $st->chargers->where('status', 'In Use')->count();
            } catch (\Exception $e) {
                $st->total_unit  = 0;
                $st->unit_aktif  = 0;
                $st->unit_in_use = 0;
            }
        });

        $totalAktif   = $stations->where('status', 'Active')->count();
        $totalMaint   = $stations->where('status', 'Maintenance')->count();
        $totalInaktif = $stations->where('status', 'Inactive')->count();
        $totalStation = $stations->count();

        // Hitung berapa stasiun yang belum punya koordinat (untuk badge tombol sync)
        $tanpaKoordinat = $stations->filter(fn($s) =>
            empty($s->latitude) || empty($s->longitude)
        )->count();

        return view('mapview', compact(
            'stations', 'totalAktif', 'totalMaint', 'totalInaktif',
            'totalStation', 'tanpaKoordinat'
        ));
    }

    /**
     * Sinkron koordinat stasiun yang belum punya lat/lng.
     * Memanggil Nominatim (OpenStreetMap) untuk semua stasiun yang koordinatnya kosong.
     * Return JSON ringkas hasil proses.
     */
    public function syncCoordinates()
    {
        $stations = StasiunPengisian::where(function ($q) {
            $q->whereNull('latitude')
              ->orWhereNull('longitude')
              ->orWhere('latitude', 0)
              ->orWhere('longitude', 0);
        })->get();

        $found   = 0;
        $missing = 0;
        $details = [];

        foreach ($stations as $st) {
            $coord = $this->geocode($st->lokasi);
            if ($coord) {
                $st->update([
                    'latitude'  => $coord['lat'],
                    'longitude' => $coord['lon'],
                ]);
                $found++;
                $details[] = "✓ {$st->nama_stasiun} ({$st->lokasi})";
            } else {
                $missing++;
                $details[] = "✗ {$st->nama_stasiun} ({$st->lokasi}) — tidak ditemukan";
            }
            // Throttle ringan biar Nominatim gak block (max 1 req/detik)
            usleep(1100000); // 1.1 detik
        }

        AuditLog::record(
            'Sinkron Koordinat',
            'Map',
            "Sinkron koordinat stasiun: {$found} berhasil, {$missing} gagal dari " . $stations->count() . " stasiun",
            $missing > 0 ? 'warning' : 'info'
        );

        return response()->json([
            'success'      => true,
            'total'        => $stations->count(),
            'found'        => $found,
            'missing'      => $missing,
            'details'      => $details,
            'message'      => $stations->count() === 0
                ? 'Semua stasiun sudah punya koordinat. Tidak ada yang perlu disinkron.'
                : "Selesai. {$found} stasiun berhasil dapat koordinat, {$missing} gagal.",
        ]);
    }

    /**
     * Helper geocode via Nominatim (OpenStreetMap).
     */
    private function geocode(?string $lokasi): ?array
    {
        if (empty($lokasi)) return null;

        try {
            $resp = Http::timeout(8)
                ->withHeaders(['User-Agent' => 'EVSahabatApp/1.0 (admin@ev-sahabat.com)'])
                ->get('https://nominatim.openstreetmap.org/search', [
                    'q'              => $lokasi,
                    'format'         => 'json',
                    'limit'          => 1,
                    'countrycodes'   => 'id',
                    'addressdetails' => 0,
                ]);

            if ($resp->successful()) {
                $data = $resp->json();
                if (!empty($data) && isset($data[0]['lat'], $data[0]['lon'])) {
                    return [
                        'lat' => (float) $data[0]['lat'],
                        'lon' => (float) $data[0]['lon'],
                    ];
                }
            }
        } catch (\Throwable $e) {
            // gagal → lewat
        }
        return null;
    }
}

