<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StasiunPengisian;
use App\Models\RiwayatPengisian;
use App\Models\LoginAttempt;
use App\Models\AccountLock;
use App\Models\AuditLog;
use Carbon\Carbon;

class AlertController extends Controller
{
    public function index()
    {
        $alert = collect();
        $resolved = session('resolved_alerts', []);

        // ===== ALERT 1: Stasiun Maintenance =====
        $maintenance = StasiunPengisian::where('status', 'Maintenance')->get();
        foreach ($maintenance as $st) {
            $key = 'maintenance_' . $st->id;
            if (in_array($key, $resolved)) continue;
            $alert->push([
                'key'        => $key,
                'type'       => 'danger',
                'icon'       => 'fas fa-tools',
                'title'      => 'Stasiun dalam Maintenance',
                'message'    => "{$st->nama_stasiun} ({$st->lokasi}) sedang dalam perbaikan dan tidak dapat melayani pengisian.",
                'time'       => 'Sekarang',
                'station'    => $st->nama_stasiun,
                'station_id' => $st->id,
                'link'       => route('station.index'),
            ]);
        }

        // ===== ALERT 2: Stasiun Inactive =====
        $inactive = StasiunPengisian::where('status', 'Inactive')->get();
        foreach ($inactive as $st) {
            $key = 'inactive_' . $st->id;
            if (in_array($key, $resolved)) continue;
            $alert->push([
                'key'        => $key,
                'type'       => 'warning',
                'icon'       => 'fas fa-pause-circle',
                'title'      => 'Stasiun Tidak Aktif',
                'message'    => "{$st->nama_stasiun} ({$st->lokasi}) saat ini tidak aktif dan perlu ditindaklanjuti.",
                'time'       => 'Sekarang',
                'station'    => $st->nama_stasiun,
                'station_id' => $st->id,
                'link'       => route('station.index'),
            ]);
        }

        // ===== ALERT 3: Energi Tinggi =====
        $thresholdEnergi = (float) env('THRESHOLD_ENERGI', 100);
        $highEnergy = RiwayatPengisian::with('stasiun')
            ->where('energi_kwh', '>', $thresholdEnergi)
            ->latest('waktu_mulai')
            ->take(10)
            ->get();
        foreach ($highEnergy as $log) {
            $key = 'highenergy_' . $log->id;
            if (in_array($key, $resolved)) continue;
            $alert->push([
                'key'        => $key,
                'type'       => 'info',
                'icon'       => 'fas fa-bolt',
                'title'      => 'Konsumsi Energi Tinggi',
                'message'    => "{$log->stasiun->nama_stasiun} mengonsumsi " . number_format($log->energi_kwh, 1, ',', '.') . " kWh — melebihi batas " . number_format($thresholdEnergi, 0, ',', '.') . " kWh.",
                'time'       => $log->waktu_mulai ? Carbon::parse($log->waktu_mulai)->diffForHumans() : '-',
                'station'    => $log->stasiun->nama_stasiun ?? '-',
                'station_id' => $log->stasiun_pengisian_id,
                'link'       => route('energy-log.index', ['highlight' => 'high-energy']),
            ]);
        }

        // ===== ALERT 4: Tidak Ada Aktivitas 7 Hari =====
        $semuaStasiun = StasiunPengisian::all();
        foreach ($semuaStasiun as $st) {
            $key = 'inactive_activity_' . $st->id;
            if (in_array($key, $resolved)) continue;

            $lastLog = RiwayatPengisian::where('stasiun_pengisian_id', $st->id)
                ->latest('waktu_mulai')
                ->first();
            if (!$lastLog || Carbon::parse($lastLog->waktu_mulai)->diffInDays(now()) >= 7) {
                $alert->push([
                    'key'        => $key,
                    'type'       => 'purple',
                    'icon'       => 'fas fa-clock',
                    'title'      => 'Tidak Ada Aktivitas',
                    'message'    => "{$st->nama_stasiun} ({$st->lokasi}) tidak ada aktivitas pengisian dalam 7 hari terakhir.",
                    'time'       => $lastLog ? Carbon::parse($lastLog->waktu_mulai)->diffForHumans() : 'Belum pernah digunakan',
                    'station'    => $st->nama_stasiun,
                    'station_id' => $st->id,
                    'link'       => route('energy-log.index'),
                ]);
            }
        }

        // ===== ALERT 5: Aktivitas Login Mencurigakan =====
        // IP yang punya >= 3 percobaan gagal dalam 24 jam terakhir
        $suspiciousIps = LoginAttempt::where('status', 'failed')
            ->where('created_at', '>=', now()->subHours(24))
            ->selectRaw('ip_address, COUNT(*) as total_gagal, MAX(created_at) as last_attempt')
            ->groupBy('ip_address')
            ->having('total_gagal', '>=', 3)
            ->orderByDesc('total_gagal')
            ->get();

        foreach ($suspiciousIps as $sus) {
            $key = 'security_ip_' . md5($sus->ip_address);
            if (in_array($key, $resolved)) continue;
            $alert->push([
                'key'        => $key,
                'type'       => 'security',
                'icon'       => 'fas fa-user-shield',
                'title'      => 'Aktivitas Login Mencurigakan',
                'message'    => "IP {$sus->ip_address} mencoba login {$sus->total_gagal}x dengan kredensial salah dalam 24 jam terakhir.",
                'time'       => Carbon::parse($sus->last_attempt)->diffForHumans(),
                'station'    => $sus->ip_address,
                'station_id' => null,
                'link'       => route('security.index'),
            ]);
        }

        // Akun yang sering dilock (lock_count >= 2)
        $persistentLocks = AccountLock::where('lock_count', '>=', 2)->get();
        foreach ($persistentLocks as $lock) {
            $key = 'security_lock_' . $lock->id;
            if (in_array($key, $resolved)) continue;
            $alert->push([
                'key'        => $key,
                'type'       => 'security',
                'icon'       => 'fas fa-lock',
                'title'      => 'Akun Sering Dikunci',
                'message'    => "Akun {$lock->email} sudah dikunci {$lock->lock_count}x. Kemungkinan ada percobaan akses ilegal.",
                'time'       => $lock->updated_at ? $lock->updated_at->diffForHumans() : '-',
                'station'    => $lock->email,
                'station_id' => null,
                'link'       => route('security.index'),
            ]);
        }

        $totalAlert    = $alert->count();
        $totalDanger   = $alert->where('type', 'danger')->count();
        $totalWarning  = $alert->where('type', 'warning')->count();
        $totalInfo     = $alert->where('type', 'info')->count();
        $totalPurple   = $alert->where('type', 'purple')->count();
        $totalSecurity = $alert->where('type', 'security')->count();
        $totalResolved = count($resolved);

        return view('alert', compact(
            'alert', 'totalAlert', 'totalDanger', 'totalWarning', 'totalInfo',
            'totalPurple', 'totalSecurity', 'totalResolved'
        ));
    }

    /**
     * Tandai alert sebagai selesai (resolved). Disimpan di session.
     */
    public function resolve(Request $request)
    {
        $key = $request->input('key');
        if (!$key) {
            return response()->json(['success' => false, 'message' => 'Kunci alert tidak valid'], 400);
        }

        $resolved = session('resolved_alerts', []);
        if (!in_array($key, $resolved)) {
            $resolved[] = $key;
            session(['resolved_alerts' => $resolved]);

            AuditLog::record(
                'Alert Diselesaikan',
                'Alert',
                "Alert dengan kunci '{$key}' ditandai sudah ditangani",
                'info'
            );
        }

        return response()->json(['success' => true, 'message' => 'Alert ditandai selesai']);
    }

    /**
     * Reset semua alert yang udah di-resolve (kembali tampil semua).
     */
    public function resetResolved()
    {
        $count = count(session('resolved_alerts', []));
        session()->forget('resolved_alerts');

        AuditLog::record(
            'Reset Alert',
            'Alert',
            "Memulihkan {$count} alert yang sebelumnya sudah diselesaikan",
            'info'
        );

        return back()->with('success', 'Semua alert dipulihkan.');
    }

    /**
     * Export semua alert aktif ke PDF (HTML printable).
     * Menampilkan semua 4 jenis alert dengan data lengkap.
     */
    public function exportPdf()
    {
        $now      = now()->setTimezone('Asia/Jakarta');
        $alerts   = collect();

        // ── Alert 1: Stasiun Maintenance ──
        $maintenance = StasiunPengisian::with('chargers')->where('status', 'Maintenance')->get();
        foreach ($maintenance as $st) {
            $alerts->push([
                'type'       => 'Critical',
                'title'      => 'Stasiun dalam Maintenance',
                'station'    => $st->nama_stasiun,
                'lokasi'     => $st->lokasi ?? '-',
                'keterangan' => "Sedang dalam perbaikan dan tidak dapat melayani pengisian.",
                'detail'     => "Unit charger: " . ($st->chargers->count()) . " unit | Koordinat: " . ($st->latitude ? number_format($st->latitude,4).', '.number_format($st->longitude,4) : 'Belum diset'),
                'time'       => 'Sekarang',
                'status'     => $st->status,
            ]);
        }

        // ── Alert 2: Stasiun Inactive ──
        $inactive = StasiunPengisian::with('chargers')->where('status', 'Inactive')->get();
        foreach ($inactive as $st) {
            $alerts->push([
                'type'       => 'Warning',
                'title'      => 'Stasiun Tidak Aktif',
                'station'    => $st->nama_stasiun,
                'lokasi'     => $st->lokasi ?? '-',
                'keterangan' => "Stasiun tidak aktif dan perlu ditindaklanjuti.",
                'detail'     => "Unit charger: " . ($st->chargers->count()) . " unit | Koordinat: " . ($st->latitude ? number_format($st->latitude,4).', '.number_format($st->longitude,4) : 'Belum diset'),
                'time'       => 'Sekarang',
                'status'     => $st->status,
            ]);
        }

        // ── Alert 3: Konsumsi Energi Tinggi ──
        $thresholdEnergi = (float) env('THRESHOLD_ENERGI', 100);
        $highEnergy = RiwayatPengisian::with('stasiun')
            ->where('energi_kwh', '>', $thresholdEnergi)
            ->latest('waktu_mulai')->take(20)->get();
        foreach ($highEnergy as $log) {
            $durasi = '-';
            if ($log->waktu_mulai && $log->waktu_selesai) {
                $mnt = Carbon::parse($log->waktu_mulai)->diffInMinutes(Carbon::parse($log->waktu_selesai));
                $durasi = $mnt >= 60 ? floor($mnt/60).'j '.($mnt%60).'m' : $mnt.' menit';
            }
            $alerts->push([
                'type'       => 'Info',
                'title'      => 'Konsumsi Energi Tinggi',
                'station'    => $log->stasiun->nama_stasiun ?? '-',
                'lokasi'     => $log->stasiun->lokasi ?? '-',
                'keterangan' => "Mengonsumsi " . number_format($log->energi_kwh, 1, ',', '.') . " kWh — melebihi batas " . number_format($thresholdEnergi, 0, ',', '.') . " kWh.",
                'detail'     => "Mulai: " . ($log->waktu_mulai ? Carbon::parse($log->waktu_mulai)->format('d/m/Y H:i') : '-') . " | Selesai: " . ($log->waktu_selesai ? Carbon::parse($log->waktu_selesai)->format('d/m/Y H:i') : '-') . " | Durasi: " . $durasi,
                'time'       => $log->waktu_mulai ? Carbon::parse($log->waktu_mulai)->format('d/m/Y H:i') : '-',
                'status'     => 'Active',
            ]);
        }

        // ── Alert 4: Tidak Ada Aktivitas 7 Hari ──
        $semuaStasiun = StasiunPengisian::all();
        foreach ($semuaStasiun as $st) {
            $lastLog = RiwayatPengisian::where('stasiun_pengisian_id', $st->id)
                ->latest('waktu_mulai')->first();
            if (!$lastLog || Carbon::parse($lastLog->waktu_mulai)->diffInDays(now()) >= 7) {
                $lastAktivitas = $lastLog
                    ? Carbon::parse($lastLog->waktu_mulai)->format('d/m/Y H:i') . ' (' . Carbon::parse($lastLog->waktu_mulai)->diffForHumans() . ')'
                    : 'Belum pernah digunakan';
                $alerts->push([
                    'type'       => 'Warning',
                    'title'      => 'Tidak Ada Aktivitas',
                    'station'    => $st->nama_stasiun,
                    'lokasi'     => $st->lokasi ?? '-',
                    'keterangan' => "Tidak ada aktivitas pengisian dalam 7 hari terakhir.",
                    'detail'     => "Terakhir aktif: " . $lastAktivitas . " | Status stasiun: " . $st->status,
                    'time'       => $lastLog ? Carbon::parse($lastLog->waktu_mulai)->diffForHumans() : 'Belum pernah',
                    'status'     => $st->status,
                ]);
            }
        }

        // ── Alert 5: Aktivitas Login Mencurigakan ──
        $suspiciousIps = LoginAttempt::where('status', 'failed')
            ->where('created_at', '>=', now()->subHours(24))
            ->selectRaw('ip_address, COUNT(*) as total_gagal, MAX(created_at) as last_attempt')
            ->groupBy('ip_address')
            ->having('total_gagal', '>=', 3)
            ->orderByDesc('total_gagal')
            ->get();
        foreach ($suspiciousIps as $sus) {
            $alerts->push([
                'type'       => 'Security',
                'title'      => 'Aktivitas Login Mencurigakan',
                'station'    => $sus->ip_address,
                'lokasi'     => 'Sumber IP',
                'keterangan' => "IP {$sus->ip_address} mencoba login {$sus->total_gagal}x dengan kredensial salah dalam 24 jam.",
                'detail'     => "Aktivitas terakhir: " . Carbon::parse($sus->last_attempt)->format('d/m/Y H:i') . " | Total percobaan gagal: {$sus->total_gagal}",
                'time'       => Carbon::parse($sus->last_attempt)->diffForHumans(),
                'status'     => 'Suspicious',
            ]);
        }

        $persistentLocks = AccountLock::where('lock_count', '>=', 2)->get();
        foreach ($persistentLocks as $lock) {
            $alerts->push([
                'type'       => 'Security',
                'title'      => 'Akun Sering Dikunci',
                'station'    => $lock->email,
                'lokasi'     => $lock->last_ip ?? '-',
                'keterangan' => "Akun {$lock->email} sudah dikunci {$lock->lock_count}x. Indikasi percobaan akses ilegal.",
                'detail'     => "Lock terakhir: " . ($lock->updated_at ? $lock->updated_at->format('d/m/Y H:i') : '-') . " | IP terakhir: " . ($lock->last_ip ?? '-'),
                'time'       => $lock->updated_at ? $lock->updated_at->diffForHumans() : '-',
                'status'     => 'Locked',
            ]);
        }

        $totalAlert = $alerts->count();
        $totalCrit  = $alerts->where('type', 'Critical')->count();
        $totalWarn  = $alerts->where('type', 'Warning')->count();
        $totalInfo  = $alerts->where('type', 'Info')->count();
        $totalSec   = $alerts->where('type', 'Security')->count();
        $tanggalCetak = $now->format('d/m/Y H:i:s') . ' WIB';

        // Build rows
        $rows = '';
        foreach ($alerts as $i => $a) {
            $color = '#2563eb'; $bg = '#dbeafe';
            if ($a['type'] === 'Critical') { $color = '#dc2626'; $bg = '#fee2e2'; }
            elseif ($a['type'] === 'Warning') { $color = '#d97706'; $bg = '#fef3c7'; }
            elseif ($a['type'] === 'Security') { $color = '#0d9488'; $bg = '#ccfbf1'; }
            $stripe = $i % 2 !== 0 ? 'background:#f8fafc;' : '';
            $rows .= '<tr style="' . $stripe . '">
                <td style="text-align:center;color:#94a3b8">' . ($i + 1) . '</td>
                <td><span style="background:' . $bg . ';color:' . $color . ';padding:3px 10px;border-radius:12px;font-size:9px;font-weight:700;white-space:nowrap">' . $a['type'] . '</span></td>
                <td style="font-weight:700;color:#0f172a">' . htmlspecialchars($a['title']) . '</td>
                <td style="font-weight:700;color:#1d4ed8">' . htmlspecialchars($a['station']) . '</td>
                <td style="color:#475569">' . htmlspecialchars($a['lokasi']) . '</td>
                <td>' . htmlspecialchars($a['keterangan']) . '</td>
                <td style="color:#64748b;font-size:9px">' . htmlspecialchars($a['detail']) . '</td>
                <td style="color:#64748b;white-space:nowrap">' . htmlspecialchars($a['time']) . '</td>
            </tr>';
        }
        if (empty($rows)) {
            $rows = '<tr><td colspan="8" style="text-align:center;padding:30px;color:#94a3b8;font-style:italic">✅ Tidak ada alert aktif. Sistem berjalan normal.</td></tr>';
        }

        $html = '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8">
<title>Laporan Alert — EV Smart Energy</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:Arial,sans-serif; font-size:11px; color:#1e293b; background:#f1f5f9; }
.toolbar { background:#0d1b3e; padding:10px 30px; display:flex; justify-content:space-between; align-items:center; position:sticky; top:0; z-index:10; box-shadow:0 2px 8px rgba(0,0,0,0.3); }
.toolbar .info { color:#94a3b8; font-size:11px; }
.toolbar .info strong { color:#FFD700; }
.btn { background:linear-gradient(135deg,#FFD700,#FFA500); color:#0d1b3e; border:none; padding:8px 24px; border-radius:6px; font-weight:bold; cursor:pointer; font-size:13px; }
.btn-back { background:transparent; border:1px solid #334155; color:#94a3b8; padding:8px 16px; border-radius:6px; cursor:pointer; margin-left:8px; }
.wrap { max-width:1200px; margin:20px auto; background:#fff; border-radius:10px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,0.1); }
.hdr { background:linear-gradient(135deg,#0d1b3e,#1a4f9c); color:#fff; padding:24px 30px; border-bottom:3px solid #FFD700; display:flex; justify-content:space-between; align-items:flex-start; }
.hdr h1 { font-size:20px; font-weight:bold; margin:0 0 4px; }
.hdr h1 span { color:#FFD700; }
.hdr .sub { font-size:11px; opacity:0.85; }
.hdr .meta { text-align:right; font-size:11px; line-height:1.8; }
.hdr .meta .tgl { color:#FFD700; font-weight:bold; font-size:13px; }
.sumbar { background:#f8fafc; border-bottom:1px solid #e2e8f0; padding:10px 30px; display:flex; gap:24px; font-size:10px; color:#64748b; }
.sumbar strong { color:#1e293b; }
.cards { display:flex; border-bottom:2px solid #e2e8f0; }
.card { flex:1; padding:16px 12px; text-align:center; border-right:1px solid #e2e8f0; }
.card:last-child { border-right:none; }
.card .v { font-size:28px; font-weight:bold; line-height:1; }
.card .l { font-size:9px; color:#94a3b8; margin-top:4px; text-transform:uppercase; letter-spacing:0.5px; }
.sec { padding:20px 30px 26px; }
.sec-title { font-size:12px; font-weight:bold; color:#fff; background:linear-gradient(90deg,#1a4f9c,#0e9cdd); padding:6px 14px; border-radius:4px; display:inline-block; margin-bottom:14px; }
table { width:100%; border-collapse:collapse; font-size:10px; }
th { background:#0f172a; color:#fff; padding:9px 8px; font-size:9px; font-weight:bold; text-align:left; border:1px solid #1e3a6e; }
td { padding:8px; border-bottom:1px solid #f1f5f9; vertical-align:top; }
.ftr { background:#f8fafc; padding:12px 30px; text-align:center; font-size:9px; color:#94a3b8; border-top:2px solid #e2e8f0; }
@media print {
    .toolbar { display:none !important; }
    body { background:#fff; }
    .wrap { margin:0; box-shadow:none; border-radius:0; }
    @page { size:A4 landscape; margin:8mm; }
    -webkit-print-color-adjust:exact; print-color-adjust:exact;
}
</style></head><body>
<div class="toolbar">
  <div class="info"><strong>⚡ EV Smart Energy</strong> &nbsp;—&nbsp; Laporan Alert Sistem &nbsp;|&nbsp; Dicetak: <strong>' . $tanggalCetak . '</strong></div>
  <div>
    <button class="btn" onclick="window.print()">🖨️ &nbsp;Print / Simpan PDF</button>
    <button class="btn-back" onclick="window.history.back()">✕ Tutup</button>
  </div>
</div>
<div class="wrap">
  <div class="hdr">
    <div>
      <h1>⚡ <span>EV Smart Energy</span> — Laporan Alert Sistem</h1>
      <div class="sub">PT. Sahabat Mitra Intrabuana — EV Charging Station Monitoring</div>
    </div>
    <div class="meta">
      <div class="tgl">' . $now->translatedFormat('d F Y') . '</div>
      <div>' . $now->format('H:i') . ' WIB</div>
      <div>EV Smart Energy Control Center</div>
    </div>
  </div>
  <div class="sumbar">
    <span>Total Alert: <strong>' . $totalAlert . '</strong></span>
    <span>Critical: <strong style="color:#dc2626">' . $totalCrit . '</strong></span>
    <span>Warning: <strong style="color:#d97706">' . $totalWarn . '</strong></span>
    <span>Info: <strong style="color:#2563eb">' . $totalInfo . '</strong></span>
    <span>Keamanan: <strong style="color:#0d9488">' . $totalSec . '</strong></span>
    <span>Dicetak: <strong>' . $tanggalCetak . '</strong></span>
  </div>
  <div class="cards">
    <div class="card" style="border-top:3px solid #06b6d4"><div class="v" style="color:#0284c7">' . $totalAlert . '</div><div class="l">Total Alert</div></div>
    <div class="card" style="border-top:3px solid #dc2626"><div class="v" style="color:#dc2626">' . $totalCrit . '</div><div class="l">Critical</div></div>
    <div class="card" style="border-top:3px solid #d97706"><div class="v" style="color:#d97706">' . $totalWarn . '</div><div class="l">Warning</div></div>
    <div class="card" style="border-top:3px solid #2563eb"><div class="v" style="color:#2563eb">' . $totalInfo . '</div><div class="l">Info</div></div>
    <div class="card" style="border-top:3px solid #14b8a6"><div class="v" style="color:#0d9488">' . $totalSec . '</div><div class="l">Keamanan</div></div>
  </div>
  <div class="sec">
    <div class="sec-title">📋 Detail Seluruh Alert Aktif</div>
    <table>
      <thead>
        <tr>
          <th style="width:30px;text-align:center">No</th>
          <th style="width:65px">Tipe</th>
          <th style="width:130px">Judul Alert</th>
          <th style="width:80px">Stasiun</th>
          <th style="width:100px">Lokasi</th>
          <th style="width:180px">Keterangan</th>
          <th>Detail Lengkap</th>
          <th style="width:90px">Waktu</th>
        </tr>
      </thead>
      <tbody>' . $rows . '</tbody>
    </table>
  </div>
  <div class="ftr">
    Dokumen ini digenerate otomatis oleh <strong style="color:#1a4f9c">EV Smart Energy Control Center</strong>
    &nbsp;|&nbsp; PT. Sahabat Mitra Intrabuana &nbsp;|&nbsp; ' . $tanggalCetak . '
    &nbsp;|&nbsp; Total: ' . $totalAlert . ' alert aktif
  </div>
</div>
<script>window.addEventListener("load",function(){setTimeout(function(){window.print();},600);});</script>
</body></html>';

        return response($html, 200)->header('Content-Type', 'text/html; charset=UTF-8');
    }
}

