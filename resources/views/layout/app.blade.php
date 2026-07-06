<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'EV Smart Energy Control Center')</title>
    <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/setting.css') }}">
    <style>
        /* ===== TOPBAR BELL NOTIFICATION ===== */
        .topbar-bell {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.14);
            cursor: pointer;
            color: #e2e8f0;
            transition: all 0.2s;
            margin-right: 8px;
        }
        .topbar-bell:hover {
            background: rgba(6,182,212,0.15);
            border-color: rgba(6,182,212,0.4);
            color: #22d3ee;
        }
        .topbar-bell .fa-bell { font-size: 16px; }

        .bell-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            min-width: 18px;
            height: 18px;
            padding: 0 5px;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: #fff;
            border-radius: 9px;
            font-size: 10px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 6px rgba(239, 68, 68, 0.5);
            border: 2px solid var(--bg, #0d1117);
            animation: bell-pulse 2s ease-in-out infinite;
        }
        @keyframes bell-pulse {
            0%, 100% { transform: scale(1); }
            50%      { transform: scale(1.15); }
        }

        .bell-dropdown {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            width: 360px;
            max-height: 480px;
            background: #0f1c30;
            border: 1px solid rgba(6,182,212,0.18);
            border-radius: 12px;
            box-shadow: 0 12px 40px rgba(0,0,0,0.6), 0 0 0 1px rgba(6,182,212,0.05);
            display: none;
            flex-direction: column;
            overflow: hidden;
            z-index: 1000;
            animation: bell-fade-in 0.2s ease-out;
        }
        .bell-dropdown.open { display: flex; }

        @keyframes bell-fade-in {
            from { opacity: 0; transform: translateY(-6px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .bell-dropdown::before {
            content: '';
            position: absolute;
            top: -6px;
            right: 14px;
            width: 12px;
            height: 12px;
            background: #0f1c30;
            border-left: 1px solid rgba(6,182,212,0.18);
            border-top: 1px solid rgba(6,182,212,0.18);
            transform: rotate(45deg);
        }

        .bell-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 16px;
            border-bottom: 1px solid rgba(6,182,212,0.12);
            background: rgba(6,182,212,0.05);
        }
        .bell-title {
            font-size: 14px;
            font-weight: 700;
            color: #e2e8f0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .bell-title i { color: #06b6d4; }
        .bell-count {
            font-size: 11px;
            color: #94a3b8;
            background: rgba(6,182,212,0.1);
            padding: 3px 10px;
            border-radius: 20px;
            font-weight: 600;
        }

        .bell-list {
            flex: 1;
            overflow-y: auto;
            max-height: 360px;
        }
        .bell-list::-webkit-scrollbar { width: 6px; }
        .bell-list::-webkit-scrollbar-thumb {
            background: #30363d;
            border-radius: 3px;
        }

        .bell-item {
            display: flex;
            gap: 12px;
            padding: 12px 16px;
            border-bottom: 1px solid rgba(6,182,212,0.07);
            text-decoration: none;
            color: inherit;
            transition: background 0.15s;
            position: relative;
        }
        .bell-item:hover { background: rgba(6,182,212,0.06); }
        .bell-item:last-child { border-bottom: none; }
        .bell-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
        }
        .bell-item-critical::before { background: #ef4444; }
        .bell-item-warning::before  { background: #f59e0b; }
        .bell-item-info::before     { background: #3b82f6; }

        .bell-item-icon {
            flex-shrink: 0;
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }
        .bell-item-critical .bell-item-icon {
            background: rgba(239, 68, 68, 0.15);
            color: #ef4444;
        }
        .bell-item-warning .bell-item-icon {
            background: rgba(245, 158, 11, 0.15);
            color: #f59e0b;
        }

        .bell-item-body {
            flex: 1;
            min-width: 0;
        }
        .bell-item-title {
            font-size: 13px;
            font-weight: 600;
            color: #e2e8f0;
            margin-bottom: 2px;
        }
        .bell-item-msg {
            font-size: 11px;
            color: #94a3b8;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .bell-item-tag {
            font-size: 9px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 4px;
            text-transform: uppercase;
            align-self: flex-start;
            white-space: nowrap;
        }
        .bell-tag-critical { background: rgba(239, 68, 68, 0.15); color: #fca5a5; }
        .bell-tag-warning  { background: rgba(245, 158, 11, 0.15); color: #fcd34d; }

        .bell-empty {
            text-align: center;
            padding: 30px 20px;
            color: #94a3b8;
            font-size: 13px;
        }
        .bell-empty p { margin: 0; }

        .bell-footer {
            display: block;
            text-align: center;
            padding: 12px;
            color: #06b6d4;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            border-top: 1px solid rgba(6,182,212,0.12);
            background: rgba(6,182,212,0.04);
            transition: background 0.15s;
        }
        .bell-footer:hover { background: rgba(6,182,212,0.1); }
        .bell-footer i { font-size: 10px; margin-left: 4px; }

        /* Light theme support */
        body.theme-light .bell-dropdown {
            background: #fff;
            border-color: rgba(29,78,216,0.15);
        }
        body.theme-light .bell-dropdown::before {
            background: #fff;
            border-color: rgba(29,78,216,0.15);
        }
        body.theme-light .bell-header {
            background: #f0f6ff;
            border-color: rgba(29,78,216,0.12);
        }
        body.theme-light .bell-title { color: #0f172a; }
        body.theme-light .bell-item-title { color: #0f172a; }
        body.theme-light .bell-item-msg { color: #64748b; }
        body.theme-light .bell-item:hover { background: #f0f6ff; }
        body.theme-light .bell-item { border-color: rgba(29,78,216,0.08); }
        body.theme-light .bell-footer {
            background: #f0f6ff;
            border-color: rgba(29,78,216,0.12);
            color: #1d4ed8;
        }
        body.theme-light .bell-footer:hover { background: #dbeafe; }

        /* ===== LOGOUT CONFIRM MODAL ===== */
        #logoutConfirmModal {
            position: fixed;
            inset: 0;
            z-index: 99999;
            opacity: 0;
            transition: opacity 0.15s ease;
            pointer-events: none;
        }
        #logoutConfirmModal.show {
            opacity: 1;
            pointer-events: auto;
        }
        .lc-backdrop {
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.55);
        }
        .lc-dialog {
            position: relative;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0.97);
            max-width: 360px;
            width: calc(100% - 40px);
            background: #0f1c30;
            border: 1px solid rgba(6,182,212,0.2);
            border-radius: 12px;
            padding: 24px 24px 20px;
            box-shadow: 0 12px 40px rgba(0,0,0,0.6);
            transition: transform 0.15s ease;
        }
        #logoutConfirmModal.show .lc-dialog { transform: translate(-50%, -50%) scale(1); }

        .lc-title {
            font-family: 'Inter', sans-serif;
            font-size: 16px;
            font-weight: 600;
            color: #e2e8f0;
            margin: 0 0 6px;
        }
        .lc-msg {
            font-size: 13px;
            color: #94a3b8;
            line-height: 1.5;
            margin: 0 0 20px;
        }
        .lc-actions {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
        }
        .lc-btn {
            padding: 8px 18px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.12s;
            font-family: 'Inter', sans-serif;
        }
        .lc-btn-cancel {
            background: transparent;
            color: #94a3b8;
            border-color: rgba(6,182,212,0.2);
        }
        .lc-btn-cancel:hover { background: rgba(6,182,212,0.06); }
        .lc-btn-confirm {
            background: #dc2626;
            color: #fff;
        }
        .lc-btn-confirm:hover { background: #b91c1c; }

        body.theme-light .lc-dialog {
            background: #fff;
            border-color: rgba(29,78,216,0.15);
        }
        body.theme-light .lc-title { color: #0f172a; }
        body.theme-light .lc-msg { color: #64748b; }
        body.theme-light .lc-btn-cancel { color: #475569; border-color: rgba(29,78,216,0.15); }
        body.theme-light .lc-btn-cancel:hover { background: #f0f6ff; }
    </style>
    @stack('styles')
</head>
<body class="{{ session('theme', 'dark') === 'light' ? 'theme-light' : 'theme-dark' }}">
<aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <div class="logo-icon"><i class="fas fa-charging-station"></i></div>
        <div class="logo-title">EV Smart Energy<br>Control Center</div>
        <div class="logo-sub" style="color:#ffffff;font-weight:600;letter-spacing:0.3px;opacity:0.9">PT. Sahabat Mitra Intrabuana</div>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section-label">Main Menu</div>
        <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>
        <a href="{{ route('station.index') }}" class="nav-item {{ request()->routeIs('station.*') ? 'active' : '' }}">
            <i class="fas fa-charging-station"></i> Station
        </a>
        <a href="{{ route('energy-log.index') }}" class="nav-item {{ request()->routeIs('energy-log.*') ? 'active' : '' }}">
            <i class="fas fa-bolt"></i> Energy Log
        </a>
        <a href="{{ route('report.index') }}" class="nav-item {{ request()->routeIs('report.*') ? 'active' : '' }}">
            <i class="fas fa-chart-bar"></i> Report
        </a>

        <div class="nav-section-label" style="margin-top:8px">Monitoring</div>
        <a href="{{ route('map-view.index') }}" class="nav-item {{ request()->routeIs('map-view.*') ? 'active' : '' }}">
            <i class="fas fa-map-marker-alt"></i> Map View
        </a>
        <a href="{{ route('analytics.index') }}" class="nav-item {{ request()->routeIs('analytics.*') ? 'active' : '' }}">
            <i class="fas fa-chart-line"></i> Analytics
        </a>
        <a href="{{ route('alert.index') }}" class="nav-item {{ request()->routeIs('alert.*') ? 'active' : '' }}">
            <i class="fas fa-bell"></i> Alert
            @php
                $thresholdEnergi = (float) env('THRESHOLD_ENERGI', 100);
                $criticalCount = \App\Models\StasiunPengisian::where('status', 'Maintenance')->count();
                $warningCount  = \App\Models\StasiunPengisian::where('status', 'Inactive')->count();
                $infoCount     = \App\Models\RiwayatPengisian::where('energi_kwh', '>', $thresholdEnergi)->count();
            @endphp
            @if($criticalCount > 0)
                <span style="background:#ff3b3b;color:#fff;border-radius:10px;padding:1px 6px;font-size:10px;margin-left:auto;">{{ $criticalCount }}</span>
            @endif
            @if($warningCount > 0)
                <span style="background:#ffc800;color:#000;border-radius:10px;padding:1px 6px;font-size:10px;margin-left:2px;">{{ $warningCount }}</span>
            @endif
        </a>

        <div class="nav-section-label" style="margin-top:8px">Integrasi</div>
        <a href="{{ route('api-integration.index') }}" class="nav-item {{ request()->routeIs('api-integration.*') ? 'active' : '' }}">
            <i class="fas fa-plug"></i> API Integration
        </a>

        <div class="nav-section-label" style="margin-top:8px">Keamanan</div>
        <a href="{{ route('security.index') }}" class="nav-item {{ request()->routeIs('security.*') ? 'active' : '' }}">
            <i class="fas fa-shield-halved"></i> Keamanan
        </a>
        <a href="{{ route('audit.index') }}" class="nav-item {{ request()->routeIs('audit.*') ? 'active' : '' }}">
            <i class="fas fa-clipboard-list"></i> Audit Trail
        </a>

        <div class="nav-section-label" style="margin-top:8px">Pengaturan</div>
        <a href="{{ route('setting.index') }}" class="nav-item {{ request()->routeIs('setting.*') ? 'active' : '' }}">
            <i class="fas fa-cog"></i> Setting
        </a>
    </nav>
    <div class="sidebar-footer">
        <div class="user-info">
            @if(Auth::user()->photo)
                <img src="{{ asset('uploads/photos/' . Auth::user()->photo) }}" class="user-avatar-img">
            @else
                <div class="user-avatar">{{ strtoupper(substr(Auth::user()->name ?? 'A', 0, 1)) }}</div>
            @endif
            <div>
                <div class="user-name">{{ Auth::user()->name ?? 'Admin' }}</div>
                <div class="user-role">{{ ucfirst(Auth::user()->role ?? 'Administrator') }}</div>
            </div>
        </div>
        <form method="POST" action="{{ route('logout') }}" id="logoutForm" onsubmit="return confirmLogout(event)">
            @csrf
            <button type="submit" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</button>
        </form>
    </div>
</aside>

<div class="main-content">
    <header class="topbar">
        <div class="topbar-title">@yield('page-title', 'Dashboard')</div>
        <div class="topbar-right">
            @php
                // Hitung alert untuk badge & dropdown di header
                $hdrCritical = \App\Models\StasiunPengisian::where('status', 'Maintenance')->count();
                $hdrWarning  = \App\Models\StasiunPengisian::where('status', 'Inactive')->count();
                $hdrTotalAlert = $hdrCritical + $hdrWarning;
                $hdrRecentAlerts = \App\Models\StasiunPengisian::whereIn('status', ['Maintenance', 'Inactive'])
                    ->latest('updated_at')->take(5)->get();
            @endphp

            {{-- Bell Notification --}}
            <div class="topbar-bell" onclick="toggleBellDropdown(event)">
                <i class="fas fa-bell"></i>
                @if($hdrTotalAlert > 0)
                    <span class="bell-badge">{{ $hdrTotalAlert > 9 ? '9+' : $hdrTotalAlert }}</span>
                @endif

                <div class="bell-dropdown" id="bellDropdown">
                    <div class="bell-header">
                        <span class="bell-title">
                            <i class="fas fa-bell"></i> Notifikasi
                        </span>
                        <span class="bell-count">{{ $hdrTotalAlert }} alert</span>
                    </div>

                    <div class="bell-list">
                        @forelse($hdrRecentAlerts as $alert)
                            <a href="{{ route('alert.index') }}" class="bell-item bell-item-{{ $alert->status === 'Maintenance' ? 'critical' : 'warning' }}">
                                <div class="bell-item-icon">
                                    <i class="fas {{ $alert->status === 'Maintenance' ? 'fa-tools' : 'fa-pause-circle' }}"></i>
                                </div>
                                <div class="bell-item-body">
                                    <div class="bell-item-title">
                                        {{ $alert->status === 'Maintenance' ? 'Stasiun Maintenance' : 'Stasiun Tidak Aktif' }}
                                    </div>
                                    <div class="bell-item-msg">
                                        {{ $alert->nama_stasiun }} ({{ $alert->lokasi }})
                                    </div>
                                </div>
                                <div class="bell-item-tag bell-tag-{{ $alert->status === 'Maintenance' ? 'critical' : 'warning' }}">
                                    {{ $alert->status === 'Maintenance' ? 'Critical' : 'Warning' }}
                                </div>
                            </a>
                        @empty
                            <div class="bell-empty">
                                <i class="fas fa-check-circle" style="color:#22c55e;font-size:24px;margin-bottom:8px"></i>
                                <p>Tidak ada notifikasi baru</p>
                            </div>
                        @endforelse
                    </div>

                    @if($hdrTotalAlert > 0)
                        <a href="{{ route('alert.index') }}" class="bell-footer">
                            Lihat Semua Alert <i class="fas fa-arrow-right"></i>
                        </a>
                    @endif
                </div>
            </div>

            <div class="live-badge"><div class="live-dot"></div> LIVE</div>
            <div class="topbar-time" id="clock"></div>
            @if(Auth::user()->photo)
                <a href="{{ route('setting.index') }}#profil" title="Edit Profil" style="display:inline-flex;border-radius:50%;transition:box-shadow 0.2s;outline:2px solid transparent;" onmouseover="this.style.boxShadow='0 0 0 2px #FFD700'" onmouseout="this.style.boxShadow='none'">
                    <img src="{{ asset('uploads/photos/' . Auth::user()->photo) }}" class="topbar-avatar-img" style="cursor:pointer;">
                </a>
            @else
                <a href="{{ route('setting.index') }}#profil" title="Edit Profil" style="display:inline-flex;border-radius:50%;transition:box-shadow 0.2s;" onmouseover="this.style.boxShadow='0 0 0 2px #FFD700'" onmouseout="this.style.boxShadow='none'">
                    <div class="topbar-avatar" style="cursor:pointer;">{{ strtoupper(substr(Auth::user()->name ?? 'A', 0, 1)) }}</div>
                </a>
            @endif
        </div>
    </header>
    <main class="page-content">
        @if(session('success'))
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> {{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> {{ $errors->first() }}</div>
        @endif
        @yield('content')
    </main>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="{{ asset('js/dashboard.js') }}"></script>
<script>
function openModal(id) {
    document.getElementById(id).style.display = 'flex';
}
function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}

// === Bell Dropdown ===
function toggleBellDropdown(event) {
    event.stopPropagation();
    const dd = document.getElementById('bellDropdown');
    if (dd) dd.classList.toggle('open');
}

// Close bell dropdown bila klik di luar
document.addEventListener('click', function(e) {
    const dd = document.getElementById('bellDropdown');
    const bell = document.querySelector('.topbar-bell');
    if (dd && bell && !bell.contains(e.target)) {
        dd.classList.remove('open');
    }
});

// === Logout Confirmation ===
function confirmLogout(event) {
    event.preventDefault();

    // Cek apakah dialog konfirmasi udah terbuka, kalau iya skip
    if (document.getElementById('logoutConfirmModal')) return false;

    const modal = document.createElement('div');
    modal.id = 'logoutConfirmModal';
    modal.innerHTML = `
        <div class="lc-backdrop"></div>
        <div class="lc-dialog">
            <h3 class="lc-title">Logout</h3>
            <p class="lc-msg">Yakin ingin keluar?</p>
            <div class="lc-actions">
                <button type="button" class="lc-btn lc-btn-cancel" onclick="closeLogoutModal()">Batal</button>
                <button type="button" class="lc-btn lc-btn-confirm" onclick="doLogout()">Logout</button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
    setTimeout(() => modal.classList.add('show'), 10);
    return false;
}

function closeLogoutModal() {
    const m = document.getElementById('logoutConfirmModal');
    if (!m) return;
    m.classList.remove('show');
    setTimeout(() => m.remove(), 200);
}

function doLogout() {
    document.getElementById('logoutForm').removeEventListener('submit', confirmLogout);
    document.getElementById('logoutForm').onsubmit = null;
    document.getElementById('logoutForm').submit();
}
</script>
@stack('scripts')
@stack('script')
</body>
</html>