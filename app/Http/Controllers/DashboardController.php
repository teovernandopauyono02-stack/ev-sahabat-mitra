<?php

namespace App\Http\Controllers;

use App\Models\StasiunPengisian;
use App\Models\RiwayatPengisian;
use App\Models\Charger;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // ==================================================
        // DATA STATISTIK
        // ==================================================
        $totalStasiun     = StasiunPengisian::count() ?? 0;
        $stasiunAktif     = StasiunPengisian::whereIn('status', ['Active', 'active'])->count() ?? 0;
        $maintenance      = StasiunPengisian::whereIn('status', ['Maintenance', 'maintenance'])->count() ?? 0;
        $stasiunOffline   = StasiunPengisian::whereIn('status', ['Inactive', 'inactive', 'Offline'])->count() ?? 0;

        $totalEnergi      = RiwayatPengisian::sum('energi_kwh') ?? 0;
        $totalPengisian   = RiwayatPengisian::count() ?? 0;
        $pengisianAktif   = 0;

        $totalUnit        = Charger::count() ?? 0;
        $unitAvailable    = Charger::whereIn('status', ['Available', 'available'])->count() ?? 0;
        $unitMaintenance  = Charger::whereIn('status', ['Maintenance', 'maintenance'])->count() ?? 0;
        $unitInUse        = Charger::whereIn('status', ['In Use', 'in use'])->count() ?? 0;

        // ==================================================
        // DATA TABEL STASIUN
        // ==================================================
        $station           = StasiunPengisian::withCount('chargers')->latest()->take(10)->get();
        $totalStationCount = StasiunPengisian::count() ?? 0;

        // ==================================================
        // DATA PETA / MANAGE STATION
        // ==================================================
        $mapStation = StasiunPengisian::select('id', 'nama_stasiun', 'lokasi', 'latitude', 'longitude', 'status')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('latitude', '!=', 0)
            ->where('longitude', '!=', 0)
            ->orderBy('nama_stasiun')
            ->get();

        // ==================================================
        // DATA ENERGY LOG
        // ==================================================
        $log = RiwayatPengisian::with('stasiun')->latest()->take(10)->get();

        // ==================================================
        // DATA GRAFIK AWAL
        // Jika tidak ada data hari ini → kirim array kosong
        // supaya grafik tidak menampilkan titik-titik 0
        // ==================================================
        $chartData = $this->getChartDataAwal();

        $companyInfo = (object) ['nama' => 'PT. Sahabat Mitra Intrabuana'];

        return view('dashboard', compact(
            'totalStasiun',
            'totalPengisian',
            'totalEnergi',
            'pengisianAktif',
            'stasiunAktif',
            'maintenance',
            'stasiunOffline',
            'totalUnit',
            'unitAvailable',
            'unitMaintenance',
            'unitInUse',
            'station',
            'mapStation',
            'log',
            'companyInfo',
            'chartData',
            'totalStationCount'
        ));
    }

    /**
     * DATA GRAFIK 24 JAM HARI INI
     * - Selalu tampilkan label 00:00 - 23:00
     * - Nilai 0 jika tidak ada pengisian
     * - Blade akan cek: jika semua nilai 0 → grafik kosong (tidak ada garis/titik)
     */
    private function getChartDataAwal()
    {
        $tz  = 'Asia/Jakarta';
        $now = Carbon::now($tz);

        // Ambil data pengisian hari ini, group per jam
        $rows = RiwayatPengisian::selectRaw('HOUR(waktu_mulai) as jam, SUM(energi_kwh) as total')
            ->whereDate('waktu_mulai', $now->toDateString())
            ->groupBy('jam')
            ->get()
            ->keyBy('jam');

        // Bangun array 24 jam
        $data = [];
        for ($j = 0; $j < 24; $j++) {
            $data[] = [
                'label' => str_pad($j, 2, '0', STR_PAD_LEFT) . ':00',
                'total' => isset($rows[$j]) ? (float) $rows[$j]->total : 0,
            ];
        }

        return $data;
    }

    /**
     * ENDPOINT AJAX FILTER GRAFIK
     * Dipanggil saat admin klik tombol 1J / 24J / 7H / 30H
     */
    public function chartData(Request $request)
    {
        $range     = $request->get('range', '1J');
        $stasiunId = $request->get('stasiun_id', '');
        $tz        = 'Asia/Jakarta';
        $now       = Carbon::now($tz);
        $bulan     = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
        $data      = [];

        $query = RiwayatPengisian::query();
        if (!empty($stasiunId)) {
            $query->where('stasiun_pengisian_id', $stasiunId);
        }

        switch ($range) {

            // ── 1J: Data hari ini per jam (00:00 - 23:00) ──
            case '1J':
                $rows = (clone $query)
                    ->whereDate('waktu_mulai', $now->toDateString())
                    ->selectRaw('HOUR(waktu_mulai) as jam, SUM(energi_kwh) as total')
                    ->groupBy('jam')
                    ->get()
                    ->keyBy('jam');

                for ($j = 0; $j < 24; $j++) {
                    $data[] = [
                        'label' => sprintf('%02d:00', $j),
                        'total' => isset($rows[$j]) ? (float) $rows[$j]->total : 0,
                    ];
                }
                break;

            // ── 24J: 24 jam terakhir per jam ──
            case '24J':
                $mulai = $now->copy()->subHours(23)->startOfHour();
                $rows  = (clone $query)
                    ->where('waktu_mulai', '>=', $mulai)
                    ->selectRaw("DATE_FORMAT(waktu_mulai, '%Y-%m-%d %H:00') as waktu, SUM(energi_kwh) as total")
                    ->groupBy('waktu')
                    ->get()
                    ->keyBy('waktu');

                for ($i = 0; $i < 24; $i++) {
                    $jam   = $mulai->copy()->addHours($i);
                    $kunci = $jam->format('Y-m-d H:00');
                    $data[] = [
                        'label' => $jam->format('H:00'),
                        'total' => isset($rows[$kunci]) ? (float) $rows[$kunci]->total : 0,
                    ];
                }
                break;

            // ── 7H: 7 hari terakhir per hari ──
            case '7H':
                $mulai = $now->copy()->subDays(6)->startOfDay();
                $rows  = (clone $query)
                    ->where('waktu_mulai', '>=', $mulai)
                    ->selectRaw('DATE(waktu_mulai) as tanggal, SUM(energi_kwh) as total')
                    ->groupBy('tanggal')
                    ->get()
                    ->keyBy('tanggal');

                for ($i = 0; $i < 7; $i++) {
                    $hari  = $mulai->copy()->addDays($i);
                    $kunci = $hari->format('Y-m-d');
                    $data[] = [
                        'label' => $hari->day . ' ' . $bulan[$hari->month - 1],
                        'total' => isset($rows[$kunci]) ? (float) $rows[$kunci]->total : 0,
                    ];
                }
                break;

            // ── 30H: 30 hari terakhir per hari ──
            case '30H':
                $mulai = $now->copy()->subDays(29)->startOfDay();
                $rows  = (clone $query)
                    ->where('waktu_mulai', '>=', $mulai)
                    ->selectRaw('DATE(waktu_mulai) as tanggal, SUM(energi_kwh) as total')
                    ->groupBy('tanggal')
                    ->get()
                    ->keyBy('tanggal');

                for ($i = 0; $i < 30; $i++) {
                    $hari  = $mulai->copy()->addDays($i);
                    $kunci = $hari->format('Y-m-d');
                    $data[] = [
                        'label' => $hari->day . ' ' . $bulan[$hari->month - 1],
                        'total' => isset($rows[$kunci]) ? (float) $rows[$kunci]->total : 0,
                    ];
                }
                break;

            default:
                $data = [];
                break;
        }

        return response()->json($data);
    }
}