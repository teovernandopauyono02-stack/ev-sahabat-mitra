@extends('layout.app')
@section('title', 'Analytics')
@section('page-title', 'Analytics')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/analytics.css') }}">
@endpush

@section('content')

{{-- ===== HEADER ===== --}}
<div class="an-header">
    <div class="an-header-left">
        <h2 style="display:flex;align-items:center;gap:12px;font-family:'Rajdhani',sans-serif;font-size:24px;font-weight:700;margin-bottom:4px">
            <span style="display:inline-flex;align-items:center;justify-content:center;width:42px;height:42px;border-radius:12px;background:linear-gradient(135deg,#d97706,#FFD700);box-shadow:0 0 18px rgba(255,215,0,0.4);flex-shrink:0;animation:pageIconPulse 3s ease-in-out infinite">
                <i class="fas fa-chart-line" style="font-size:20px;color:#fff"></i>
            </span>
            <span style="background:linear-gradient(135deg,#f1f5f9,#FFD700);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent">
                Analytics
            </span>
        </h2>
        <p style="font-size:12px;color:#94a3b8">Analitik konsumsi energi skala besar dengan algoritma statistik</p>
    </div>
    <div class="an-header-right">
        <a href="{{ route('station.index') }}" class="an-btn an-btn-gold"><i class="fas fa-charging-station"></i> Lihat Station</a>
        <a href="{{ route('energy-log.index') }}" class="an-btn an-btn-cyan"><i class="fas fa-bolt"></i> Lihat Energy Log</a>
        <button onclick="window.location.reload()" class="an-btn an-btn-gold"><i class="fas fa-sync-alt"></i> Refresh</button>
    </div>
</div>

{{-- ===== ROW 1: STAT CARDS ===== --}}
<div class="an-stats">
    <div class="an-stat s1">
        <i class="fas fa-database an-stat-ico"></i>
        <div class="an-stat-lbl">Total Sesi Pengisian</div>
        <div class="an-stat-val">{{ number_format($stats['total_sesi']) }}<span class="an-stat-unit">sesi</span></div>
        <div class="an-stat-sub">📈 Akumulasi sepanjang masa</div>
    </div>
    <div class="an-stat s2">
        <i class="fas fa-bolt an-stat-ico"></i>
        <div class="an-stat-lbl">Total Energi Tersalurkan</div>
        <div class="an-stat-val">{{ number_format($stats['total_energi'], 0, ',', '.') }}<span class="an-stat-unit">kWh</span></div>
        <div class="an-stat-sub">⚡ Seluruh stasiun aktif</div>
    </div>
    <div class="an-stat s3">
        <i class="fas fa-chart-area an-stat-ico"></i>
        <div class="an-stat-lbl">Rata-rata per Sesi</div>
        <div class="an-stat-val">{{ number_format($stats['avg_energi'], 1, ',', '.') }}<span class="an-stat-unit">kWh</span></div>
        <div class="an-stat-sub">σ = {{ number_format($stats['stddev_energi'], 2) }} (Std. Deviasi)</div>
    </div>
    <div class="an-stat s4">
        <i class="fas fa-shield-virus an-stat-ico"></i>
        <div class="an-stat-lbl">Anomali Terdeteksi</div>
        <div class="an-stat-val">{{ count($anomalies['anomalies']) }}<span class="an-stat-unit">data</span></div>
        <div class="an-stat-sub">🔍 Z-score |z| &gt; 2σ dari mean</div>
    </div>
</div>

{{-- ===== ROW 2: KOMPARASI BULAN ===== --}}
<div class="an-compare">
    <div class="an-cmp-side left">
        <div>
            <div class="an-cmp-lbl">{{ $comparison['last_month']['label'] }}</div>
            <div class="an-cmp-val">{{ number_format($comparison['last_month']['kwh'], 0, ',', '.') }}</div>
            <div class="an-cmp-sub">kWh &nbsp;·&nbsp; {{ number_format($comparison['last_month']['sesi']) }} sesi</div>
            <div style="font-size:10px;color:#64748b;margin-top:3px">
                ~{{ number_format($comparison['last_month']['kwh_per_day'], 1) }} kWh/hari ({{ $comparison['last_month']['days_counted'] }} hari)
            </div>
        </div>
    </div>
    <div class="an-cmp-mid {{ $comparison['trend'] }}">
        @if($comparison['trend'] === 'naik')
            <div class="an-cmp-delta"><i class="fas fa-arrow-up"></i> +{{ $comparison['delta_pct_per_day'] }}%</div>
        @elseif($comparison['trend'] === 'turun')
            <div class="an-cmp-delta"><i class="fas fa-arrow-down"></i> {{ $comparison['delta_pct_per_day'] }}%</div>
        @else
            <div class="an-cmp-delta"><i class="fas fa-equals"></i> {{ $comparison['delta_pct_per_day'] }}%</div>
        @endif
        <div class="an-cmp-trend">{{ ucfirst($comparison['trend']) }}</div>
        <div style="font-size:9px;color:#475569;margin-top:4px">per hari<br>(rata-rata)</div>
    </div>
    <div class="an-cmp-side right">
        <div style="text-align:right">
            <div class="an-cmp-lbl">{{ $comparison['this_month']['label'] }}</div>
            <div class="an-cmp-val">{{ number_format($comparison['this_month']['kwh'], 0, ',', '.') }}</div>
            <div class="an-cmp-sub">kWh &nbsp;·&nbsp; {{ number_format($comparison['this_month']['sesi']) }} sesi</div>
            <div style="font-size:10px;color:#64748b;margin-top:3px">
                ~{{ number_format($comparison['this_month']['kwh_per_day'], 1) }} kWh/hari ({{ $comparison['this_month']['days_counted'] }} hari berjalan)
            </div>
            <div style="font-size:10px;color:#64748b;margin-top:3px">
                <i class="fas fa-info-circle" style="margin-right:3px"></i>Data s/d {{ now()->setTimezone('Asia/Jakarta')->format('d M Y') }}
            </div>
        </div>
    </div>
</div>

{{-- ===== ROW 3: HEATMAP FULL WIDTH + TOP STATIONS ===== --}}

{{-- HEATMAP FULL WIDTH --}}
<div class="an-card" style="margin-bottom:14px">
    <div class="an-card-hdr" style="flex-wrap:wrap;gap:10px">
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
            <span class="an-card-title"><i class="fas fa-fire"></i> Heatmap Konsumsi Bulan {{ $heatmap['month_label'] }}</span>
            <span class="an-card-sub">{{ $heatmap['total_days'] }} hari × 24 jam</span>
        </div>

        {{-- Navigasi bulan --}}
        <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap">
            <a href="{{ route('analytics.index', ['month' => $heatmap['prev_month']]) }}#heatmap"
               style="display:inline-flex;align-items:center;gap:6px;padding:7px 14px;background:rgba(6,182,212,0.1);border:1px solid rgba(6,182,212,0.25);color:#22d3ee;border-radius:8px;font-size:11px;font-weight:700;text-decoration:none;transition:all 0.2s"
               onmouseover="this.style.background='rgba(6,182,212,0.22)'" onmouseout="this.style.background='rgba(6,182,212,0.1)'"
               title="Lihat bulan sebelumnya">
                <i class="fas fa-chevron-left" style="font-size:9px"></i> Bulan Sebelumnya
            </a>

            {{-- Dropdown pilih bulan langsung --}}
            <form method="GET" action="{{ route('analytics.index') }}" style="display:inline-flex;align-items:center;gap:0;margin:0">
                <input type="month" name="month" value="{{ $heatmap['month_value'] }}"
                    onchange="this.form.submit()"
                    style="padding:7px 10px;background:rgba(255,215,0,0.1);border:1px solid rgba(255,215,0,0.3);color:#FFD700;border-radius:8px;font-size:11px;font-weight:700;font-family:inherit;cursor:pointer;color-scheme:dark"
                    title="Pilih bulan tertentu">
            </form>

            @if(!$heatmap['is_current_month'])
                <a href="{{ route('analytics.index') }}#heatmap"
                   style="display:inline-flex;align-items:center;gap:6px;padding:7px 14px;background:rgba(255,215,0,0.12);border:1px solid rgba(255,215,0,0.35);color:#FFD700;border-radius:8px;font-size:11px;font-weight:700;text-decoration:none;transition:all 0.2s"
                   onmouseover="this.style.background='rgba(255,215,0,0.22)'" onmouseout="this.style.background='rgba(255,215,0,0.12)'"
                   title="Kembali ke bulan ini">
                    <i class="fas fa-calendar-day" style="font-size:9px"></i> Bulan Ini
                </a>
            @endif

            <a href="{{ route('analytics.index', ['month' => $heatmap['next_month']]) }}#heatmap"
               style="display:inline-flex;align-items:center;gap:6px;padding:7px 14px;background:rgba(6,182,212,0.1);border:1px solid rgba(6,182,212,0.25);color:#22d3ee;border-radius:8px;font-size:11px;font-weight:700;text-decoration:none;transition:all 0.2s"
               onmouseover="this.style.background='rgba(6,182,212,0.22)'" onmouseout="this.style.background='rgba(6,182,212,0.1)'"
               title="Lihat bulan berikutnya">
                Bulan Berikutnya <i class="fas fa-chevron-right" style="font-size:9px"></i>
            </a>
        </div>
    </div>
    <div id="heatmap" class="hm-wrap">
        <table class="hm-table" style="width:100%">
            <thead>
                <tr>
                    <th style="width:120px;text-align:left;padding-left:6px;font-size:10px">Tanggal</th>
                    @foreach($heatmap['hours'] as $hi => $h)
                    <th style="min-width:30px;text-align:center;font-size:9px">{{ $h }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @php
                    // Distribusi nilai sesi (>0) untuk percentile
                    $allSesi = [];
                    foreach ($heatmap['matrix'] as $row) {
                        foreach ($row as $cell) {
                            if (!($cell['future'] ?? false) && $cell['sesi'] > 0) {
                                $allSesi[] = $cell['sesi'];
                            }
                        }
                    }
                    sort($allSesi);
                    $totalCells = count($allSesi);

                    $thresholds = [];
                    if ($totalCells > 0) {
                        $pcts = [0.10, 0.25, 0.40, 0.55, 0.70, 0.82, 0.92];
                        foreach ($pcts as $p) {
                            $idx = (int) floor($p * ($totalCells - 1));
                            $thresholds[] = $allSesi[$idx] ?? 0;
                        }
                    }

                    $tierColors = [
                        ['bg' => '#1e3a8a', 'fc' => '#bfdbfe'],
                        ['bg' => '#2563eb', 'fc' => '#dbeafe'],
                        ['bg' => '#0891b2', 'fc' => '#cffafe'],
                        ['bg' => '#0d9488', 'fc' => '#ccfbf1'],
                        ['bg' => '#16a34a', 'fc' => '#dcfce7'],
                        ['bg' => '#ca8a04', 'fc' => '#fef9c3'],
                        ['bg' => '#ea580c', 'fc' => '#ffedd5'],
                        ['bg' => '#dc2626', 'fc' => '#ffffff'],
                    ];
                @endphp
                @foreach($heatmap['dates'] as $di => $dateInfo)
                @php
                    $rowBg = $dateInfo['is_today'] ? 'background:rgba(255,215,0,0.08);' :
                             ($dateInfo['is_weekend'] ? 'background:rgba(168,85,247,0.04);' : '');
                @endphp
                <tr style="{{ $rowBg }}">
                    <td class="hm-day" style="font-size:10.5px;padding:4px 8px;font-weight:700;
                        color:{{ $dateInfo['is_today'] ? '#FFD700' : ($dateInfo['is_weekend'] ? '#c084fc' : '#cbd5e1') }};
                        white-space:nowrap">
                        {{ $dateInfo['label'] }}
                        @if($dateInfo['is_today'])
                            <span style="font-size:8px;font-weight:800;background:#FFD700;color:#0d1b3e;padding:1px 5px;border-radius:8px;margin-left:4px">HARI INI</span>
                        @endif
                    </td>
                    @foreach($heatmap['matrix'][$di] as $hi => $cell)
                        @php
                            $sesi   = $cell['sesi'];
                            $future = $cell['future'] ?? false;

                            if ($future) {
                                // Belum tiba — abu silang
                                $bg = 'transparent';
                                $fc = '#475569';
                                $extraStyle = 'background-image:repeating-linear-gradient(-45deg,rgba(100,116,139,0.18) 0,rgba(100,116,139,0.18) 2px,transparent 2px,transparent 6px);border:1px dashed rgba(100,116,139,0.25);';
                                $cellTitle  = $dateInfo['label'] . ' · ' . $heatmap['hours'][$hi] . ':00 → Belum tiba';
                                $cellText   = '';
                            } elseif ($sesi <= 0) {
                                $bg = '#0f1c30';
                                $fc = '#1e3a5f';
                                $extraStyle = '';
                                $cellTitle  = $dateInfo['label'] . ' · ' . $heatmap['hours'][$hi] . ':00 → Tidak ada sesi';
                                $cellText   = '';
                            } else {
                                $tier = 0;
                                foreach ($thresholds as $i => $t) {
                                    if ($sesi <= $t) { $tier = $i; break; }
                                    $tier = $i + 1;
                                }
                                $tier = min($tier, 7);
                                $bg   = $tierColors[$tier]['bg'];
                                $fc   = $tierColors[$tier]['fc'];
                                $extraStyle = '';
                                $cellTitle  = $dateInfo['label'] . ' · ' . $heatmap['hours'][$hi] . ':00 → ' . $sesi . ' sesi · ' . $cell['kwh'] . ' kWh';
                                $cellText   = $sesi;
                            }
                        @endphp
                        <td class="hm-cell"
                            style="background:{{ $bg }};color:{{ $fc }};width:auto;height:24px;font-size:9px;font-weight:700;{{ $extraStyle }}"
                            title="{{ $cellTitle }}">
                            {{ $cellText }}
                        </td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Legend warna heatmap --}}
        <div style="display:flex;align-items:center;gap:6px;margin-top:14px;padding:10px 14px;background:rgba(15,28,48,0.5);border-radius:8px;flex-wrap:wrap;font-size:10px;color:#94a3b8">
            <span style="font-weight:700;color:#cbd5e1">Skala:</span>
            <span style="display:inline-flex;align-items:center;gap:4px"><span style="width:14px;height:14px;border-radius:3px;background:#0f1c30;border:1px solid #1e3a5f"></span>Kosong</span>
            <span style="display:inline-flex;align-items:center;gap:4px"><span style="width:14px;height:14px;border-radius:3px;background-image:repeating-linear-gradient(-45deg,rgba(100,116,139,0.4) 0,rgba(100,116,139,0.4) 2px,transparent 2px,transparent 6px);border:1px dashed rgba(100,116,139,0.5)"></span>Belum Tiba</span>
            <span style="display:inline-flex;align-items:center;gap:4px"><span style="width:14px;height:14px;border-radius:3px;background:#1e3a8a"></span>Sangat Sepi</span>
            <span style="display:inline-flex;align-items:center;gap:4px"><span style="width:14px;height:14px;border-radius:3px;background:#2563eb"></span>Sepi</span>
            <span style="display:inline-flex;align-items:center;gap:4px"><span style="width:14px;height:14px;border-radius:3px;background:#0891b2"></span>Rendah</span>
            <span style="display:inline-flex;align-items:center;gap:4px"><span style="width:14px;height:14px;border-radius:3px;background:#0d9488"></span>Sedang</span>
            <span style="display:inline-flex;align-items:center;gap:4px"><span style="width:14px;height:14px;border-radius:3px;background:#16a34a"></span>Aktif</span>
            <span style="display:inline-flex;align-items:center;gap:4px"><span style="width:14px;height:14px;border-radius:3px;background:#ca8a04"></span>Cukup Ramai</span>
            <span style="display:inline-flex;align-items:center;gap:4px"><span style="width:14px;height:14px;border-radius:3px;background:#ea580c"></span>Ramai</span>
            <span style="display:inline-flex;align-items:center;gap:4px"><span style="width:14px;height:14px;border-radius:3px;background:#dc2626"></span>Sangat Ramai</span>
        </div>
    </div>
</div>

{{-- TOP STATIONS FULL WIDTH --}}
<div class="an-card" style="margin-bottom:14px">
    <div class="an-card-hdr">
        <span class="an-card-title"><i class="fas fa-chart-bar"></i> Top 10 Stasiun Paling Aktif</span>
        <span class="an-card-sub">30 hari terakhir · Diurutkan berdasarkan total konsumsi energi</span>
    </div>
    {{-- Urutan 1-10 lurus ke bawah, 1 kolom --}}
    <div style="display:flex;flex-direction:column;gap:7px">
        @forelse($topStations as $i => $st)
        @php
            // Label aktivitas berdasarkan ranking pemakaian energi
            if ($i === 0)      { $rankClass = 'r-danger';  $warnColor = '#34d399'; $warnText = 'Sangat Aktif'; }
            elseif ($i === 1)  { $rankClass = 'r-danger';  $warnColor = '#34d399'; $warnText = 'Aktif'; }
            elseif ($i === 2)  { $rankClass = 'r-danger';  $warnColor = '#fbbf24'; $warnText = 'Cukup Aktif'; }
            elseif ($i < 5)    { $rankClass = 'r-warning'; $warnColor = '#94a3b8'; $warnText = 'Normal'; }
            else               { $rankClass = 'rn';        $warnColor = '#64748b'; $warnText = ''; }
        @endphp
        <a href="{{ route('station.index') }}?highlight={{ $st['id'] }}&tier={{ urlencode($warnText) }}" class="ts-item" style="order:{{ $i }}">
            <div class="ts-rank {{ $rankClass }}">{{ $i+1 }}</div>
            <div class="ts-info">
                <div class="ts-name">{{ $st['nama'] }}
                    @if($warnText)
                        <span style="font-size:9px;color:{{ $warnColor }};margin-left:6px;font-weight:600">{{ $warnText }}</span>
                    @endif
                </div>
                <div class="ts-meta">{{ $st['lokasi'] }} · {{ number_format($st['total_sesi']) }} sesi · avg {{ number_format($st['avg_kwh'],1) }} kWh</div>
            </div>
            <div class="ts-kwh">
                <div class="ts-kwh-val">{{ number_format($st['total_kwh'],0,',','.') }}</div>
                <div class="ts-kwh-lbl">kWh</div>
            </div>
        </a>
        @empty
        <div style="text-align:center;padding:24px;color:#475569;font-size:12px;grid-column:span 2">Belum ada data</div>
        @endforelse
    </div>
</div>

{{-- ===== ROW 4: ANOMALY DETECTION ===== --}}
<div class="an-card an-anom">
    <div class="an-card-hdr">
        <span class="an-card-title"><i class="fas fa-shield-virus"></i> Anomaly Detection (Z-Score)</span>
        <span class="an-card-sub">Algoritma: |z| &gt; 2σ · {{ count($anomalies['anomalies'] ?? []) }} anomali dari {{ number_format($anomalies['total_count'] ?? 0) }} data</span>
    </div>

    <div class="anom-bar">
        <div class="anom-bar-item">
            <div class="anom-bar-lbl">Mean (μ)</div>
            <div class="anom-bar-val cyan">{{ number_format($anomalies['mean'] ?? 0, 2) }} kWh</div>
        </div>
        <div class="anom-bar-item">
            <div class="anom-bar-lbl">Std. Deviasi (σ)</div>
            <div class="anom-bar-val yellow">{{ number_format($anomalies['stddev'],2) }}</div>
        </div>
        <div class="anom-bar-item">
            <div class="anom-bar-lbl">Threshold High (μ+2σ)</div>
            <div class="anom-bar-val red">{{ number_format($anomalies['threshold_high'],2) }} kWh</div>
        </div>
        <div class="anom-bar-item">
            <div class="anom-bar-lbl">Threshold Low (μ−2σ)</div>
            <div class="anom-bar-val yellow">{{ number_format($anomalies['threshold_low'],2) }} kWh</div>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th style="width:36px">No</th>
                    <th>Stasiun</th>
                    <th>Lokasi</th>
                    <th style="text-align:right">Energi (kWh)</th>
                    <th style="text-align:center">Z-Score</th>
                    <th style="text-align:center">Tipe</th>
                    <th>Waktu</th>
                </tr>
            </thead>
            <tbody>
                @forelse(array_slice($anomalies['anomalies'], 0, 10) as $i => $a)
                @php
                    $anomalyUrl = route('energy-log.index', [
                        'stasiun_pengisian_id' => $a['stasiun_id'] ?? '',
                        'highlight_anomaly'    => $a['id'],
                        'anomaly_threshold'    => $anomalies['threshold_high'],
                        'anomaly_threshold_low'=> $anomalies['threshold_low'],
                        'severity'             => $a['severity'] ?? 'warning',
                    ]);
                @endphp
                <tr class="anom-row"
                    onclick="window.location='{{ $anomalyUrl }}'"
                    title="Klik → Energy Log akan highlight baris anomali ini dengan kedip {{ $a['severity'] === 'critical' ? 'MERAH' : 'KUNING' }}">
                    <td style="color:#94a3b8">{{ $i+1 }}</td>
                    <td style="color:#FFD700;font-weight:700">{{ $a['stasiun'] }}</td>
                    <td style="color:#f1f5f9">{{ $a['lokasi'] }}</td>
                    <td style="text-align:right;font-family:'Rajdhani',sans-serif;font-size:14px;font-weight:700;color:#fff">{{ number_format($a['energi_kwh'],2) }}</td>
                    <td style="text-align:center">
                        <span class="z-badge z-{{ $a['severity'] }}">{{ $a['z_score'] > 0 ? '+' : '' }}{{ $a['z_score'] }}σ</span>
                    </td>
                    <td style="text-align:center;font-size:11px;font-weight:600;color:{{ $a['severity']==='critical'?'#f87171':'#fbbf24' }}">{{ $a['tipe'] }}</td>
                    <td style="color:#94a3b8;font-size:11px">
                        {{ \Carbon\Carbon::parse($a['waktu_mulai'])->format('d/m/Y H:i') }}
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" style="text-align:center;padding:28px;color:#475569">✅ Tidak ada anomali terdeteksi</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ===== ROW 5: FORECAST CHART ===== --}}
<div class="an-card">
    <div class="an-card-hdr">
        <span class="an-card-title"><i class="fas fa-chart-line"></i> Forecast 7 Hari Ke Depan</span>
        <span class="an-card-sub">{{ $forecast['method'] }}</span>
    </div>
    <div class="fc-info">
        <span><i class="fas fa-info-circle"></i> Prediksi SMA berdasarkan rata-rata <strong>{{ $forecast['window_days'] }} hari terakhir</strong> = <strong>{{ number_format($forecast['sma_value'],2) }} kWh/hari</strong></span>
        <span style="color:#64748b">30 hari historis + 7 hari prediksi</span>
    </div>

    {{-- Keterangan legend --}}
    <div style="display:flex;gap:20px;margin-bottom:12px;flex-wrap:wrap">
        <div style="display:flex;align-items:center;gap:8px;padding:6px 12px;background:rgba(6,182,212,0.08);border:1px solid rgba(6,182,212,0.2);border-radius:8px">
            <div style="width:28px;height:3px;background:#22d3ee;border-radius:2px"></div>
            <div>
                <div style="font-size:11px;font-weight:700;color:#22d3ee">Aktual (Historis)</div>
                <div style="font-size:10px;color:#94a3b8">Data nyata yang sudah terjadi — 30 hari ke belakang</div>
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:8px;padding:6px 12px;background:rgba(139,92,246,0.08);border:1px solid rgba(139,92,246,0.2);border-radius:8px">
            <div style="width:28px;height:3px;background:#a78bfa;border-radius:2px;border-top:2px dashed #a78bfa;background:none"></div>
            <div>
                <div style="font-size:11px;font-weight:700;color:#a78bfa">Prediksi SMA (Simple Moving Average)</div>
                <div style="font-size:10px;color:#94a3b8">Perkiraan 7 hari ke depan berdasarkan rata-rata historis</div>
            </div>
        </div>
    </div>
    <div style="position:relative;height:200px">
        <canvas id="forecastChart"></canvas>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
    const history  = @json($forecast['history']);
    const forecast = @json($forecast['forecast']);

    const labels = [
        ...history.map(h => h.tanggal),
        ...forecast.map(f => f.tanggal),
    ];
    const aktual = [
        ...history.map(h => h.aktual),
        ...forecast.map(() => null),
    ];
    const pred = [
        ...history.map(() => null),
        ...forecast.map(f => f.prediksi),
    ];

    // connector: last actual → first forecast
    if (history.length > 0 && forecast.length > 0) {
        pred[history.length - 1] = aktual[history.length - 1];
    }

    const ctx = document.getElementById('forecastChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: 'Aktual — Data nyata 30 hari lalu',
                    data: aktual,
                    borderColor: '#22d3ee',
                    backgroundColor: 'rgba(6,182,212,0.1)',
                    fill: true, tension: 0.4, spanGaps: false,
                    pointRadius: 2, pointHoverRadius: 5,
                    borderWidth: 2,
                },
                {
                    label: 'Prediksi SMA — Perkiraan 7 hari ke depan',
                    data: pred,
                    borderColor: '#a78bfa',
                    backgroundColor: 'rgba(139,92,246,0.08)',
                    borderDash: [7, 4],
                    fill: true, tension: 0.4, spanGaps: true,
                    pointRadius: 3, pointStyle: 'rectRot', pointHoverRadius: 6,
                    borderWidth: 2,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { labels: { color: '#94a3b8', font: { size: 11 }, boxWidth: 14 } },
                tooltip: {
                    callbacks: {
                        label: c => `${c.dataset.label}: ${c.parsed.y?.toLocaleString('id-ID')} kWh`
                    }
                }
            },
            scales: {
                x: {
                    ticks: { color: '#64748b', maxRotation: 45, minRotation: 45, font: { size: 10 } },
                    grid:  { color: 'rgba(255,255,255,0.04)' }
                },
                y: {
                    ticks: { color: '#64748b', callback: v => v.toLocaleString('id-ID') + ' kWh', font: { size: 10 } },
                    grid:  { color: 'rgba(255,255,255,0.04)' }
                }
            }
        }
    });
})();
</script>
@endpush
