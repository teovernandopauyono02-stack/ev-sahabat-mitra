<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Tracking — EV Smart Energy</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
    <link rel="stylesheet" href="{{ asset('css/track.css') }}">
</head>
<body>
    <div class="card">
        <div class="card-header">
            <h1>📍 Live Tracking</h1>
            <p>EV Smart Energy Control Center</p>
        </div>
        <div class="card-body">
            <div class="info-row">
                <div class="info-icon"><i class="fas fa-user"></i></div>
                <div class="info-text">
                    <div class="info-label">Karyawan</div>
                    <div class="info-value">{{ $info['nama_karyawan'] }}</div>
                </div>
            </div>

            @if($info['stasiun_nama'])
            <div class="info-row">
                <div class="info-icon" style="background:#fef3c7;color:#d97706"><i class="fas fa-flag-checkered"></i></div>
                <div class="info-text">
                    <div class="info-label">Tujuan</div>
                    <div class="info-value">{{ $info['stasiun_nama'] }} — {{ $info['stasiun_lokasi'] }}</div>
                </div>
            </div>
            @endif

            <div id="statusBox" class="status-box idle">
                <div class="status-icon"><i class="fas fa-location-arrow"></i></div>
                <div class="status-title">Tracking Belum Aktif</div>
                <div class="status-msg">Klik tombol di bawah untuk mulai berbagi lokasi GPS Anda dengan admin.</div>
            </div>

            <div id="gpsStats" class="gps-stats" style="display:none">
                <div class="gps-stat">
                    <div class="gps-stat-label">Latitude</div>
                    <div class="gps-stat-val" id="statLat">-</div>
                </div>
                <div class="gps-stat">
                    <div class="gps-stat-label">Longitude</div>
                    <div class="gps-stat-val" id="statLng">-</div>
                </div>
                <div class="gps-stat">
                    <div class="gps-stat-label">Akurasi</div>
                    <div class="gps-stat-val" id="statAcc">-</div>
                </div>
                <div class="gps-stat">
                    <div class="gps-stat-label">Update Terakhir</div>
                    <div class="gps-stat-val" id="statTime">-</div>
                </div>
            </div>

            <button id="btnStart" class="btn-start">
                <i class="fas fa-play"></i> Mulai Tracking
            </button>

            <div class="footer-note">
                <i class="fas fa-shield-halved"></i>
                Lokasi GPS Anda hanya dibagikan ke admin selama browser ini terbuka.
                Tutup tab untuk berhenti.
            </div>

            <div class="last-update" id="lastUpdate"></div>
        </div>
    </div>

<script>
const TOKEN = '{{ $token }}';
const UPDATE_URL = '{{ route("tracking.update", ["token" => $token]) }}';
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

let watchId = null;
let updateCount = 0;
let lastSuccessTime = null;

const statusBox = document.getElementById('statusBox');
const btnStart  = document.getElementById('btnStart');
const gpsStats  = document.getElementById('gpsStats');

function setStatus(type, title, msg) {
    statusBox.className = 'status-box ' + type;
    let icon = 'fa-location-arrow';
    if (type === 'active') icon = 'fa-satellite-dish';
    if (type === 'error')  icon = 'fa-exclamation-triangle';

    statusBox.innerHTML = `
        <div class="status-icon"><i class="fas ${icon}"></i></div>
        <div class="status-title">${type === 'active' ? '<span class="pulse-dot"></span>' : ''}${title}</div>
        <div class="status-msg">${msg}</div>
    `;
}

function startTracking() {
    if (!navigator.geolocation) {
        setStatus('error', 'GPS Tidak Tersedia', 'Browser Anda tidak mendukung GPS.');
        return;
    }

    btnStart.disabled = true;
    btnStart.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Meminta Izin GPS...';

    watchId = navigator.geolocation.watchPosition(
        sendPosition,
        function(err) {
            let msg = 'Gagal mendapatkan lokasi GPS.';
            if (err.code === 1) msg = 'Anda menolak akses GPS. Aktifkan di pengaturan browser.';
            if (err.code === 2) msg = 'Lokasi tidak tersedia. Pastikan GPS aktif.';
            if (err.code === 3) msg = 'Timeout — sinyal GPS lemah.';
            setStatus('error', 'Gagal', msg);
            btnStart.disabled = false;
            btnStart.innerHTML = '<i class="fas fa-redo"></i> Coba Lagi';
        },
        {
            enableHighAccuracy: true,
            timeout: 15000,
            maximumAge: 0
        }
    );
}

function sendPosition(pos) {
    const lat = pos.coords.latitude;
    const lng = pos.coords.longitude;
    const acc = pos.coords.accuracy;
    const spd = pos.coords.speed;
    const hdg = pos.coords.heading;

    document.getElementById('statLat').textContent  = lat.toFixed(6);
    document.getElementById('statLng').textContent  = lng.toFixed(6);
    document.getElementById('statAcc').textContent  = acc ? Math.round(acc) + ' m' : '-';
    gpsStats.style.display = 'grid';

    fetch(UPDATE_URL, {
        method: 'POST',
        headers: {
            'Content-Type':     'application/json',
            'Accept':           'application/json',
            'X-CSRF-TOKEN':     CSRF,
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({
            latitude:  lat,
            longitude: lng,
            accuracy:  acc,
            speed:     spd,
            heading:   hdg,
        })
    })
    .then(r => r.json())
    .then(j => {
        if (j.success) {
            updateCount++;
            lastSuccessTime = new Date();
            document.getElementById('statTime').textContent = j.time;
            setStatus('active', 'Tracking Aktif', `Lokasi terkirim ${updateCount}x ke admin. Tetap buka halaman ini selama perjalanan.`);
            btnStart.style.display = 'none';
            document.getElementById('lastUpdate').textContent = 'Update terakhir: ' + new Date().toLocaleTimeString('id-ID');
        } else {
            setStatus('error', 'Gagal Kirim', j.message || 'Server menolak data.');
        }
    })
    .catch(err => {
        setStatus('error', 'Tidak Terhubung', 'Periksa koneksi internet Anda.');
    });
}

btnStart.addEventListener('click', startTracking);

// Auto-stop saat tab ditutup
window.addEventListener('beforeunload', function() {
    if (watchId !== null) navigator.geolocation.clearWatch(watchId);
});
</script>
</body>
</html>
