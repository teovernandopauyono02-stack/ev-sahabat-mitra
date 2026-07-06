@extends('layout.app')
@section('title', 'Report - EV Smart Energy')
@section('page-title', 'Report')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/report.css') }}">
@endpush

@section('content')

{{-- Page Header --}}
<div class="page-header">
    <div class="page-header-left">
        <h2 style="display:flex;align-items:center;gap:12px;font-family:'Rajdhani',sans-serif;font-size:24px;font-weight:700;margin-bottom:4px">
            <span style="display:inline-flex;align-items:center;justify-content:center;width:42px;height:42px;border-radius:12px;background:linear-gradient(135deg,#7c3aed,#8b5cf6);box-shadow:0 0 18px rgba(139,92,246,0.4);flex-shrink:0;animation:pageIconPulse 3s ease-in-out infinite">
                <i class="fas fa-chart-bar" style="font-size:20px;color:#fff"></i>
            </span>
            <span style="background:linear-gradient(135deg,#f1f5f9,#a78bfa);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent">
                Report
            </span>
        </h2>
        <p style="font-size:12px;color:#94a3b8">Laporan analitik konsumsi energi</p>
    </div>
</div>

{{-- Summary Stats --}}
<div class="report-stats">
    <div class="r-stat">
        <span class="r-stat-val" style="color:var(--yellow)">{{ number_format($totalEnergi, 1) }}</span>
        <div class="r-stat-label">Total Energi (kWh)</div>
    </div>
    <div class="r-stat">
        <span class="r-stat-val" style="color:var(--cyan)">{{ $totalSesi }}</span>
        <div class="r-stat-label">Total Sesi Pengisian</div>
    </div>
    <div class="r-stat">
        <span class="r-stat-val" style="color:var(--green)">{{ $avgEnergi }}</span>
        <div class="r-stat-label">Rata-rata kWh / Sesi</div>
    </div>
</div>

{{-- Filter --}}
<form method="GET" action="{{ route('report.index') }}" class="filter-form">
    <div class="form-group">
        <label class="form-label">Dari Tanggal</label>
        <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
    </div>
    <div class="form-group">
        <label class="form-label">Sampai Tanggal</label>
        <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
    </div>
    <div class="form-group">
        <label class="form-label">Stasiun</label>
        <select name="stasiun_id" class="form-control">
            <option value="">Semua Stasiun</option>
            @foreach($station as $st)
                <option value="{{ $st->id }}" {{ $stasiunId == $st->id ? 'selected' : '' }}>
                    {{ $st->nama_stasiun }} - {{ $st->lokasi }}
                </option>
            @endforeach
        </select>
    </div>
    <div style="display:flex;gap:8px;align-items:flex-end;margin-top:22px">
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-search"></i> Filter
        </button>
        <a href="{{ route('report.index') }}" class="btn btn-secondary">
            <i class="fas fa-sync-alt"></i> Reset
        </a>
    </div>
</form>

{{-- ============================================================
     EXPORT BAR — Premium Redesigned Buttons
     ============================================================ --}}
<div class="export-bar">
    <div class="export-bar-label">
        <i class="fas fa-file-export"></i>
        <span>Export</span>
    </div>

    <div class="export-bar-sep"></div>

    {{-- Export PDF --}}
    <a href="{{ route('report.export-pdf', request()->all()) }}"
       class="btn-export btn-pdf"
       target="_blank">
        <span class="btn-export-icon">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
                <line x1="9" y1="13" x2="15" y2="13"/>
                <line x1="9" y1="17" x2="15" y2="17"/>
            </svg>
        </span>
        <span class="btn-export-text">Export PDF</span>
        <span class="btn-export-badge">PDF</span>
    </a>

    {{-- Export Excel --}}
    <a href="{{ route('report.export-excel', request()->all()) }}"
       class="btn-export btn-excel">
        <span class="btn-export-icon">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="18" height="18" rx="2"/>
                <path d="M3 9h18M9 21V9"/>
            </svg>
        </span>
        <span class="btn-export-text">Export Excel</span>
        <span class="btn-export-badge">XLS</span>
    </a>

    <div class="export-bar-divider"></div>

    {{-- Print --}}
    <button class="btn-export btn-print" onclick="handlePrint(this)">
        <span class="btn-export-icon">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 6 2 18 2 18 9"/>
                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                <rect x="6" y="14" width="12" height="8"/>
            </svg>
        </span>
        <span class="btn-export-text">Print</span>
    </button>
</div>

{{-- Tren Energi per Hari --}}
@php
    // Palet warna cerah & beragam untuk donut + bar konsumsi per stasiun
    $chartColors = [
        '#FFD700', '#00d2ff', '#39d353', '#e05252', '#388bfd',
        '#a371f7', '#fd7e14', '#10b981', '#f472b6', '#22d3ee',
        '#fbbf24', '#84cc16', '#f87171', '#a78bfa', '#0ea5e9',
        '#34d399', '#fb923c', '#ec4899', '#06b6d4', '#eab308',
    ];
    $maxE = $perStation->max('total_energi') ?: 1;

    // Bagi list konsumsi per stasiun jadi 2 kolom (split di tengah)
    $perStationArr = $perStation->values();
    $half          = (int) ceil($perStationArr->count() / 2);
    $colKiri       = $perStationArr->slice(0, $half);
    $colKanan      = $perStationArr->slice($half);
@endphp

<div class="card" style="margin-bottom:24px">
    <div class="card-header">
        <span class="card-title">Tren Energi per Hari</span>
        <span style="font-size:12px;color:var(--text-muted)">{{ $periodeText }}</span>
    </div>
    <div style="padding:16px 20px 20px;height:240px;position:relative">
        <canvas id="trendChart"></canvas>
    </div>
</div>

{{-- Distribusi Energi (Donut) — full width di atas --}}
<div class="card" style="margin-bottom:18px">
    <div class="card-header">
        <span class="card-title"><i class="fas fa-chart-pie" style="color:#FFD700;margin-right:6px"></i>Distribusi Energi per Stasiun</span>
        <span style="font-size:12px;color:var(--text-muted)">{{ $perStation->count() }} stasiun</span>
    </div>
    <div class="card-body" style="padding:20px">
        <div class="chart-box" style="height:340px;position:relative">
            <canvas id="pieChart"></canvas>
        </div>
    </div>
</div>

{{-- Konsumsi per Stasiun — dibagi 2 kolom di bawah donut --}}
<div class="card" style="margin-bottom:24px">
    <div class="card-header">
        <span class="card-title"><i class="fas fa-bolt" style="color:#FFD700;margin-right:6px"></i>Konsumsi per Stasiun</span>
        <span style="font-size:12px;color:var(--text-muted)">Diurutkan dari konsumsi terbesar</span>
    </div>
    <div class="card-body" style="padding:18px 20px">
        @if($perStation->isEmpty())
            <div class="no-data">Tidak ada data</div>
        @else
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(360px,1fr));gap:14px 28px">
            {{-- Kolom kiri --}}
            <div>
                @foreach($colKiri as $idx => $ps)
                <div class="progress-item">
                    <div class="progress-label">
                        <div class="progress-label-left">
                            <span class="progress-dot" style="background:{{ $chartColors[$idx % count($chartColors)] }}"></span>
                            <span class="progress-name">{{ $ps->stasiun->nama_stasiun ?? 'Unknown' }}</span>
                            <span class="progress-sesi">({{ $ps->total_sesi }} sesi)</span>
                        </div>
                        <span class="progress-kwh">{{ number_format($ps->total_energi, 1) }} kWh</span>
                    </div>
                    <div class="progress-track">
                        <div class="progress-fill" style="width:{{ ($ps->total_energi / $maxE * 100) }}%;background:{{ $chartColors[$idx % count($chartColors)] }}"></div>
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Kolom kanan --}}
            <div>
                @foreach($colKanan as $idx2 => $ps)
                @php $globalIdx = $half + $idx2; @endphp
                <div class="progress-item">
                    <div class="progress-label">
                        <div class="progress-label-left">
                            <span class="progress-dot" style="background:{{ $chartColors[$globalIdx % count($chartColors)] }}"></span>
                            <span class="progress-name">{{ $ps->stasiun->nama_stasiun ?? 'Unknown' }}</span>
                            <span class="progress-sesi">({{ $ps->total_sesi }} sesi)</span>
                        </div>
                        <span class="progress-kwh">{{ number_format($ps->total_energi, 1) }} kWh</span>
                    </div>
                    <div class="progress-track">
                        <div class="progress-fill" style="width:{{ ($ps->total_energi / $maxE * 100) }}%;background:{{ $chartColors[$globalIdx % count($chartColors)] }}"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>

{{-- Detail Table --}}
<div class="card">
    <div class="card-header">
        <span class="card-title">
            Detail Log &nbsp;
            <span style="font-size:12px;color:#FFD700;font-weight:600;background:rgba(255,215,0,0.1);border:1px solid rgba(255,215,0,0.25);padding:2px 10px;border-radius:20px;">
                {{ $periodeText }}
            </span>
        </span>
        <span class="record-count"><span>{{ $report->total() }}</span> record</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Stasiun</th>
                    <th>Lokasi</th>
                    <th>Energi</th>
                    <th>Waktu Mulai</th>
                    <th>Waktu Selesai</th>
                    <th>Durasi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($report as $i => $r)
                <tr>
                    <td class="row-no">{{ $report->firstItem() + $i }}</td>
                    <td><span class="station-name">{{ $r->stasiun->nama_stasiun ?? '-' }}</span></td>
                    <td><span class="station-loc">{{ $r->stasiun->lokasi ?? '-' }}</span></td>
                    <td>
                        <span class="energy-val">{{ number_format($r->energi_kwh, 0) }}</span>
                        <span class="energy-unit"> kWh</span>
                    </td>
                    <td>{{ $r->waktu_mulai ? \Carbon\Carbon::parse($r->waktu_mulai)->format('d/m/Y H:i') : '-' }}</td>
                    <td>{{ $r->waktu_selesai ? \Carbon\Carbon::parse($r->waktu_selesai)->format('d/m/Y H:i') : '-' }}</td>
                    <td class="durasi-val">
                        @if($r->waktu_mulai && $r->waktu_selesai)
                            @php
                                $mnt = \Carbon\Carbon::parse($r->waktu_mulai)->diffInMinutes(\Carbon\Carbon::parse($r->waktu_selesai));
                                echo $mnt >= 60 ? floor($mnt/60).'j '.($mnt%60).'m' : $mnt.' menit';
                            @endphp
                        @else —
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7">
                        <div class="no-data">
                            <i class="fas fa-file-alt"></i>
                            Tidak ada data untuk periode ini.
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($report->hasPages())
    @php
        $current = $report->currentPage();
        $last    = $report->lastPage();
        $window  = 2;
        $pages   = [1];
        for ($i = max(2, $current - $window); $i <= min($last - 1, $current + $window); $i++) {
            $pages[] = $i;
        }
        if ($last > 1) $pages[] = $last;
        $pages = array_values(array_unique($pages));
    @endphp
    <div class="pagination-wrap">
        <span class="page-info">
            Halaman <strong style="color:var(--yellow,#FFD700)">{{ $current }}</strong> dari <strong>{{ $last }}</strong>
            · {{ $report->firstItem() }}–{{ $report->lastItem() }} dari {{ number_format($report->total(), 0, ',', '.') }}
        </span>

        <div class="pagination">
            {{-- First --}}
            @if($current > 2)
                <a class="page-link" href="{{ $report->url(1) }}" title="Halaman pertama">«</a>
            @else
                <span class="page-link" style="opacity:0.4">«</span>
            @endif

            {{-- Prev --}}
            @if($report->onFirstPage())
                <span class="page-link" style="opacity:0.4">‹</span>
            @else
                <a class="page-link" href="{{ $report->previousPageUrl() }}">‹</a>
            @endif

            {{-- Numbered with ellipsis --}}
            @php $prev = 0; @endphp
            @foreach($pages as $p)
                @if($prev && $p - $prev > 1)
                    <span class="page-link" style="opacity:0.4;cursor:default">…</span>
                @endif
                @if($p == $current)
                    <span class="page-link active">{{ $p }}</span>
                @else
                    <a class="page-link" href="{{ $report->url($p) }}">{{ $p }}</a>
                @endif
                @php $prev = $p; @endphp
            @endforeach

            {{-- Next --}}
            @if($report->hasMorePages())
                <a class="page-link" href="{{ $report->nextPageUrl() }}">›</a>
            @else
                <span class="page-link" style="opacity:0.4">›</span>
            @endif

            {{-- Last --}}
            @if($current < $last - 1)
                <a class="page-link" href="{{ $report->url($last) }}" title="Halaman terakhir">»</a>
            @else
                <span class="page-link" style="opacity:0.4">»</span>
            @endif
        </div>

        {{-- Jump to page (kalau halaman > 7) — auto submit on Enter / blur --}}
        @if($last > 7)
        <form method="GET" action="" id="jumpReportForm" style="display:inline-flex;align-items:center;gap:6px;margin-left:8px"
              onsubmit="return jumpReportPage(event, {{ $last }})">
            @foreach(request()->except('page') as $k => $v)
                @if(is_array($v))
                    @foreach($v as $vv)<input type="hidden" name="{{ $k }}[]" value="{{ $vv }}">@endforeach
                @else
                    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                @endif
            @endforeach
            <span style="font-size:11px;color:var(--text-muted)">Ke halaman</span>
            <input type="number" name="page" min="1" max="{{ $last }}" value="{{ $current }}"
                   class="form-control" style="width:70px;padding:4px 8px;font-size:12px;text-align:center"
                   onchange="this.form.requestSubmit ? this.form.requestSubmit() : this.form.submit()"
                   onkeydown="if(event.key==='Enter'){event.preventDefault();this.form.requestSubmit ? this.form.requestSubmit() : this.form.submit();}"
                   title="Ketik nomor halaman lalu Enter atau klik di luar">
        </form>
        @endif
    </div>
    @endif
</div>

@endsection

@push('scripts')
<script>
// ===================================================
// DONUT CHART
// ===================================================
const perStation  = @json($perStation);
// 20 warna cerah, cycle untuk semua stasiun (sinkron dengan progress bar)
const chartColors = [
    '#FFD700','#00d2ff','#39d353','#e05252','#388bfd',
    '#a371f7','#fd7e14','#10b981','#f472b6','#22d3ee',
    '#fbbf24','#84cc16','#f87171','#a78bfa','#0ea5e9',
    '#34d399','#fb923c','#ec4899','#06b6d4','#eab308'
];
const labels      = perStation.map(p => p.stasiun ? p.stasiun.nama_stasiun : 'Unknown');
const values      = perStation.map(p => parseFloat(p.total_energi));
// Generate warna untuk SEMUA slice (cycle palette) supaya tidak ada yang abu-abu
const sliceColors = perStation.map((_, i) => chartColors[i % chartColors.length]);

new Chart(document.getElementById('pieChart'), {
    type: 'doughnut',
    data: {
        labels: labels.length ? labels : ['Tidak ada data'],
        datasets: [{
            data:            values.length ? values : [1],
            backgroundColor: sliceColors.length ? sliceColors : ['#475569'],
            borderColor:     '#1c2128',
            borderWidth:     2,
            hoverOffset:     8
        }]
    },
    options: {
        responsive:          true,
        maintainAspectRatio: false,
        cutout:              '62%',
        layout: { padding: 8 },
        plugins: {
            legend: {
                position: 'right',
                align:    'center',
                labels: {
                    color: '#e6edf3',
                    font:  { size: 12, family: 'Inter, sans-serif' },
                    padding: 10,
                    boxWidth: 12,
                    boxHeight: 12,
                    usePointStyle: true,
                    pointStyle: 'circle',
                    generateLabels: function(chart) {
                        const data = chart.data;
                        if (data.labels.length && data.datasets.length) {
                            const total = data.datasets[0].data.reduce((a, b) => a + b, 0);
                            return data.labels.map((label, i) => {
                                const value = data.datasets[0].data[i];
                                const pct   = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return {
                                    text:        `${label}  ·  ${pct}%`,
                                    fillStyle:   data.datasets[0].backgroundColor[i],
                                    strokeStyle: data.datasets[0].backgroundColor[i],
                                    pointStyle:  'circle',
                                    fontColor:   '#e6edf3',
                                    index:       i,
                                };
                            });
                        }
                        return [];
                    }
                }
            },
            tooltip: {
                backgroundColor: '#1c2128',
                borderColor:     '#30363d', borderWidth: 1,
                titleColor:      '#FFD700', bodyColor: '#e6edf3',
                padding:         12,
                callbacks: {
                    label: function(ctx) {
                        const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                        const pct = total > 0 ? ((ctx.parsed / total) * 100).toFixed(1) : 0;
                        return ` ${ctx.label}: ${Number(ctx.parsed).toLocaleString('id-ID')} kWh (${pct}%)`;
                    }
                }
            }
        }
    }
});

// ===================================================
// TREN LINE CHART
// ===================================================
const trendData   = @json($trendData);
const trendLabels = Object.keys(trendData);
const trendValues = Object.values(trendData).map(v => parseFloat(v));

new Chart(document.getElementById('trendChart'), {
    type: 'line',
    data: {
        labels: trendLabels.length ? trendLabels : ['Tidak ada data'],
        datasets: [{
            label: 'Energi (kWh)',
            data:  trendValues.length ? trendValues : [0],
            borderColor: '#FFD700',
            backgroundColor: function(context) {
                const chart = context.chart;
                const { ctx, chartArea } = chart;
                if (!chartArea) return 'rgba(255,215,0,0.08)';
                const gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                gradient.addColorStop(0, 'rgba(255,215,0,0.25)');
                gradient.addColorStop(1, 'rgba(255,215,0,0.01)');
                return gradient;
            },
            borderWidth:          2.5,
            pointBackgroundColor: '#FFD700',
            pointBorderColor:     '#1c2128',
            pointBorderWidth:     2,
            pointRadius:          5,
            pointHoverRadius:     7,
            tension:              0.4,
            fill:                 true
        }]
    },
    options: {
        responsive:          true,
        maintainAspectRatio: false,
        animation:           { duration: 600 },
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#1c2128',
                borderColor:     '#FFD700', borderWidth: 1,
                titleColor:      '#FFD700', bodyColor: '#e6edf3',
                padding:         12, displayColors: false,
                callbacks: {
                    title: ctx => 'Tanggal: ' + ctx[0].label,
                    label: ctx => ' Energi: ' + Number(ctx.parsed.y).toLocaleString() + ' kWh'
                }
            }
        },
        scales: {
            x: {
                grid:  { color: 'rgba(255,255,255,0.05)', drawBorder: false },
                ticks: { color: '#6e7681', font: { size: 11 } }
            },
            y: {
                grid:  { color: 'rgba(255,255,255,0.05)', drawBorder: false },
                ticks: {
                    color: '#6e7681', font: { size: 11 },
                    callback: val => Number(val).toLocaleString() + ' kWh'
                },
                min: 0
            }
        }
    }
});

// ===================================================
// EXPORT BUTTONS — Ripple Effect
// ===================================================
document.querySelectorAll('.btn-export').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
        var ripple = document.createElement('span');
        ripple.className = 'ripple';
        var size = Math.max(this.clientWidth, this.clientHeight);
        var rect = this.getBoundingClientRect();
        ripple.style.width  = size + 'px';
        ripple.style.height = size + 'px';
        ripple.style.left   = (e.clientX - rect.left  - size / 2) + 'px';
        ripple.style.top    = (e.clientY - rect.top   - size / 2) + 'px';
        this.appendChild(ripple);
        ripple.addEventListener('animationend', function() { ripple.remove(); });
    });
});

// ===================================================
// PRINT HANDLER
// ===================================================
function handlePrint(btn) {
    var original = btn.innerHTML;
    btn.innerHTML = '<span class="btn-export-icon"><i class="fas fa-spinner fa-spin"></i></span><span class="btn-export-text">Menyiapkan...</span>';
    btn.style.pointerEvents = 'none';
    setTimeout(function() {
        window.print();
        btn.innerHTML = original;
        btn.style.pointerEvents = '';
    }, 400);
}

// Validasi & auto-submit form jump-to-page di Report
function jumpReportPage(e, lastPage) {
    var input = e.target.querySelector('input[name="page"]');
    var v = parseInt(input.value, 10);
    if (isNaN(v) || v < 1) { input.value = 1; return false; }
    if (v > lastPage)      { input.value = lastPage; }
    return true;
}
</script>
@endpush