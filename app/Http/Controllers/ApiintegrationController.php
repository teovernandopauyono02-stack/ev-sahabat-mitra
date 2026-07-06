<?php

namespace App\Http\Controllers;

use App\Models\ApiConnection;
use App\Models\ApiSynclog;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Arr;
use Carbon\Carbon;

class ApiintegrationController extends Controller
{
    /**
     * Format tanggal jadi "1 Jan 2026" (Bahasa Indonesia singkat).
     */
    private function formatTanggalID($date): string
    {
        $bulan = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
        $d = Carbon::parse($date);
        return $d->day . ' ' . $bulan[$d->month - 1] . ' ' . $d->year;
    }

    /**
     * Format teks periode laporan, contoh: "1 Jan 2026 s/d 31 Des 2026".
     * Kalau tanggal sama, cukup tampilkan satu tanggal.
     */
    private function formatPeriode($startDate, $endDate): string
    {
        $start = Carbon::parse($startDate);
        $end   = Carbon::parse($endDate);

        if ($start->isSameDay($end)) {
            return $this->formatTanggalID($start);
        }
        return $this->formatTanggalID($start) . ' s/d ' . $this->formatTanggalID($end);
    }

    /**
     * Parse filter periode dari request.
     * Mendukung preset: today, 7days, 30days, month, custom.
     * Default: 7days (7 hari terakhir).
     *
     * @return array [startDate, endDate, periodeText]
     */
    private function parsePeriodeFilter(Request $request): array
    {
        $periode = $request->get('periode', '7days');
        $now     = Carbon::now('Asia/Jakarta');

        switch ($periode) {
            case 'today':
                $start = $now->copy()->startOfDay();
                $end   = $now->copy()->endOfDay();
                $text  = 'Hari ini, ' . $this->formatTanggalID($start);
                break;

            case '30days':
                $start = $now->copy()->subDays(29)->startOfDay();
                $end   = $now->copy()->endOfDay();
                $text  = '30 hari terakhir (' . $this->formatPeriode($start, $end) . ')';
                break;

            case 'month':
                $start = $now->copy()->startOfMonth();
                $end   = $now->copy()->endOfMonth();
                $bulan = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
                $text  = 'Bulan ' . $bulan[$start->month - 1] . ' ' . $start->year;
                break;

            case 'year':
                $start = $now->copy()->startOfYear();
                $end   = $now->copy()->endOfYear();
                $text  = 'Tahun ' . $now->year;
                break;

            case 'custom':
                $startDate = $request->get('start_date');
                $endDate   = $request->get('end_date');
                if ($startDate && $endDate) {
                    $start = Carbon::parse($startDate)->startOfDay();
                    $end   = Carbon::parse($endDate)->endOfDay();
                    $text  = $this->formatPeriode($start, $end);
                    break;
                }
                // Kalau custom tapi tanggal kosong, fallback ke 7days
                // fall-through

            case '7days':
            default:
                $start = $now->copy()->subDays(6)->startOfDay();
                $end   = $now->copy()->endOfDay();
                $text  = '7 hari terakhir (' . $this->formatPeriode($start, $end) . ')';
                break;
        }

        return [$start, $end, $text];
    }

    // ============================================
    // HALAMAN UTAMA
    // ============================================

    public function index()
    {
        return view('api-integration');
    }

    // ============================================
    // GET DATA
    // ============================================

    public function getConnections()
    {
        $connections = ApiConnection::orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data'    => $connections,
        ]);
    }

    public function getStats()
    {
        $totalKoneksi = ApiConnection::count();
        $koneksiAktif = ApiConnection::where('is_active', true)->count();

        // Real-time data: ambil LIVE dari HopeWind getPowerPlantList
        // supaya angka kW & kWh selalu match dengan dashboard HopeWind.
        $activeConn = ApiConnection::where('is_active', true)->first();
        $realtimePower = 0;
        $todayYield    = 0;

        if ($activeConn) {
            $plantData = $this->fetchHopeWindPlantData($activeConn);
            if ($plantData) {
                if (isset($plantData['nowKw']) && is_numeric($plantData['nowKw'])) {
                    $realtimePower = (float) $plantData['nowKw'];
                }
                if (isset($plantData['todayKwh']) && is_numeric($plantData['todayKwh'])) {
                    $todayYield = (float) $plantData['todayKwh'];

                    // Simpan todayKwh ke sync log terbaru agar fallback bisa pakai
                    if ($todayYield > 0) {
                        ApiSynclog::where('api_connection_id', $activeConn->id)
                            ->where('status', 'success')
                            ->whereDate('created_at', today())
                            ->orderBy('created_at', 'desc')
                            ->limit(1)
                            ->update(['daily_yield_kwh' => $todayYield]);
                    }
                }
            }
        }

        // Fallback: kalau live fetch gagal, ambil dari log terakhir
        if ($realtimePower == 0) {
            $latestLog = ApiSynclog::where('status', 'success')
                ->whereNotNull('power_value')
                ->orderBy('created_at', 'desc')
                ->first();
            if ($latestLog) $realtimePower = (float) $latestLog->power_value;
        }
        if ($todayYield == 0) {
            $todayYield = $this->getTodayYield();
        }

        // Total data
        $totalData = ApiSynclog::where('status', 'success')
            ->whereNotNull('power_value')
            ->count();

        $latestLog = ApiSynclog::where('status', 'success')
            ->orderBy('created_at', 'desc')
            ->first();

        return response()->json([
            'success' => true,
            'data'    => [
                'total_koneksi'  => $totalKoneksi,
                'koneksi_aktif'  => $koneksiAktif,
                'kwh_hari_ini'   => round($todayYield, 2),
                'realtime_power' => round($realtimePower, 2),
                'total_data'     => $totalData,
                'last_sync_time' => $latestLog ? $latestLog->created_at->setTimezone('Asia/Jakarta')->format('H:i:s') : null,
            ],
        ])->header('Cache-Control', 'no-cache, no-store, must-revalidate')
          ->header('Pragma', 'no-cache')
          ->header('Expires', '0');
    }

    /**
     * Ambil Today Yield (kWh) dengan logika:
     * 1. Cek apakah ada sync log hari ini dengan daily_yield_kwh dari API → pakai itu (paling akurat)
     * 2. Kalau tidak ada, hitung sendiri via integrasi
     */
    private function getTodayYield(): float
    {
        $tz    = 'Asia/Jakarta';
        $start = Carbon::now($tz)->startOfDay();

        // Prioritas 1: ambil daily_yield_kwh dari sync log hari ini (paling akurat)
        $apiYield = ApiSynclog::where('status', 'success')
            ->whereNotNull('daily_yield_kwh')
            ->where('daily_yield_kwh', '>', 0)
            ->where('created_at', '>=', $start)
            ->orderBy('created_at', 'desc')
            ->value('daily_yield_kwh');

        if ($apiYield !== null && $apiYield > 0) {
            return (float) $apiYield;
        }

        // Prioritas 2: ambil dari sync log kemarin kalau hari ini belum ada
        // (misalnya baru pagi, belum ada sync hari ini)
        $yesterday = Carbon::now($tz)->subDay()->startOfDay();
        $lastYield = ApiSynclog::where('status', 'success')
            ->whereNotNull('daily_yield_kwh')
            ->where('daily_yield_kwh', '>', 0)
            ->where('created_at', '>=', $yesterday)
            ->orderBy('created_at', 'desc')
            ->value('daily_yield_kwh');

        if ($lastYield !== null && $lastYield > 0) {
            return (float) $lastYield;
        }

        // Prioritas 3: hitung via integrasi trapezoid dari power log hari ini
        return $this->calculateTodayYield();
    }

    /**
     * Hitung Today Yield (kWh) dari akumulasi sync log hari ini.
     * Pakai integrasi trapezoid: energy = avg(P1, P2) * deltaSeconds / 3600
     */
    private function calculateTodayYield(): float
    {
        $tz   = 'Asia/Jakarta';
        $now  = Carbon::now($tz);
        $start = $now->copy()->startOfDay();

        $logs = ApiSynclog::where('status', 'success')
            ->whereNotNull('power_value')
            ->where('created_at', '>=', $start)
            ->orderBy('created_at', 'asc')
            ->get(['power_value', 'created_at']);

        if ($logs->count() < 2) {
            return 0;
        }

        $totalKwh = 0;
        for ($i = 1; $i < $logs->count(); $i++) {
            $p1 = (float) $logs[$i - 1]->power_value;
            $p2 = (float) $logs[$i]->power_value;
            $t1 = Carbon::parse($logs[$i - 1]->created_at);
            $t2 = Carbon::parse($logs[$i]->created_at);

            // PENTING: pakai abs() karena diffInSeconds bisa return nilai negatif
            // tergantung versi Carbon, walau urutan sudah ASC.
            $deltaSec = abs($t2->diffInSeconds($t1));

            // Skip kalau gap > 30 menit (sistem mati/sync terlewat)
            if ($deltaSec > 1800 || $deltaSec <= 0) continue;

            $avgPower = ($p1 + $p2) / 2;
            $totalKwh += $avgPower * $deltaSec / 3600;
        }

        return $totalKwh;
    }

    public function getSyncLogs(Request $request)
    {
        $query = ApiSynclog::with('connection')
            ->orderBy('created_at', 'desc');

        if ($request->filled('connection_id')) {
            $query->where('api_connection_id', $request->connection_id);
        }

        $logs = $query->limit(50)->get();

        return response()->json([
            'success' => true,
            'data'    => $logs,
        ]);
    }

    public function getChartData($id, Request $request)
    {
        $mode = $request->get('mode', 'day');     // day | month | year | total
        $date = $request->get('date');             // YYYY-MM-DD untuk day, YYYY-MM untuk month, YYYY untuk year
        $tz   = 'Asia/Jakarta';
        $now  = Carbon::now($tz);

        $query = ApiSynclog::where('api_connection_id', $id)
            ->where('status', 'success');

        $labels = [];
        $kwh    = [];
        $power  = [];

        switch ($mode) {
            case 'month':
                $base  = $date ? Carbon::parse($date . '-01', $tz) : $now->copy()->startOfMonth();
                $start = $base->copy()->startOfMonth();
                $end   = $base->copy()->endOfMonth();
                $logs  = $query->whereBetween('created_at', [$start, $end])
                               ->orderBy('created_at')->get();

                // Hitung daily energy via integrasi (kWh per hari)
                $dailyEnergy = $this->integratePowerToEnergy($logs, 'd', $tz);
                // Power harian = rata-rata
                $dailyPower = [];
                foreach ($logs as $log) {
                    $d = $log->created_at->setTimezone($tz)->format('d');
                    $dailyPower[$d][] = (float) $log->power_value;
                }
                $jumlahHari = $end->day;
                for ($d = 1; $d <= $jumlahHari; $d++) {
                    $key      = str_pad($d, 2, '0', STR_PAD_LEFT);
                    $labels[] = (string) $d;
                    $kwh[]    = round($dailyEnergy[$key] ?? 0, 2);
                    $power[]  = isset($dailyPower[$key]) ? round(array_sum($dailyPower[$key]) / count($dailyPower[$key]), 2) : 0;
                }
                $rangeText = $base->translatedFormat('F Y');
                break;

            case 'year':
                $tahun = $date ? (int) $date : $now->year;
                $start = Carbon::create($tahun, 1, 1, 0, 0, 0, $tz)->startOfDay();
                $end   = Carbon::create($tahun, 12, 31, 23, 59, 59, $tz)->endOfDay();
                $logs  = $query->whereBetween('created_at', [$start, $end])
                               ->orderBy('created_at')->get();

                $monthlyEnergy = $this->integratePowerToEnergy($logs, 'm', $tz);
                $monthlyPower = [];
                foreach ($logs as $log) {
                    $m = $log->created_at->setTimezone($tz)->format('m');
                    $monthlyPower[$m][] = (float) $log->power_value;
                }
                $bulanShort = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
                for ($m = 1; $m <= 12; $m++) {
                    $key      = str_pad($m, 2, '0', STR_PAD_LEFT);
                    $labels[] = $bulanShort[$m - 1];
                    $kwh[]    = round($monthlyEnergy[$key] ?? 0, 2);
                    $power[]  = isset($monthlyPower[$key]) ? round(array_sum($monthlyPower[$key]) / count($monthlyPower[$key]), 2) : 0;
                }
                $rangeText = (string) $tahun;
                break;

            case 'total':
                // Akumulasi sepanjang masa, group per bulan
                $logs = $query->orderBy('created_at')->get();
                $bucketEnergy = $this->integratePowerToEnergy($logs, 'Y-m', $tz);
                $bucketPower = [];
                foreach ($logs as $log) {
                    $key = $log->created_at->setTimezone($tz)->format('Y-m');
                    $bucketPower[$key][] = (float) $log->power_value;
                }
                ksort($bucketEnergy);
                foreach ($bucketEnergy as $key => $val) {
                    $labels[] = $key;
                    $kwh[]    = round($val, 2);
                    $power[]  = isset($bucketPower[$key]) ? round(array_sum($bucketPower[$key]) / count($bucketPower[$key]), 2) : 0;
                }
                $rangeText = 'Sepanjang waktu';
                break;

            case 'day':
            default:
                $base  = $date ? Carbon::parse($date, $tz) : $now->copy();
                $start = $base->copy()->startOfDay();
                $end   = $base->copy()->endOfDay();
                $logs  = $query->whereBetween('created_at', [$start, $end])
                               ->orderBy('created_at')->get();

                // Per jam: tampilkan rata-rata Power (kW) per jam — sama seperti Running Curve hopewindcloud
                $hourlyPower = [];
                foreach ($logs as $log) {
                    $h = (int) $log->created_at->setTimezone($tz)->format('H');
                    $hourlyPower[$h][] = (float) $log->power_value;
                }
                // Hitung kWh harian total via integrasi (untuk "Today Yield")
                $hourlyEnergy = $this->integratePowerToEnergy($logs, 'H', $tz);

                for ($h = 0; $h < 24; $h++) {
                    $labels[] = sprintf('%02d:00', $h);
                    $hKey = str_pad($h, 2, '0', STR_PAD_LEFT);
                    $kwh[]    = round($hourlyEnergy[$hKey] ?? 0, 2);
                    $power[]  = isset($hourlyPower[$h]) ? round(array_sum($hourlyPower[$h]) / count($hourlyPower[$h]), 2) : 0;
                }
                $rangeText = $this->formatTanggalID($base);
                break;
        }

        $totalKwh    = array_sum($kwh);
        $maxPower    = $power ? max($power) : 0;
        $avgPower    = $power ? round(array_sum($power) / max(1, count(array_filter($power))), 2) : 0;
        $dailyRevenue = round($totalKwh * 1500, 0); // asumsi tarif Rp 1.500/kWh

        return response()->json([
            'success' => true,
            'data'    => [
                'mode'          => $mode,
                'range_text'    => $rangeText,
                'labels'        => $labels,
                'power'         => $power,
                'kwh'           => $kwh,
                'total_kwh'     => round($totalKwh, 2),
                'max_power'     => $maxPower,
                'avg_power'     => $avgPower,
                'daily_revenue' => $dailyRevenue,
            ],
        ]);
    }

    /**
     * Integrasi power (kW) → energy (kWh) per bucket waktu.
     * Pakai metode trapezoid: E += avg(P1, P2) * deltaSec / 3600
     *
     * @param  iterable $logs   collection of ApiSynclog yang sudah diurutkan ASC
     * @param  string   $format format key bucket: 'H' (jam), 'd' (tanggal), 'm' (bulan), 'Y-m'
     * @param  string   $tz     timezone untuk format
     * @return array            ['key' => kwh_total, ...]
     */
    private function integratePowerToEnergy($logs, string $format, string $tz): array
    {
        $result = [];
        $logs   = collect($logs)->values();
        if ($logs->count() < 2) return $result;

        for ($i = 1; $i < $logs->count(); $i++) {
            $prev = $logs[$i - 1];
            $curr = $logs[$i];

            $p1 = (float) ($prev->power_value ?? 0);
            $p2 = (float) ($curr->power_value ?? 0);
            $t1 = Carbon::parse($prev->created_at);
            $t2 = Carbon::parse($curr->created_at);

            $deltaSec = abs($t2->diffInSeconds($t1));
            // Skip gap > 30 menit (bisa berarti sistem mati / sync terlewat)
            if ($deltaSec > 1800 || $deltaSec <= 0) continue;

            $energy = (($p1 + $p2) / 2) * $deltaSec / 3600; // kWh
            $key    = $curr->created_at->setTimezone($tz)->format($format);

            if (!isset($result[$key])) $result[$key] = 0;
            $result[$key] += $energy;
        }

        return $result;
    }

    // ============================================
    // DEBUG — hapus setelah selesai testing
    // ============================================

    // ============================================
    // GET LAST RESPONSE — buat lihat response sync terakhir
    // (gak perlu password, ambil dari DB)
    // ============================================

    public function getLastResponse($id)
    {
        $connection = ApiConnection::findOrFail($id);

        $latestLog = ApiSynclog::where('api_connection_id', $id)
            ->where('status', 'success')
            ->whereNotNull('response_data')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$latestLog) {
            return response()->json([
                'success' => false,
                'message' => 'Belum ada data sync. Silakan klik "Sync Semua" dulu.',
            ], 404);
        }

        $data = json_decode($latestLog->response_data, true);
        if (!is_array($data)) {
            return response()->json([
                'success' => false,
                'message' => 'Response data tidak valid.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'response'                  => $data,
                'current_path_kwh'          => $connection->response_path_kwh,
                'current_path_daily_yield'  => $connection->response_path_daily_yield,
                'sync_time'                 => $latestLog->created_at->setTimezone('Asia/Jakarta')->format('d/m/Y H:i:s'),
            ],
        ]);
    }

    // ============================================
    // SET YIELD PATH — simpan path tanpa password
    // ============================================

    public function setYieldPath(Request $request, $id)
    {
        $connection = ApiConnection::findOrFail($id);

        $request->validate([
            'path' => 'required|string|max:255',
        ]);

        $connection->update([
            'response_path_daily_yield' => $request->path,
        ]);

        // Test ekstrak nilai dari response terakhir untuk validasi
        $latestLog = ApiSynclog::where('api_connection_id', $id)
            ->where('status', 'success')
            ->whereNotNull('response_data')
            ->orderBy('created_at', 'desc')
            ->first();

        $extractedValue = null;
        if ($latestLog) {
            $data = json_decode($latestLog->response_data, true);
            if (is_array($data)) {
                $extractedValue = $this->extractValueFromPath($data, $request->path);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Path berhasil disimpan!',
            'data' => [
                'path'             => $request->path,
                'extracted_value'  => $extractedValue,
                'is_numeric'       => is_numeric($extractedValue),
            ],
        ]);
    }

    public function debugResponse($id)
    {
        $connection = ApiConnection::findOrFail($id);        try {
            $plantId = $this->extractPlantIdFromUrl($connection->api_url);

            $params = [
                'appid'   => $connection->api_username,
                'plantId' => $plantId,
            ];

            $sign           = $this->generateHopewindSign($params, $connection->api_password);
            $params['sign'] = $sign;

            $baseUrl  = $this->getBaseUrl($connection->api_url);
            $response = Http::timeout(15)->get($baseUrl, $params);

            return response()->json([
                'success'        => true,
                'base_url'       => $baseUrl,
                'params_sent'    => $params,
                'sign_generated' => $sign,
                'http_code'      => $response->status(),
                'raw_response'   => $response->json() ?? $response->body(),
                'path_kwh'       => $connection->response_path_kwh,
                'extracted_kwh'  => $this->extractValueFromPath(
                    $response->json(),
                    $connection->response_path_kwh
                ),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
        }
    }

    // ============================================
    // STORE / UPDATE
    // ============================================

    public function store(Request $request)
    {
        // Cek apakah ini update (ada id) atau create baru
        $isUpdate = $request->filled('id');

        // Auto-fill default path untuk HopeWind kalau user gak isi
        // (response_path_kwh = path Real-time Power)
        if (!$request->filled('response_path_kwh')) {
            $url = $request->input('api_url', '');
            if (str_contains($url, 'hopewind')) {
                $request->merge(['response_path_kwh' => 'result.nowKw']);
            }
        }

        $validator = Validator::make($request->all(), [
            'nama_koneksi'      => [
                'required', 'string', 'max:255',
                // Cegah nama duplikat (kecuali pas update koneksi yang sama)
                \Illuminate\Validation\Rule::unique('api_connections', 'nama_koneksi')
                    ->ignore($isUpdate ? $request->id : null),
            ],
            'tipe_sistem'       => 'required|in:solar_panel,charging_station,energy_meter,other',
            'api_url'           => [
                'required', 'url',
                // Cegah URL API duplikat
                \Illuminate\Validation\Rule::unique('api_connections', 'api_url')
                    ->ignore($isUpdate ? $request->id : null),
            ],
            'api_method'        => 'required|in:GET,POST',
            'response_path_kwh' => 'required|string',
            'sync_interval'     => 'required|integer|min:60|max:86400',
            'is_active'         => 'required|boolean',
        ], [
            'nama_koneksi.unique' => 'Nama koneksi sudah dipakai. Coba edit koneksi yang sudah ada, atau pakai nama lain.',
            'api_url.unique'      => 'URL API ini sudah terdaftar. Klik tombol Edit di koneksi yang sudah ada untuk memperbarui Private Key.',
            'nama_koneksi.required' => 'Nama koneksi wajib diisi.',
            'api_url.required'      => 'URL API wajib diisi.',
            'api_url.url'           => 'Format URL API tidak valid.',
            'response_path_kwh.required' => 'Path untuk kWh wajib diisi.',
        ]);

        if ($validator->fails()) {
            // Ambil pesan error pertama supaya user tahu masalahnya apa
            $firstError = $validator->errors()->first();

            return response()->json([
                'success' => false,
                'message' => $firstError ?: 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        if ($request->filled('api_header')) {
            json_decode($request->api_header);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'success' => false,
                    'message' => 'Format JSON header tidak valid',
                ], 422);
            }
        }

        $data = $request->only([
            'nama_koneksi', 'tipe_sistem', 'api_url', 'api_method',
            'api_username', 'api_header',
            'response_path_kwh', 'response_path_voltage',
            'response_path_current', 'response_path_power', 'response_path_daily_yield',
            'sync_interval', 'is_active',
        ]);

        // PENTING: api_password (Private Key) cuma ditimpa kalau user benar-benar input nilai baru.
        // Kalau field kosong saat edit, tetap pakai password yang sudah ada di DB.
        if ($request->filled('api_password')) {
            $data['api_password'] = $request->api_password;
        }

        if ($request->filled('id')) {
            $connection = ApiConnection::findOrFail($request->id);
            $connection->update($data);
            $message = 'Koneksi API berhasil diupdate';
            AuditLog::record(
                'Update Koneksi API',
                'ApiIntegration',
                "Memperbarui koneksi {$connection->nama_koneksi} ({$connection->tipe_sistem})",
                'info'
            );
        } else {
            // Saat create baru, api_password wajib ada
            if (!$request->filled('api_password')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Private Key (api_password) wajib diisi saat menambah koneksi baru.',
                    'errors'  => ['api_password' => ['Private Key wajib diisi.']],
                ], 422);
            }
            $data['api_password'] = $request->api_password;
            $connection = ApiConnection::create($data);
            $message = 'Koneksi API berhasil ditambahkan';
            AuditLog::record(
                'Tambah Koneksi API',
                'ApiIntegration',
                "Menambahkan koneksi {$connection->nama_koneksi} ({$connection->tipe_sistem}) — {$connection->api_url}",
                'info'
            );
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $connection,
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->merge(['id' => $id]);
        return $this->store($request);
    }

    // ============================================
    // HAPUS
    // ============================================

    public function destroy($id)
    {
        $connection = ApiConnection::findOrFail($id);

        AuditLog::record(
            'Hapus Koneksi API',
            'ApiIntegration',
            "Menghapus koneksi {$connection->nama_koneksi} (ID {$id})",
            'warning'
        );

        $connection->delete();

        return response()->json([
            'success' => true,
            'message' => 'Koneksi API berhasil dihapus',
        ]);
    }

    // ============================================
    // TEST KONEKSI
    // ============================================

    public function testConnection(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'api_url'           => 'required|url',
            'api_method'        => 'required|in:GET,POST',
            'response_path_kwh' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $startTime = microtime(true);

            $headers = [];
            if ($request->filled('api_header')) {
                $customHeaders = json_decode($request->api_header, true);
                if (is_array($customHeaders)) {
                    $headers = array_merge($headers, $customHeaders);
                }
            }

            $plantId = $this->extractPlantIdFromUrl($request->api_url);
            $baseUrl = $this->getBaseUrl($request->api_url);

            $params = ['appid' => $request->api_username];
            if ($plantId) {
                $params['plantId'] = $plantId;
            }

            $params['sign'] = $this->generateHopewindSign($params, $request->api_password);

            $http     = Http::timeout(15)->withHeaders($headers);
            $response = $request->api_method === 'GET'
                ? $http->get($baseUrl, $params)
                : $http->post($baseUrl, $params);

            $responseTime = round((microtime(true) - $startTime) * 1000);
            $data         = $response->json();

            $apiSuccess = $response->successful()
                && isset($data['success'])
                && $data['success'] === true;

            if ($apiSuccess) {
                $kwhValue = $this->extractValueFromPath($data, $request->response_path_kwh);

                return response()->json([
                    'success' => true,
                    'message' => 'Koneksi berhasil!',
                    'data'    => [
                        'http_code'       => $response->status(),
                        'response_time'   => $responseTime,
                        'kwh_value'       => $kwhValue,
                        'sample_response' => $data,
                    ],
                ]);
            }

            $apiMessage = $data['message'] ?? ('HTTP ' . $response->status());

            return response()->json([
                'success' => false,
                'message' => 'Koneksi gagal: ' . $apiMessage,
                'data'    => [
                    'http_code'     => $response->status(),
                    'response_time' => $responseTime,
                    'raw_response'  => $data,
                ],
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ============================================
    // FETCH DATA
    // ============================================

    public function fetchData($id)
    {
        $connection = ApiConnection::findOrFail($id);
        $result     = $this->performSync($connection);

        return response()->json($result);
    }

    // ============================================
    // SYNC SEMUA KONEKSI AKTIF
    // ============================================

    public function syncAll()
    {
        $connections = ApiConnection::where('is_active', true)->get();

        $results = [
            'total'   => $connections->count(),
            'success' => 0,
            'failed'  => 0,
            'details' => [],
        ];

        foreach ($connections as $connection) {
            $result = $this->performSync($connection);

            if ($result['success']) {
                $results['success']++;
            } else {
                $results['failed']++;
            }

            $results['details'][] = [
                'nama_koneksi' => $connection->nama_koneksi,
                'status'       => $result['success'] ? 'success' : 'failed',
                'message'      => $result['message'],
            ];
        }

        return response()->json([
            'success' => true,
            'message' => "Sync selesai: {$results['success']} berhasil, {$results['failed']} gagal",
            'data'    => $results,
        ]);
    }

    // ============================================
    // CORE SYNC FUNCTION
    // ============================================

    private function performSync(ApiConnection $connection)
    {
        $startTime = microtime(true);
        $logData   = [
            'api_connection_id' => $connection->id,
            'status'            => 'failed',
        ];

        try {
            $headers = $connection->api_header_array ?? [];
            $plantId = $this->extractPlantIdFromUrl($connection->api_url);
            $baseUrl = $this->getBaseUrl($connection->api_url);

            $params = ['appid' => $connection->api_username];
            if ($plantId) {
                $params['plantId'] = $plantId;
            }
            $params['sign'] = $this->generateHopewindSign($params, $connection->api_password);

            $response = Http::timeout(15)
                ->withHeaders($headers)
                ->get($baseUrl, $params);

            $responseTime             = round((microtime(true) - $startTime) * 1000);
            $logData['response_time'] = $responseTime;
            $logData['http_code']     = $response->status();

            $data = $response->json();

            $apiSuccess = $response->successful()
                && isset($data['success'])
                && $data['success'] === true;

            if ($apiSuccess) {
                $logData['response_data'] = json_encode($data);

                // === Mapping HopeWind ===
                // Endpoint utama getPowerPlantRealData ngasih nowKw, tapi nilainya
                // sering lag/beda detik dibanding dashboard HopeWind.
                // Solusi: ambil data dari endpoint getPowerPlantList yang ngasih
                // SEMUA data sekaligus (nowKw + todayKwh + dst) dan SAMA persis
                // dengan yang di tampilkan dashboard HopeWind.
                $plantData = $this->fetchHopeWindPlantData($connection);

                // Voltage & Current dari endpoint utama (kalau diisi)
                $voltageValue = $connection->response_path_voltage
                    ? $this->extractValueFromPath($data, $connection->response_path_voltage)
                    : null;
                $currentValue = $connection->response_path_current
                    ? $this->extractValueFromPath($data, $connection->response_path_current)
                    : null;

                // Power & kWh dari getPowerPlantList kalau ada (paling akurat),
                // fallback ke endpoint utama kalau gak ada.
                if ($plantData && isset($plantData['nowKw']) && is_numeric($plantData['nowKw'])) {
                    $powerValue    = (float) $plantData['nowKw'];
                    // kwh_value diisi todayKwh (energy), BUKAN nowKw (power)
                    $dailyYieldKwh = isset($plantData['todayKwh']) && is_numeric($plantData['todayKwh'])
                        ? (float) $plantData['todayKwh']
                        : null;
                    $kwhValue      = $dailyYieldKwh; // simpan today yield sebagai kwh_value
                } else {
                    // Fallback: pakai response endpoint utama
                    $rawValue = $this->extractValueFromPath($data, $connection->response_path_kwh);
                    $powerFromPath = $connection->response_path_power
                        ? $this->extractValueFromPath($data, $connection->response_path_power)
                        : $rawValue;
                    $powerValue    = is_numeric($powerFromPath) ? (float) $powerFromPath : null;
                    $kwhValue      = is_numeric($rawValue) ? (float) $rawValue : null;
                    $dailyYieldKwh = $this->fetchDailyYield($connection, $data);
                }

                $logData['status']          = 'success';
                $logData['kwh_value']       = $kwhValue;
                $logData['voltage_value']   = $voltageValue;
                $logData['current_value']   = $currentValue;
                $logData['power_value']     = $powerValue;
                $logData['daily_yield_kwh'] = $dailyYieldKwh;

                $connection->update([
                    'last_sync_at'       => now(),
                    'last_sync_status'   => 'success',
                    'last_sync_message'  => 'Sync berhasil',
                    'last_response_time' => $responseTime,
                    'last_http_code'     => $response->status(),
                ]);

                ApiSynclog::create($logData);

                return [
                    'success' => true,
                    'message' => 'Sync berhasil',
                    'data'    => [
                        'kwh'     => $kwhValue,
                        'voltage' => $voltageValue,
                        'current' => $currentValue,
                        'power'   => $powerValue,
                    ],
                ];
            }

            $apiMessage               = $data['message'] ?? ('HTTP Error: ' . $response->status());
            $logData['error_message'] = $apiMessage;
            $logData['response_data'] = json_encode($data);

            ApiSynclog::create($logData);

            $connection->update([
                'last_sync_at'       => now(),
                'last_sync_status'   => 'failed',
                'last_sync_message'  => $apiMessage,
                'last_response_time' => $responseTime,
                'last_http_code'     => $response->status(),
            ]);

            return ['success' => false, 'message' => $apiMessage];

        } catch (\Exception $e) {
            $logData['error_message'] = $e->getMessage();
            ApiSynclog::create($logData);

            $connection->update([
                'last_sync_at'      => now(),
                'last_sync_status'  => 'failed',
                'last_sync_message' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Fetch Today Yield (kWh) — otomatis cari dari berbagai sumber.
     *
     * Strategi:
     * 1. Kalau path Today Yield udah dikonfigurasi user → pakai itu
     * 2. Kalau response API utama udah ada field daily yield (auto-detect) → ekstrak
     * 3. Kalau gak ada di response utama → coba fetch endpoint statistik HopeWind
     *    yang umum (auto-discovery)
     * 4. Kalau semua gagal → return null (sistem fallback ke kalkulasi integrasi)
     *
     * @param  ApiConnection $connection
     * @param  array|null    $data  response JSON dari endpoint utama (untuk auto-detect)
     * @return float|null
     */
    private function fetchDailyYield(ApiConnection $connection, ?array $data = null): ?float
    {
        // === Strategi 1: User sudah konfigurasi path manual ===
        if (!empty($connection->response_path_daily_yield) && $data !== null) {
            $val = $this->extractValueFromPath($data, $connection->response_path_daily_yield);
            if (is_numeric($val) && $val > 0) {
                return (float) $val;
            }
        }

        // === Strategi 2: Auto-detect field daily yield di response utama ===
        if ($data !== null) {
            $val = $this->autoDetectDailyYield($data);
            if ($val !== null) return $val;
        }

        // === Strategi 3: Auto-discovery — coba endpoint HopeWind yang umum ===
        return $this->discoverHopeWindDailyYield($connection);
    }

    /**
     * Auto-detect field yang kemungkinan berisi Today Yield di response JSON.
     * Cek field-field umum yang dipakai HopeWind dan vendor lain.
     */
    private function autoDetectDailyYield(array $data): ?float
    {
        // Daftar nama field yang umum dipakai untuk Today Yield (case-insensitive)
        // Urutkan dari paling spesifik ke paling umum
        $candidates = [
            // Path lengkap dengan prefix
            'result.dayKwh', 'result.dayKWh', 'result.dayKWH',
            'result.todayKwh', 'result.todayKWh', 'result.todayYield', 'result.todayEnergy',
            'result.dailyKwh', 'result.dailyKWh', 'result.dailyEnergy', 'result.dayEnergy',
            'result.dayProduction', 'result.todayProduction', 'result.dayGen', 'result.todayGen',
            'result.eDay', 'result.energyDay', 'result.kWhDay', 'result.kwhToday',
            'data.dayKwh', 'data.dayKWh', 'data.todayKwh', 'data.todayYield',
            'data.dailyKwh', 'data.dayEnergy', 'data.eDay', 'data.kWhDay',
            // Tanpa prefix (kalau struktur flat)
            'dayKwh', 'dayKWh', 'todayKwh', 'todayYield', 'todayEnergy',
            'dailyKwh', 'dailyEnergy', 'dayEnergy', 'eDay', 'kWhDay',
        ];

        foreach ($candidates as $path) {
            $val = $this->extractValueFromPath($data, $path);
            if (is_numeric($val) && $val > 0) {
                return (float) $val;
            }
        }

        // Fallback: scan rekursif cari key yang kelihatannya kWh harian
        return $this->scanForDailyYieldKey($data);
    }

    /**
     * Scan rekursif response JSON cari key yang namanya
     * mengandung pola "day"+"kwh"/"yield"/"energy"/"production".
     */
    private function scanForDailyYieldKey($data, int $depth = 0): ?float
    {
        if ($depth > 5 || !is_array($data)) return null;

        foreach ($data as $key => $val) {
            if (is_string($key) && is_numeric($val) && $val > 0) {
                $lower = strtolower($key);
                // Match pattern: ada "day" atau "today" + "kwh"/"yield"/"energy"
                if (
                    (str_contains($lower, 'day') || str_contains($lower, 'today')) &&
                    (str_contains($lower, 'kwh') || str_contains($lower, 'yield') ||
                     str_contains($lower, 'energy') || str_contains($lower, 'production') ||
                     str_contains($lower, 'gen'))
                ) {
                    return (float) $val;
                }
            }
            if (is_array($val)) {
                $found = $this->scanForDailyYieldKey($val, $depth + 1);
                if ($found !== null) return $found;
            }
        }
        return null;
    }

    /**
     * Auto-discovery: panggil endpoint HopeWind getPowerPlantList yang
     * mengembalikan SEMUA data (Real-time Power, Today Yield, Monthly, Yearly, Total).
     *
     * Endpoint ini gak butuh token, cuma butuh sign + pageIndex + pageSize.
     * Hasilnya di-cache 1 jam supaya gak bombardir API.
     */
    private function discoverHopeWindDailyYield(ApiConnection $connection): ?float
    {
        $plant = $this->fetchHopeWindPlantData($connection);
        if (!$plant) return null;

        if (isset($plant['todayKwh']) && is_numeric($plant['todayKwh'])) {
            return (float) $plant['todayKwh'];
        }

        return null;
    }

    /**
     * Ambil data lengkap dari endpoint HopeWind getPowerPlantList.
     * Endpoint ini ngasih SEMUA data dalam 1 panggilan: nowKw, todayKwh,
     * monKwh, yearKwh, sumKwh.
     *
     * SELALU fetch fresh dari HopeWind — tanpa cache supaya angka selalu
     * sama persis dengan dashboard HopeWind real-time.
     *
     * @return array|null Data plant {nowKw, todayKwh, monKwh, yearKwh, sumKwh, ...}
     */
    private function fetchHopeWindPlantData(ApiConnection $connection): ?array
    {
        try {
            $headers  = $connection->api_header_array ?? [];
            $baseHost = $this->getApiBaseHost($connection->api_url);
            if (!$baseHost) return null;

            $url = rtrim($baseHost, '/') . '/openApi/powerPlant/getPowerPlantList';
            $params = [
                'appid'     => $connection->api_username,
                'pageIndex' => 1,
                'pageSize'  => 50,
            ];
            $params['sign'] = $this->generateHopewindSign($params, $connection->api_password);

            $response = Http::timeout(10)->withHeaders($headers)->get($url, $params);
            if (!$response->successful()) return null;

            $data = $response->json();
            if (!isset($data['success']) || $data['success'] !== true) return null;

            $records = $data['result']['records'] ?? [];
            if (empty($records)) return null;

            // Cocokin plantId dari URL utama, atau ambil yang pertama
            $targetPlantId = $this->extractPlantIdFromUrl($connection->api_url);
            foreach ($records as $plant) {
                if ($targetPlantId && (string) ($plant['id'] ?? '') === (string) $targetPlantId) {
                    return $plant;
                }
            }
            return $records[0];

        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Ambil base host dari URL API (scheme + host + port, tanpa path).
     * Contoh: "http://openapi.hopewindcloud.eu/openApi/..." → "http://openapi.hopewindcloud.eu"
     */
    private function getApiBaseHost(string $url): ?string
    {
        $parts = parse_url($url);
        if (!$parts || !isset($parts['host'])) return null;

        $scheme = $parts['scheme'] ?? 'http';
        $host   = $parts['host'];
        $port   = isset($parts['port']) ? ':' . $parts['port'] : '';

        return $scheme . '://' . $host . $port;
    }

    // ============================================
    // EXPORT EXCEL (.xls — XML Spreadsheet)
    // ✅ FIX: Timezone Asia/Jakarta (WIB)
    // ✅ FIX: Warna header biru, badge berwarna
    // ============================================

    public function exportExcel(Request $request)
    {
        // ✅ Gunakan timezone WIB
        $now = now()->setTimezone('Asia/Jakarta');

        // Parse filter periode (default: 7 hari terakhir)
        [$periodeStart, $periodeEnd, $periodeText] = $this->parsePeriodeFilter($request);

        $query = ApiSynclog::with('connection')
            ->whereBetween('created_at', [$periodeStart, $periodeEnd])
            ->orderBy('created_at', 'desc');

        if ($request->filled('connection_id')) {
            $query->where('api_connection_id', $request->connection_id);
        }

        $logs        = $query->get();
        $connections = ApiConnection::orderBy('created_at', 'desc')->get();
        $fileName    = 'api-integration-log-' . $now->format('Ymd-His') . '.xls';
        $filePath    = storage_path('app/' . $fileName);

        $tanggalCetak = $now->format('d/m/Y H:i:s') . ' WIB';

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<?mso-application progid="Excel.Sheet"?>' . "\n";
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"';
        $xml .= ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n";

        // ===== Styles =====
        $xml .= '<Styles>';
        // Header utama — biru navy bold putih
        $xml .= '<Style ss:ID="H"><Font ss:Bold="1" ss:Color="#FFFFFF" ss:Size="11"/><Interior ss:Color="#0d1b3e" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="2" ss:Color="#FFD700"/></Borders></Style>';
        // Sub-header — biru medium
        $xml .= '<Style ss:ID="SH"><Font ss:Bold="1" ss:Color="#FFFFFF" ss:Size="10"/><Interior ss:Color="#1a4f9c" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/></Style>';
        // Judul laporan
        $xml .= '<Style ss:ID="TITLE"><Font ss:Bold="1" ss:Color="#0d1b3e" ss:Size="14"/><Alignment ss:Horizontal="Left"/></Style>';
        // Sub judul
        $xml .= '<Style ss:ID="SUB"><Font ss:Italic="1" ss:Color="#64748b" ss:Size="10"/></Style>';
        // Tanggal cetak
        $xml .= '<Style ss:ID="DATE"><Font ss:Bold="1" ss:Color="#1a4f9c" ss:Size="10"/></Style>';
        // Badge SUCCESS
        $xml .= '<Style ss:ID="S"><Font ss:Bold="1" ss:Color="#065f46" ss:Size="10"/><Interior ss:Color="#d1fae5" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center"/></Style>';
        // Badge FAILED
        $xml .= '<Style ss:ID="F"><Font ss:Bold="1" ss:Color="#991b1b" ss:Size="10"/><Interior ss:Color="#fee2e2" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center"/></Style>';
        // Badge AKTIF
        $xml .= '<Style ss:ID="A"><Font ss:Bold="1" ss:Color="#1e40af" ss:Size="10"/><Interior ss:Color="#dbeafe" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center"/></Style>';
        // Badge NONAKTIF
        $xml .= '<Style ss:ID="NA"><Font ss:Color="#475569" ss:Size="10"/><Interior ss:Color="#f1f5f9" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center"/></Style>';
        // Center normal
        $xml .= '<Style ss:ID="C"><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Font ss:Size="10"/></Style>';
        // Left normal
        $xml .= '<Style ss:ID="L"><Alignment ss:Horizontal="Left" ss:Vertical="Center"/><Font ss:Size="10"/></Style>';
        // Number format
        $xml .= '<Style ss:ID="N"><NumberFormat ss:Format="0.00"/><Alignment ss:Horizontal="Right"/><Font ss:Size="10"/></Style>';
        // Row bergaris (stripe)
        $xml .= '<Style ss:ID="STR"><Interior ss:Color="#f8fafc" ss:Pattern="Solid"/><Font ss:Size="10"/></Style>';
        $xml .= '<Style ss:ID="STRN"><NumberFormat ss:Format="0.00"/><Interior ss:Color="#f8fafc" ss:Pattern="Solid"/><Alignment ss:Horizontal="Right"/><Font ss:Size="10"/></Style>';
        $xml .= '<Style ss:ID="STRC"><Interior ss:Color="#f8fafc" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center"/><Font ss:Size="10"/></Style>';
        $xml .= '</Styles>';

        // ============================================
        // SHEET 1: SYNC LOG
        // ============================================
        $xml .= '<Worksheet ss:Name="Sync Log">';
        $xml .= '<Table ss:DefaultRowHeight="18">';

        // Lebar kolom
        // Lebar kolom: No, Nama Koneksi, Status, kW, Today Yield kWh, Voltage, Current, Response Time, HTTP Code, Waktu Sync
        $cols = [35, 170, 80, 100, 120, 80, 80, 120, 80, 160];
        foreach ($cols as $w) {
            $xml .= '<Column ss:Width="' . $w . '"/>';
        }

        // Baris judul laporan
        $xml .= '<Row ss:Height="30">';
        $xml .= '<Cell ss:MergeAcross="9" ss:StyleID="TITLE"><Data ss:Type="String">⚡ EV Sahabat — Laporan API Integration (Sync Log)</Data></Cell>';
        $xml .= '</Row>';

        // Baris tanggal cetak
        $xml .= '<Row ss:Height="18">';
        $xml .= '<Cell ss:MergeAcross="9" ss:StyleID="DATE"><Data ss:Type="String">Dicetak: ' . $tanggalCetak . '</Data></Cell>';
        $xml .= '</Row>';

        // Baris sub judul
        $xml .= '<Row ss:Height="16">';
        $xml .= '<Cell ss:MergeAcross="9" ss:StyleID="SUB"><Data ss:Type="String">Periode: ' . htmlspecialchars($periodeText, ENT_XML1, 'UTF-8') . ' — Data Riwayat Sinkronisasi API Eksternal</Data></Cell>';
        $xml .= '</Row>';

        // Baris kosong
        $xml .= '<Row ss:Height="8"><Cell><Data ss:Type="String"></Data></Cell></Row>';

        // Header tabel
        // Header tabel — kolom sesuai data yang tersedia
        $xml .= '<Row ss:Height="22">';
        foreach ([
            'No', 'Nama Koneksi', 'Status',
            'kW (Real-time)', 'Today Yield (kWh)',
            'Voltage (V)', 'Current (A)',
            'Response Time (ms)', 'HTTP Code', 'Waktu Sync (WIB)'
        ] as $h) {
            $xml .= '<Cell ss:StyleID="SH"><Data ss:Type="String">' . $h . '</Data></Cell>';
        }
        $xml .= '</Row>';

        // Data rows
        $no = 1;
        foreach ($logs as $log) {
            $isStripe    = ($no % 2 === 0);
            $statusStyle = $log->status === 'success' ? 'S' : 'F';
            $cStyle      = $isStripe ? 'STRC' : 'C';
            $lStyle      = $isStripe ? 'STR'  : 'L';
            $nStyle      = $isStripe ? 'STRN' : 'N';

            $waktuWib    = $log->created_at->setTimezone('Asia/Jakarta')->format('d/m/Y H:i:s');
            $powerKw     = round((float)($log->power_value     ?? 0), 2);
            $todayYield  = round((float)($log->daily_yield_kwh ?? $log->kwh_value ?? 0), 2);

            $xml .= '<Row ss:Height="17">';
            $xml .= '<Cell ss:StyleID="' . $cStyle . '"><Data ss:Type="Number">' . $no++ . '</Data></Cell>';
            $xml .= '<Cell ss:StyleID="' . $lStyle . '"><Data ss:Type="String">' . htmlspecialchars($log->connection->nama_koneksi ?? '-') . '</Data></Cell>';
            $xml .= '<Cell ss:StyleID="' . $statusStyle . '"><Data ss:Type="String">' . strtoupper($log->status) . '</Data></Cell>';
            $xml .= '<Cell ss:StyleID="' . $nStyle . '"><Data ss:Type="Number">' . $powerKw . '</Data></Cell>';
            $xml .= '<Cell ss:StyleID="' . $nStyle . '"><Data ss:Type="Number">' . $todayYield . '</Data></Cell>';
            $xml .= '<Cell ss:StyleID="' . $nStyle . '"><Data ss:Type="Number">' . round((float)($log->voltage_value ?? 0), 2) . '</Data></Cell>';
            $xml .= '<Cell ss:StyleID="' . $nStyle . '"><Data ss:Type="Number">' . round((float)($log->current_value ?? 0), 2) . '</Data></Cell>';
            $xml .= '<Cell ss:StyleID="' . $cStyle . '"><Data ss:Type="Number">' . (int)($log->response_time ?? 0) . '</Data></Cell>';
            $xml .= '<Cell ss:StyleID="' . $cStyle . '"><Data ss:Type="Number">' . (int)($log->http_code ?? 0) . '</Data></Cell>';
            $xml .= '<Cell ss:StyleID="' . $lStyle . '"><Data ss:Type="String">' . $waktuWib . '</Data></Cell>';
            $xml .= '</Row>';
        }

        // Baris total — pakai daily_yield_kwh (Today Yield) bukan kwh_value lama
        $totalYield = $logs->where('status', 'success')
            ->sum(fn($l) => (float)($l->daily_yield_kwh ?? $l->kwh_value ?? 0));
        $maxPower   = $logs->where('status', 'success')->max('power_value') ?? 0;

        $xml .= '<Row ss:Height="20">';
        $xml .= '<Cell ss:MergeAcross="2" ss:StyleID="H"><Data ss:Type="String">RINGKASAN (Success)</Data></Cell>';
        $xml .= '<Cell ss:StyleID="H"><Data ss:Type="Number">' . round((float)$maxPower, 2) . '</Data></Cell>';
        $xml .= '<Cell ss:StyleID="H"><Data ss:Type="Number">' . round($totalYield, 2) . '</Data></Cell>';
        $xml .= '<Cell ss:MergeAcross="4" ss:StyleID="H"><Data ss:Type="String">Max kW | Total Today Yield kWh</Data></Cell>';
        $xml .= '</Row>';

        $xml .= '</Table></Worksheet>';

        // ============================================
        // SHEET 2: DAFTAR KONEKSI
        // ============================================
        $xml .= '<Worksheet ss:Name="Daftar Koneksi">';
        $xml .= '<Table ss:DefaultRowHeight="18">';

        $cols2 = [35, 180, 110, 80, 110, 160, 160];
        foreach ($cols2 as $w) {
            $xml .= '<Column ss:Width="' . $w . '"/>';
        }

        // Judul
        $xml .= '<Row ss:Height="30">';
        $xml .= '<Cell ss:MergeAcross="6" ss:StyleID="TITLE"><Data ss:Type="String">⚡ EV Sahabat — Daftar Koneksi API</Data></Cell>';
        $xml .= '</Row>';

        $xml .= '<Row ss:Height="18">';
        $xml .= '<Cell ss:MergeAcross="6" ss:StyleID="DATE"><Data ss:Type="String">Dicetak: ' . $tanggalCetak . '</Data></Cell>';
        $xml .= '</Row>';

        $xml .= '<Row ss:Height="8"><Cell><Data ss:Type="String"></Data></Cell></Row>';

        // Header
        $xml .= '<Row ss:Height="22">';
        foreach (['No', 'Nama Koneksi', 'Tipe Sistem', 'Status', 'Last Sync Status', 'Last Sync At (WIB)', 'Pesan Terakhir'] as $h) {
            $xml .= '<Cell ss:StyleID="SH"><Data ss:Type="String">' . $h . '</Data></Cell>';
        }
        $xml .= '</Row>';

        $no = 1;
        foreach ($connections as $conn) {
            $isStripe    = ($no % 2 === 0);
            $aktifStyle  = $conn->is_active ? 'A' : 'NA';
            $aktifText   = $conn->is_active ? 'AKTIF' : 'NONAKTIF';
            $lastStyle   = $conn->last_sync_status === 'success' ? 'S' : ($conn->last_sync_status ? 'F' : ($isStripe ? 'STRC' : 'C'));
            $lastText    = $conn->last_sync_status ? strtoupper($conn->last_sync_status) : '-';
            $lastAt      = $conn->last_sync_at
                ? $conn->last_sync_at->setTimezone('Asia/Jakarta')->format('d/m/Y H:i:s')
                : '-';
            $cStyle = $isStripe ? 'STRC' : 'C';
            $lStyle = $isStripe ? 'STR' : 'L';

            $xml .= '<Row ss:Height="17">';
            $xml .= '<Cell ss:StyleID="' . $cStyle . '"><Data ss:Type="Number">' . $no++ . '</Data></Cell>';
            $xml .= '<Cell ss:StyleID="' . $lStyle . '"><Data ss:Type="String">' . htmlspecialchars($conn->nama_koneksi) . '</Data></Cell>';
            $xml .= '<Cell ss:StyleID="' . $cStyle . '"><Data ss:Type="String">' . htmlspecialchars($conn->tipe_sistem) . '</Data></Cell>';
            $xml .= '<Cell ss:StyleID="' . $aktifStyle . '"><Data ss:Type="String">' . $aktifText . '</Data></Cell>';
            $xml .= '<Cell ss:StyleID="' . $lastStyle . '"><Data ss:Type="String">' . $lastText . '</Data></Cell>';
            $xml .= '<Cell ss:StyleID="' . $cStyle . '"><Data ss:Type="String">' . $lastAt . '</Data></Cell>';
            $xml .= '<Cell ss:StyleID="' . $lStyle . '"><Data ss:Type="String">' . htmlspecialchars($conn->last_sync_message ?? '-') . '</Data></Cell>';
            $xml .= '</Row>';
        }

        $xml .= '</Table></Worksheet>';
        $xml .= '</Workbook>';

        file_put_contents($filePath, $xml);

        return response()->download($filePath, $fileName, [
            'Content-Type'        => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ])->deleteFileAfterSend(true);
    }

    // ============================================
    // EXPORT PDF
    // ✅ FIX: Timezone WIB
    // ✅ FIX: Tombol Print di atas dekat header
    // ============================================

    public function exportPdf(Request $request)
    {
        // ✅ Gunakan timezone WIB
        $now = now()->setTimezone('Asia/Jakarta');

        // Parse filter periode (default: 7 hari terakhir)
        [$periodeStart, $periodeEnd, $periodeText] = $this->parsePeriodeFilter($request);

        $query = ApiSynclog::with('connection')
            ->whereBetween('created_at', [$periodeStart, $periodeEnd])
            ->orderBy('created_at', 'desc');

        if ($request->filled('connection_id')) {
            $query->where('api_connection_id', $request->connection_id);
        }

        $logs         = $query->get();
        $connections  = ApiConnection::orderBy('created_at', 'desc')->get();
        $totalSync    = $logs->count();
        $syncBerhasil = $logs->where('status', 'success')->count();
        $syncGagal    = $logs->where('status', 'failed')->count();

        // === Ambil data live dari HopeWind (sama persis dengan dashboard utama) ===
        $realtimePower = 0;
        $todayYield    = 0;
        $activeConn    = ApiConnection::where('is_active', true)->first();
        if ($activeConn) {
            $plantData = $this->fetchHopeWindPlantData($activeConn);
            if ($plantData) {
                $realtimePower = isset($plantData['nowKw']) && is_numeric($plantData['nowKw'])
                    ? (float) $plantData['nowKw'] : 0;
                $todayYield    = isset($plantData['todayKwh']) && is_numeric($plantData['todayKwh'])
                    ? (float) $plantData['todayKwh'] : 0;
            }
        }
        // Fallback: kalau live fetch gagal, ambil dari log terakhir
        if ($realtimePower == 0) {
            $latestLog = ApiSynclog::where('status', 'success')
                ->whereNotNull('power_value')
                ->orderBy('created_at', 'desc')
                ->first();
            if ($latestLog) $realtimePower = (float) $latestLog->power_value;
        }
        if ($todayYield == 0) {
            $todayYield = $this->getTodayYield();
        }

        $totalDataSync = ApiSynclog::where('status', 'success')
            ->whereNotNull('power_value')
            ->count();

        // ✅ Format waktu WIB yang konsisten
        $tanggal     = $now->translatedFormat('d F Y');
        $jam         = $now->format('H:i') . ' WIB';
        $tanggalJam  = $now->format('d/m/Y H:i:s') . ' WIB';

        $html = '<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan API Integration - EV Sahabat</title>
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: Arial, sans-serif; font-size: 11px; color: #1e293b; background: #f1f5f9; }

  /* ===== TOOLBAR PRINT (tampil di browser, hilang saat print) ===== */
  .print-toolbar {
    background: #0d1b3e;
    padding: 10px 30px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: sticky;
    top: 0;
    z-index: 100;
    box-shadow: 0 2px 8px rgba(0,0,0,0.3);
  }
  .print-toolbar .toolbar-info {
    color: #94a3b8;
    font-size: 11px;
  }
  .print-toolbar .toolbar-info strong {
    color: #FFD700;
  }
  .btn-print {
    background: linear-gradient(135deg, #FFD700, #FFA500);
    color: #0d1b3e;
    border: none;
    padding: 8px 24px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: bold;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: opacity 0.2s;
  }
  .btn-print:hover { opacity: 0.9; }

  /* ===== KONTEN LAPORAN ===== */
  .report-wrap {
    max-width: 1100px;
    margin: 20px auto;
    background: #fff;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
  }

  .header {
    background: linear-gradient(135deg, #0d1b3e 0%, #1a4f9c 100%);
    color: white;
    padding: 24px 30px 20px;
    border-bottom: 3px solid #FFD700;
  }
  .header-top { display: flex; justify-content: space-between; align-items: flex-start; }
  .header h1 { font-size: 20px; font-weight: bold; letter-spacing: 0.3px; }
  .header h1 span { color: #FFD700; }
  .header .subtitle { font-size: 11px; opacity: 0.8; margin-top: 4px; }
  .header .meta-right { text-align: right; font-size: 10px; opacity: 0.85; line-height: 1.6; }
  .header .meta-right .tgl { font-size: 12px; font-weight: bold; color: #FFD700; }

  .summary-bar {
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    padding: 10px 30px;
    display: flex;
    gap: 24px;
    font-size: 10px;
    color: #64748b;
  }
  .summary-bar strong { color: #1e293b; }

  .stat-cards {
    display: flex;
    gap: 0;
    border-bottom: 2px solid #e2e8f0;
  }
  .stat-card {
    flex: 1;
    padding: 16px 12px;
    text-align: center;
    border-right: 1px solid #e2e8f0;
  }
  .stat-card:last-child { border-right: none; }
  .stat-card .val { font-size: 26px; font-weight: bold; color: #1a4f9c; line-height: 1; }
  .stat-card .val.red { color: #ef4444; }
  .stat-card .val.gold { color: #f59e0b; }
  .stat-card .val.green { color: #10b981; }
  .stat-card .val.blue { color: #3b82f6; }
  .stat-card .val .unit { font-size: 12px; color: #94a3b8; font-weight: 500; margin-left: 4px; }
  .stat-card .lbl { font-size: 9px; color: #94a3b8; margin-top: 4px; text-transform: uppercase; letter-spacing: 0.5px; }

  .section { padding: 20px 30px; }
  .section + .section { border-top: 1px solid #f1f5f9; padding-top: 16px; }
  .section-title {
    font-size: 12px;
    font-weight: bold;
    color: #fff;
    background: linear-gradient(90deg, #1a4f9c, #0e9cdd);
    padding: 6px 14px;
    border-radius: 4px;
    margin-bottom: 12px;
    display: inline-block;
  }

  table { width: 100%; border-collapse: collapse; font-size: 10px; }
  th {
    background: #1a4f9c;
    color: white;
    padding: 8px 10px;
    text-align: center;
    font-weight: bold;
    font-size: 10px;
    border: 1px solid #1557a0;
  }
  th:first-child { border-radius: 0; }
  td {
    padding: 7px 10px;
    border-bottom: 1px solid #f1f5f9;
    border-right: 1px solid #f1f5f9;
    vertical-align: middle;
  }
  tr:nth-child(even) td { background: #f8fafc; }
  tr:hover td { background: #eff6ff; }
  td.center { text-align: center; }
  td.right { text-align: right; }
  td.bold { font-weight: bold; }

  .badge {
    display: inline-block;
    padding: 2px 10px;
    border-radius: 20px;
    font-size: 9px;
    font-weight: bold;
    letter-spacing: 0.5px;
  }
  .badge-success  { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
  .badge-failed   { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
  .badge-active   { background: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; }
  .badge-inactive { background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; }

  .footer {
    background: #f8fafc;
    border-top: 2px solid #e2e8f0;
    padding: 12px 30px;
    text-align: center;
    font-size: 9px;
    color: #94a3b8;
  }
  .footer strong { color: #1a4f9c; }

  @media print {
    body { background: #fff; }
    .print-toolbar { display: none !important; }
    .report-wrap { margin: 0; box-shadow: none; border-radius: 0; }
    tr:hover td { background: inherit; }
    @page { margin: 10mm 8mm; size: A4 landscape; }
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }
</style>
</head>
<body>

<!-- ===== TOOLBAR PRINT (hilang saat print) ===== -->
<div class="print-toolbar no-print">
  <div class="toolbar-info">
    <strong>⚡ EV Sahabat</strong> &nbsp;—&nbsp; Laporan API Integration &nbsp;|&nbsp;
    Dicetak: <strong>' . $tanggalJam . '</strong>
  </div>
  <button class="btn-print" onclick="window.print()">
    🖨️ &nbsp;Print / Save as PDF
  </button>
</div>

<!-- ===== KONTEN LAPORAN ===== -->
<div class="report-wrap">

  <!-- Header -->
  <div class="header">
    <div class="header-top">
      <div>
        <h1>⚡ <span>EV Sahabat</span> — API Integration</h1>
        <div class="subtitle">Laporan Sinkronisasi Data Energi dari API Eksternal (Solar Panel, Energy Meter, dll)</div>
      </div>
      <div class="meta-right">
        <div class="tgl">' . $tanggal . '</div>
        <div>' . $jam . '</div>
        <div>EV Smart Energy Control Center</div>
      </div>
    </div>
  </div>

  <!-- Summary bar -->
  <div class="summary-bar">
    <span>Periode: <strong>' . $periodeText . '</strong></span>
    <span>Total Sync: <strong>' . $totalSync . '</strong></span>
    <span>Berhasil: <strong style="color:#10b981">' . $syncBerhasil . '</strong></span>
    <span>Gagal: <strong style="color:#ef4444">' . $syncGagal . '</strong></span>
    <span>Real-time Power: <strong style="color:#3b82f6">' . number_format($realtimePower, 2) . ' kW</strong></span>
    <span>Today Yield: <strong style="color:#f59e0b">' . number_format($todayYield, 2) . ' kWh</strong></span>
  </div>

  <!-- Stat Cards (sama persis dengan dashboard utama) -->
  <div class="stat-cards">
    <div class="stat-card">
      <div class="val">' . $connections->count() . '</div>
      <div class="lbl">Total Koneksi</div>
    </div>
    <div class="stat-card">
      <div class="val green">' . $connections->where('is_active', true)->count() . '</div>
      <div class="lbl">Koneksi Aktif</div>
    </div>
    <div class="stat-card">
      <div class="val blue">' . number_format($realtimePower, 2) . '<span class="unit">kW</span></div>
      <div class="lbl">Real-time Power</div>
    </div>
    <div class="stat-card">
      <div class="val gold">' . number_format($todayYield, 2) . '<span class="unit">kWh</span></div>
      <div class="lbl">Today Yield</div>
    </div>
    <div class="stat-card">
      <div class="val">' . $totalDataSync . '</div>
      <div class="lbl">Total Data Sync</div>
    </div>
  </div>

  <!-- Tabel Daftar Koneksi -->
  <div class="section">
    <div class="section-title">📡 Daftar Koneksi API</div>
    <table>
      <thead>
        <tr>
          <th style="width:35px">No</th>
          <th style="text-align:left">Nama Koneksi</th>
          <th>Tipe</th>
          <th>Status</th>
          <th>Last Sync</th>
          <th>Last Status</th>
          <th style="text-align:left">Pesan Terakhir</th>
        </tr>
      </thead>
      <tbody>';

        $no = 1;
        foreach ($connections as $conn) {
            $aktif      = $conn->is_active
                ? '<span class="badge badge-active">AKTIF</span>'
                : '<span class="badge badge-inactive">NONAKTIF</span>';
            $lastStatus = $conn->last_sync_status === 'success'
                ? '<span class="badge badge-success">SUCCESS</span>'
                : ($conn->last_sync_status
                    ? '<span class="badge badge-failed">FAILED</span>'
                    : '<span style="color:#94a3b8">-</span>');
            $lastAt = $conn->last_sync_at
                ? $conn->last_sync_at->setTimezone('Asia/Jakarta')->format('d/m/Y H:i') . ' WIB'
                : '-';

            $html .= '<tr>
              <td class="center">' . $no++ . '</td>
              <td class="bold">' . htmlspecialchars($conn->nama_koneksi) . '</td>
              <td class="center">' . htmlspecialchars($conn->tipe_sistem) . '</td>
              <td class="center">' . $aktif . '</td>
              <td class="center">' . $lastAt . '</td>
              <td class="center">' . $lastStatus . '</td>
              <td>' . htmlspecialchars($conn->last_sync_message ?? '-') . '</td>
            </tr>';
        }

        $html .= '</tbody></table></div>';

        // Tabel Sync Log
        $html .= '<div class="section">
    <div class="section-title">📋 Riwayat Sync Log</div>
    <table>
      <thead>
        <tr>
          <th style="width:30px">No</th>
          <th style="text-align:left">Nama Koneksi</th>
          <th>Status</th>
          <th>kWh</th>
          <th>Voltage</th>
          <th>Current</th>
          <th>Power</th>
          <th>Resp. Time</th>
          <th>Waktu Sync (WIB)</th>
        </tr>
      </thead>
      <tbody>';

        $no = 1;
        foreach ($logs as $log) {
            $badge = $log->status === 'success'
                ? '<span class="badge badge-success">SUCCESS</span>'
                : '<span class="badge badge-failed">FAILED</span>';

            $waktuWib = $log->created_at->setTimezone('Asia/Jakarta')->format('d/m/Y H:i:s');

            $html .= '<tr>
              <td class="center">' . $no++ . '</td>
              <td class="bold">' . htmlspecialchars($log->connection->nama_koneksi ?? '-') . '</td>
              <td class="center">' . $badge . '</td>
              <td class="right">' . number_format((float)($log->kwh_value ?? 0), 2) . '</td>
              <td class="right">' . number_format((float)($log->voltage_value ?? 0), 2) . '</td>
              <td class="right">' . number_format((float)($log->current_value ?? 0), 2) . '</td>
              <td class="right">' . number_format((float)($log->power_value ?? 0), 2) . '</td>
              <td class="center">' . number_format((int)($log->response_time ?? 0)) . ' ms</td>
              <td class="center">' . $waktuWib . '</td>
            </tr>';
        }

        $html .= '</tbody></table></div>';

        $html .= '
  <!-- Footer -->
  <div class="footer">
    Dokumen ini digenerate otomatis oleh <strong>EV Sahabat</strong> — EV Smart Energy Control Center
    &nbsp;|&nbsp; <strong>' . $tanggalJam . '</strong>
  </div>

</div><!-- end report-wrap -->
</body>
</html>';

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }

    // ============================================
    // LOG MANAGEMENT
    // ============================================

    public function destroyLog($id)
    {
        $log = ApiSynclog::findOrFail($id);
        $log->delete();

        return response()->json([
            'success' => true,
            'message' => 'Log berhasil dihapus',
        ]);
    }

    public function destroyAllLogs()
    {
        $count = ApiSynclog::count();
        ApiSynclog::truncate();

        AuditLog::record(
            'Hapus Semua Log Sync API',
            'ApiIntegration',
            "Menghapus {$count} log sync API (truncate)",
            'warning'
        );

        return response()->json([
            'success' => true,
            'message' => 'Semua log berhasil dihapus',
        ]);
    }

    // ============================================
    // HELPER — Generate RSA Signature HopeWind
    // ============================================

    private function generateHopewindSign(array $params, string $privateKey): string
    {
        $filtered = [];
        foreach ($params as $key => $value) {
            if ($key === 'sign' || $value === null || $value === '') {
                continue;
            }
            $filtered[$key] = (string) $value;
        }

        ksort($filtered);

        $parts = [];
        foreach ($filtered as $key => $value) {
            $parts[] = $key . '=' . $value;
        }
        $signStr = implode('&', $parts);

        $privateKeyFormatted = $this->formatPrivateKey($privateKey);
        $pkeyResource        = openssl_pkey_get_private($privateKeyFormatted);

        if ($pkeyResource === false) {
            throw new \Exception('Private Key tidak valid. OpenSSL error: ' . openssl_error_string());
        }

        $signature = '';
        $result    = openssl_sign($signStr, $signature, $pkeyResource, OPENSSL_ALGO_SHA1);

        if (! $result) {
            throw new \Exception('Gagal generate signature: ' . openssl_error_string());
        }

        return base64_encode($signature);
    }

    // ============================================
    // HELPER — Format RSA Private Key ke PEM
    // ============================================

    private function formatPrivateKey(string $rawKey): string
    {
        $key = trim($rawKey);

        if (str_contains($key, '-----BEGIN')) {
            return $key;
        }

        return "-----BEGIN PRIVATE KEY-----\n"
            . chunk_split($key, 64, "\n")
            . "-----END PRIVATE KEY-----";
    }

    // ============================================
    // HELPER — Ekstrak plantId dari URL
    // ============================================

    private function extractPlantIdFromUrl(string $url): ?string
    {
        $parsed = parse_url($url, PHP_URL_QUERY);
        if (! $parsed) {
            return null;
        }

        parse_str($parsed, $queryParams);

        return $queryParams['plantId'] ?? null;
    }

    // ============================================
    // HELPER — Ambil base URL tanpa query string
    // ============================================

    private function getBaseUrl(string $url): string
    {
        $parsed = parse_url($url);

        return ($parsed['scheme'] ?? 'http') . '://'
            . ($parsed['host'] ?? '')
            . ($parsed['path'] ?? '');
    }

    // ============================================
    // HELPER — Extract value dari dot notation path
    // ============================================

    private function extractValueFromPath($data, $path)
    {
        return Arr::get($data, $path);
    }
}