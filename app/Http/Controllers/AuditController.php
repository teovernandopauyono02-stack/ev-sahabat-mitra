<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AuditLog;
use Carbon\Carbon;

/**
 * AuditController — Pusat audit trail dengan filter, search, dashboard analitik, dan export.
 * Untuk mendukung Makul Audit TI tingkat Gold.
 */
class AuditController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::query();

        // Filter
        if ($request->filled('module'))   $query->where('module', $request->module);
        if ($request->filled('severity')) $query->where('severity', $request->severity);
        if ($request->filled('user_id'))  $query->where('user_id', $request->user_id);
        if ($request->filled('date_from'))$query->where('created_at', '>=', Carbon::parse($request->date_from)->startOfDay());
        if ($request->filled('date_to'))  $query->where('created_at', '<=', Carbon::parse($request->date_to)->endOfDay());

        // Search
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sub) use ($q) {
                $sub->where('action', 'LIKE', "%{$q}%")
                    ->orWhere('description', 'LIKE', "%{$q}%")
                    ->orWhere('user_name', 'LIKE', "%{$q}%")
                    ->orWhere('ip_address', 'LIKE', "%{$q}%");
            });
        }

        $logs = $query->latest()->paginate(20)->appends($request->all());

        // Stats untuk dashboard kecil
        $totalAll      = AuditLog::count();
        $totalCritical = AuditLog::where('severity', 'critical')->count();
        $totalWarning  = AuditLog::where('severity', 'warning')->count();
        $totalToday    = AuditLog::whereDate('created_at', today())->count();

        // Top 5 modul aktif
        $topModules = AuditLog::selectRaw('module, COUNT(*) as total')
            ->whereNotNull('module')
            ->groupBy('module')
            ->orderByDesc('total')
            ->take(5)
            ->get();

        // Top 5 action
        $topActions = AuditLog::selectRaw('action, COUNT(*) as total')
            ->groupBy('action')
            ->orderByDesc('total')
            ->take(5)
            ->get();

        // Trend 7 hari
        $trend7Days = AuditLog::selectRaw('DATE(created_at) as tgl, COUNT(*) as total')
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->groupBy('tgl')
            ->orderBy('tgl')
            ->get();

        // Daftar modul untuk dropdown filter
        $availableModules = AuditLog::distinct()->whereNotNull('module')->pluck('module')->sort()->values();

        return view('audit', compact(
            'logs', 'totalAll', 'totalCritical', 'totalWarning', 'totalToday',
            'topModules', 'topActions', 'trend7Days', 'availableModules'
        ));
    }

    /** Detail satu audit log (untuk modal old/new data diff) */
    public function show($id)
    {
        $log = AuditLog::findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => [
                'id'          => $log->id,
                'action'      => $log->action,
                'module'      => $log->module,
                'description' => $log->description,
                'user_name'   => $log->user_name,
                'user_role'   => $log->user_role,
                'severity'    => $log->severity,
                'ip_address'  => $log->ip_address,
                'user_agent'  => $log->user_agent,
                'old_data'    => $log->old_data,
                'new_data'    => $log->new_data,
                'created_at'  => $log->created_at->setTimezone('Asia/Jakarta')->format('d/m/Y H:i:s') . ' WIB',
            ],
        ]);
    }

    /** Export audit log ke PDF (HTML printable) */
    public function exportPdf(Request $request)
    {
        $query = AuditLog::query();
        if ($request->filled('module'))   $query->where('module', $request->module);
        if ($request->filled('severity')) $query->where('severity', $request->severity);
        if ($request->filled('date_from'))$query->where('created_at', '>=', Carbon::parse($request->date_from)->startOfDay());
        if ($request->filled('date_to'))  $query->where('created_at', '<=', Carbon::parse($request->date_to)->endOfDay());
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sub) use ($q) {
                $sub->where('action', 'LIKE', "%{$q}%")
                    ->orWhere('description', 'LIKE', "%{$q}%");
            });
        }
        $logs = $query->latest()->limit(500)->get();

        AuditLog::record(
            'Export Audit Trail PDF',
            'Audit',
            "Export audit log ({$logs->count()} entry) dengan filter aktif",
            'info'
        );

        $now = now()->setTimezone('Asia/Jakarta');
        $rows = '';
        foreach ($logs as $i => $log) {
            $stripe = $i % 2 !== 0 ? 'background:#f8fafc;' : '';
            $sevColor = match($log->severity) {
                'critical' => '#dc2626',
                'warning'  => '#d97706',
                default    => '#16a34a',
            };
            $sevBg = match($log->severity) {
                'critical' => '#fee2e2',
                'warning'  => '#fef3c7',
                default    => '#dcfce7',
            };
            $rows .= '<tr style="' . $stripe . '">
                <td style="text-align:center;color:#94a3b8">' . ($i + 1) . '</td>
                <td style="font-size:9px">' . $log->created_at->setTimezone('Asia/Jakarta')->format('d/m/Y H:i:s') . '</td>
                <td style="font-weight:700;color:#1d4ed8">' . htmlspecialchars($log->user_name ?? '-') . '</td>
                <td><span style="background:#e0f2fe;color:#075985;padding:2px 8px;border-radius:8px;font-size:9px;font-weight:600">' . htmlspecialchars($log->module ?? '-') . '</span></td>
                <td style="font-weight:700;color:#0f172a">' . htmlspecialchars($log->action) . '</td>
                <td style="color:#475569;font-size:9px">' . htmlspecialchars($log->description ?? '-') . '</td>
                <td style="color:#64748b;font-family:monospace;font-size:9px">' . htmlspecialchars($log->ip_address ?? '-') . '</td>
                <td style="text-align:center"><span style="background:' . $sevBg . ';color:' . $sevColor . ';padding:3px 8px;border-radius:8px;font-size:9px;font-weight:700;text-transform:uppercase">' . $log->severity . '</span></td>
            </tr>';
        }
        if (empty($rows)) {
            $rows = '<tr><td colspan="7" style="text-align:center;padding:30px;color:#94a3b8">Tidak ada audit log dengan filter ini</td></tr>';
        }

        $tanggalCetak = $now->format('d/m/Y H:i:s') . ' WIB';
        $totalCrit = $logs->where('severity', 'critical')->count();
        $totalWarn = $logs->where('severity', 'warning')->count();
        $totalInfo = $logs->where('severity', 'info')->count();

        $html = '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8">
<title>Audit Trail — EV Smart Energy</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:Arial,sans-serif; font-size:10px; color:#1e293b; background:#f1f5f9; }
.toolbar { background:#0d1b3e; padding:10px 30px; display:flex; justify-content:space-between; align-items:center; position:sticky; top:0; z-index:10; box-shadow:0 2px 8px rgba(0,0,0,0.3); }
.toolbar .info { color:#94a3b8; font-size:11px; }
.toolbar .info strong { color:#FFD700; }
.btn { background:linear-gradient(135deg,#FFD700,#FFA500); color:#0d1b3e; border:none; padding:8px 24px; border-radius:6px; font-weight:bold; cursor:pointer; font-size:13px; }
.btn-back { background:transparent; border:1px solid #334155; color:#94a3b8; padding:8px 16px; border-radius:6px; cursor:pointer; margin-left:8px; }
.wrap { max-width:1280px; margin:20px auto; background:#fff; border-radius:10px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,0.1); }
.hdr { background:linear-gradient(135deg,#0d1b3e,#1a4f9c); color:#fff; padding:24px 30px; border-bottom:3px solid #FFD700; display:flex; justify-content:space-between; align-items:flex-start; }
.hdr h1 { font-size:20px; font-weight:bold; }
.hdr h1 span { color:#FFD700; }
.hdr .sub { font-size:11px; opacity:0.85; margin-top:4px; }
.hdr .meta { text-align:right; font-size:10px; line-height:1.7; }
.hdr .meta .tgl { color:#FFD700; font-weight:bold; font-size:13px; }
.cards { display:flex; border-bottom:2px solid #e2e8f0; }
.card { flex:1; padding:16px 12px; text-align:center; border-right:1px solid #e2e8f0; }
.card:last-child { border-right:none; }
.card .v { font-size:26px; font-weight:bold; line-height:1; }
.card .l { font-size:9px; color:#94a3b8; margin-top:4px; text-transform:uppercase; letter-spacing:0.5px; }
.sec { padding:18px 30px 24px; }
table { width:100%; border-collapse:collapse; font-size:9px; }
th { background:#0f172a; color:#fff; padding:8px 6px; font-size:9px; font-weight:bold; text-align:left; border:1px solid #1e3a6e; }
td { padding:7px 6px; border-bottom:1px solid #f1f5f9; vertical-align:top; }
.ftr { background:#f8fafc; padding:12px 30px; text-align:center; font-size:9px; color:#94a3b8; border-top:2px solid #e2e8f0; }
@media print { .toolbar { display:none !important } body { background:#fff } .wrap { margin:0; box-shadow:none; border-radius:0 } @page { size:A4 landscape; margin:8mm } -webkit-print-color-adjust:exact; print-color-adjust:exact; }
</style></head><body>
<div class="toolbar">
  <div class="info"><strong>🔍 Audit Trail</strong> &nbsp;—&nbsp; EV Smart Energy &nbsp;|&nbsp; Dicetak: <strong>' . $tanggalCetak . '</strong></div>
  <div>
    <button class="btn" onclick="window.print()">🖨️ Print / Simpan PDF</button>
    <button class="btn-back" onclick="window.history.back()">✕ Tutup</button>
  </div>
</div>
<div class="wrap">
  <div class="hdr">
    <div>
      <h1>🔍 <span>Audit Trail Sistem</span></h1>
      <div class="sub">PT. Sahabat Mitra Intrabuana — Catatan Aktivitas Sistem</div>
    </div>
    <div class="meta">
      <div class="tgl">' . $now->translatedFormat('d F Y') . '</div>
      <div>' . $now->format('H:i') . ' WIB</div>
      <div>EV Smart Energy Control Center</div>
    </div>
  </div>
  <div class="cards">
    <div class="card" style="border-top:3px solid #06b6d4"><div class="v" style="color:#0284c7">' . $logs->count() . '</div><div class="l">Total Entry</div></div>
    <div class="card" style="border-top:3px solid #dc2626"><div class="v" style="color:#dc2626">' . $totalCrit . '</div><div class="l">Critical</div></div>
    <div class="card" style="border-top:3px solid #d97706"><div class="v" style="color:#d97706">' . $totalWarn . '</div><div class="l">Warning</div></div>
    <div class="card" style="border-top:3px solid #16a34a"><div class="v" style="color:#16a34a">' . $totalInfo . '</div><div class="l">Info</div></div>
  </div>
  <div class="sec">
    <table>
      <thead>
        <tr>
          <th style="width:30px">No</th>
          <th style="width:115px">Waktu</th>
          <th style="width:110px">User</th>
          <th style="width:90px">Modul</th>
          <th style="width:140px">Aksi</th>
          <th>Deskripsi</th>
          <th style="width:100px">IP Address</th>
          <th style="width:65px">Severity</th>
        </tr>
      </thead>
      <tbody>' . $rows . '</tbody>
    </table>
  </div>
  <div class="ftr">
    Audit Trail Sistem · ' . $logs->count() . ' entry · ' . $tanggalCetak . ' · EV Smart Energy Control Center
  </div>
</div>
<script>window.addEventListener("load",function(){setTimeout(()=>window.print(),600);});</script>
</body></html>';

        return response($html, 200)->header('Content-Type', 'text/html; charset=UTF-8');
    }
}
