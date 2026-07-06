<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StasiunPengisian;
use App\Models\AuditLog;

class StasiunController extends Controller
{
    use \App\Http\Controllers\Traits\GeocodingTrait;

    public function index()
    {
        $highlightId = request('highlight');
        $perPage = 20;

        // Kalau highlight by ID numerik, cari halaman yang berisi stasiun itu
        if ($highlightId && is_numeric($highlightId)) {
            // Ambil semua ID terurut seperti query normal
            $allIds = StasiunPengisian::latest()->pluck('id')->toArray();
            $pos    = array_search((int)$highlightId, $allIds);

            if ($pos !== false) {
                // Hitung halaman yang benar
                $targetPage = (int) floor($pos / $perPage) + 1;
                // Redirect ke halaman yang tepat kalau belum di sana
                if (request('page', 1) != $targetPage) {
                    return redirect()->route('station.index', [
                        'highlight' => $highlightId,
                        'page'      => $targetPage,
                    ]);
                }
            }
        }

        $station = StasiunPengisian::withCount('chargers')->latest()->paginate($perPage);
        return view('stasiun', compact('station'));
    }
    public function show($id)
    {
        $stasiun  = StasiunPengisian::with('chargers')->findOrFail($id);
        $chargers = $stasiun->chargers;
        return view('stasiun-detail', compact('stasiun', 'chargers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_stasiun' => 'required|string|max:100',
            'lokasi'       => 'required|string|max:255',
            'status'       => 'required|in:Active,Maintenance,Inactive',
            'latitude'     => 'nullable|numeric',
            'longitude'    => 'nullable|numeric',
        ]);

        $data = $request->only(['nama_stasiun','lokasi','status','latitude','longitude']);

        // Auto-geocode kalau lat/lng kosong
        if (empty($data['latitude']) || empty($data['longitude'])) {
            $coord = $this->geocodeLokasi($data['lokasi']);
            if ($coord) {
                $data['latitude']  = $coord['lat'];
                $data['longitude'] = $coord['lon'];
            }
        }

        $stasiun = StasiunPengisian::create($data);

        AuditLog::record('Tambah Stasiun', 'Station',
            "Menambahkan stasiun: {$request->nama_stasiun} di {$request->lokasi}"
                . (!empty($data['latitude']) ? ' (koordinat auto-geocode)' : ''),
            'info',
            [],
            $data
        );

        return redirect()->route('station.index')
            ->with('success', !empty($data['latitude'])
                ? 'Stasiun berhasil ditambahkan & langsung muncul di peta!'
                : 'Stasiun berhasil ditambahkan! (Koordinat belum ketemu, cek manual via Map View)'
            );
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nama_stasiun' => 'required|string|max:100',
            'lokasi'       => 'required|string|max:255',
            'status'       => 'required|in:Active,Maintenance,Inactive',
            'latitude'     => 'nullable|numeric',
            'longitude'    => 'nullable|numeric',
        ]);

        $station = StasiunPengisian::findOrFail($id);
        $oldData = $station->only(['nama_stasiun', 'lokasi', 'status', 'latitude', 'longitude']);
        $data    = $request->only(['nama_stasiun','lokasi','status','latitude','longitude']);

        // Auto-geocode kalau lat/lng kosong (atau lokasi berubah & koordinat dikosongkan user)
        if (empty($data['latitude']) || empty($data['longitude'])) {
            $coord = $this->geocodeLokasi($data['lokasi']);
            if ($coord) {
                $data['latitude']  = $coord['lat'];
                $data['longitude'] = $coord['lon'];
            }
        }

        $station->update($data);
        $newData = $station->only(['nama_stasiun', 'lokasi', 'status', 'latitude', 'longitude']);

        AuditLog::record(
            'Update Stasiun', 'Station',
            "Memperbarui stasiun ID {$id}: {$station->nama_stasiun}",
            'info',
            $oldData,
            $newData
        );

        return redirect()->route('station.index')
            ->with('success', 'Stasiun berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $station = StasiunPengisian::findOrFail($id);
        $oldData = $station->only(['nama_stasiun', 'lokasi', 'status', 'latitude', 'longitude']);

        AuditLog::record(
            'Hapus Stasiun', 'Station',
            "Menghapus stasiun: {$station->nama_stasiun} (ID {$id})",
            'warning',
            $oldData,
            []
        );
        $station->delete();

        return redirect()->route('station.index')
            ->with('success', 'Stasiun berhasil dihapus!');
    }
}

