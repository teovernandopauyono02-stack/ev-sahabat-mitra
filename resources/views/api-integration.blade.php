@extends('layout.app')

@section('title', 'API Integration')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/api-integration.css') }}">
@endpush

@section('content')
<div class="api-page">

    {{-- ====== HEADER ====== --}}
    <div class="api-page-header">
        <div>
            <h2 style="display:flex;align-items:center;gap:12px;font-family:'Rajdhani',sans-serif;font-size:24px;font-weight:700;margin-bottom:4px">
                <span style="display:inline-flex;align-items:center;justify-content:center;width:42px;height:42px;border-radius:12px;background:linear-gradient(135deg,#0891b2,#06b6d4);box-shadow:0 0 18px rgba(6,182,212,0.4);flex-shrink:0;animation:pageIconPulse 3s ease-in-out infinite">
                    <i class="fas fa-plug" style="font-size:20px;color:#fff"></i>
                </span>
                <span style="background:linear-gradient(135deg,#f1f5f9,#22d3ee);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent">
                    API Integration
                </span>
            </h2>
            <p style="font-size:12px;color:#94a3b8">Integrasi data real-time dari perangkat solar panel</p>
        </div>
        <div class="api-header-actions">
            <button class="api-btn api-btn-secondary" onclick="apiRefreshAll()">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
            <button class="api-btn api-btn-primary" onclick="apiOpenModal()">
                <i class="fas fa-plus-circle"></i> Tambah Koneksi API
            </button>
        </div>
    </div>

    {{-- ====== STAT CARDS ====== --}}
    <div class="api-stats">
        <div class="api-stat-card">
            <div class="api-stat-icon blue"><i class="fas fa-link"></i></div>
            <div>
                <div class="api-stat-label">Total Koneksi</div>
                <div class="api-stat-value" id="statTotal">–</div>
            </div>
        </div>
        <div class="api-stat-card">
            <div class="api-stat-icon green"><i class="fas fa-check-circle"></i></div>
            <div>
                <div class="api-stat-label">Koneksi Aktif</div>
                <div class="api-stat-value green" id="statAktif">–</div>
            </div>
        </div>
        <div class="api-stat-card">
            <div class="api-stat-icon" style="background:rgba(59,130,246,.15);color:#3b82f6"><i class="fas fa-bolt"></i></div>
            <div>
                <div class="api-stat-label">Real-time Power</div>
                <div class="api-stat-value" style="color:#3b82f6">
                    <span id="statPower">–</span>
                    <span style="font-size:13px;color:#6b7280;font-weight:500">kW</span>
                </div>
            </div>
        </div>
        <div class="api-stat-card">
            <div class="api-stat-icon yellow"><i class="fas fa-sun"></i></div>
            <div>
                <div class="api-stat-label">Today Yield</div>
                <div class="api-stat-value yellow">
                    <span id="statKwh">–</span>
                    <span style="font-size:13px;color:#6b7280;font-weight:500">kWh</span>
                </div>
            </div>
        </div>
        <div class="api-stat-card">
            <div class="api-stat-icon purple"><i class="fas fa-database"></i></div>
            <div>
                <div class="api-stat-label">Total Data Sync</div>
                <div class="api-stat-value" id="statData">–</div>
            </div>
        </div>
    </div>

    {{-- ====================================================
         FILTER PERIODE — Quick presets + Custom range
         ==================================================== --}}
    <div class="periode-filter">
        <div class="periode-filter-label">
            <i class="fas fa-calendar-alt"></i>
            <span>Periode Laporan</span>
        </div>

        <div class="periode-filter-presets">
            <button type="button" class="periode-btn" data-periode="today" onclick="apiSetPeriode('today', this)">
                Hari Ini
            </button>
            <button type="button" class="periode-btn active" data-periode="7days" onclick="apiSetPeriode('7days', this)">
                7 Hari
            </button>
            <button type="button" class="periode-btn" data-periode="30days" onclick="apiSetPeriode('30days', this)">
                30 Hari
            </button>
            <button type="button" class="periode-btn" data-periode="year" onclick="apiSetPeriode('year', this)">
                Tahun Ini
            </button>
            <button type="button" class="periode-btn" data-periode="custom" onclick="apiSetPeriode('custom', this)">
                <i class="fas fa-calendar"></i> Custom
            </button>
        </div>

        <div class="periode-filter-custom" id="periodeCustom" style="display:none">
            <input type="date" id="periodeStart" class="periode-date">
            <span class="periode-sep">s/d</span>
            <input type="date" id="periodeEnd" class="periode-date">
        </div>

        <div class="periode-filter-info">
            <span class="periode-info-text" id="periodeInfo">7 hari terakhir</span>
        </div>
    </div>

    {{-- ====================================================
         EXPORT BAR — Premium (sama persis seperti Report)
         ==================================================== --}}
    <div class="export-bar">
        <div class="export-bar-label">
            <i class="fas fa-file-export"></i>
            <span>Export</span>
        </div>

        <div class="export-bar-sep"></div>

        {{-- Export PDF --}}
        <button class="btn-export btn-pdf" onclick="apiExportPdf()">
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
        </button>

        {{-- Export Excel --}}
        <button class="btn-export btn-excel" onclick="apiExportExcel()">
            <span class="btn-export-icon">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="18" height="18" rx="2"/>
                    <path d="M3 9h18M9 21V9"/>
                </svg>
            </span>
            <span class="btn-export-text">Export Excel</span>
            <span class="btn-export-badge">XLS</span>
        </button>

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

    {{-- ====== DAFTAR KONEKSI ====== --}}
    <div class="api-card">
        <div class="api-card-header">
            <h3><i class="fas fa-list-ul" style="color:#3b82f6"></i> Daftar Koneksi API</h3>
            <button class="api-btn api-btn-success api-btn-sm" id="btnSyncAll" onclick="apiSyncAll()">
                <i class="fas fa-sync"></i> Sync Semua
            </button>
        </div>
        <div class="api-card-body">
            <table class="api-table">
                <thead>
                    <tr>
                        <th>NAMA KONEKSI</th>
                        <th>TIPE</th>
                        <th>URL API</th>
                        <th>STATUS</th>
                        <th>SYNC TERAKHIR</th>
                        <th>kWh</th>
                        <th>AKSI</th>
                    </tr>
                </thead>
                <tbody id="tbodyKoneksi">
                    <tr>
                        <td colspan="7" style="text-align:center;padding:2rem;color:#6b7280">
                            <i class="fas fa-spinner fa-spin"></i> Memuat data...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- ====== GRAFIK ENERGI (Running Curve style) ====== --}}
    <div class="api-card">
        <div class="api-card-header">
            <h3><i class="fas fa-chart-line" style="color:#FFD700"></i> Running Curve — Grafik Energi</h3>
            <select class="api-select" id="selChart" onchange="apiLoadChart()">
                <option value="">-- Pilih Koneksi --</option>
            </select>
        </div>

        {{-- Toolbar: Mode toggle + Date picker + Yield info --}}
        <div class="rc-toolbar">
            <div class="rc-modes">
                <button type="button" class="rc-mode-btn active" data-mode="day"   onclick="apiSetChartMode('day', this)">Day</button>
                <button type="button" class="rc-mode-btn"        data-mode="month" onclick="apiSetChartMode('month', this)">Month</button>
                <button type="button" class="rc-mode-btn"        data-mode="year"  onclick="apiSetChartMode('year', this)">Year</button>
                <button type="button" class="rc-mode-btn"        data-mode="total" onclick="apiSetChartMode('total', this)">Total</button>
            </div>

            <div class="rc-datepicker" id="rcDatepicker">
                <button type="button" class="rc-nav" onclick="apiChartShiftDate(-1)" title="Sebelumnya">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <input type="date" id="rcDate" class="rc-date-input" onchange="apiLoadChart()">
                <button type="button" class="rc-nav" onclick="apiChartShiftDate(1)" title="Berikutnya">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>

            <div class="rc-yield">
                <span class="rc-yield-item">
                    <span class="rc-yield-label" id="rcYieldLabel">Today Yield:</span>
                    <span class="rc-yield-value yield" id="rcYield">0.00 kWh</span>
                </span>
                <span class="rc-yield-item">
                    <span class="rc-yield-label">Daily Revenue:</span>
                    <span class="rc-yield-value rev" id="rcRevenue">Rp 0</span>
                </span>
            </div>
        </div>

        <div class="api-card-body">
            <div class="api-chart-wrap" style="height:320px;position:relative">
                <canvas id="chartEnergi"></canvas>
                <div id="rcEmpty" class="rc-empty" style="display:none">
                    <i class="fas fa-chart-area"></i>
                    <p>Belum ada data sync untuk periode ini.<br>Pilih koneksi atau lakukan sync untuk melihat grafik.</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ====== LOG SINKRONISASI ====== --}}
    <div class="api-card">
        <div class="api-card-header">
            <h3><i class="fas fa-history" style="color:#a855f7"></i> Log Sinkronisasi (50 Terakhir)</h3>
            <button class="api-btn api-btn-danger api-btn-sm" onclick="apiHapusSemualog()" id="btnHapusAllLog">
                <i class="fas fa-trash"></i> Hapus Semua Log
            </button>
        </div>
        <div class="api-card-body">
            <table class="api-table">
                <thead>
                    <tr>
                        <th>WAKTU</th>
                        <th>KONEKSI</th>
                        <th>STATUS</th>
                        <th>RESP. TIME</th>
                        <th>HTTP</th>
                        <th>POWER (kW)</th>
                        <th>YIELD (kWh)</th>
                        <th>AKSI</th>
                    </tr>
                </thead>
                <tbody id="tbodyLog">
                    <tr>
                        <td colspan="8" style="text-align:center;padding:2rem;color:#6b7280">Belum ada log</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</div>

{{-- ====== MODAL TAMBAH/EDIT ====== --}}
<div id="apiModalBackdrop" class="api-modal-backdrop" onclick="apiBackdropClick(event)">
    <div class="api-modal">
        <div class="api-modal-head">
            <h2 id="apiModalTitle">Tambah Koneksi API</h2>
            <button onclick="apiCloseModal()" style="background:none;border:none;color:#9ca3af;font-size:1.2rem;cursor:pointer;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="api-modal-body">
            <input type="hidden" id="fId">
            <div class="api-form-row">
                <div class="api-form-group">
                    <label>Nama Koneksi *</label>
                    <input id="fNama" class="api-input" type="text" placeholder="Contoh: HopeWind Solar Panel">
                </div>
                <div class="api-form-group">
                    <label>Tipe Sistem *</label>
                    <select id="fTipe" class="api-input">
                        <option value="solar_panel">Solar Panel</option>
                        <option value="charging_station">Charging Station</option>
                        <option value="energy_meter">Energy Meter</option>
                        <option value="other">Lainnya</option>
                    </select>
                </div>
            </div>
            <div class="api-form-row api-form-row-3">
                <div class="api-form-group">
                    <label>API URL *</label>
                    <input id="fUrl" class="api-input" type="url" placeholder="http://openapi.hopewindcloud.eu/openApi/...">
                </div>
                <div class="api-form-group">
                    <label>Method *</label>
                    <select id="fMethod" class="api-input">
                        <option value="GET">GET</option>
                        <option value="POST">POST</option>
                    </select>
                </div>
            </div>
            <div class="api-form-row">
                <div class="api-form-group">
                    <label>Username / AppID</label>
                    <input id="fUser" class="api-input" type="text" placeholder="AppID HopeWind atau API Key">
                </div>
                <div class="api-form-group">
                    <label>Password / AppSecret</label>
                    <input id="fPass" class="api-input" type="password" placeholder="AppSecret HopeWind atau API Secret">
                </div>
            </div>
            <div class="api-form-group">
                <label>Custom Headers (JSON - Optional)</label>
                <textarea id="fHeader" class="api-input" rows="2" placeholder='{"Authorization": "Bearer TOKEN"}'></textarea>
                <span class="api-hint">Format JSON untuk custom headers tambahan</span>
            </div>
            <div class="api-info-box">
                <i class="fas fa-info-circle"></i>
                <strong>Response Path:</strong> Gunakan dot notation untuk ambil nilai dari JSON response.
                Contoh: <code>result.nowKw</code> untuk <code>{"result": {"nowKw": 123.45}}</code>
            </div>

            {{-- ============ POWER (kW) — REAL-TIME ============ ---}}
            <div class="api-form-row">
                <div class="api-form-group">
                    <label>Path untuk Power / kW *</label>
                    <input id="fPathKwh" class="api-input" type="text" placeholder="result.nowKw">
                    <span class="api-hint">Field daya sesaat (Real-time Power)</span>
                </div>
                <div class="api-form-group">
                    <label>Path untuk Voltage</label>
                    <input id="fPathVolt" class="api-input" type="text" placeholder="data.voltage">
                </div>
            </div>
            <div class="api-form-row">
                <div class="api-form-group">
                    <label>Path untuk Current</label>
                    <input id="fPathCurr" class="api-input" type="text" placeholder="data.current">
                </div>
                <div class="api-form-group">
                    <label>Path untuk Power (Alternatif)</label>
                    <input id="fPathPow" class="api-input" type="text" placeholder="kosongkan kalau gak perlu">
                </div>
            </div>

            {{-- Today Yield otomatis di-fetch dari HopeWind, gak perlu input --}}
            <input id="fPathYield" type="hidden" value="">
            <div class="api-form-row">
                <div class="api-form-group">
                    <label>Interval Sync (detik) *</label>
                    <input id="fInterval" class="api-input" type="number" value="300" min="60">
                    <span class="api-hint">Minimal 60 detik</span>
                </div>
                <div class="api-form-group">
                    <label>Status *</label>
                    <select id="fActive" class="api-input">
                        <option value="1">Aktif</option>
                        <option value="0">Nonaktif</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="api-modal-foot">
            <button id="btnTest" class="api-btn api-btn-secondary" onclick="apiTest()">
                <i class="fas fa-vial"></i> Test Koneksi
            </button>
            <button class="api-btn api-btn-secondary" onclick="apiCloseModal()">Batal</button>
            <button id="btnSimpan" class="api-btn api-btn-primary" onclick="apiSimpan()">
                <i class="fas fa-save"></i> Simpan
            </button>
        </div>
    </div>
</div>

{{-- Toast --}}
<div id="apiToast" style="display:none;position:fixed;bottom:2rem;right:2rem;background:var(--bg-card);border:1px solid rgba(6,182,212,0.25);border-radius:12px;padding:1rem 1.5rem;color:var(--text-primary);font-size:.875rem;z-index:99999;min-width:260px;box-shadow:0 10px 30px rgba(0,0,0,.5);align-items:center;gap:.75rem;">
    <span id="apiToastIcon" style="font-size:1.2rem"></span>
    <span id="apiToastMsg"></span>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const API_CSRF = '{{ csrf_token() }}';
let apiChart   = null;
let apiList    = [];

// ── Toast ──────────────────────────────────────────────────────────
function apiToast(msg, type = 'success') {
    const el = document.getElementById('apiToast');
    document.getElementById('apiToastIcon').textContent = type==='success'?'✅':type==='error'?'❌':'ℹ️';
    document.getElementById('apiToastMsg').textContent  = msg;
    el.style.display     = 'flex';
    el.style.borderColor = type==='success'?'#22c55e':type==='error'?'#ef4444':'#3b82f6';
    setTimeout(() => { el.style.display = 'none'; }, 4000);
}

// ── Fetch Helper ───────────────────────────────────────────────────
async function apiFetch(url, opts = {}) {
    const res = await fetch(url, {
        headers: {
            'Content-Type'    : 'application/json',
            'Accept'          : 'application/json',
            'X-CSRF-TOKEN'    : API_CSRF,
            'X-Requested-With': 'XMLHttpRequest',
            ...(opts.headers || {})
        },
        ...opts
    });
    return res.json();
}

// ── Load Stats ─────────────────────────────────────────────────────
async function apiLoadStats() {
    try {
        // Cache-buster timestamp supaya browser gak pakai cache lama
        const j = await apiFetch('/api-integration/stats?_t=' + Date.now());
        if (!j.success) return;
        document.getElementById('statTotal').textContent = j.data.total_koneksi;
        document.getElementById('statAktif').textContent = j.data.koneksi_aktif;
        document.getElementById('statPower').textContent = (j.data.realtime_power ?? 0).toFixed(2);
        document.getElementById('statKwh').textContent   = (j.data.kwh_hari_ini ?? 0).toFixed(2);
        document.getElementById('statData').textContent  = j.data.total_data;
    } catch(e) {}
}

// ── Load Connections ───────────────────────────────────────────────
async function apiLoadConnections() {
    try {
        const j = await apiFetch('/api-integration/connections');
        if (!j.success) return;
        apiList = j.data;
        apiRenderTable(j.data);
        const sel = document.getElementById('selChart');
        sel.innerHTML = '<option value="">-- Pilih Koneksi --</option>';
        j.data.forEach(d => sel.innerHTML += `<option value="${d.id}">${d.nama_koneksi}</option>`);
    } catch(e) {
        document.getElementById('tbodyKoneksi').innerHTML =
            '<tr><td colspan="7" style="text-align:center;padding:2rem;color:#ef4444"><i class="fas fa-exclamation-triangle"></i> Gagal memuat data.</td></tr>';
    }
}

// ── Load Logs ──────────────────────────────────────────────────────
async function apiLoadLogs() {
    try {
        const j = await apiFetch('/api-integration/logs');
        if (j.success) apiRenderLogs(j.data);
    } catch(e) {}
}

// ── Refresh All ────────────────────────────────────────────────────
function apiRefreshAll() {
    apiLoadStats(); apiLoadConnections(); apiLoadLogs();
    apiToast('Data diperbarui', 'info');
}

// ── Render Tabel Koneksi ───────────────────────────────────────────
function apiRenderTable(data) {
    const el = document.getElementById('tbodyKoneksi');
    if (!data.length) {
        el.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:2rem;color:#6b7280">Belum ada koneksi API. Klik "Tambah Koneksi API".</td></tr>';
        return;
    }
    el.innerHTML = data.map(d => `
        <tr>
            <td style="font-weight:600;color:#fff">${d.nama_koneksi}</td>
            <td style="color:#9ca3af;text-transform:capitalize">${d.tipe_sistem.replace('_',' ')}</td>
            <td style="color:#60a5fa;font-size:.8rem;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="${d.api_url}">${d.api_url}</td>
            <td><span class="badge ${d.is_active?'badge-active':'badge-inactive'}">${d.is_active?'Aktif':'Nonaktif'}</span></td>
            <td style="color:#9ca3af;font-size:.8rem">
                ${d.last_sync_at ? apiFmtDate(d.last_sync_at) : '–'}<br>
                ${d.last_sync_status ? `<span class="badge badge-${d.last_sync_status}">${d.last_sync_status}</span>` : ''}
            </td>
            <td style="color:#FFD700;font-weight:600">${d.last_sync_status==='success'?'✓':'–'}</td>
            <td>
                <div style="display:flex;gap:5px">
                    <button class="api-btn api-btn-success api-btn-sm" onclick="apiSync(${d.id})" title="Sync sekarang"><i class="fas fa-sync"></i></button>
                    <button class="api-btn api-btn-sm" style="background:#22c55e;color:#fff" onclick="apiPickYieldField(${d.id})" title="Cari & atur field kWh harian"><i class="fas fa-search"></i></button>
                    <button class="api-btn api-btn-warning api-btn-sm" onclick="apiEdit(${d.id})" title="Edit"><i class="fas fa-edit"></i></button>
                    <button class="api-btn api-btn-danger api-btn-sm" onclick="apiHapus(${d.id},'${d.nama_koneksi}')" title="Hapus"><i class="fas fa-trash"></i></button>
                </div>
            </td>
        </tr>`).join('');
}

// ── Render Log ─────────────────────────────────────────────────────
function apiRenderLogs(data) {
    const el = document.getElementById('tbodyLog');
    if (!data.length) {
        el.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:2rem;color:#6b7280">Belum ada log</td></tr>';
        return;
    }
    el.innerHTML = data.map(d => {
        const power = (d.power_value !== null && d.power_value !== undefined && d.power_value !== '')
            ? `<span style="color:#3b82f6;font-weight:600">${parseFloat(d.power_value).toFixed(2)} kW</span>`
            : '<span style="color:#6b7280">–</span>';
        const yield_ = (d.daily_yield_kwh !== null && d.daily_yield_kwh !== undefined && d.daily_yield_kwh !== '')
            ? `<span style="color:#FFD700;font-weight:600">${parseFloat(d.daily_yield_kwh).toFixed(2)} kWh</span>`
            : '<span style="color:#6b7280;font-style:italic" title="Endpoint Today Yield belum diisi">–</span>';
        return `
        <tr id="log-row-${d.id}">
            <td style="font-size:.8rem;color:#9ca3af">${apiFmtDate(d.created_at)}</td>
            <td>${d.connection?.nama_koneksi ?? '–'}</td>
            <td><span class="badge badge-${d.status}">${d.status}</span></td>
            <td style="color:#9ca3af">${d.response_time ? d.response_time+'ms' : '–'}</td>
            <td style="color:#9ca3af">${d.http_code ?? '–'}</td>
            <td>${power}</td>
            <td>${yield_}</td>
            <td>
                <button class="btn-trash" onclick="apiHapusLog(${d.id})" title="Hapus log ini">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </td>
        </tr>`;
    }).join('');
}

// ── Hapus 1 Log ────────────────────────────────────────────────────
async function apiHapusLog(id) {
    try {
        const j = await apiFetch(`/api-integration/log/${id}/delete`, { method: 'POST' });
        if (j.success) {
            const row = document.getElementById(`log-row-${id}`);
            if (row) row.remove();
            const tbody = document.getElementById('tbodyLog');
            if (!tbody.querySelector('tr[id]')) {
                tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:2rem;color:#6b7280">Belum ada log</td></tr>';
            }
            apiLoadStats();
        } else {
            apiToast(j.message ?? 'Gagal hapus log', 'error');
        }
    } catch(e) { apiToast('Error: '+e.message, 'error'); }
}

// ── Hapus Semua Log ────────────────────────────────────────────────
async function apiHapusSemualog() {
    if (!confirm('Hapus SEMUA log sinkronisasi?\nData tidak bisa dikembalikan.')) return;
    const btn = document.getElementById('btnHapusAllLog');
    btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menghapus...';
    try {
        const j = await apiFetch('/api-integration/logs/delete-all', { method: 'POST' });
        if (j.success) {
            apiToast(j.message);
            document.getElementById('tbodyLog').innerHTML =
                '<tr><td colspan="8" style="text-align:center;padding:2rem;color:#6b7280">Belum ada log</td></tr>';
            apiLoadStats();
        } else {
            apiToast(j.message ?? 'Gagal hapus log', 'error');
        }
    } catch(e) { apiToast('Error: '+e.message, 'error'); }
    finally { btn.disabled = false; btn.innerHTML = '<i class="fas fa-trash"></i> Hapus Semua Log'; }
}

// ── Periode Filter State ───────────────────────────────────────────
var apiCurrentPeriode = '7days';

function apiSetPeriode(periode, btn) {
    apiCurrentPeriode = periode;

    // Update active state pada tombol
    document.querySelectorAll('.periode-btn').forEach(function(b) {
        b.classList.remove('active');
    });
    if (btn) btn.classList.add('active');

    // Tampilkan/sembunyikan input custom
    var customWrap = document.getElementById('periodeCustom');
    if (periode === 'custom') {
        customWrap.style.display = 'inline-flex';
    } else {
        customWrap.style.display = 'none';
    }

    // Update info text
    var labelMap = {
        'today':  'Hari ini',
        '7days':  '7 hari terakhir',
        '30days': '30 hari terakhir',
        'year':   'Tahun berjalan',
        'custom': 'Pilih tanggal di bawah'
    };
    var info = document.getElementById('periodeInfo');
    if (info) info.textContent = labelMap[periode] || '';

    // ── Sinkronkan chart mode & tanggal sesuai periode ──
    var rcDate = document.getElementById('rcDate');
    var today  = new Date().toISOString().slice(0, 10);

    if (periode === 'today') {
        // Mode day — tampilkan kW per jam hari ini
        apiChartMode = 'day';
        if (rcDate) rcDate.value = today;
        document.querySelectorAll('.rc-mode-btn').forEach(function(b) { b.classList.remove('active'); });
        var dayBtn = document.querySelector('.rc-mode-btn[onclick*="day"]');
        if (dayBtn) dayBtn.classList.add('active');

    } else if (periode === '7days') {
        // Mode day — tampilkan kWh per hari, 7 hari ke belakang
        // Gunakan mode 'day' dengan tanggal hari ini, chart akan query range
        apiChartMode = 'day';
        if (rcDate) rcDate.value = today;
        document.querySelectorAll('.rc-mode-btn').forEach(function(b) { b.classList.remove('active'); });
        var dayBtn = document.querySelector('.rc-mode-btn[onclick*="day"]');
        if (dayBtn) dayBtn.classList.add('active');

    } else if (periode === '30days' || periode === 'year') {
        // 30 hari → mode month (bar per hari dalam bulan)
        // Tahun ini → mode year (bar per bulan)
        apiChartMode = (periode === 'year') ? 'year' : 'month';
        if (rcDate) rcDate.value = today;
        document.querySelectorAll('.rc-mode-btn').forEach(function(b) { b.classList.remove('active'); });
        var targetMode = apiChartMode;
        var modeBtn = document.querySelector('.rc-mode-btn[onclick*="' + targetMode + '"]');
        if (modeBtn) modeBtn.classList.add('active');
    }

    // Reload chart dengan mode yang sudah diupdate
    apiLoadChart();
}

function apiBuildPeriodeQuery() {
    var params = new URLSearchParams();
    params.append('periode', apiCurrentPeriode);

    if (apiCurrentPeriode === 'custom') {
        var s = document.getElementById('periodeStart').value;
        var e = document.getElementById('periodeEnd').value;
        if (!s || !e) {
            apiToast('Silakan isi tanggal mulai dan tanggal selesai', 'warning');
            return null;
        }
        params.append('start_date', s);
        params.append('end_date', e);
    }
    return params.toString();
}

// ── Export PDF ─────────────────────────────────────────────────────
function apiExportPdf() {
    var qs = apiBuildPeriodeQuery();
    if (qs === null) return;
    apiToast('Membuka laporan PDF...', 'info');
    window.open('/api-integration/export/pdf?' + qs, '_blank');
}

// ── Export Excel ───────────────────────────────────────────────────
function apiExportExcel() {
    var qs = apiBuildPeriodeQuery();
    if (qs === null) return;
    apiToast('Mengunduh file Excel...', 'info');
    window.location.href = '/api-integration/export/excel?' + qs;
}

// ── Print Handler ──────────────────────────────────────────────────
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

// ── Modal ──────────────────────────────────────────────────────────
function apiOpenModal() {
    document.getElementById('apiModalTitle').textContent = 'Tambah Koneksi API';
    apiClearForm();
    document.getElementById('apiModalBackdrop').style.display = 'flex';
}
function apiCloseModal() { document.getElementById('apiModalBackdrop').style.display = 'none'; }
function apiBackdropClick(e) { if (e.target.id === 'apiModalBackdrop') apiCloseModal(); }
function apiClearForm() {
    ['fId','fNama','fUrl','fUser','fPass','fHeader','fPathKwh','fPathVolt','fPathCurr','fPathPow','fPathYield']
        .forEach(id => document.getElementById(id).value = '');
    document.getElementById('fTipe').value     = 'solar_panel';
    document.getElementById('fMethod').value   = 'GET';
    document.getElementById('fInterval').value = '300';
    document.getElementById('fActive').value   = '1';
}

// ── Edit ───────────────────────────────────────────────────────────
function apiEdit(id) {
    const d = apiList.find(k => k.id === id); if (!d) return;
    document.getElementById('apiModalTitle').textContent = 'Edit Koneksi API';
    document.getElementById('fId').value        = d.id;
    document.getElementById('fNama').value      = d.nama_koneksi;
    document.getElementById('fTipe').value      = d.tipe_sistem;
    document.getElementById('fUrl').value       = d.api_url;
    document.getElementById('fMethod').value    = d.api_method;
    document.getElementById('fUser').value      = d.api_username ?? '';
    document.getElementById('fPass').value      = '';
    document.getElementById('fHeader').value    = d.api_header ?? '';
    document.getElementById('fPathKwh').value   = d.response_path_kwh;
    document.getElementById('fPathVolt').value  = d.response_path_voltage ?? '';
    document.getElementById('fPathCurr').value  = d.response_path_current ?? '';
    document.getElementById('fPathPow').value   = d.response_path_power ?? '';
    document.getElementById('fPathYield').value = d.response_path_daily_yield ?? '';
    document.getElementById('fInterval').value  = d.sync_interval;
    document.getElementById('fActive').value    = d.is_active ? '1' : '0';
    document.getElementById('apiModalBackdrop').style.display = 'flex';
}

// ── Simpan ─────────────────────────────────────────────────────────
async function apiSimpan() {
    const nama = document.getElementById('fNama').value.trim();
    const url  = document.getElementById('fUrl').value.trim();
    const path = document.getElementById('fPathKwh').value.trim();
    if (!nama || !url || !path) { apiToast('Nama, URL, dan Path kWh wajib diisi!', 'error'); return; }

    const btn = document.getElementById('btnSimpan');
    btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
    const id = document.getElementById('fId').value;

    // Build payload — field sensitif (password, username) cuma dikirim kalau user benar-benar mengisi
    const userVal = document.getElementById('fUser').value;
    const passVal = document.getElementById('fPass').value;

    const payload = {
        nama_koneksi         : nama,
        tipe_sistem          : document.getElementById('fTipe').value,
        api_url              : url,
        api_method           : document.getElementById('fMethod').value,
        api_header           : document.getElementById('fHeader').value,
        response_path_kwh    : path,
        response_path_voltage: document.getElementById('fPathVolt').value,
        response_path_current: document.getElementById('fPathCurr').value,
        response_path_power  : document.getElementById('fPathPow').value,
        response_path_daily_yield: document.getElementById('fPathYield').value,
        sync_interval        : document.getElementById('fInterval').value,
        is_active            : document.getElementById('fActive').value,
    };
    // Kirim username/password HANYA kalau ada isinya (cegah overwrite kosong saat edit)
    if (userVal && userVal.trim() !== '') payload.api_username = userVal;
    if (passVal && passVal.trim() !== '') payload.api_password = passVal;

    try {
        const j = await apiFetch(id ? `/api-integration/${id}/update` : '/api-integration', {
            method: 'POST',
            body: JSON.stringify(payload)
        });
        if (j.success) {
            apiToast(j.message);
            apiCloseModal();
            apiLoadConnections();
            apiLoadStats();
        } else {
            // Tampilkan error spesifik per field, bukan cuma "Validasi gagal"
            let msg = j.message || 'Gagal menyimpan';
            if (j.errors && typeof j.errors === 'object') {
                const firstErr = Object.values(j.errors)[0];
                if (Array.isArray(firstErr) && firstErr.length) {
                    msg = firstErr[0];
                }
            }
            apiToast(msg, 'error');
        }
    } catch(e) { apiToast('Error: '+e.message, 'error'); }
    finally { btn.disabled = false; btn.innerHTML = '<i class="fas fa-save"></i> Simpan'; }
}

// ── Hapus Koneksi ──────────────────────────────────────────────────
async function apiHapus(id, nama) {
    if (!confirm(`Hapus koneksi "${nama}"?\nSemua log akan ikut terhapus.`)) return;
    try {
        const j = await apiFetch(`/api-integration/${id}/delete`, { method: 'POST' });
        apiToast(j.message, j.success ? 'success' : 'error');
        if (j.success) { apiLoadConnections(); apiLoadStats(); apiLoadLogs(); }
    } catch(e) { apiToast('Error: '+e.message, 'error'); }
}

// ── Test Koneksi ───────────────────────────────────────────────────
async function apiTest() {
    const url  = document.getElementById('fUrl').value.trim();
    const path = document.getElementById('fPathKwh').value.trim();
    if (!url || !path) { apiToast('URL dan Path kW wajib diisi!', 'error'); return; }
    const btn = document.getElementById('btnTest');
    btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Testing...';
    try {
        const j = await apiFetch('/api-integration/test-connection', {
            method: 'POST',
            body: JSON.stringify({
                api_url          : url,
                api_method       : document.getElementById('fMethod').value,
                api_username     : document.getElementById('fUser').value,
                api_password     : document.getElementById('fPass').value,
                api_header       : document.getElementById('fHeader').value,
                response_path_kwh: path,
            })
        });
        if (j.success) {
            apiToast(`✅ Berhasil! HTTP ${j.data.http_code} | ${j.data.response_time}ms | kW: ${j.data.kwh_value ?? 'N/A'}`);
            // Tampilkan response viewer agar user bisa lihat field apa saja yang tersedia
            apiShowResponseViewer(j.data.sample_response);
        } else {
            apiToast('Gagal: '+j.message, 'error');
        }
    } catch(e) { apiToast('Error: '+e.message, 'error'); }
    finally { btn.disabled = false; btn.innerHTML = '<i class="fas fa-vial"></i> Test Koneksi'; }
}

// ── Tampilkan Response JSON dalam modal popup ─────────────────────
function apiShowResponseViewer(response, opts) {
    if (!response) return;
    opts = opts || {};

    let modal = document.getElementById('responseViewerModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'responseViewerModal';
        modal.innerHTML = `
            <div class="rv-backdrop" onclick="apiCloseResponseViewer()"></div>
            <div class="rv-dialog">
                <div class="rv-header">
                    <h3><i class="fas fa-search" style="color:#22c55e"></i> Pilih Field untuk Today Yield (kWh)</h3>
                    <button onclick="apiCloseResponseViewer()" class="rv-close"><i class="fas fa-times"></i></button>
                </div>
                <div class="rv-body">
                    <p class="rv-info" id="rvInfoTop">
                        <i class="fas fa-mouse-pointer"></i>
                        Klik pada angka <strong style="color:#56d364">hijau</strong> di JSON di bawah —
                        cari nilai yang match dengan <strong>Today Yield di HopeWind</strong> (sekitar 80-85 kWh).
                    </p>
                    <pre id="rvJson" class="rv-json"></pre>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }

    // Tampilkan info path saat ini kalau ada
    const infoEl = document.getElementById('rvInfoTop');
    if (infoEl) {
        let extra = '';
        if (opts.currentPath) {
            extra += `<br><small style="opacity:0.85">Path saat ini: <code style="color:#FFD700">${opts.currentPath}</code></small>`;
        }
        if (opts.syncTime) {
            extra += `<br><small style="opacity:0.7">Data dari sync terakhir: ${opts.syncTime}</small>`;
        }
        infoEl.innerHTML = `
            <i class="fas fa-mouse-pointer"></i>
            Klik pada angka <strong style="color:#56d364">hijau</strong> di JSON di bawah —
            cari nilai yang match dengan <strong>Today Yield di HopeWind</strong> (sekitar 80-85 kWh).
            ${extra}
        `;
    }

    const jsonEl = document.getElementById('rvJson');
    jsonEl.innerHTML = apiBuildJsonHtml(response, '');
    modal.classList.add('show');
}

function apiCloseResponseViewer() {
    const m = document.getElementById('responseViewerModal');
    if (m) m.classList.remove('show');
}

// Build interactive HTML dari JSON dengan path clickable
function apiBuildJsonHtml(obj, basePath) {
    if (obj === null) return '<span class="rv-null">null</span>';
    if (typeof obj === 'string') return '<span class="rv-str">"' + obj.replace(/"/g, '\\"') + '"</span>';
    if (typeof obj === 'number') return '<span class="rv-num">' + obj + '</span>';
    if (typeof obj === 'boolean') return '<span class="rv-bool">' + obj + '</span>';
    if (Array.isArray(obj)) {
        if (!obj.length) return '<span class="rv-bracket">[]</span>';
        let html = '<span class="rv-bracket">[</span>\n';
        obj.forEach((item, i) => {
            const path = basePath + '[' + i + ']';
            html += '  ' + apiBuildJsonHtml(item, path) + (i < obj.length - 1 ? ',' : '') + '\n';
        });
        html += '<span class="rv-bracket">]</span>';
        return html;
    }
    if (typeof obj === 'object') {
        const keys = Object.keys(obj);
        if (!keys.length) return '<span class="rv-bracket">{}</span>';
        let html = '<span class="rv-bracket">{</span>\n';
        keys.forEach((k, i) => {
            const path = basePath ? basePath + '.' + k : k;
            const isLeafNum = typeof obj[k] === 'number';
            const keyClass = isLeafNum ? 'rv-key-clickable' : 'rv-key';
            const onClick = isLeafNum ? `onclick="apiCopyPath('${path}')"` : '';
            const title = isLeafNum ? `title="Klik untuk copy: ${path}"` : '';
            html += '  <span class="' + keyClass + '" ' + onClick + ' ' + title + '>"' + k + '"</span>: '
                  + apiBuildJsonHtml(obj[k], path)
                  + (i < keys.length - 1 ? ',' : '') + '\n';
        });
        html += '<span class="rv-bracket">}</span>';
        return html;
    }
    return String(obj);
}

function apiCopyPath(path) {
    // Kalau ada koneksi ID terkait (dari Pick Yield Field), auto-save ke DB
    const connId = window.__pickYieldConnId;
    if (connId) {
        apiSaveYieldPath(connId, path);
    } else {
        // Mode lama (dari modal form): tinggal copy ke field
        navigator.clipboard.writeText(path).then(() => {
            apiToast(`Path "${path}" disalin! Paste ke field "Path untuk Today Yield".`, 'success');
            const f = document.getElementById('fPathYield');
            if (f) f.value = path;
        });
    }
}

// Save path Today Yield langsung ke DB (gak perlu password)
async function apiSaveYieldPath(connId, path) {
    try {
        const j = await apiFetch(`/api-integration/${connId}/set-yield-path`, {
            method: 'POST',
            body: JSON.stringify({ path: path })
        });
        if (j.success) {
            const val = j.data.extracted_value;
            const isNum = j.data.is_numeric;
            if (isNum) {
                apiToast(`✅ Path tersimpan! Nilai terbaca: ${val} kWh`, 'success');
            } else {
                apiToast(`Path tersimpan, tapi nilai tidak numeric: ${val}`, 'warning');
            }
            apiCloseResponseViewer();
            apiLoadStats();
            apiLoadLogs();
            window.__pickYieldConnId = null;
        } else {
            apiToast('Gagal simpan: ' + (j.message || 'Unknown'), 'error');
        }
    } catch(e) {
        apiToast('Error: ' + e.message, 'error');
    }
}

// ── Pick Yield Field — buka response viewer dari sync log terakhir ─
async function apiPickYieldField(connId) {
    try {
        const j = await apiFetch(`/api-integration/${connId}/last-response`);
        if (!j.success) {
            apiToast(j.message || 'Gagal ambil response', 'error');
            return;
        }
        window.__pickYieldConnId = connId;
        apiShowResponseViewer(j.data.response, {
            currentPath: j.data.current_path_daily_yield,
            syncTime: j.data.sync_time,
        });
    } catch(e) {
        apiToast('Error: '+e.message, 'error');
    }
}

// ── Sync 1 ─────────────────────────────────────────────────────────
async function apiSync(id) {
    try {
        const j = await apiFetch(`/api-integration/${id}/fetch`, { method: 'POST' });
        apiToast(j.success ? `Sync berhasil! kWh: ${j.kwh ?? 'N/A'}` : 'Sync gagal: '+j.message, j.success ? 'success' : 'error');
        if (j.success) { apiLoadConnections(); apiLoadLogs(); apiLoadStats(); }
    } catch(e) { apiToast('Error: '+e.message, 'error'); }
}

// ── Sync Semua ─────────────────────────────────────────────────────
async function apiSyncAll() {
    const btn = document.getElementById('btnSyncAll');
    btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Syncing...';
    try {
        const j = await apiFetch('/api-integration/sync-all', { method: 'POST' });
        apiToast(j.message, j.success ? 'success' : 'error');
        if (j.success) { apiLoadConnections(); apiLoadLogs(); apiLoadStats(); }
    } catch(e) { apiToast('Error: '+e.message, 'error'); }
    finally { btn.disabled = false; btn.innerHTML = '<i class="fas fa-sync"></i> Sync Semua'; }
}

// ── Chart (Running Curve) ──────────────────────────────────────────
var apiChartMode = 'day';
var apiChartPollTimer = null;

function apiSetChartMode(mode, btn) {
    apiChartMode = mode;
    document.querySelectorAll('.rc-mode-btn').forEach(function(b) { b.classList.remove('active'); });
    if (btn) btn.classList.add('active');

    // Sembunyikan datepicker untuk mode 'total'
    var picker = document.getElementById('rcDatepicker');
    if (picker) picker.style.display = (mode === 'total') ? 'none' : 'inline-flex';

    apiLoadChart();
}

function apiChartShiftDate(delta) {
    var input = document.getElementById('rcDate');
    if (!input || !input.value) {
        input.valueAsDate = new Date();
    }
    var d = new Date(input.value);
    if (apiChartMode === 'month') d.setMonth(d.getMonth() + delta);
    else if (apiChartMode === 'year') d.setFullYear(d.getFullYear() + delta);
    else d.setDate(d.getDate() + delta);
    input.value = d.toISOString().slice(0, 10);
    apiLoadChart();
}

async function apiLoadChart() {
    const id = document.getElementById('selChart').value;
    if (!id) {
        document.getElementById('rcEmpty').style.display = 'flex';
        if (apiChart) { apiChart.destroy(); apiChart = null; }
        return;
    }

    // Default tanggal kalau kosong = hari ini
    var dateInput = document.getElementById('rcDate');
    if (!dateInput.value) {
        dateInput.valueAsDate = new Date();
    }

    var params = new URLSearchParams({ mode: apiChartMode });
    if (apiChartMode !== 'total') {
        var v = dateInput.value;
        if (apiChartMode === 'month')      params.append('date', v.slice(0, 7));
        else if (apiChartMode === 'year')  params.append('date', v.slice(0, 4));
        else                                params.append('date', v);
    }

    try {
        const j = await apiFetch(`/api-integration/${id}/chart-data?` + params.toString());
        if (j.success) apiRenderChart(j.data);
    } catch(e) { apiToast('Gagal load chart: ' + e.message, 'error'); }
}

function apiRenderChart(data) {
    // Update info Yield & Revenue
    var yieldLabel = (apiChartMode === 'day') ? 'Today Yield' :
                     (apiChartMode === 'month') ? 'Month Yield' :
                     (apiChartMode === 'year') ? 'Year Yield' : 'Total Yield';
    var rcYieldLabel = document.getElementById('rcYieldLabel');
    if (rcYieldLabel) rcYieldLabel.textContent = yieldLabel + ':';
    document.getElementById('rcYield').textContent   = (data.total_kwh ?? 0).toFixed(2) + ' kWh';
    document.getElementById('rcRevenue').textContent = 'Rp ' + Number(data.daily_revenue ?? 0).toLocaleString('id-ID');

    // Cek apakah semua nilai 0 (anggap kosong)
    var hasData = (data.power || []).some(function(v) { return v > 0; })
               || (data.kwh   || []).some(function(v) { return v > 0; });
    document.getElementById('rcEmpty').style.display = hasData ? 'none' : 'flex';

    const ctx = document.getElementById('chartEnergi').getContext('2d');
    if (apiChart) apiChart.destroy();

    // Gradient emas — selaras warna kWh card
    const grad = ctx.createLinearGradient(0, 0, 0, 320);
    grad.addColorStop(0, 'rgba(255,215,0,0.40)');
    grad.addColorStop(0.5, 'rgba(255,215,0,0.12)');
    grad.addColorStop(1, 'rgba(255,215,0,0.01)');

    // Label sumbu Y berdasarkan mode
    var yUnit = (apiChartMode === 'day') ? ' kW' : ' kWh';
    var dataset = (apiChartMode === 'day')
        ? { label: 'Power (kW)', data: data.power, color: '#FFD700' }
        : { label: 'Energy (kWh)', data: data.kwh, color: '#FFD700' };

    apiChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.labels,
            datasets: [{
                label: dataset.label,
                data: dataset.data,
                borderColor: dataset.color,
                backgroundColor: grad,
                borderWidth: 2,
                fill: true,
                tension: 0.35,
                pointRadius: 0,
                pointHoverRadius: 5,
                pointHoverBackgroundColor: dataset.color,
                pointHoverBorderColor: '#fff',
                pointHoverBorderWidth: 2,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: { color: '#94a3b8', boxWidth: 12, padding: 12, font: { size: 12 } }
                },
                tooltip: {
                    backgroundColor: '#0f1c30',
                    titleColor: '#FFD700',
                    bodyColor: '#e2e8f0',
                    borderColor: 'rgba(255,215,0,0.35)',
                    borderWidth: 1,
                    padding: 10,
                    callbacks: {
                        label: function(ctx) {
                            return ctx.dataset.label + ': ' + ctx.parsed.y.toFixed(2) + yUnit.trim();
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { color: 'rgba(255,215,0,0.06)', drawBorder: false },
                    ticks: { color: '#64748b', font: { size: 11 }, maxRotation: 0, autoSkip: true, maxTicksLimit: 12 }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(255,215,0,0.06)', drawBorder: false },
                    ticks: {
                        color: '#64748b',
                        font: { size: 11 },
                        callback: function(v) { return v + yUnit; }
                    },
                    title: {
                        display: true,
                        text: (apiChartMode === 'day') ? 'kW' : 'kWh',
                        color: '#FFD700',
                        font: { size: 11, weight: 'bold' }
                    }
                }
            }
        }
    });
}

// ── Format Tanggal ─────────────────────────────────────────────────
function apiFmtDate(str) {
    if (!str) return '–';
    return new Date(str).toLocaleString('id-ID', {
        day:'2-digit', month:'short', year:'numeric',
        hour:'2-digit', minute:'2-digit'
    });
}

// ── Ripple Effect pada Export Buttons ─────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
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

    // Init data
    apiLoadStats();
    apiLoadConnections();
    apiLoadLogs();
    // Stat cards (kW + kWh) auto-refresh tiap 15 detik — aman untuk API HopeWind
    setInterval(() => { apiLoadStats(); }, 15000);
    // Tabel koneksi & log refresh tiap 30 detik
    setInterval(() => { apiLoadConnections(); apiLoadLogs(); }, 30000);

    // Auto-refresh chart Running Curve tiap 15 detik
    setInterval(() => {
        var sel = document.getElementById('selChart');
        if (sel && sel.value) {
            apiLoadChart();
        }
    }, 15000);
});
</script>
@endpush