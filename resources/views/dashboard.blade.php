@extends('layout.app')
@section('title', 'Dashboard - EV Smart Energy')
@section('page-title', 'Dashboard')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
@endpush

@section('content')

{{-- Page Header --}}
<div class="page-header" style="margin-bottom:20px">
    <div class="page-header-left">
        <h2 style="display:flex;align-items:center;gap:12px;font-family:'Rajdhani',sans-serif;font-size:24px;font-weight:700;margin-bottom:4px">
            <span style="display:inline-flex;align-items:center;justify-content:center;width:42px;height:42px;border-radius:12px;background:linear-gradient(135deg,#1d4ed8,#06b6d4);box-shadow:0 0 18px rgba(6,182,212,0.4);flex-shrink:0;animation:pageIconPulse 3s ease-in-out infinite">
                <i class="fas fa-tachometer-alt" style="font-size:20px;color:#fff"></i>
            </span>
            <span style="background:linear-gradient(135deg,#f1f5f9,#22d3ee);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent">
                Dashboard
            </span>
        </h2>
        <p style="font-size:12px;color:#94a3b8">Monitoring stasiun pengisian EV secara real-time</p>
    </div>
</div>

{{-- STAT CARDS ROW 1: Station --}}
<div class="stats-grid">
    <div class="stat-card c-cyan">
        <div class="stat-label">Total Station</div>
        <div class="stat-value c-cyan">{{ $totalStasiun }}</div>
        <div class="stat-unit">Stasiun terdaftar</div>
        <i class="fas fa-charging-station stat-icon" style="color:var(--cyan)"></i>
    </div>
    <div class="stat-card c-green">
        <div class="stat-label">Active Station</div>
        <div class="stat-value c-green">{{ $stasiunAktif }}</div>
        <div class="stat-unit">Beroperasi aktif</div>
        <i class="fas fa-check-circle stat-icon" style="color:var(--green)"></i>
    </div>
    <div class="stat-card c-red">
        <div class="stat-label">Maintenance</div>
        <div class="stat-value c-red">{{ $maintenance }}</div>
        <div class="stat-unit">Sedang perbaikan</div>
        <i class="fas fa-tools stat-icon" style="color:var(--red)"></i>
    </div>
    <div class="stat-card c-yellow">
        <div class="stat-label">Total Energy</div>
        <div class="stat-value c-yellow">{{ number_format($totalEnergi, 0) }}</div>
        <div class="stat-unit">kWh dikonsumsi</div>
        <i class="fas fa-bolt stat-icon" style="color:var(--yellow)"></i>
    </div>
</div>

{{-- STAT CARDS ROW 2: Unit Charger --}}
<div class="stats-grid" style="margin-bottom:20px">
    <div class="stat-card c-cyan">
        <div class="stat-label">Total Unit Charger</div>
        <div class="stat-value c-cyan">{{ $totalUnit }}</div>
        <div class="stat-unit">Unit terdaftar</div>
        <i class="fas fa-plug stat-icon" style="color:var(--cyan)"></i>
    </div>
    <div class="stat-card c-green">
        <div class="stat-label">Unit Available</div>
        <div class="stat-value c-green">{{ $unitAvailable }}</div>
        <div class="stat-unit">Siap digunakan</div>
        <i class="fas fa-check-circle stat-icon" style="color:var(--green)"></i>
    </div>
    <div class="stat-card c-red">
        <div class="stat-label">Unit Maintenance</div>
        <div class="stat-value c-red">{{ $unitMaintenance }}</div>
        <div class="stat-unit">Sedang perbaikan</div>
        <i class="fas fa-tools stat-icon" style="color:var(--red)"></i>
    </div>
     <div class="stat-card c-yellow">
        <div class="stat-label">Unit In Use</div>
        <div class="stat-value c-yellow">{{ $unitInUse }}</div>
        <div class="stat-unit">Sedang digunakan</div>
        <i class="fas fa-bolt stat-icon" style="color:var(--yellow)"></i>
    </div>
</div>

{{-- TOP GRID: Chart + Station --}}
<div class="dash-grid-top">

    {{-- Energy Chart --}}
    <div class="card">
        <div class="card-header">
            <span class="card-title">Energy Monitoring</span>
            <div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center">
                <div class="chart-filter" style="display:flex;gap:4px">
                    <button class="filter-btn active" onclick="loadChartData('1J',this)">1J</button>
                    <button class="filter-btn" onclick="loadChartData('24J',this)">24J</button>
                    <button class="filter-btn" onclick="loadChartData('7H',this)">7H</button>
                    <button class="filter-btn" onclick="loadChartData('30H',this)">30H</button>
                </div>
                <!-- Custom station picker — tampilan sama dengan filter-btn -->
                <div class="station-picker" id="stationPicker">
                    <button type="button" class="filter-btn station-picker-btn" id="stationPickerBtn" onclick="toggleStationPicker(event)">
                        <span id="stationPickerLabel">All Station</span>
                        <svg width="8" height="8" viewBox="0 0 8 8" style="margin-left:4px;flex-shrink:0"><path fill="currentColor" d="M4 6L0 2h8z"/></svg>
                    </button>
                    <div class="station-picker-dropdown" id="stationPickerDropdown">
                        <div class="sp-item sp-active" data-value="" onclick="selectStation('', 'All Station', this)">All Station</div>
                        @foreach($mapStation as $st)
                        <div class="sp-item" data-value="{{ $st->id }}" onclick="selectStation('{{ $st->id }}', '{{ $st->nama_stasiun }}', this)">{{ $st->nama_stasiun }}</div>
                        @endforeach
                    </div>
                </div>
                <!-- Hidden input untuk value -->
                <input type="hidden" id="stationFilter" value="">
            </div>
        </div>
        <div class="chart-wrap">
            <canvas id="energyChart"></canvas>
        </div>
    </div>

    {{-- Station List --}}
    <div class="card">
        <div class="card-header">
            <span class="card-title">
                Station
                <span style="font-size:11px;color:#94a3b8;font-weight:500;margin-left:6px">
                    · {{ $station->count() }} dari {{ $totalStationCount }}
                </span>
            </span>
            <a href="{{ route('station.index') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Add Record
            </a>
        </div>
        <div class="table-wrap">
            <table class="dash-table station-tbl">
                <colgroup>
                    <col style="width:22%"><col style="width:35%">
                    <col style="width:13%"><col style="width:20%"><col style="width:10%">
                </colgroup>
                <thead>
                    <tr>
                        <th>Station</th>
                        <th>Lokasi</th>
                        <th>Unit</th>
                        <th>Status</th>
                        <th>Del</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($station as $st)
                    <tr>
                        <td>
                            <a href="{{ route('station.show', $st->id) }}" class="station-id">
                                {{ $st->nama_stasiun }}
                            </a>
                        </td>
                        <td class="station-loc">{{ $st->lokasi }}</td>
                        <td style="text-align:center">
                            <span class="station-unit">
                                <i class="fas fa-plug"></i> {{ $st->chargers_count }}
                            </span>
                        </td>
                        <td style="text-align:center">
                            @if($st->status==='Active')
                                <span class="badge badge-active">Active</span>
                            @elseif($st->status==='Maintenance')
                                <span class="badge badge-maintenance">Maintenance</span>
                            @else
                                <span class="badge badge-inactive">Inactive</span>
                            @endif
                        </td>
                        <td style="text-align:center">
                            <form method="POST" action="{{ route('station.destroy',$st->id) }}" style="margin:0;display:inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-del-sm" onclick="return confirm('Hapus stasiun?')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($totalStationCount > $station->count())
        <div style="padding:12px 16px;border-top:1px solid var(--border);text-align:center">
            <a href="{{ route('station.index') }}"
               style="display:inline-flex;align-items:center;gap:8px;font-size:12px;font-weight:600;color:#06b6d4;text-decoration:none;padding:6px 14px;border-radius:6px;background:rgba(6,182,212,0.08);border:1px solid rgba(6,182,212,0.2);transition:all 0.2s"
               onmouseover="this.style.background='rgba(6,182,212,0.18)'" onmouseout="this.style.background='rgba(6,182,212,0.08)'">
                <i class="fas fa-list"></i>
                Lihat semua {{ $totalStationCount }} stasiun
                <i class="fas fa-arrow-right" style="font-size:10px"></i>
            </a>
        </div>
        @endif
    </div>
</div>

{{-- BOTTOM GRID: Log + Map --}}
<div class="dash-grid-bottom">

    {{-- Energy Log --}}
    <div class="card">
        <div class="card-header">
            <span class="card-title">Energy Log</span>
            <button class="btn btn-primary btn-sm" onclick="openModal('addLogModal')">
                <i class="fas fa-plus"></i> Add Record
            </button>
        </div>
        <div class="table-wrap">
            <table class="dash-table log-tbl">
                <colgroup>
                    <col style="width:7%"><col style="width:20%"><col style="width:30%">
                    <col style="width:18%"><col style="width:15%"><col style="width:10%">
                </colgroup>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Stasiun</th>
                        <th>Lokasi</th>
                        <th>Energi</th>
                        <th>Waktu</th>
                        <th>Del</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($log as $loop_item)
                    <tr>
                        <td class="log-no">{{ $loop->iteration }}</td>
                        <td><span class="log-station">{{ $loop_item->stasiun->nama_stasiun ?? '-' }}</span></td>
                        <td class="log-lokasi">{{ $loop_item->stasiun->lokasi ?? '-' }}</td>
                        <td><span class="log-energy">{{ number_format($loop_item->energi_kwh, 0) }} kWh</span></td>
                        <td class="log-time">{{ $loop_item->created_at->format('H:i') }}</td>
                        <td>
                            <form method="POST" action="{{ route('energy-log.destroy',$loop_item->id) }}" style="margin:0;display:inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-del-sm" onclick="return confirm('Hapus log?')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Map --}}
    <div class="card" style="padding:0;overflow:hidden">
        <div class="card-header" style="padding:12px 16px">
            <span class="card-title">Manage Station</span>
            <a href="{{ route('map-view.index') }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-expand"></i> Full Map
            </a>
        </div>
        <div id="map"></div>
        <div class="map-legend">
            <div class="map-legend-item">
                <div class="map-legend-dot" style="background:#22c55e"></div>
                <span>Active</span>
            </div>
            <div class="map-legend-item">
                <div class="map-legend-dot" style="background:#ef4444"></div>
                <span>Maintenance</span>
            </div>
            <div class="map-legend-item">
                <div class="map-legend-dot" style="background:#ffc800"></div>
                <span>Inactive</span>
            </div>
        </div>
    </div>
</div>

{{-- MODAL: Add Log --}}
<div class="modal-overlay" id="addLogModal">
    <div class="modal">
        <button class="btn-close-modal" onclick="closeModal('addLogModal')"><i class="fas fa-times"></i></button>
        <div class="modal-title">Tambah Energy Log</div>
        <form method="POST" action="{{ route('energy-log.store') }}">
            @csrf
            <div class="form-group">
                <label class="form-label">Stasiun</label>
                <select name="stasiun_pengisian_id" class="form-control" required>
                    <option value="">-- Pilih Stasiun --</option>
                    @foreach($mapStation as $st)
                        <option value="{{ $st->id }}">{{ $st->nama_stasiun }} - {{ $st->lokasi }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Energi (kWh)</label>
                <input type="number" step="0.01" name="energi_kwh" class="form-control" required min="0" placeholder="contoh: 36.5">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Waktu Mulai</label>
                    <input type="datetime-local" name="waktu_mulai" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Waktu Selesai</label>
                    <input type="datetime-local" name="waktu_selesai" class="form-control" required>
                </div>
            </div>
            <div class="modal-action">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addLogModal')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
// === Auto Refresh Dashboard tiap 60 detik ===
// (live monitoring vibes; gak terlalu sering supaya gak ganggu user)
(function setupAutoRefresh() {
    const REFRESH_INTERVAL = 60000; // 60 detik
    let countdown = REFRESH_INTERVAL / 1000;
    let timerEl = null;

    // Cek apakah user lagi pakai modal/form, jangan refresh
    function userIsBusy() {
        const openModal = document.querySelector('.modal-overlay[style*="flex"], .modal-overlay.active');
        const focusedInput = document.activeElement &&
            ['INPUT','TEXTAREA','SELECT'].includes(document.activeElement.tagName);
        return !!openModal || focusedInput;
    }

    function tick() {
        if (userIsBusy()) {
            countdown = REFRESH_INTERVAL / 1000;
            return;
        }
        countdown--;
        if (timerEl) timerEl.textContent = countdown + 's';
        if (countdown <= 0) {
            window.location.reload();
        }
    }

    setInterval(tick, 1000);
})();

const chartData = @json($chartData);
const labels    = chartData.map(d => d.label ?? (String(d.hour).padStart(2,'0')+':00'));
const values    = chartData.map(d => parseFloat(d.total));
initEnergyChart(labels, values);

const stationData = @json($mapStation);
initMap(stationData);

// ===== CUSTOM STATION PICKER =====
function toggleStationPicker(e) {
    e.stopPropagation();
    document.getElementById('stationPickerDropdown').classList.toggle('open');
}
function selectStation(value, label, el) {
    // Update label tombol
    document.getElementById('stationPickerLabel').textContent = label;
    // Update hidden input
    document.getElementById('stationFilter').value = value;
    // Update active state
    document.querySelectorAll('.sp-item').forEach(i => i.classList.remove('sp-active'));
    el.classList.add('sp-active');
    // Update tombol warna: kalau bukan All Station, tetap emas aktif
    const btn = document.getElementById('stationPickerBtn');
    if (value !== '') {
        btn.classList.add('active');
    } else {
        btn.classList.remove('active');
    }
    // Tutup dropdown
    document.getElementById('stationPickerDropdown').classList.remove('open');
    // Reload chart
    const activeBtn = document.querySelector('.filter-btn.active:not(.station-picker-btn)');
    const range = activeBtn ? activeBtn.textContent.trim() : '1J';
    loadChartData(range, activeBtn);
}
// Tutup dropdown kalau klik di luar
document.addEventListener('click', function() {
    document.getElementById('stationPickerDropdown')?.classList.remove('open');
});
</script>
@endpush