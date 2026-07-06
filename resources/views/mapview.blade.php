@extends('layout.app')
@section('title', 'Map View - EV Smart Energy')
@section('page-title', 'Map View')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/mapview.css') }}">
<link rel="stylesheet" href="{{ asset('css/mapview-extra.css') }}">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css"/>
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css"/>
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css"/>
@endpush

@section('content')

{{-- Page Header --}}
<div class="map-page-header">
    <div>
        <h2 style="display:flex;align-items:center;gap:12px;font-family:'Rajdhani',sans-serif;font-size:24px;font-weight:700;margin-bottom:4px">
            <span style="display:inline-flex;align-items:center;justify-content:center;width:42px;height:42px;border-radius:12px;background:linear-gradient(135deg,#0891b2,#06b6d4);box-shadow:0 0 18px rgba(6,182,212,0.4);flex-shrink:0;animation:pageIconPulse 3s ease-in-out infinite">
                <i class="fas fa-map-marker-alt" style="font-size:20px;color:#fff"></i>
            </span>
            <span style="background:linear-gradient(135deg,#f1f5f9,#22d3ee);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent">
                Map View
            </span>
        </h2>
        <p style="font-size:12px;color:#94a3b8">Peta lokasi seluruh stasiun pengisian EV</p>
    </div>
</div>

{{-- Stat Cards --}}
<div class="map-stats">
    <div class="map-stat-card">
        <div class="map-stat-icon" style="color:var(--cyan)"><i class="fas fa-charging-station"></i></div>
        <div>
            <div class="map-stat-val" style="color:var(--cyan)">{{ $totalStation }}</div>
            <div class="map-stat-label">Total Stasiun</div>
        </div>
    </div>
    <div class="map-stat-card">
        <div class="map-stat-icon" style="color:var(--green)"><i class="fas fa-check-circle"></i></div>
        <div>
            <div class="map-stat-val" style="color:var(--green)">{{ $totalAktif }}</div>
            <div class="map-stat-label">Active</div>
        </div>
    </div>
    <div class="map-stat-card">
        <div class="map-stat-icon" style="color:var(--red)"><i class="fas fa-tools"></i></div>
        <div>
            <div class="map-stat-val" style="color:var(--red)">{{ $totalMaint }}</div>
            <div class="map-stat-label">Maintenance</div>
        </div>
    </div>
    <div class="map-stat-card">
        <div class="map-stat-icon" style="color:var(--yellow)"><i class="fas fa-pause-circle"></i></div>
        <div>
            <div class="map-stat-val" style="color:var(--yellow)">{{ $totalInaktif }}</div>
            <div class="map-stat-label">Inactive</div>
        </div>
    </div>
</div>

{{-- Banner peringatan koordinat hilang --}}
@if(($tanpaKoordinat ?? 0) > 0)
<div id="coordWarning" style="display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;
     padding:14px 18px;margin-bottom:16px;background:linear-gradient(135deg,rgba(245,158,11,0.12),rgba(245,158,11,0.06));
     border:1px solid rgba(245,158,11,0.35);border-radius:12px">
    <div style="display:flex;align-items:center;gap:12px">
        <div style="width:36px;height:36px;border-radius:9px;background:linear-gradient(135deg,#f59e0b,#fbbf24);
             display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="fas fa-triangle-exclamation" style="color:#fff;font-size:16px"></i>
        </div>
        <div>
            <div style="font-size:13px;font-weight:700;color:#fbbf24">
                {{ $tanpaKoordinat }} stasiun belum punya koordinat di peta
            </div>
            <div style="font-size:11px;color:#94a3b8;margin-top:2px">
                Klik tombol "Sinkron Koordinat" untuk mencari otomatis berdasarkan nama lokasi
            </div>
        </div>
    </div>
    <button id="btnSyncCoord" onclick="syncCoordinates()" style="display:inline-flex;align-items:center;gap:8px;
        padding:9px 18px;background:linear-gradient(135deg,#f59e0b,#fbbf24);color:#1a1200;border:none;
        border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;font-family:inherit;transition:all 0.2s">
        <i class="fas fa-arrows-rotate"></i> Sinkron Koordinat
    </button>
</div>
@else
<button id="btnSyncCoord" onclick="syncCoordinates()" style="display:none"></button>
@endif

{{-- Hasil sync (alert) --}}
<div id="syncResult" style="display:none;padding:12px 16px;margin-bottom:14px;border-radius:10px;font-size:12px"></div>

{{-- Main Layout --}}
<div class="map-layout">

    {{-- Station List --}}
    <div class="map-sidebar card">
        <div class="card-header">
            <span class="card-title">Daftar Stasiun</span>
            <span style="font-size:12px;font-weight:700;color:#FFD700;background:rgba(255,215,0,0.1);border:1px solid rgba(255,215,0,0.25);padding:2px 10px;border-radius:20px;">{{ $totalStation }} stasiun</span>
        </div>
        <div class="sidebar-search">
            <i class="fas fa-search"></i>
            <input type="text" id="sidebarSearch" placeholder="Cari stasiun..." oninput="filterSidebar(this.value)">
        </div>
        <div class="sidebar-list" id="sidebarList">
            @foreach($stations as $st)
            <div class="sidebar-item" onclick="focusStation({{ $st->id }}, {{ $st->latitude ?? 0 }}, {{ $st->longitude ?? 0 }})">
                <div class="sidebar-item-left">
                    <div class="sidebar-dot
                        @if($st->status === 'Active') dot-active
                        @elseif($st->status === 'Maintenance') dot-maint
                        @else dot-inactive
                        @endif">
                    </div>
                    <div>
                        <div class="sidebar-name">{{ $st->nama_stasiun }}</div>
                        <div class="sidebar-loc"><i class="fas fa-map-marker-alt"></i> {{ $st->lokasi }}</div>
                    </div>
                </div>
                <span class="badge
                    @if($st->status === 'Active') badge-active
                    @elseif($st->status === 'Maintenance') badge-maintenance
                    @else badge-inactive
                    @endif">
                    {{ $st->status }}
                </span>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Map --}}
    <div class="map-main card" style="padding:0;overflow:hidden;position:relative">
        <div class="card-header" style="padding:12px 16px">
            <span class="card-title">Peta Stasiun</span>
            <div style="display:flex;gap:8px;align-items:center">
                <button class="route-btn" onclick="toggleRoutePanel()">
                    <i class="fas fa-route"></i> Rute & Tracking
                </button>
                <div class="map-legend-inline">
                    <span class="legend-dot" style="background:#22c55e"></span> Active
                    <span class="legend-dot" style="background:#ef4444;margin-left:10px"></span> Maintenance
                    <span class="legend-dot" style="background:#ffc800;margin-left:10px"></span> Inactive
                </div>
            </div>
        </div>

        {{-- Panel Rute & Tracking --}}
        <div id="routePanel" class="route-panel">
            <div class="route-panel-header">
                <h4><i class="fas fa-map-signs"></i> Rute & Tracking Karyawan</h4>
                <button class="route-close" onclick="toggleRoutePanel()"><i class="fas fa-times"></i></button>
            </div>

            <div class="route-panel-body">
                <div class="route-form-group">
                    <label><i class="fas fa-user"></i> Nama Karyawan</label>
                    <input type="text" id="empName" placeholder="Contoh: Budi Teknisi" value="Budi Teknisi">
                </div>

                <div class="route-form-group">
                    <label><i class="fas fa-circle" style="color:#16a34a"></i> Titik Mulai</label>
                    <select id="routeStart">
                        <option value="">-- Pilih lokasi awal --</option>
                        <option value="current">?? Lokasi Saya Sekarang</option>
                        @foreach($stations as $st)
                        @if($st->latitude && $st->longitude)
                        <option value="{{ $st->latitude }},{{ $st->longitude }}">{{ $st->nama_stasiun }} — {{ $st->lokasi }}</option>
                        @endif
                        @endforeach
                    </select>
                </div>

                <div class="route-form-group">
                    <label><i class="fas fa-flag-checkered" style="color:#dc2626"></i> Tujuan Stasiun</label>
                    <select id="routeEnd">
                        <option value="">-- Pilih stasiun tujuan --</option>
                        @foreach($stations as $st)
                        @if($st->latitude && $st->longitude)
                        <option value="{{ $st->latitude }},{{ $st->longitude }}" data-name="{{ $st->nama_stasiun }}">{{ $st->nama_stasiun }} — {{ $st->lokasi }}</option>
                        @endif
                        @endforeach
                    </select>
                </div>

                <div class="route-actions">
                    <button class="route-btn-primary" onclick="calculateRoute()">
                        <i class="fas fa-directions"></i> Hitung Rute
                    </button>
                    <button class="route-btn-secondary" onclick="clearRoute()">
                        <i class="fas fa-eraser"></i> Bersihkan
                    </button>
                </div>

                {{-- Info Rute --}}
                <div id="routeInfo" class="route-info" style="display:none">
                    <div class="route-info-title"><i class="fas fa-info-circle"></i> Informasi Rute</div>
                    <div class="route-info-grid">
                        <div class="route-info-item">
                            <div class="route-info-label">Jarak</div>
                            <div class="route-info-val" id="routeDistance">-</div>
                        </div>
                        <div class="route-info-item">
                            <div class="route-info-label">Estimasi Waktu</div>
                            <div class="route-info-val" id="routeDuration">-</div>
                        </div>
                    </div>
                </div>

                {{-- Tracking Simulasi --}}
                <div id="trackingPanel" class="tracking-panel" style="display:none">
                    <div class="tracking-header">
                        <span class="tracking-status-dot"></span>
                        <span class="tracking-title">Tracking Aktif</span>
                        <span class="tracking-emp" id="trackingEmpName">-</span>
                    </div>
                    <div class="tracking-progress-wrap">
                        <div class="tracking-progress" id="trackingProgress"></div>
                    </div>
                    <div class="tracking-stats">
                        <div><i class="fas fa-tachometer-alt"></i> <span id="trackingPercent">0%</span> dari rute</div>
                        <div><i class="fas fa-clock"></i> <span id="trackingETA">-</span> tersisa</div>
                    </div>
                    <div class="tracking-actions">
                        <button class="track-btn" onclick="startTracking()" id="btnStartTrack">
                            <i class="fas fa-play"></i> Simulasi Tracking
                        </button>
                        <button class="track-btn track-btn-stop" onclick="stopTracking()" id="btnStopTrack" style="display:none">
                            <i class="fas fa-stop"></i> Stop
                        </button>
                    </div>
                </div>

                {{-- --- REAL GPS TRACKING --- --}}
                <div class="real-track-divider">
                    <span>?? ATAU TRACKING REAL GPS</span>
                </div>

                <div class="real-track-box">
                    <p class="real-track-info">
                        <i class="fas fa-info-circle"></i>
                        Buat link untuk dikirim ke karyawan via WhatsApp.
                        Karyawan buka link di HP, posisi GPS langsung muncul di peta admin.
                    </p>
                    <button class="real-track-btn" onclick="createTrackingLink()">
                        <i class="fas fa-share-square"></i> Buat Link Tracking Karyawan
                    </button>
                </div>

                {{-- Daftar Karyawan Aktif --}}
                <div id="activeEmployees" class="active-employees" style="display:none">
                    <div class="active-emp-header">
                        <i class="fas fa-users"></i> Karyawan Aktif
                        <span class="active-emp-count" id="activeEmpCount">0</span>
                    </div>
                    <div id="activeEmpList"></div>
                </div>
            </div>
        </div>

        <div id="mainMap"></div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
<script>
const stations = @json($stations);

// Fungsi warna pin sesuai status
function pinColor(status) {
    if (status === 'Active')      return '#22c55e';
    if (status === 'Maintenance') return '#ef4444';
    return '#ffc800';
}

// Buat icon custom (pin charger ala Google Maps)
function makeIcon(color, status) {
    const icon = status === 'Active' ? '?' : (status === 'Maintenance' ? '??' : '?');
    return L.divIcon({
        className: 'custom-station-pin',
        html: `
            <div class="pin-wrap" style="--pin-color:${color}">
                <div class="pin-shadow"></div>
                <div class="pin-body">
                    <div class="pin-icon">${icon}</div>
                </div>
                <div class="pin-pulse"></div>
            </div>
        `,
        iconSize:    [36, 46],
        iconAnchor:  [18, 44],
        popupAnchor: [0, -42]
    });
}

// Init map
const map = L.map('mainMap', {
    center:        [-2.5, 118],
    zoom:          5,
    minZoom:       3,
    maxZoom:       19,
    zoomControl:   true,
    scrollWheelZoom: true,
});

// === Tile Layer Options ===
const tileStreet = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors',
    maxZoom: 19,
});

const tileSatellite = L.tileLayer(
    'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
    {
        attribution: 'Tiles © Esri — Source: Esri, Earthstar Geographics',
        maxZoom: 19,
    }
);

const tileHybrid = L.tileLayer(
    'https://stamen-tiles-{s}.a.ssl.fastly.net/toner-lines/{z}/{x}/{y}.png',
    {
        attribution: '© Stamen Design',
        maxZoom: 18,
        opacity: 0.7,
    }
);

// CartoDB voyager — detail bagus + nama jalan jelas
const tileVoyager = L.tileLayer(
    'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png',
    {
        attribution: '© OpenStreetMap, © CARTO',
        maxZoom: 20,
        subdomains: 'abcd',
    }
);

// Default: Voyager (paling detail dan rapi)
tileVoyager.addTo(map);

// Layer Control — user bisa pilih jenis peta
const baseLayers = {
    '??? Standard (Detail)':  tileVoyager,
    '??? Satellite':          tileSatellite,
    '?? OpenStreetMap':       tileStreet,
};
L.control.layers(baseLayers, null, { position: 'topright', collapsed: true }).addTo(map);

// Scale bar — biar user tau jaraknya
L.control.scale({ position: 'bottomleft', metric: true, imperial: false }).addTo(map);

// =================================================================
// MARKER CLUSTERING — biar map ringan walau ribuan stasiun
// =================================================================
const markers   = {};
const bounds    = [];
const formatter = new Intl.NumberFormat('id-ID');

// Cluster group: marker yang berdekatan auto-grouping jadi 1 bundle
const clusterGroup = L.markerClusterGroup({
    showCoverageOnHover: false,        // jangan gambar polygon area cluster
    spiderfyOnMaxZoom:   true,         // saat zoom maks, marker yang masih nyatu kebuka spiral
    disableClusteringAtZoom: 14,       // zoom 14+ tampil marker individual semua
    chunkedLoading:      true,         // load marker bertahap (anti-freeze)
    chunkInterval:       50,
    chunkDelay:          20,
    maxClusterRadius:    60,
    iconCreateFunction: function (cluster) {
        const count = cluster.getChildCount();
        let cls = 'cluster-small';
        if (count >= 100)      cls = 'cluster-huge';
        else if (count >= 30)  cls = 'cluster-large';
        else if (count >= 10)  cls = 'cluster-medium';
        return L.divIcon({
            html: '<div class="cluster-inner"><span>' + count + '</span></div>',
            className: 'station-cluster ' + cls,
            iconSize: L.point(44, 44),
        });
    },
});

// Lazy popup: HTML dibangun saat marker diklik (bukan saat init)
function buildPopupHtml(st, lat, lng) {
    const color  = pinColor(st.status);
    const total  = st.total_unit ?? (st.chargers?.length ?? 0);
    const aktif  = st.unit_aktif ?? 0;
    const inuse  = st.unit_in_use ?? 0;

    let chargerList = '';
    if (st.chargers && st.chargers.length) {
        chargerList = '<div class="popup-section"><div class="popup-section-title">Unit Charger</div>';
        st.chargers.forEach(c => {
            const stClass = c.status === 'Available' ? 'avail' :
                            c.status === 'In Use'    ? 'inuse' :
                            c.status === 'Maintenance' ? 'maint' : 'offline';
            chargerList += '<div class="popup-charger">' +
                '<span class="popup-charger-name">' + (c.kode_unit ?? '-') + '</span>' +
                '<span class="popup-charger-meta">' + (c.tipe ?? '-') + ' • ' + parseFloat(c.daya_kw ?? 0).toFixed(1) + ' kW</span>' +
                '<span class="popup-charger-status ' + stClass + '">' + (c.status ?? '-') + '</span>' +
                '</div>';
        });
        chargerList += '</div>';
    } else {
        chargerList = '<div class="popup-empty">Belum ada unit charger</div>';
    }

    const safeName = (st.nama_stasiun ?? '').replace(/'/g, "\\'");
    const safeLoc  = (st.lokasi ?? '').replace(/'/g, "\\'");

    return `
        <div class="station-popup">
            <div class="popup-header" style="--header-color:${color}">
                <div class="popup-title">${st.nama_stasiun ?? '-'}</div>
                <div class="popup-status" style="background:${color}">${st.status ?? '-'}</div>
            </div>
            <div class="popup-body">
                <div class="popup-loc">
                    <i class="fas fa-map-marker-alt"></i> ${st.lokasi && st.lokasi.trim() ? st.lokasi : '<em style="color:#cbd5e1">Lokasi belum diisi</em>'}
                </div>
                <div class="popup-stats">
                    <div class="popup-stat"><div class="popup-stat-val">${total}</div><div class="popup-stat-lbl">Total Unit</div></div>
                    <div class="popup-stat"><div class="popup-stat-val avail">${aktif}</div><div class="popup-stat-lbl">Tersedia</div></div>
                    <div class="popup-stat"><div class="popup-stat-val inuse">${inuse}</div><div class="popup-stat-lbl">Dipakai</div></div>
                </div>
                ${chargerList}
                <div class="popup-coords"><i class="fas fa-crosshairs"></i> ${lat.toFixed(6)}, ${lng.toFixed(6)}</div>
                <div class="popup-actions">
                    <a href="/station/${st.id}" class="popup-btn popup-btn-primary">
                        <i class="fas fa-eye"></i> Lihat Detail
                    </a>
                    <div class="popup-nav-wrap">
                        <button class="popup-btn popup-btn-nav" onclick="toggleNavMenu(event, ${lat}, ${lng}, '${safeName}')">
                            <i class="fas fa-route"></i> Navigasi
                            <i class="fas fa-chevron-up" style="font-size:9px;margin-left:2px"></i>
                        </button>
                        <div class="popup-nav-menu" id="navmenu-${st.id}">
                            <a href="https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}" target="_blank" class="nav-option">
                                <img src="https://www.google.com/favicon.ico" width="14" height="14" style="border-radius:2px"> Google Maps
                            </a>
                            <a href="https://waze.com/ul?ll=${lat},${lng}&navigate=yes" target="_blank" class="nav-option">
                                <img src="https://www.waze.com/favicon.ico" width="14" height="14" style="border-radius:2px"> Waze
                            </a>
                            <a href="https://maps.apple.com/?daddr=${lat},${lng}" target="_blank" class="nav-option">
                                <i class="fab fa-apple" style="width:14px;text-align:center;color:#555"></i> Apple Maps
                            </a>
                            <div class="nav-divider"></div>
                            <button class="nav-option" onclick="shareLocation(${lat}, ${lng}, '${safeName}', '${safeLoc}')">
                                <i class="fas fa-share-alt" style="width:14px;text-align:center;color:#1d4ed8"></i> Bagikan Lokasi
                            </button>
                            <button class="nav-option" onclick="copyCoords(${lat}, ${lng})">
                                <i class="fas fa-copy" style="width:14px;text-align:center;color:#64748b"></i> Salin Koordinat
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
}

stations.forEach(st => {
    const lat = parseFloat(st.latitude);
    const lng = parseFloat(st.longitude);
    if (!lat || !lng || isNaN(lat) || isNaN(lng)) return;

    const color  = pinColor(st.status);

    const marker = L.marker([lat, lng], { icon: makeIcon(color, st.status) });
    // Lazy popup: bind sebagai function — Leaflet panggil saat popup dibuka
    marker.bindPopup(() => buildPopupHtml(st, lat, lng), {
        maxWidth:    320,
        minWidth:    280,
        maxHeight:   480,         // popup auto-scroll kalau konten panjang
        autoPan:     true,        // peta otomatis geser supaya popup terlihat full
        autoPanPadding: [40, 60], // padding dari tepi peta saat auto-pan
        keepInView:  true,        // popup tidak boleh kepotong layar
        closeButton: true,
        className:   'station-popup-wrap',
    });

    clusterGroup.addLayer(marker);
    markers[st.id] = marker;
    bounds.push([lat, lng]);
});

map.addLayer(clusterGroup);

// Auto-fit ke semua marker (kalau ada)
if (bounds.length) {
    map.fitBounds(bounds, { padding: [40, 40], maxZoom: 11 });
}

// Fokus ke stasiun dari sidebar
function focusStation(id, lat, lng) {
    if (!lat || !lng) return;
    const marker = markers[id];
    if (marker) {
        // Buka cluster dulu kalau marker masih dalam cluster, baru flyTo + buka popup
        clusterGroup.zoomToShowLayer(marker, () => {
            map.flyTo([lat, lng], Math.max(map.getZoom(), 15), { animate: true, duration: 0.8 });
            setTimeout(() => marker.openPopup(), 700);
        });
    } else {
        map.flyTo([lat, lng], 15, { animate: true, duration: 1 });
    }
}

// Filter sidebar
function filterSidebar(val) {
    const items = document.querySelectorAll('.sidebar-item');
    const q     = val.toLowerCase();
    items.forEach(item => {
        const text = item.innerText.toLowerCase();
        item.style.display = text.includes(q) ? '' : 'none';
    });
}

// =================================================================
// SINKRON KOORDINAT — geocode otomatis stasiun yang lat/lng kosong
// =================================================================
async function syncCoordinates() {
    const btn    = document.getElementById('btnSyncCoord');
    const result = document.getElementById('syncResult');
    if (!btn) return;

    const original = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyinkron... (mohon tunggu)';

    if (result) {
        result.style.display = 'block';
        result.style.background = 'rgba(6,182,212,0.1)';
        result.style.border     = '1px solid rgba(6,182,212,0.3)';
        result.style.color      = '#22d3ee';
        result.innerHTML        = '<i class="fas fa-circle-notch fa-spin"></i> Mencari koordinat ke server OpenStreetMap, ini bisa makan beberapa detik per stasiun...';
    }

    try {
        const res = await fetch('{{ route("map-view.sync-coordinates") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept':       'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
        });
        const j = await res.json();

        if (j.success) {
            if (result) {
                const isOk = j.found > 0 && j.missing === 0;
                result.style.background = isOk
                    ? 'rgba(16,185,129,0.1)'
                    : 'rgba(245,158,11,0.1)';
                result.style.border = isOk
                    ? '1px solid rgba(16,185,129,0.3)'
                    : '1px solid rgba(245,158,11,0.3)';
                result.style.color = isOk ? '#34d399' : '#fbbf24';
                result.innerHTML = `<i class="fas fa-${isOk ? 'check-circle' : 'circle-info'}"></i> ${j.message}`;
            }
            // Reload halaman setelah 1.5 detik biar marker baru langsung tampil
            if (j.found > 0) {
                setTimeout(() => window.location.reload(), 1500);
            } else {
                btn.disabled = false;
                btn.innerHTML = original;
            }
        } else {
            if (result) {
                result.style.background = 'rgba(239,68,68,0.1)';
                result.style.border     = '1px solid rgba(239,68,68,0.3)';
                result.style.color      = '#f87171';
                result.innerHTML        = '<i class="fas fa-circle-xmark"></i> Gagal sinkron: ' + (j.message || 'unknown');
            }
            btn.disabled = false;
            btn.innerHTML = original;
        }
    } catch (e) {
        if (result) {
            result.style.background = 'rgba(239,68,68,0.1)';
            result.style.border     = '1px solid rgba(239,68,68,0.3)';
            result.style.color      = '#f87171';
            result.innerHTML        = '<i class="fas fa-circle-xmark"></i> Koneksi gagal: ' + e.message;
        }
        btn.disabled = false;
        btn.innerHTML = original;
    }
}

// =================================================================
// AUTO-REFRESH MARKER — disabled untuk performa
// =================================================================
// Sebelumnya halaman fetch ulang HTML tiap 30 detik utk cek perubahan stasiun.
// Itu bikin map lag. Sekarang admin tinggal klik tombol Sinkron Koordinat
// atau refresh manual saat butuh data terbaru.

// Toggle nav dropdown menu
function toggleNavMenu(e, lat, lng, name) {
    e.stopPropagation();
    // Tutup semua menu lain dulu
    document.querySelectorAll('.popup-nav-menu.open').forEach(m => m.classList.remove('open'));
    // Cari menu terdekat dari tombol
    const btn  = e.currentTarget;
    const menu = btn.parentElement.querySelector('.popup-nav-menu');
    if (menu) menu.classList.toggle('open');
}

// Tutup nav menu kalau klik di luar
document.addEventListener('click', function() {
    document.querySelectorAll('.popup-nav-menu.open').forEach(m => m.classList.remove('open'));
});

// Share lokasi via Web Share API atau fallback WhatsApp
function shareLocation(lat, lng, name, lokasi) {
    const mapsUrl = `https://www.google.com/maps?q=${lat},${lng}`;
    const text    = `?? *${name}*\n?? ${lokasi}\n??? Lokasi: ${mapsUrl}`;

    if (navigator.share) {
        navigator.share({
            title: `Lokasi SPKLU: ${name}`,
            text:  `${name} - ${lokasi}`,
            url:   mapsUrl
        }).catch(() => {});
    } else {
        // Fallback: buka WhatsApp share
        const waUrl = `https://wa.me/?text=${encodeURIComponent(text)}`;
        window.open(waUrl, '_blank');
    }
}

// Salin koordinat ke clipboard
function copyCoords(lat, lng) {
    const text = `${lat}, ${lng}`;
    navigator.clipboard.writeText(text).then(() => {
        // Toast notifikasi
        const toast = document.createElement('div');
        toast.className = 'coords-toast';
        toast.innerHTML = '<i class="fas fa-check-circle"></i> Koordinat disalin!';
        document.body.appendChild(toast);
        setTimeout(() => toast.classList.add('show'), 10);
        setTimeout(() => { toast.classList.remove('show'); setTimeout(() => toast.remove(), 300); }, 2000);
    });
}

// =================================================================
// ROUTING & TRACKING — fitur monitoring karyawan ke stasiun
// =================================================================
let routingControl = null;
let trackingMarker = null;
let trackingInterval = null;
let routeCoords = [];
let routeDistanceM = 0;
let routeDurationS = 0;
let trackProgress = 0;

function toggleRoutePanel() {
    document.getElementById('routePanel').classList.toggle('open');
}

function parseLatLng(value) {
    if (!value || value === 'current') return null;
    const parts = value.split(',');
    return { lat: parseFloat(parts[0]), lng: parseFloat(parts[1]) };
}

function getCurrentLocation() {
    return new Promise((resolve, reject) => {
        if (!navigator.geolocation) { reject('Browser tidak mendukung geolocation'); return; }
        navigator.geolocation.getCurrentPosition(
            pos => resolve({ lat: pos.coords.latitude, lng: pos.coords.longitude }),
            err => reject('Gagal ambil lokasi: ' + err.message),
            { enableHighAccuracy: true, timeout: 10000 }
        );
    });
}

async function calculateRoute() {
    const startVal = document.getElementById('routeStart').value;
    const endVal   = document.getElementById('routeEnd').value;
    if (!startVal || !endVal) { alert('Pilih titik mulai dan tujuan terlebih dahulu'); return; }

    let start;
    if (startVal === 'current') {
        try { start = await getCurrentLocation(); }
        catch (e) { alert(e); return; }
    } else { start = parseLatLng(startVal); }

    const end = parseLatLng(endVal);
    if (!start || !end) { alert('Koordinat tidak valid'); return; }

    clearRoute();

    routingControl = L.Routing.control({
        waypoints: [ L.latLng(start.lat, start.lng), L.latLng(end.lat, end.lng) ],
        routeWhileDragging: false,
        showAlternatives: false,
        addWaypoints: false,
        draggableWaypoints: false,
        fitSelectedRoutes: true,
        show: false,
        lineOptions: {
            styles: [
                { color: '#1e40af', weight: 7, opacity: 0.4 },
                { color: '#3b82f6', weight: 4, opacity: 1 }
            ]
        },
        createMarker: function(i, wp) {
            const isStart = i === 0;
            const color   = isStart ? '#16a34a' : '#dc2626';
            const icon    = isStart ? 'fa-circle' : 'fa-flag-checkered';
            return L.marker(wp.latLng, {
                icon: L.divIcon({
                    className: 'route-waypoint',
                    html: `<div class="route-wp-pin" style="background:${color}"><i class="fas ${icon}"></i></div>`,
                    iconSize: [32, 32],
                    iconAnchor: [16, 16]
                })
            });
        }
    }).addTo(map);

    routingControl.on('routesfound', function(e) {
        const route = e.routes[0];
        routeCoords    = route.coordinates;
        routeDistanceM = route.summary.totalDistance;
        routeDurationS = route.summary.totalTime;

        document.getElementById('routeDistance').textContent = (routeDistanceM / 1000).toFixed(1) + ' km';
        document.getElementById('routeDuration').textContent = formatDuration(routeDurationS);
        document.getElementById('routeInfo').style.display = 'block';
        document.getElementById('trackingPanel').style.display = 'block';
        document.getElementById('trackingEmpName').textContent = document.getElementById('empName').value || 'Karyawan';
    });
}

function clearRoute() {
    if (routingControl) { map.removeControl(routingControl); routingControl = null; }
    if (trackingMarker) { map.removeLayer(trackingMarker); trackingMarker = null; }
    stopTracking();
    document.getElementById('routeInfo').style.display = 'none';
    document.getElementById('trackingPanel').style.display = 'none';
    routeCoords = [];
    trackProgress = 0;
}

function formatDuration(seconds) {
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    if (h > 0) return h + ' jam ' + m + ' mnt';
    return m + ' menit';
}

function startTracking() {
    if (!routeCoords.length) { alert('Hitung rute terlebih dahulu'); return; }
    document.getElementById('btnStartTrack').style.display = 'none';
    document.getElementById('btnStopTrack').style.display = 'inline-flex';

    const empName = document.getElementById('empName').value || 'Karyawan';

    if (trackingMarker) map.removeLayer(trackingMarker);
    trackingMarker = L.marker(routeCoords[0], {
        icon: L.divIcon({
            className: 'employee-marker',
            html: `
                <div class="emp-marker-wrap">
                    <div class="emp-marker-pulse"></div>
                    <div class="emp-marker-body"><i class="fas fa-user"></i></div>
                    <div class="emp-marker-label">${empName}</div>
                </div>
            `,
            iconSize: [40, 50],
            iconAnchor: [20, 25]
        })
    }).addTo(map);

    const totalSteps = routeCoords.length;
    const stepInterval = 60000 / totalSteps;
    trackProgress = 0;

    trackingInterval = setInterval(() => {
        trackProgress++;
        if (trackProgress >= totalSteps) {
            stopTracking();
            updateProgressUI(100, 0);
            setTimeout(() => alert(empName + ' telah sampai di tujuan!'), 100);
            return;
        }
        trackingMarker.setLatLng(routeCoords[trackProgress]);
        const pct = Math.round((trackProgress / totalSteps) * 100);
        const remainingS = Math.round(routeDurationS * (1 - trackProgress / totalSteps));
        updateProgressUI(pct, remainingS);
    }, stepInterval);
}

function stopTracking() {
    if (trackingInterval) { clearInterval(trackingInterval); trackingInterval = null; }
    document.getElementById('btnStartTrack').style.display = 'inline-flex';
    document.getElementById('btnStopTrack').style.display = 'none';
}

function updateProgressUI(pct, remainingS) {
    document.getElementById('trackingProgress').style.width = pct + '%';
    document.getElementById('trackingPercent').textContent = pct + '%';
    document.getElementById('trackingETA').textContent = formatDuration(remainingS);
}

// =================================================================
// REAL GPS TRACKING — Karyawan kirim posisi dari HP-nya
// =================================================================
const realEmpMarkers = {}; // {token: marker}
let realTrackPollTimer = null;

async function createTrackingLink() {
    const empName  = document.getElementById('empName').value.trim();
    const stasiunVal = document.getElementById('routeEnd').value;

    if (!empName) {
        alert('Masukkan nama karyawan terlebih dahulu di kolom Nama Karyawan');
        return;
    }

    // Cari stasiun_tujuan_id dari select option
    let stasiunId = null;
    if (stasiunVal) {
        const opt = document.querySelector(`#routeEnd option[value="${stasiunVal}"]`);
        // Cari ID stasiun dengan match nama
        const stasiunNama = opt ? opt.dataset.name : null;
        if (stasiunNama) {
            const found = stations.find(s => s.nama_stasiun === stasiunNama);
            if (found) stasiunId = found.id;
        }
    }

    try {
        const res = await fetch('{{ route("tracking.create") }}', {
            method: 'POST',
            headers: {
                'Content-Type':     'application/json',
                'Accept':           'application/json',
                'X-CSRF-TOKEN':     document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                nama_karyawan:     empName,
                stasiun_tujuan_id: stasiunId,
            })
        });
        const j = await res.json();

        if (j.success) {
            // Tampilkan modal dengan link untuk di-copy
            showTrackingLinkModal(empName, j.url, j.is_local, j.warning);
            // Mulai polling jika belum
            startRealTrackingPoll();
        } else {
            alert('Gagal: ' + (j.message || 'Unknown error'));
        }
    } catch (err) {
        alert('Error: ' + err.message);
    }
}

function showTrackingLinkModal(empName, url, isLocal, warningMsg) {
    const wa = encodeURIComponent(`Halo ${empName}, klik link ini untuk tracking lokasi Anda:\n${url}\n\nBuka di HP, izinkan akses GPS, lalu klik "Mulai Tracking". Tetap buka tab selama perjalanan.`);

    const warningHtml = isLocal && warningMsg ? `
        <div class="track-modal-warning">
            <i class="fas fa-triangle-exclamation"></i>
            <div>
                <strong>Link belum bisa diakses dari HP karyawan</strong>
                <div style="margin-top:4px;line-height:1.5">${warningMsg}</div>
                <div style="margin-top:6px;font-size:11px">
                    <strong>Solusi cepat:</strong> jalankan <code>ngrok http 8000</code> atau host aplikasi ke domain publik. Untuk uji coba di WiFi yang sama, ganti <code>localhost</code> dengan IP komputer ini di link.
                </div>
            </div>
        </div>
    ` : '';

    const html = `
        <div class="track-modal-overlay" id="trackModalOverlay" onclick="if(event.target.id==='trackModalOverlay')closeTrackingModal()">
            <div class="track-modal">
                <div class="track-modal-header">
                    <h3><i class="fas fa-share-square"></i> Link Tracking Dibuat</h3>
                    <button onclick="closeTrackingModal()"><i class="fas fa-times"></i></button>
                </div>
                <div class="track-modal-body">
                    <p class="track-modal-emp"><i class="fas fa-user"></i> ${empName}</p>
                    ${warningHtml}
                    <p class="track-modal-desc">Kirim link berikut ke HP karyawan via WhatsApp:</p>
                    <div class="track-link-box">
                        <input type="text" id="trackLinkInput" value="${url}" readonly>
                        <button onclick="copyTrackingLink()"><i class="fas fa-copy"></i> Copy</button>
                    </div>
                    <a href="https://wa.me/?text=${wa}" target="_blank" class="track-wa-btn">
                        <i class="fab fa-whatsapp"></i> Kirim via WhatsApp
                    </a>
                    <div class="track-modal-note">
                        <i class="fas fa-info-circle"></i>
                        Karyawan tinggal buka link di HP, izinkan GPS, posisi langsung muncul di peta ini.
                    </div>
                </div>
            </div>
        </div>
    `;
    document.body.insertAdjacentHTML('beforeend', html);
}

function closeTrackingModal() {
    const m = document.getElementById('trackModalOverlay');
    if (m) m.remove();
}

function copyTrackingLink() {
    const input = document.getElementById('trackLinkInput');
    input.select();
    document.execCommand('copy');
    const btn = event.target.closest('button');
    const original = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-check"></i> Tersalin!';
    btn.style.background = '#16a34a';
    setTimeout(() => { btn.innerHTML = original; btn.style.background = ''; }, 2000);
}

function startRealTrackingPoll() {
    if (realTrackPollTimer) return;
    fetchActiveEmployees();
    realTrackPollTimer = setInterval(fetchActiveEmployees, 5000);
}

async function fetchActiveEmployees() {
    try {
        const res = await fetch('{{ route("tracking.all") }}');
        const j   = await res.json();
        if (j.success) updateRealEmployees(j.data);
    } catch (e) {}
}

function updateRealEmployees(employees) {
    const list = document.getElementById('activeEmpList');
    const wrap = document.getElementById('activeEmployees');
    const cnt  = document.getElementById('activeEmpCount');

    if (!employees.length) {
        wrap.style.display = 'none';
        // Hapus semua marker lama
        Object.values(realEmpMarkers).forEach(m => map.removeLayer(m));
        Object.keys(realEmpMarkers).forEach(k => delete realEmpMarkers[k]);
        return;
    }

    wrap.style.display = 'block';
    cnt.textContent = employees.length;

    // Hapus marker yang sudah tidak ada di data
    const activeTokens = new Set(employees.map(e => e.token));
    Object.keys(realEmpMarkers).forEach(token => {
        if (!activeTokens.has(token)) {
            map.removeLayer(realEmpMarkers[token]);
            delete realEmpMarkers[token];
        }
    });

    let html = '';
    employees.forEach(emp => {
        const statusClass = emp.status === 'on_the_way' ? 'live' :
                            emp.status === 'idle' ? 'idle' :
                            emp.status === 'arrived' ? 'arrived' : 'offline';
        const statusLabel = emp.status === 'on_the_way' ? '?? Live' :
                            emp.status === 'idle' ? '?? Idle' :
                            emp.status === 'arrived' ? '? Sampai' : '? Offline';
        const ago = emp.last_update_ago !== null ? formatAgo(emp.last_update_ago) : 'Belum aktif';

        html += `
            <div class="active-emp-item">
                <div class="active-emp-info">
                    <div class="active-emp-name">${emp.nama_karyawan}</div>
                    <div class="active-emp-meta">
                        <span class="emp-status-${statusClass}">${statusLabel}</span>
                        ${emp.stasiun_nama ? '? ' + emp.stasiun_nama : ''}
                    </div>
                    <div class="active-emp-time">${ago}</div>
                </div>
                <button class="emp-action-btn" onclick="focusEmployee(${emp.latitude || 0}, ${emp.longitude || 0})" title="Lihat di peta">
                    <i class="fas fa-eye"></i>
                </button>
                <button class="emp-action-btn emp-action-del" onclick="removeTracking('${emp.token}', '${emp.nama_karyawan}')" title="Hapus">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;

        // Update marker di peta
        if (emp.latitude && emp.longitude) {
            const pos = [parseFloat(emp.latitude), parseFloat(emp.longitude)];
            if (realEmpMarkers[emp.token]) {
                realEmpMarkers[emp.token].setLatLng(pos);
            } else {
                realEmpMarkers[emp.token] = L.marker(pos, {
                    icon: L.divIcon({
                        className: 'real-emp-marker',
                        html: `
                            <div class="real-emp-wrap">
                                <div class="real-emp-pulse"></div>
                                <div class="real-emp-body"><i class="fas fa-user"></i></div>
                                <div class="real-emp-label">${emp.nama_karyawan}</div>
                            </div>
                        `,
                        iconSize: [40, 50],
                        iconAnchor: [20, 25]
                    })
                }).addTo(map).bindPopup(
                    `<b>${emp.nama_karyawan}</b><br>` +
                    (emp.stasiun_nama ? `Tujuan: ${emp.stasiun_nama}<br>` : '') +
                    `Update: ${ago}`
                );
            }
        }
    });

    list.innerHTML = html;
}

function formatAgo(seconds) {
    if (seconds < 10) return 'Baru saja';
    if (seconds < 60) return seconds + ' detik lalu';
    if (seconds < 3600) return Math.floor(seconds/60) + ' menit lalu';
    return Math.floor(seconds/3600) + ' jam lalu';
}

function focusEmployee(lat, lng) {
    if (!lat || !lng) {
        alert('Karyawan belum mengirim lokasi GPS');
        return;
    }
    map.flyTo([lat, lng], 16, { animate: true, duration: 1.2 });
}

async function removeTracking(token, name) {
    if (!confirm(`Hapus tracking ${name}?`)) return;
    try {
        await fetch(`/tracking/${token}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN':     document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest',
            }
        });
        fetchActiveEmployees();
    } catch (e) { alert('Gagal: ' + e.message); }
}

// Auto-start polling saat halaman load (kalau ada tracking aktif)
window.addEventListener('load', () => {
    startRealTrackingPoll();
});
</script>










@endpush
