/* ============================================================
   dashboard.js - Dashboard interactive logic
   ============================================================ */

// ===== CLOCK =====
function updateClock() {
    const el = document.getElementById('clock');
    if (!el) return;
    const now = new Date();
    el.textContent = now.toLocaleTimeString('id-ID', {
        hour: '2-digit', minute: '2-digit', second: '2-digit'
    });
}
updateClock();
setInterval(updateClock, 1000);

// ===== MODAL HELPERS =====
function openModal(id) {
    const el = document.getElementById(id);
    if (el) el.style.display = 'flex';
}
function closeModal(id) {
    const el = document.getElementById(id);
    if (el) el.style.display = 'none';
}

document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function(e) {
        if (e.target === this) this.style.display = 'none';
    });
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay').forEach(m => m.style.display = 'none');
    }
});

// ===== ENERGY CHART =====
let energyChart;

function initEnergyChart(labels, values) {
    const ctx = document.getElementById('energyChart');
    if (!ctx) return;

    if (energyChart) energyChart.destroy();

    const fallbackLabels = ['08:00','10:00','12:00','14:00','16:00'];
    const fallbackValues = [20, 38, 50, 44, 56];

    const finalLabels = (labels && labels.length) ? labels : fallbackLabels;
    const finalValues = (values && values.length) ? values : fallbackValues;

    energyChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: finalLabels,
            datasets: [{
                label: 'Energi (kWh)',
                data: finalValues,
                borderColor: '#FFD700',
                backgroundColor: function(context) {
                    const chart = context.chart;
                    const { ctx: c, chartArea } = chart;
                    if (!chartArea) return 'rgba(255,215,0,0.08)';
                    const gradient = c.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                    gradient.addColorStop(0, 'rgba(255,215,0,0.30)');
                    gradient.addColorStop(0.5, 'rgba(255,165,0,0.08)');
                    gradient.addColorStop(1, 'rgba(255,215,0,0.00)');
                    return gradient;
                },
                borderWidth: 2.5,
                pointBackgroundColor: '#0f1c30',
                pointBorderColor: '#FFD700',
                pointBorderWidth: 2,
                pointRadius: 3,
                pointHoverRadius: 6,
                pointHoverBackgroundColor: '#FFD700',
                pointHoverBorderColor: '#fff',
                pointHoverBorderWidth: 2,
                tension: 0.5,
                fill: true,
                cubicInterpolationMode: 'monotone'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                duration: 800,
                easing: 'easeInOutCubic'
            },
            interaction: {
                mode: 'index',
                intersect: false
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(20,25,40,0.95)',
                    borderColor: 'rgba(255,215,0,0.5)',
                    borderWidth: 1,
                    titleColor: '#FFD700',
                    bodyColor: '#c9d1d9',
                    padding: { top: 10, bottom: 10, left: 14, right: 14 },
                    displayColors: false,
                    cornerRadius: 8,
                    callbacks: {
                        title: ctx => '📅 ' + ctx[0].label,
                        label: ctx => '⚡ ' + ctx.parsed.y.toLocaleString() + ' kWh'
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        color: 'rgba(255,255,255,0.04)',
                        drawBorder: false,
                        tickLength: 0
                    },
                    ticks: {
                        color: '#6e7681',
                        font: { size: 11, family: 'Inter, sans-serif' },
                        maxRotation: 0,
                        padding: 8
                    },
                    border: { display: false }
                },
                y: {
                    grid: {
                        color: 'rgba(255,255,255,0.04)',
                        drawBorder: false,
                        tickLength: 0
                    },
                    ticks: {
                        color: '#6e7681',
                        font: { size: 11, family: 'Inter, sans-serif' },
                        padding: 10,
                        callback: val => val.toLocaleString() + ' kWh'
                    },
                    border: { display: false },
                    min: 0
                }
            }
        }
    });
}

function loadChartData(range, btn) {
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');

    const stationId = document.getElementById('stationFilter')?.value || '';

    fetch(`/dashboard/chart-data?range=${range}&stasiun_id=${stationId}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        const labels = data.map(d => d.label ?? (String(d.hour).padStart(2,'0') + ':00'));
        const values = data.map(d => parseFloat(d.total));
        initEnergyChart(labels, values);
    })
    .catch(() => initEnergyChart([], []));
}

document.getElementById('stationFilter')?.addEventListener('change', function() {
    const activeBtn = document.querySelector('.filter-btn.active');
    const range = activeBtn ? activeBtn.textContent : '1H';
    loadChartData(range, activeBtn);
});

// ===== MAP INIT =====
function initMap(stations) {
    const mapEl = document.getElementById('map');
    if (!mapEl || typeof L === 'undefined') return;

    const map = L.map('map', { zoomControl: true, scrollWheelZoom: false }).setView([-2.5, 118], 5);

    // Pakai CartoDB Voyager — sama seperti Map View utama
    L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
        attribution: '© OpenStreetMap, © CARTO',
        maxZoom: 19,
        subdomains: 'abcd',
    }).addTo(map);

    // Pin marker style sama persis kayak Map View
    function makePin(color, status) {
        const icon = status === 'Active' ? '⚡' : (status === 'Maintenance' ? '🔧' : '⏸');
        return L.divIcon({
            className: 'dash-pin',
            html: `
                <div class="dash-pin-wrap" style="--pin-color:${color}">
                    <div class="dash-pin-body">
                        <div class="dash-pin-icon">${icon}</div>
                    </div>
                </div>
            `,
            iconSize:    [28, 36],
            iconAnchor:  [14, 34],
            popupAnchor: [0, -32]
        });
    }

    const bounds = [];
    stations.forEach(st => {
        const lat = parseFloat(st.latitude);
        const lng = parseFloat(st.longitude);
        if (!lat || !lng || isNaN(lat) || isNaN(lng)) return;

        const color = st.status === 'Active' ? '#22c55e'
                    : st.status === 'Maintenance' ? '#ef4444' : '#ffc800';

        L.marker([lat, lng], { icon: makePin(color, st.status) })
            .addTo(map)
            .bindPopup(`
                <div style="font-family:Inter,sans-serif;font-size:12px;min-width:160px">
                    <div style="font-weight:700;font-size:14px;color:#0d1b3e">${st.nama_stasiun}</div>
                    <div style="color:#666;margin:2px 0 4px">📍 ${st.lokasi}</div>
                    <div><span style="background:${color};color:#fff;padding:2px 10px;border-radius:12px;font-size:10px;font-weight:700">${st.status}</span></div>
                </div>
            `);
        bounds.push([lat, lng]);
    });

    if (bounds.length) {
        map.fitBounds(bounds, { padding: [30, 30], maxZoom: 8 });
    }

    // Inject style buat pin (cuma sekali)
    if (!document.getElementById('dashPinStyle')) {
        const s = document.createElement('style');
        s.id = 'dashPinStyle';
        s.textContent = `
            .dash-pin { background:transparent; border:none; }
            .dash-pin-wrap { position:relative; width:28px; height:36px; }
            .dash-pin-body {
                width:28px; height:28px;
                background: var(--pin-color);
                border:2.5px solid #fff;
                border-radius:50% 50% 50% 0;
                transform:rotate(-45deg);
                box-shadow:0 2px 8px rgba(0,0,0,0.3);
                display:flex; align-items:center; justify-content:center;
            }
            .dash-pin-icon {
                transform:rotate(45deg);
                font-size:13px; color:#fff;
            }
        `;
        document.head.appendChild(s);
    }
}

// ===== REAL-TIME CHART POLLING =====
let chartPollTimer = null;

function startChartRealtime() {
    // Polling tiap 30 detik dengan range yg lagi aktif
    if (chartPollTimer) clearInterval(chartPollTimer);
    chartPollTimer = setInterval(() => {
        // Skip kalau ada modal/form aktif
        const openModal = document.querySelector('.modal-overlay[style*="flex"]');
        if (openModal) return;

        const activeBtn = document.querySelector('.filter-btn.active');
        const range     = activeBtn ? activeBtn.textContent : '1J';
        const stationId = document.getElementById('stationFilter')?.value || '';

        fetch(`/dashboard/chart-data?range=${range}&stasiun_id=${stationId}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            if (!data || !data.length) return;
            const labels = data.map(d => d.label ?? (String(d.hour).padStart(2,'0') + ':00'));
            const values = data.map(d => parseFloat(d.total));
            // Update chart smooth, gak destroy
            if (energyChart) {
                energyChart.data.labels = labels;
                energyChart.data.datasets[0].data = values;
                energyChart.update('active');
            }
        })
        .catch(() => {});
    }, 30000); // 30 detik
}

// Auto-start polling pas chart udah init
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(startChartRealtime, 2000);
});