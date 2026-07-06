<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Support\Facades\Http;

/**
 * Trait GeocodingTrait — Geocode lokasi via Nominatim (OpenStreetMap).
 *
 * Digunakan oleh:
 * - StasiunController
 * - MapController
 * (sebelumnya method ini di-copy-paste manual di kedua controller)
 */
trait GeocodingTrait
{
    /**
     * Coba geocode lokasi (Nominatim/OpenStreetMap).
     * Return ['lat' => ..., 'lon' => ...] kalau ketemu, atau null kalau gagal.
     */
    private function geocodeLokasi(string $lokasi): ?array
    {
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
            // Geocoding gagal → biarkan lewat
        }
        return null;
    }
}
