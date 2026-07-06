@extends('layout.app')
@section('title', 'Keamanan Sistem')
@section('page-title', 'Keamanan')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/security.css') }}">
@endpush

@section('content')

@php
    $suspiciousCount = $suspiciousIp->count();
    $lockedCount = isset($accountLocks) ? $accountLocks->where('locked_until', '>', now())->count() : 0;
    $failedToday = \App\Models\LoginAttempt::where('status','failed')->whereDate('created_at', today())->count();

    // Tentukan level ancaman
    if ($suspiciousCount >= 3 || $lockedCount >= 2 || $failedToday >= 10) {
        $threatLevel = 'bahaya';
        $threatText  = 'BAHAYA — Ada aktivitas mencurigakan yang perlu segera ditangani';
        $threatIcon  = '🔴';
    } elseif ($suspiciousCount >= 1 || $lockedCount >= 1 || $failedToday >= 5) {
        $threatLevel = 'waspada';
        $threatText  = 'WASPADA — Ada aktivitas yang perlu dipantau';
        $threatIcon  = '🟡';
    } else {
        $threatLevel = 'aman';
        $threatText  = 'AMAN — Sistem berjalan normal, tidak ada ancaman terdeteksi';
        $threatIcon  = '🟢';
    }
@endphp

{{-- ===== HEADER ===== --}}
<div class="sec-header">
    <div>
        <h2>
            {{-- Icon perisai dengan kilat di dalam --}}
            <span style="
                display:inline-flex; align-items:center; justify-content:center;
                width:42px; height:42px; border-radius:12px;
                background: linear-gradient(135deg, #1d4ed8, #06b6d4);
                box-shadow: 0 0 18px rgba(6,182,212,0.4), 0 0 0 1px rgba(6,182,212,0.2);
                flex-shrink:0;
                animation: shieldPulse 3s ease-in-out infinite;
            ">
                <i class="fas fa-shield-halved" style="font-size:20px;color:#fff"></i>
            </span>
            <span style="background:linear-gradient(135deg,#f1f5f9,#22d3ee);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent">
                Keamanan
            </span>
        </h2>
        <p>Monitoring keamanan sistem secara real-time — login, ancaman, akun terkunci, dan audit trail</p>
    </div>
    <button onclick="window.location.reload()" class="btn btn-secondary" style="font-size:12px">
        <i class="fas fa-sync-alt"></i> Refresh
    </button>
</div>

{{-- ===== STATUS KEAMANAN SISTEM ===== --}}
<div class="sec-status-bar {{ $threatLevel }}">
    <div class="sec-status-icon">{{ $threatIcon }}</div>
    <div class="sec-status-text">
        <div class="sec-status-title">Status Keamanan: {{ $threatText }}</div>
        <div class="sec-status-sub">
            {{ $suspiciousCount }} IP mencurigakan ·
            {{ $lockedCount }} akun terkunci ·
            {{ $failedToday }} percobaan gagal hari ini ·
            {{ $percobaanGagal }} gagal 24 jam terakhir
        </div>
    </div>
    <div class="sec-status-time">Update: {{ now()->setTimezone('Asia/Jakarta')->format('H:i:s') }} WIB</div>
</div>

{{-- ===== STAT CARDS ===== --}}
<div class="sec-stats">
    <div class="sec-stat s1">
        <i class="fas fa-check-circle sec-ico"></i>
        <div class="sec-val">{{ $totalLogin }}</div>
        <div class="sec-lbl">Login Berhasil</div>
        <div class="sec-sub">Total sepanjang masa</div>
    </div>
    <div class="sec-stat s2">
        <i class="fas fa-times-circle sec-ico"></i>
        <div class="sec-val">{{ $totalGagal }}</div>
        <div class="sec-lbl">Login Gagal</div>
        <div class="sec-sub">Total sepanjang masa</div>
    </div>
    <div class="sec-stat s3">
        <i class="fas fa-calendar-day sec-ico"></i>
        <div class="sec-val">{{ $loginHariIni }}</div>
        <div class="sec-lbl">Aktivitas Hari Ini</div>
        <div class="sec-sub">Semua percobaan login</div>
    </div>
    <div class="sec-stat s4">
        <i class="fas fa-user-shield sec-ico"></i>
        <div class="sec-val">{{ $suspiciousCount }}</div>
        <div class="sec-lbl">IP Mencurigakan</div>
        <div class="sec-sub">24 jam terakhir</div>
    </div>
    <div class="sec-stat s5">
        <i class="fas fa-lock sec-ico"></i>
        <div class="sec-val">{{ isset($accountLocks) ? $accountLocks->count() : 0 }}</div>
        <div class="sec-lbl">Akun Pernah Dikunci</div>
        <div class="sec-sub">Total riwayat lock</div>
    </div>
</div>

{{-- ===== GRID: GRAFIK LOGIN + IP MENCURIGAKAN ===== --}}
<div class="sec-grid2">

    {{-- Grafik Login 7 Hari --}}
    <div class="sec-card">
        <div class="sec-card-hdr">
            <span class="sec-card-title"><i class="fas fa-chart-area"></i> Aktivitas Login (7 Hari)</span>
            <span class="sec-card-sub">Berhasil vs Gagal</span>
        </div>
        <canvas id="loginChart"></canvas>
    </div>

    {{-- IP Mencurigakan + Akun Terkunci --}}
    <div class="sec-card">
        <div class="sec-card-hdr">
            <span class="sec-card-title"><i class="fas fa-exclamation-triangle"></i> Ancaman Aktif</span>
            <span class="sec-card-sub">Real-time</span>
        </div>

        @if($suspiciousIp->count() > 0)
        <div style="font-size:10px;color:#f87171;text-transform:uppercase;letter-spacing:1px;font-weight:700;margin-bottom:8px">
            <i class="fas fa-wifi"></i> IP Mencurigakan ({{ $suspiciousIp->count() }})
        </div>
        @foreach($suspiciousIp as $ip)
        <div class="sus-item">
            <i class="fas fa-network-wired" style="color:#f87171;font-size:14px;flex-shrink:0"></i>
            <div class="sus-ip">{{ $ip->ip_address }}</div>
            <div class="sus-count">{{ $ip->total_gagal }}x gagal</div>
            <span class="sus-badge">⚠ Blokir</span>
        </div>
        @endforeach
        @else
        <div style="text-align:center;padding:16px;color:#34d399;font-size:12px">
            <i class="fas fa-shield-check" style="font-size:20px;margin-bottom:6px;display:block"></i>
            Tidak ada IP mencurigakan
        </div>
        @endif

        @if(isset($accountLocks) && $accountLocks->count() > 0)
        <div style="font-size:10px;color:#fbbf24;text-transform:uppercase;letter-spacing:1px;font-weight:700;margin:12px 0 8px">
            <i class="fas fa-lock"></i> Akun Terkunci ({{ $accountLocks->count() }})
        </div>
        @foreach($accountLocks->take(3) as $lock)
        @php $isLocked = $lock->locked_until && $lock->locked_until->isFuture(); @endphp
        <div class="lock-item">
            <i class="fas fa-{{ $isLocked ? 'lock' : 'lock-open' }}" style="color:{{ $isLocked ? '#f87171' : '#34d399' }};font-size:14px;flex-shrink:0"></i>
            <div class="lock-email">{{ $lock->email }}</div>
            <div class="lock-count">{{ $lock->lock_count }}x dikunci</div>
            @if($isLocked)
                <span style="background:rgba(239,68,68,0.18);color:#f87171;padding:2px 8px;border-radius:8px;font-size:10px;font-weight:700">Terkunci</span>
            @else
                <span style="background:rgba(16,185,129,0.15);color:#34d399;padding:2px 8px;border-radius:8px;font-size:10px;font-weight:700">Bebas</span>
            @endif
        </div>
        @endforeach
        @endif
    </div>

</div>

{{-- ===== RIWAYAT LOGIN TERBARU ===== --}}
<div class="card" style="margin-bottom:16px">
    <div class="card-header">
        <span class="card-title"><i class="fas fa-history" style="margin-right:8px;color:#06b6d4"></i>Riwayat Login Terbaru</span>
        <span style="font-size:11px;color:#64748b">30 percobaan terakhir</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Email</th>
                    <th style="text-align:center">Status</th>
                    <th>IP Address</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($loginLog as $log)
                <tr class="{{ $log->status === 'failed' ? 'login-row-failed' : '' }}">
                    <td style="font-size:12px;white-space:nowrap;color:#cbd5e1">{{ $log->created_at->setTimezone('Asia/Jakarta')->format('d/m/Y H:i:s') }}</td>
                    <td style="color:#FFD700;font-weight:700">{{ $log->email }}</td>
                    <td style="text-align:center">
                        @if($log->status === 'success')
                            <span style="background:rgba(16,185,129,0.15);color:#34d399;border:1px solid rgba(16,185,129,0.3);padding:2px 10px;border-radius:10px;font-size:11px;font-weight:700">✓ Berhasil</span>
                        @else
                            <span style="background:rgba(239,68,68,0.15);color:#f87171;border:1px solid rgba(239,68,68,0.3);padding:2px 10px;border-radius:10px;font-size:11px;font-weight:700">✗ Gagal</span>
                        @endif
                    </td>
                    <td style="color:#94a3b8;font-family:monospace;font-size:12px">{{ $log->ip_address ?? '-' }}</td>
                    <td style="color:#94a3b8;font-size:12px">{{ $log->failure_reason ?? '-' }}</td>
                </tr>
                @empty
                <tr><td colspan="5" style="text-align:center;padding:28px;color:#475569">Belum ada riwayat login</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ===== AUDIT TRAIL ===== --}}
<div class="card">
    <div class="card-header">
        <span class="card-title"><i class="fas fa-clipboard-list" style="margin-right:8px;color:#FFD700"></i>Audit Trail Sistem</span>
        <div style="display:flex;gap:8px;align-items:center">
            <span style="font-size:11px;color:#64748b">50 aktivitas terakhir</span>
            <a href="{{ route('audit.index') }}" style="font-size:11px;color:#06b6d4;text-decoration:none;font-weight:600">
                Lihat Semua <i class="fas fa-arrow-right" style="font-size:10px"></i>
            </a>
        </div>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>User</th>
                    <th>Modul</th>
                    <th>Aksi</th>
                    <th>Deskripsi</th>
                    <th style="text-align:center">Level</th>
                </tr>
            </thead>
            <tbody>
                @forelse($auditLog as $log)
                <tr>
                    <td style="font-size:12px;white-space:nowrap">{{ $log->created_at->setTimezone('Asia/Jakarta')->format('d/m/Y H:i:s') }}</td>
                    <td style="color:#FFD700;font-weight:600">{{ $log->user_name ?? '-' }}</td>
                    <td>
                        <span style="background:rgba(6,182,212,0.12);color:#22d3ee;border:1px solid rgba(6,182,212,0.25);padding:2px 8px;border-radius:10px;font-size:11px">{{ $log->module ?? '-' }}</span>
                    </td>
                    <td style="font-weight:600">{{ $log->action }}</td>
                    <td style="color:#94a3b8;font-size:12px">{{ \Illuminate\Support\Str::limit($log->description, 60) }}</td>
                    <td style="text-align:center">
                        @if($log->severity === 'critical')
                            <span style="background:rgba(239,68,68,0.15);color:#f87171;border:1px solid rgba(239,68,68,0.3);padding:2px 8px;border-radius:10px;font-size:11px;font-weight:700">Critical</span>
                        @elseif($log->severity === 'warning')
                            <span style="background:rgba(245,158,11,0.15);color:#fbbf24;border:1px solid rgba(245,158,11,0.3);padding:2px 8px;border-radius:10px;font-size:11px;font-weight:700">Warning</span>
                        @else
                            <span style="background:rgba(16,185,129,0.12);color:#34d399;border:1px solid rgba(16,185,129,0.25);padding:2px 8px;border-radius:10px;font-size:11px;font-weight:700">Info</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" style="text-align:center;padding:28px;color:#475569">Belum ada audit log</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection

@push('scripts')
<script>
// ── Grafik Login 7 Hari ──
(function () {
    const loginData = @json($loginChart7Days);

    const ctx = document.getElementById('loginChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: loginData.map(d => d.label),
            datasets: [
                {
                    label: 'Berhasil',
                    data: loginData.map(d => d.success),
                    backgroundColor: '#00cc66',
                    borderColor: '#00ff88',
                    borderWidth: 1,
                    borderRadius: 4,
                },
                {
                    label: 'Gagal',
                    data: loginData.map(d => d.failed),
                    backgroundColor: '#cc2222',
                    borderColor: '#ff3b3b',
                    borderWidth: 1,
                    borderRadius: 4,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { labels: { color: '#94a3b8', font: { size: 11 }, boxWidth: 12 } },
                tooltip: {
                    callbacks: {
                        label: c => `${c.dataset.label}: ${c.parsed.y} kali`
                    }
                }
            },
            scales: {
                x: { ticks: { color: '#64748b' }, grid: { color: 'rgba(255,255,255,0.04)' } },
                y: { ticks: { color: '#64748b', stepSize: 1 }, grid: { color: 'rgba(255,255,255,0.04)' }, beginAtZero: true }
            }
        }
    });
})();

// Auto-refresh setiap 15 detik — lebih responsif terhadap ancaman baru
setTimeout(() => window.location.reload(), 15000);
</script>
@endpush
