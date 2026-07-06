<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StasiunPengisian;
use App\Models\AuditLog;

class MapController extends Controller
{
    use \App\Http\Controllers\Traits\GeocodingTrait;

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
            $coord = $this->geocodeLokasi($st->lokasi);
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

}

