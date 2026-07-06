<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Charger;
use App\Models\StasiunPengisian;
use App\Models\AuditLog;

class ChargerController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'stasiun_pengisian_id' => 'required|exists:stasiun_pengisian,id',
            'kode_unit'            => 'required|string|max:50',
            'tipe'                 => 'required|in:AC,DC,Fast Charging',
            'daya_kw'              => 'required|numeric|min:0',
            'status'               => 'required|in:Available,In Use,Maintenance,Offline',
        ]);

        $charger = Charger::create($request->all());
        $stasiun = StasiunPengisian::find($request->stasiun_pengisian_id);

        AuditLog::record(
            'Tambah Unit Charger',
            'Charger',
            "Menambahkan charger {$charger->kode_unit} ({$charger->tipe} {$charger->daya_kw}kW) di stasiun " . ($stasiun->nama_stasiun ?? '-'),
            'info'
        );

        return redirect()->route('station.show', $request->stasiun_pengisian_id)
            ->with('success', 'Unit charger berhasil ditambahkan!');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'kode_unit' => 'required|string|max:50',
            'tipe'      => 'required|in:AC,DC,Fast Charging',
            'daya_kw'   => 'required|numeric|min:0',
            'status'    => 'required|in:Available,In Use,Maintenance,Offline',
        ]);

        $charger = Charger::findOrFail($id);
        $oldData = $charger->only(['kode_unit', 'tipe', 'daya_kw', 'status']);
        $charger->update($request->all());
        $newData = $charger->only(['kode_unit', 'tipe', 'daya_kw', 'status']);

        AuditLog::record(
            'Update Unit Charger',
            'Charger',
            "Memperbarui charger ID {$id} ({$charger->kode_unit}) — status: {$charger->status}",
            'info',
            $oldData,
            $newData
        );

        return redirect()->route('station.show', $charger->stasiun_pengisian_id)
            ->with('success', 'Unit charger berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $charger   = Charger::findOrFail($id);
        $stasiunId = $charger->stasiun_pengisian_id;
        $oldData = $charger->only(['kode_unit', 'tipe', 'daya_kw', 'status']);

        AuditLog::record(
            'Hapus Unit Charger',
            'Charger',
            "Menghapus charger {$charger->kode_unit} (ID {$id}) dari stasiun ID {$stasiunId}",
            'warning',
            $oldData,
            []
        );

        $charger->delete();

        return redirect()->route('station.show', $stasiunId)
            ->with('success', 'Unit charger berhasil dihapus!');
    }
}
