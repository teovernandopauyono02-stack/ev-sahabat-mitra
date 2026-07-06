<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AnalyticsService;
use App\Models\AuditLog;

class AnalyticsController extends Controller
{
    public function __construct(private AnalyticsService $analytics) {}

    public function index(Request $request)
    {
        // Validasi & ambil bulan dari query (format YYYY-MM); default = bulan ini
        $monthParam = $request->get('month');
        if ($monthParam && !preg_match('/^\d{4}-\d{2}$/', $monthParam)) {
            $monthParam = null;
        }

        $stats        = $this->analytics->basicStats();
        $anomalies    = $this->analytics->detectAnomalies(20);
        $anomalies['total_count'] = $anomalies['total'] ?? 0;
        $heatmap      = $this->analytics->consumptionHeatmap($monthParam);
        $comparison   = $this->analytics->periodComparison();
        $topStations  = $this->analytics->topStations(10, 30);
        $forecast     = $this->analytics->forecast(7);
        $hourlyDist   = $this->analytics->hourlyDistribution(30);

        // Tidak perlu audit log untuk akses halaman — hanya aksi ubah data yang dicatat

        return view('analytics', compact(
            'stats', 'anomalies', 'heatmap', 'comparison',
            'topStations', 'forecast', 'hourlyDist'
        ));
    }
}
