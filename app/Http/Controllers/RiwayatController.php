<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RiwayatPengisian;
use App\Models\StasiunPengisian;
use App\Models\AuditLog;
use App\Http\Controllers\Controller;

class RiwayatController extends Controller
{
    public function index(Request $request)
    {
        $query = RiwayatPengisian::with('stasiun');

        // Filter stasiun
        if ($request->stasiun_pengisian_id) {
            $query->where('stasiun_pengisian_id', $request->stasiun_pengisian_id);
        }

        // Filter periode
        $periode = $request->get('periode', 'all');
        $now     = \Carbon\Carbon::now('Asia/Jakarta');

        switch ($periode) {
            case 'today':
                $query->whereDate('waktu_mulai', $now->toDateString());
                break;
            case '7days':
                $query->whereBetween('waktu_mulai', [
                    $now->copy()->subDays(6)->startOfDay(),
                    $now->copy()->endOfDay(),
                ]);
                break;
            case '30days':
                $query->whereBetween('waktu_mulai', [
                    $now->copy()->subDays(29)->startOfDay(),
                    $now->copy()->endOfDay(),
                ]);
                break;
            case 'month':
                $query->whereBetween('waktu_mulai', [
                    $now->copy()->startOfMonth(),
                    $now->copy()->endOfMonth(),
                ]);
                break;
            case 'custom':
                if ($request->filled('start_date') && $request->filled('end_date')) {
                    $query->whereBetween('waktu_mulai', [
                        \Carbon\Carbon::parse($request->start_date)->startOfDay(),
                        \Carbon\Carbon::parse($request->end_date)->endOfDay(),
                    ]);
                }
                break;
        }

        // Filter tanggal harian (legacy)
        if ($request->tanggal) {
            $query->whereDate('waktu_mulai', $request->tanggal);
        }

        // Kalau highlight high-energy dari Alert Info → sort by energi DESC supaya
        // baris yang relevan (energi tinggi) muncul di atas / halaman 1
        if ($request->filled('highlight_anomaly')) {
            $log = $query->orderByDesc('energi_kwh')->paginate(20)->appends($request->all());
        } elseif ($request->highlight === 'high-energy') {
            $log = $query->orderByDesc('energi_kwh')->paginate(20)->appends($request->all());
        } elseif ($request->filled('highlight_station')) {
            // Filter stasiun spesifik dari Alert "Tidak Ada Aktivitas" → semua baris pasti match
            $query->where('stasiun_pengisian_id', $request->highlight_station);
            $log = $query->latest()->paginate(20)->appends($request->all());
        } else {
            $log = $query->latest()->paginate(20)->appends($request->all());
        }

        $station = StasiunPengisian::select('id', 'nama_stasiun', 'lokasi')->orderBy('nama_stasiun')->get();

        return view('riwayat', compact('log', 'station'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'stasiun_pengisian_id' => 'required|exists:stasiun_pengisian,id',
            'energi_kwh'           => 'required|numeric|min:0',
            'waktu_mulai'          => 'required|date',
            'waktu_selesai'        => 'required|date|after:waktu_mulai',
        ]);

        $stasiun = StasiunPengisian::findOrFail($request->stasiun_pengisian_id);

        RiwayatPengisian::create([
            'stasiun_pengisian_id' => $request->stasiun_pengisian_id,
            'energi_kwh'           => $request->energi_kwh,
            'waktu_mulai'          => $request->waktu_mulai,
            'waktu_selesai'        => $request->waktu_selesai,
        ]);

        AuditLog::record(
            'Tambah Log Energi',
            'EnergyLog',
            "Menambahkan log energi {$request->energi_kwh} kWh di stasiun {$stasiun->nama_stasiun}",
            'info'
        );

        return redirect()->route('energy-log.index')
            ->with('success', 'Log energi berhasil ditambahkan!');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'stasiun_pengisian_id' => 'required|exists:stasiun_pengisian,id',
            'energi_kwh'           => 'required|numeric|min:0',
            'waktu_mulai'          => 'required|date',
            'waktu_selesai'        => 'required|date|after:waktu_mulai',
        ]);

        $stasiun = StasiunPengisian::findOrFail($request->stasiun_pengisian_id);
        $log     = RiwayatPengisian::findOrFail($id);
        $oldData = $log->only(['stasiun_pengisian_id', 'energi_kwh', 'waktu_mulai', 'waktu_selesai']);

        $log->update([
            'stasiun_pengisian_id' => $request->stasiun_pengisian_id,
            'energi_kwh'           => $request->energi_kwh,
            'waktu_mulai'          => $request->waktu_mulai,
            'waktu_selesai'        => $request->waktu_selesai,
        ]);

        $newData = $log->only(['stasiun_pengisian_id', 'energi_kwh', 'waktu_mulai', 'waktu_selesai']);

        AuditLog::record(
            'Update Log Energi',
            'EnergyLog',
            "Memperbarui log energi ID {$id} — {$request->energi_kwh} kWh di {$stasiun->nama_stasiun}",
            'info',
            $oldData,
            $newData
        );

        return redirect()->route('energy-log.index')
            ->with('success', 'Log energi berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $log     = RiwayatPengisian::with('stasiun')->findOrFail($id);
        $namaStasiun = $log->stasiun->nama_stasiun ?? 'Unknown';

        AuditLog::record(
            'Hapus Log Energi',
            'EnergyLog',
            "Menghapus log energi ID {$id} ({$log->energi_kwh} kWh) di {$namaStasiun}",
            'warning'
        );

        $log->delete();

        return redirect()->route('energy-log.index')
            ->with('success', 'Log energi berhasil dihapus!');
    }
}

