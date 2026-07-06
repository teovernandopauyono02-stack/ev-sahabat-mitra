let chartInstance = null;
let koneksiList = [];

document.addEventListener('DOMContentLoaded', function () {
    loadStats();
    loadKoneksi();
    loadSyncLogs();
});

function loadStats() {
    fetch('/api-integration/stats', {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            document.getElementById('statTotalKoneksi').textContent = res.data.total_koneksi;
            document.getElementById('statKoneksiAktif').textContent = res.data.koneksi_aktif;
            document.getElementById('statKwhHariIni').textContent = res.data.kwh_hari_ini + ' kWh';
            document.getElementById('statTotalData').textContent = res.data.total_data;
        }
    })
    .catch(() => {});
}

function loadKoneksi() {
    const tbody = document.querySelector('#tabelKoneksi tbody');
    tbody.innerHTML = '<tr><td colspan="7" class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>';
    fetch('/api-integration/connections', {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(res => {
        if (!res.success || res.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center" style="color:#94a3b8;padding:32px">Belum ada koneksi API</td></tr>';
            return;
        }
        koneksiList = res.data;
        updateSelectChart(koneksiList);
        tbody.innerHTML = koneksiList.map(k => `
            <tr>
                <td><strong>${escHtml(k.nama_koneksi)}</strong></td>
                <td>${tipeBadge(k.tipe_sistem)}</td>
                <td><span class="url-text" title="${escHtml(k.api_url)}">${escHtml(k.api_url)}</span></td>
                <td>${statusBadge(k.is_active, k.last_sync_status)}</td>
                <td style="font-size:0.8rem;color:#64748b">${k.last_sync_at ? formatWaktu(k.last_sync_at) : '-'}</td>
                <td>-</td>
                <td>
                    <div class="action-btns">
                        <button class="btn btn-sm btn-warning" onclick="syncKoneksi(${k.id})"><i class="fas fa-sync"></i></button>
                        <button class="btn btn-sm btn-primary" onclick="editKoneksi(${k.id})"><i class="fas fa-edit"></i></button>
                        <button class="btn btn-sm btn-danger" onclick="hapusKoneksi(${k.id}, '${escHtml(k.nama_koneksi)}')"><i class="fas fa-trash"></i></button>
                    </div>
                </td>
            </tr>
        `).join('');
    })
    .catch(() => {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center" style="color:#ef4444">Gagal memuat data</td></tr>';
    });
}

function updateSelectChart(list) {
    const sel = document.getElementById('selectKoneksiChart');
    sel.innerHTML = '<option value="">-- Pilih Koneksi --</option>' +
        list.map(k => `<option value="${k.id}">${escHtml(k.nama_koneksi)}</option>`).join('');
}

function loadSyncLogs(connectionId = '') {
    const tbody = document.querySelector('#tabelLogSync tbody');
    if (!tbody) return;
    tbody.innerHTML = '<tr><td colspan="6" class="text-center"><i class="fas fa-spinner fa-spin"></i></td></tr>';
    const url = connectionId ? `/api-integration/logs?connection_id=${connectionId}` : '/api-integration/logs';
    fetch(url, {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(res => {
        if (!res.success || res.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center" style="color:#94a3b8;padding:20px">Belum ada log</td></tr>';
            return;
        }
        tbody.innerHTML = res.data.map(log => `
            <tr>
                <td style="font-size:0.78rem;color:#64748b;white-space:nowrap">${formatWaktu(log.created_at)}</td>
                <td>${escHtml(log.connection?.nama_koneksi ?? '-')}</td>
                <td>${logStatusBadge(log.status)}</td>
                <td>${log.response_time ? log.response_time + ' ms' : '-'}</td>
                <td>${log.http_code ?? '-'}</td>
                <td>${log.kwh_value ? parseFloat(log.kwh_value).toFixed(2) + ' kWh' : '-'}</td>
            </tr>
        `).join('');
    })
    .catch(() => {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center" style="color:#ef4444">Gagal memuat log</td></tr>';
    });
}

function loadChartData() {
    const connectionId = document.getElementById('selectKoneksiChart').value;
    if (!connectionId) return;
    fetch(`/api-integration/${connectionId}/chart-data`, {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(res => { if (res.success) renderChart(res.data); })
    .catch(() => {});
}

function renderChart(data) {
    const ctx = document.getElementById('chartEnergi').getContext('2d');
    if (chartInstance) chartInstance.destroy();
    chartInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.labels,
            datasets: [
                { label: 'kWh', data: data.kwh, borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,0.08)', tension: 0.4, fill: true, pointRadius: 3 },
                { label: 'Voltage (V)', data: data.voltage, borderColor: '#f59e0b', backgroundColor: 'transparent', tension: 0.4, fill: false, pointRadius: 3 },
                { label: 'Current (A)', data: data.current, borderColor: '#22c55e', backgroundColor: 'transparent', tension: 0.4, fill: false, pointRadius: 3 },
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { position: 'top' }, tooltip: { mode: 'index', intersect: false } },
            scales: { x: { grid: { color: '#f1f5f9' } }, y: { grid: { color: '#f1f5f9' }, beginAtZero: true } }
        }
    });
}

function openTambahModal() {
    document.getElementById('modalTitle').textContent = 'Tambah Koneksi API';
    document.getElementById('formKoneksi').reset();
    document.getElementById('koneksiId').value = '';
    document.getElementById('syncInterval').value = '300';
    document.getElementById('isActive').value = '1';
    document.getElementById('modalKoneksi').style.display = 'flex';
}

function editKoneksi(id) {
    const k = koneksiList.find(x => x.id == id);
    if (!k) return;
    document.getElementById('modalTitle').textContent = 'Edit Koneksi API';
    document.getElementById('koneksiId').value = k.id;
    document.getElementById('namaKoneksi').value = k.nama_koneksi;
    document.getElementById('tipeSistem').value = k.tipe_sistem;
    document.getElementById('apiUrl').value = k.api_url;
    document.getElementById('apiMethod').value = k.api_method;
    document.getElementById('apiUsername').value = k.api_username ?? '';
    document.getElementById('apiPassword').value = '';
    document.getElementById('apiHeader').value = k.api_header ?? '';
    document.getElementById('responsePathKwh').value = k.response_path_kwh;
    document.getElementById('responsePathVoltage').value = k.response_path_voltage ?? '';
    document.getElementById('responsePathCurrent').value = k.response_path_current ?? '';
    document.getElementById('responsePathPower').value = k.response_path_power ?? '';
    document.getElementById('syncInterval').value = k.sync_interval;
    document.getElementById('isActive').value = k.is_active ? '1' : '0';
    document.getElementById('modalKoneksi').style.display = 'flex';
}

function tutupModal() {
    document.getElementById('modalKoneksi').style.display = 'none';
}

document.addEventListener('click', function (e) {
    if (e.target.id === 'modalKoneksi') tutupModal();
});

function simpanKoneksi() {
    const id = document.getElementById('koneksiId').value;
    const formData = new FormData(document.getElementById('formKoneksi'));
    const url = id ? `/api-integration/${id}/update` : '/api-integration';
    fetch(url, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': getCsrf(), 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            showToast(res.message, 'success');
            tutupModal();
            loadKoneksi();
            loadStats();
        } else {
            const errors = res.errors ? Object.values(res.errors).flat().join('\n') : res.message;
            showToast(errors, 'error');
        }
    })
    .catch(err => showToast('Terjadi kesalahan: ' + err.message, 'error'));
}

function hapusKoneksi(id, nama) {
    if (!confirm(`Hapus koneksi "${nama}"?\nSemua log sync juga akan dihapus.`)) return;
    fetch(`/api-integration/${id}/delete`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': getCsrf(), 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) { showToast(res.message, 'success'); loadKoneksi(); loadStats(); }
        else showToast(res.message, 'error');
    })
    .catch(() => showToast('Terjadi kesalahan', 'error'));
}

function syncKoneksi(id) {
    const k = koneksiList.find(x => x.id == id);
    showToast(`Menyinkronkan ${k ? k.nama_koneksi : ''}...`, 'info');
    fetch(`/api-integration/${id}/fetch`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': getCsrf(), 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(res => { showToast(res.message, res.success ? 'success' : 'error'); loadKoneksi(); loadStats(); loadSyncLogs(); })
    .catch(() => showToast('Gagal sync', 'error'));
}

function syncSemuaKoneksi() {
    showToast('Menyinkronkan semua koneksi...', 'info');
    fetch('/api-integration/sync-all', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': getCsrf(), 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(res => { showToast(res.message, res.success ? 'success' : 'error'); loadKoneksi(); loadStats(); loadSyncLogs(); })
    .catch(() => showToast('Gagal sync', 'error'));
}

function testKoneksiAPI() {
    const apiUrl = document.getElementById('apiUrl').value;
    const pathKwh = document.getElementById('responsePathKwh').value;
    if (!apiUrl || !pathKwh) { showToast('Isi URL API dan Path kWh terlebih dahulu', 'error'); return; }
    showToast('Menguji koneksi...', 'info');
    const formData = new FormData(document.getElementById('formKoneksi'));
    fetch('/api-integration/test-connection', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': getCsrf(), 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            const d = res.data;
            showToast(`Berhasil! HTTP ${d.http_code} | ${d.response_time}ms | kWh: ${d.kwh_value ?? 'N/A'}`, 'success', 5000);
        } else {
            showToast(res.message, 'error', 5000);
        }
    })
    .catch(() => showToast('Gagal menguji koneksi', 'error'));
}

function getCsrf() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

function escHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function formatWaktu(str) {
    if (!str) return '-';
    const d = new Date(str);
    return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) + ' ' + d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
}

function tipeBadge(tipe) {
    const map = { solar_panel: ['tipe-solar', 'fa-sun', 'Solar Panel'], charging_station: ['tipe-charging', 'fa-bolt', 'Charging Station'], energy_meter: ['tipe-meter', 'fa-bolt', 'Energy Meter'], other: ['tipe-other', 'fa-circle', 'Lainnya'] };
    const [cls, icon, label] = map[tipe] ?? map['other'];
    return `<span class="tipe-badge ${cls}"><i class="fas ${icon}"></i> ${label}</span>`;
}

function statusBadge(isActive, lastStatus) {
    if (!isActive) return '<span class="badge badge-secondary"><i class="fas fa-pause"></i> Nonaktif</span>';
    if (lastStatus === 'success') return '<span class="badge badge-success"><i class="fas fa-check"></i> Aktif</span>';
    if (lastStatus === 'failed') return '<span class="badge badge-danger"><i class="fas fa-times"></i> Error</span>';
    return '<span class="badge badge-info"><i class="fas fa-circle"></i> Aktif</span>';
}

function logStatusBadge(status) {
    if (status === 'success') return '<span class="badge badge-success">Success</span>';
    if (status === 'timeout') return '<span class="badge badge-warning">Timeout</span>';
    return '<span class="badge badge-danger">Failed</span>';
}

function showToast(message, type = 'info', duration = 3000) {
    document.querySelectorAll('.api-toast').forEach(t => t.remove());
    const colors = { success: '#22c55e', error: '#ef4444', info: '#3b82f6', warning: '#f59e0b' };
    const toast = document.createElement('div');
    toast.className = 'api-toast';
    toast.style.cssText = `position:fixed;bottom:24px;right:24px;background:${colors[type]??colors.info};color:#fff;padding:12px 20px;border-radius:10px;font-size:0.875rem;font-weight:500;box-shadow:0 4px 20px rgba(0,0,0,0.2);z-index:9999;max-width:360px;line-height:1.5;`;
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), duration);
}