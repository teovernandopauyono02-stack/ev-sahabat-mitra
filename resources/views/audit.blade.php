@extends('layout.app')
@section('title', 'Audit Trail')
@section('page-title', 'Audit Trail')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/audit.css') }}">
@endpush

@section('content')

{{-- ===== HEADER ===== --}}
<div class="aud-header">
    <div>
        <h2 style="display:flex;align-items:center;gap:13px;font-family:'Rajdhani',sans-serif;font-size:26px;font-weight:700;margin-bottom:5px">
            <span style="display:inline-flex;align-items:center;justify-content:center;width:46px;height:46px;border-radius:13px;background:linear-gradient(135deg,#1d4ed8,#3b82f6);box-shadow:0 0 18px rgba(59,130,246,0.4);flex-shrink:0;animation:pageIconPulse 3s ease-in-out infinite">
                <i class="fas fa-clipboard-list" style="font-size:21px;color:#fff"></i>
            </span>
            <span style="background:linear-gradient(135deg,#f1f5f9,#60a5fa);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent">
                Audit Trail
            </span>
        </h2>
        <p style="font-size:12px;color:#94a3b8;margin:0;padding-left:59px">
            Catatan permanen seluruh aktivitas user di sistem &nbsp;·&nbsp;
            <span style="color:#00d4ff">{{ number_format($totalAll) }} total log</span> &nbsp;·&nbsp;
            <span style="color:#00ff88">{{ $totalToday }} hari ini</span>
            @if($totalCritical > 0)
                &nbsp;·&nbsp; <span style="color:#ff3b3b;font-weight:700">{{ $totalCritical }} critical</span>
            @endif
        </p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
        <a href="{{ route('audit.export-pdf', request()->all()) }}" target="_blank"
           class="btn btn-warning"
           style="font-size:12px;display:inline-flex;align-items:center;gap:6px;padding:8px 16px">
            <i class="fas fa-file-pdf"></i> Export PDF
        </a>
        <a href="{{ route('audit.index') }}"
           class="btn btn-secondary"
           style="font-size:12px;display:inline-flex;align-items:center;gap:6px;padding:8px 16px">
            <i class="fas fa-undo"></i> Reset
        </a>
    </div>
</div>

{{-- ===== ROW 1 — STAT CARDS ===== --}}
<div class="aud-stats">
    <div class="aud-stat s1">
        <i class="fas fa-database aud-stat-ico"></i>
        <div class="aud-stat-val">{{ number_format($totalAll) }}</div>
        <div class="aud-stat-lbl">Total Audit Log</div>
        <div class="aud-stat-sub">Semua waktu</div>
    </div>
    <div class="aud-stat s2">
        <i class="fas fa-skull-crossbones aud-stat-ico"></i>
        <div class="aud-stat-val">{{ number_format($totalCritical) }}</div>
        <div class="aud-stat-lbl">Critical</div>
        <div class="aud-stat-sub">Perlu perhatian segera</div>
    </div>
    <div class="aud-stat s3">
        <i class="fas fa-exclamation-triangle aud-stat-ico"></i>
        <div class="aud-stat-val">{{ number_format($totalWarning) }}</div>
        <div class="aud-stat-lbl">Warning</div>
        <div class="aud-stat-sub">Perlu ditinjau</div>
    </div>
    <div class="aud-stat s4">
        <i class="fas fa-calendar-day aud-stat-ico"></i>
        <div class="aud-stat-val">{{ number_format($totalToday) }}</div>
        <div class="aud-stat-lbl">Hari Ini</div>
        <div class="aud-stat-sub">{{ now()->format('d M Y') }}</div>
    </div>
</div>

{{-- ===== ROW 2 — TREND CHART (55%) + TOP MODUL (45%) ===== --}}
<div class="aud-grid2">

    {{-- Grafik Trend 7 Hari --}}
    <div class="aud-card">
        <div class="aud-card-hdr">
            <div class="aud-card-title">
                <i class="fas fa-chart-bar" style="color:#00d4ff"></i>
                Trend 7 Hari Terakhir
            </div>
            <div class="aud-card-sub">Aktivitas harian</div>
        </div>
        <canvas id="trendChart"></canvas>
    </div>

    {{-- Top 5 Modul --}}
    <div class="aud-card">
        <div class="aud-card-hdr">
            <div class="aud-card-title">
                <i class="fas fa-cubes" style="color:#00d4ff"></i>
                Top 5 Modul Aktif
            </div>
            <div class="aud-card-sub">Berdasarkan total aktivitas</div>
        </div>
        @php
            $maxModule = $topModules->max('total') ?: 1;
            $barColors = [
                'linear-gradient(90deg,#00d4ff,#0891b2)',
                'linear-gradient(90deg,#3b82f6,#1d4ed8)',
                'linear-gradient(90deg,#a855f7,#7c3aed)',
                'linear-gradient(90deg,#ffcc00,#f59e0b)',
                'linear-gradient(90deg,#00ff88,#00cc66)',
            ];
            $valColors = ['#00d4ff','#60a5fa','#c084fc','#ffcc00','#00ff88'];
        @endphp
        <div class="mod-bar-wrap">
            @forelse($topModules as $idx => $m)
            <div class="mod-bar-item">
                <div class="mod-bar-name" title="{{ $m->module }}">{{ $m->module }}</div>
                <div class="mod-bar-bg">
                    <div class="mod-bar-fill"
                         style="width:{{ round(($m->total / $maxModule) * 100) }}%;background:{{ $barColors[$idx] ?? $barColors[0] }}">
                    </div>
                </div>
                <div class="mod-bar-val" style="color:{{ $valColors[$idx] ?? $valColors[0] }}">
                    {{ number_format($m->total) }}
                </div>
            </div>
            @empty
            <div style="text-align:center;color:#475569;padding:28px 0;font-size:13px">
                <i class="fas fa-inbox" style="font-size:26px;margin-bottom:8px;display:block;opacity:0.35"></i>
                Belum ada data modul
            </div>
            @endforelse
        </div>
    </div>

</div>

{{-- ===== ROW 3 — FILTER BAR ===== --}}
<form method="GET" action="{{ route('audit.index') }}">
<div class="aud-filter">
    <div>
        <label><i class="fas fa-search" style="margin-right:4px;opacity:0.7"></i>Cari</label>
        <input type="text" name="q" value="{{ request('q') }}"
               placeholder="Aksi, deskripsi, user, IP...">
    </div>
    <div>
        <label>Modul</label>
        <select name="module">
            <option value="">Semua Modul</option>
            @foreach($availableModules as $mod)
                <option value="{{ $mod }}" {{ request('module') == $mod ? 'selected' : '' }}>
                    {{ $mod }}
                </option>
            @endforeach
        </select>
    </div>
    <div>
        <label>Severity</label>
        <select name="severity">
            <option value="">Semua</option>
            <option value="info"     {{ request('severity') == 'info'     ? 'selected' : '' }}>Info</option>
            <option value="warning"  {{ request('severity') == 'warning'  ? 'selected' : '' }}>Warning</option>
            <option value="critical" {{ request('severity') == 'critical' ? 'selected' : '' }}>Critical</option>
        </select>
    </div>
    <div>
        <label>Dari Tanggal</label>
        <input type="date" name="date_from" value="{{ request('date_from') }}">
    </div>
    <div>
        <label>Sampai Tanggal</label>
        <input type="date" name="date_to" value="{{ request('date_to') }}">
    </div>
    <div>
        <label>&nbsp;</label>
        <button type="submit" class="btn btn-primary"
                style="font-size:12px;width:100%;display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:8px 14px">
            <i class="fas fa-filter"></i> Filter
        </button>
    </div>
    <div>
        <label>&nbsp;</label>
        <a href="{{ route('audit.index') }}" class="btn btn-secondary"
           style="font-size:12px;width:100%;display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:8px 14px;text-decoration:none">
            <i class="fas fa-undo"></i> Reset
        </a>
    </div>
</div>
</form>

{{-- Result info bar --}}
<div class="aud-result-bar">
    <div class="aud-result-info">
        Menampilkan
        @if($logs->firstItem())
            <strong>{{ $logs->firstItem() }}–{{ $logs->lastItem() }}</strong> dari
        @endif
        <strong>{{ number_format($totalAll) }}</strong> total entry
        @if(request()->hasAny(['q','module','severity','date_from','date_to']))
            <span class="aud-filter-active-tag">
                <i class="fas fa-filter" style="font-size:9px"></i> Filter Aktif
            </span>
        @endif
    </div>
    <div style="font-size:12px;color:#94a3b8">
        Halaman {{ $logs->currentPage() }} &nbsp;·&nbsp; {{ $logs->count() }} baris
    </div>
</div>

{{-- ===== ROW 4 — TABEL AUDIT LOG ===== --}}
<div class="aud-table-wrap">
    <div class="aud-table-title">
        <div class="aud-table-title-left">
            <i class="fas fa-database"></i>
            <div>
                <div class="aud-table-name">Tabel Riwayat Audit Log</div>
                <div class="aud-table-desc">Catatan permanen seluruh aktivitas user — siapa, kapan, melakukan apa, dan dari IP mana</div>
            </div>
        </div>
        <div class="aud-table-title-right">
            <span class="aud-table-badge"><i class="fas fa-shield-halved"></i> Read-Only</span>
        </div>
    </div>
    <div class="aud-table-scroll">
        <table class="aud-table">
            <thead>
                <tr>
                    <th style="width:46px" title="Nomor urut record">No</th>
                    <th style="width:148px" title="Tanggal & jam aktivitas dilakukan (zona WIB)">Waktu</th>
                    <th style="width:130px" title="Nama dan role user yang melakukan aksi">User</th>
                    <th style="width:110px" title="Modul/halaman tempat aksi dilakukan">Modul</th>
                    <th style="width:160px" title="Jenis aksi: Login, Tambah, Update, Hapus, dll">Aksi</th>
                    <th title="Penjelasan detail aktivitas yang dilakukan">Deskripsi</th>
                    <th style="width:115px" title="Alamat IP komputer/HP user saat melakukan aksi">IP Address</th>
                    <th style="width:92px;text-align:center" title="Tingkat penting: Info (biasa), Warning (perhatian), Critical (penting)">Severity</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $i => $log)
                @php
                    $sev = strtolower($log->severity ?? 'info');
                    $rowClass = 'aud-row';
                    if ($sev === 'critical') $rowClass .= ' aud-row-critical';
                    elseif ($sev === 'warning') $rowClass .= ' aud-row-warning';
                @endphp
                <tr class="{{ $rowClass }}" onclick="showAuditDetail({{ $log->id }})"
                    title="Klik untuk detail">
                    <td style="color:#475569;font-size:12px">
                        {{ ($logs->currentPage() - 1) * $logs->perPage() + $i + 1 }}
                    </td>
                    <td style="font-size:11px;white-space:nowrap">
                        <div style="color:#cbd5e1">
                            {{ $log->created_at->setTimezone('Asia/Jakarta')->format('d/m/Y') }}
                        </div>
                        <div style="color:#94a3b8;font-size:10px">
                            {{ $log->created_at->setTimezone('Asia/Jakarta')->format('H:i:s') }} WIB
                        </div>
                    </td>
                    <td>
                        <div style="font-weight:700;color:#FFD700;font-size:13px">
                            {{ $log->user_name ?? '-' }}
                        </div>
                        @if($log->user_role)
                            <div style="font-size:10px;color:#94a3b8">{{ $log->user_role }}</div>
                        @endif
                    </td>
                    <td><span class="module-tag">{{ $log->module ?? '-' }}</span></td>
                    <td style="color:#f1f5f9;font-weight:700;font-size:13px">{{ $log->action }}</td>
                    <td style="color:#94a3b8;font-size:12px;max-width:260px">
                        {{ \Illuminate\Support\Str::limit($log->description, 90) }}
                    </td>
                    <td style="font-family:monospace;font-size:11px;color:#94a3b8">
                        {{ $log->ip_address ?? '-' }}
                    </td>
                    <td style="text-align:center">
                        <span class="sev-{{ $sev }}">{{ $sev }}</span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align:center;padding:52px 20px;color:#475569">
                        <i class="fas fa-search" style="font-size:30px;margin-bottom:12px;display:block;opacity:0.28"></i>
                        <div style="font-size:14px;font-weight:600;color:#94a3b8">Tidak ada audit log</div>
                        <div style="font-size:12px;margin-top:5px">Coba ubah filter pencarian</div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination (simplePaginate: Prev / Next only) --}}
    @if($logs->hasPages())
    <div class="aud-pagination">
        <div class="page-info">
            Halaman <strong style="color:#f1f5f9">{{ $logs->currentPage() }}</strong>
            @if($logs->hasMorePages())
                &nbsp;·&nbsp; Ada halaman berikutnya
            @else
                &nbsp;·&nbsp; Halaman terakhir
            @endif
        </div>
        <div class="page-links">
            @if($logs->onFirstPage())
                <span><i class="fas fa-chevron-left" style="font-size:10px"></i> Prev</span>
            @else
                <a href="{{ $logs->previousPageUrl() }}">
                    <i class="fas fa-chevron-left" style="font-size:10px"></i> Prev
                </a>
            @endif
            @if($logs->hasMorePages())
                <a href="{{ $logs->nextPageUrl() }}">
                    Next <i class="fas fa-chevron-right" style="font-size:10px"></i>
                </a>
            @else
                <span>Next <i class="fas fa-chevron-right" style="font-size:10px"></i></span>
            @endif
        </div>
    </div>
    @endif
</div>

{{-- ===== MODAL DETAIL ===== --}}
<div class="aud-modal-bg" id="auditModalBg" onclick="closeAuditModal(event)">
    <div class="aud-modal" id="auditModalBox">
        <button class="aud-modal-close" onclick="closeAuditModal()" title="Tutup">&times;</button>
        <div id="auditModalContent">
            <div class="aud-modal-loading">
                <i class="fas fa-circle-notch"></i>
                Memuat detail...
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
/* ===== AUTO-SCROLL TO TOP ===== */
window.scrollTo({ top: 0, behavior: 'smooth' });

/* ===== TREND BAR CHART ===== */
(function () {
    const raw = @json($trend7Days);
    const labels = raw.map(function (r) {
        var d = new Date(r.tgl);
        return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short' });
    });
    const data = raw.map(function (r) { return r.total; });

    var ctx = document.getElementById('trendChart');
    if (!ctx) return;

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Aktivitas',
                data: data,
                backgroundColor: 'rgba(0,212,255,0.75)',
                borderColor: '#00d4ff',
                borderWidth: 1,
                borderRadius: 5,
                borderSkipped: false,
                hoverBackgroundColor: 'rgba(0,212,255,0.95)',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0f1c30',
                    borderColor: 'rgba(0,212,255,0.3)',
                    borderWidth: 1,
                    titleColor: '#00d4ff',
                    bodyColor: '#f1f5f9',
                    padding: 10,
                    callbacks: {
                        label: function (ctx) {
                            return '  ' + ctx.parsed.y + ' aktivitas';
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { color: 'rgba(255,255,255,0.04)', drawBorder: false },
                    ticks: { color: '#94a3b8', font: { size: 10 } }
                },
                y: {
                    grid: { color: 'rgba(255,255,255,0.04)', drawBorder: false },
                    ticks: { color: '#94a3b8', font: { size: 10 }, precision: 0 },
                    beginAtZero: true
                }
            }
        }
    });
})();

/* ===== MODAL DETAIL ===== */
function showAuditDetail(id) {
    var bg = document.getElementById('auditModalBg');
    var content = document.getElementById('auditModalContent');

    content.innerHTML = '<div class="aud-modal-loading"><i class="fas fa-circle-notch"></i>Memuat detail...</div>';
    bg.classList.add('show');
    document.body.style.overflow = 'hidden';

    fetch('{{ url("audit") }}/' + id, {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    })
    .then(function (res) { return res.json(); })
    .then(function (json) {
        if (!json.success) throw new Error('Gagal memuat data');
        renderModal(json.data);
    })
    .catch(function (err) {
        content.innerHTML = '<div style="text-align:center;padding:40px;color:#f87171">'
            + '<i class="fas fa-exclamation-circle" style="font-size:28px;margin-bottom:10px;display:block"></i>'
            + 'Gagal memuat detail: ' + err.message + '</div>';
    });
}

function renderModal(d) {
    var sevClass = 'sev-' + (d.severity || 'info').toLowerCase();

    var diffHtml = '';
    if (d.old_data || d.new_data) {
        var oldStr = d.old_data ? (typeof d.old_data === 'string' ? d.old_data : JSON.stringify(d.old_data, null, 2)) : '—';
        var newStr = d.new_data ? (typeof d.new_data === 'string' ? d.new_data : JSON.stringify(d.new_data, null, 2)) : '—';
        diffHtml = '<div class="modal-section-title"><i class="fas fa-code-branch" style="margin-right:5px;color:#00d4ff"></i>Perubahan Data</div>'
            + '<div class="diff-grid">'
            + '<div class="diff-old"><div class="diff-label"><i class="fas fa-minus-circle"></i> Sebelum</div><pre>' + escHtml(oldStr) + '</pre></div>'
            + '<div class="diff-new"><div class="diff-label"><i class="fas fa-plus-circle"></i> Sesudah</div><pre>' + escHtml(newStr) + '</pre></div>'
            + '</div>';
    }

    var descHtml = '';
    if (d.description) {
        descHtml = '<div class="modal-section-title"><i class="fas fa-align-left" style="margin-right:5px;color:#00d4ff"></i>Deskripsi</div>'
            + '<div style="background:rgba(0,212,255,0.05);border:1px solid rgba(0,212,255,0.12);border-radius:8px;padding:12px 14px;font-size:13px;color:#f1f5f9;line-height:1.6">'
            + escHtml(d.description) + '</div>';
    }

    var uaHtml = '';
    if (d.user_agent) {
        uaHtml = '<div class="modal-section-title" style="margin-top:12px"><i class="fas fa-desktop" style="margin-right:5px;color:#94a3b8"></i>User Agent</div>'
            + '<div style="font-size:11px;color:#64748b;word-break:break-all;line-height:1.5">' + escHtml(d.user_agent) + '</div>';
    }

    document.getElementById('auditModalContent').innerHTML =
        '<h3><i class="fas fa-clipboard-check" style="margin-right:8px;font-size:16px"></i>Detail Audit Log #' + d.id + '</h3>'
        + '<div style="font-size:11px;color:#64748b;margin-bottom:2px">Klik di luar modal untuk menutup</div>'
        + '<div class="meta-grid">'
        + metaItem('Waktu', d.created_at, 'fa-clock', '#00d4ff')
        + metaItem('User', d.user_name || '—', 'fa-user', '#FFD700')
        + metaItem('Role', d.user_role || '—', 'fa-id-badge', '#94a3b8')
        + metaItem('Modul', d.module || '—', 'fa-cube', '#22d3ee')
        + metaItem('Aksi', d.action || '—', 'fa-bolt', '#f1f5f9')
        + metaItem('IP Address', d.ip_address || '—', 'fa-network-wired', '#94a3b8')
        + '</div>'
        + '<div style="margin-bottom:12px"><span class="' + sevClass + '" style="font-size:12px;padding:4px 14px">'
        + (d.severity || 'info').toUpperCase() + '</span></div>'
        + descHtml
        + diffHtml
        + uaHtml;
}

function metaItem(label, value, icon, color) {
    return '<div class="meta-item">'
        + '<div class="meta-label"><i class="fas ' + icon + '" style="margin-right:4px;color:' + color + ';opacity:0.8"></i>' + label + '</div>'
        + '<div class="meta-value">' + escHtml(String(value)) + '</div>'
        + '</div>';
}

function escHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function closeAuditModal(event) {
    if (event && event.target !== document.getElementById('auditModalBg')) return;
    document.getElementById('auditModalBg').classList.remove('show');
    document.body.style.overflow = '';
}

/* Close on Escape key */
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        document.getElementById('auditModalBg').classList.remove('show');
        document.body.style.overflow = '';
    }
});
</script>
@endpush
