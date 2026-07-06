<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$page_title = "API Integration";
$current_page = "api-integration";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - EV Smart Energy Control Center</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/api-integration.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        
        <div class="content-wrapper">
            <!-- Page Header -->
            <div class="page-header">
                <div class="header-left">
                    <h1><i class="fas fa-plug-circle-bolt"></i> API Integration</h1>
                    <p class="subtitle">Kelola koneksi API untuk mengambil data kWh dari sistem eksternal (Solar Panel, Energy Meter, dll)</p>
                </div>
                <div class="header-actions">
                    <button class="btn btn-secondary" onclick="window.location.reload()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                    <button class="btn btn-primary" onclick="openTambahModal()">
                        <i class="fas fa-plus-circle"></i> Tambah Koneksi API
                    </button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="stats-container">
                <div class="stat-card blue">
                    <div class="stat-icon">
                        <i class="fas fa-link"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Total Koneksi</div>
                        <div class="stat-value" id="statTotalKoneksi">0</div>
                    </div>
                </div>
                
                <div class="stat-card green">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Koneksi Aktif</div>
                        <div class="stat-value" id="statKoneksiAktif">0</div>
                    </div>
                </div>
                
                <div class="stat-card yellow">
                    <div class="stat-icon">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">kWh Hari Ini</div>
                        <div class="stat-value" id="statKwhHariIni">0</div>
                    </div>
                </div>
                
                <div class="stat-card purple">
                    <div class="stat-icon">
                        <i class="fas fa-database"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Total Data</div>
                        <div class="stat-value" id="statTotalData">0</div>
                    </div>
                </div>
            </div>

            <!-- Connections Table -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-list-ul"></i> Daftar Koneksi API</h3>
                    <button class="btn btn-sm btn-success" onclick="syncSemuaKoneksi()">
                        <i class="fas fa-sync"></i> Sync Semua Koneksi
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table" id="tabelKoneksi">
                            <thead>
                                <tr>
                                    <th>Nama Koneksi</th>
                                    <th>Tipe</th>
                                    <th>URL API</th>
                                    <th>Status</th>
                                    <th>Sync Terakhir</th>
                                    <th>kWh Terakhir</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="7" class="text-center">
                                        <i class="fas fa-spinner fa-spin"></i> Loading...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Chart Section -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-chart-line"></i> Grafik Data Energi Real-time</h3>
                    <select class="form-select" id="selectKoneksiChart" onchange="loadChartData()">
                        <option value="">-- Pilih Koneksi --</option>
                    </select>
                </div>
                <div class="card-body">
                    <div class="chart-wrapper">
                        <canvas id="chartEnergi"></canvas>
                    </div>
                </div>
            </div>

            <!-- Sync Logs -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-history"></i> Log Sinkronisasi (50 Terakhir)</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm" id="tabelLogSync">
                            <thead>
                                <tr>
                                    <th>Waktu</th>
                                    <th>Koneksi</th>
                                    <th>Status</th>
                                    <th>Response Time</th>
                                    <th>HTTP Code</th>
                                    <th>Pesan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="6" class="text-center">Belum ada log</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tambah/Edit Koneksi -->
    <div id="modalKoneksi" class="modal fade">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 id="modalTitle">Tambah Koneksi API</h2>
                    <button type="button" class="btn-close" onclick="tutupModal()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="formKoneksi">
                        <input type="hidden" id="koneksiId" name="id">
                        
                        <!-- Nama & Tipe -->
                        <div class="form-row">
                            <div class="form-group">
                                <label>Nama Koneksi *</label>
                                <input type="text" class="form-control" id="namaKoneksi" name="nama_koneksi" required placeholder="Contoh: HopeWind Solar Panel">
                            </div>
                            <div class="form-group">
                                <label>Tipe Sistem *</label>
                                <select class="form-control" id="tipeSistem" name="tipe_sistem" required>
                                    <option value="solar_panel">Solar Panel</option>
                                    <option value="charging_station">Charging Station</option>
                                    <option value="energy_meter">Energy Meter</option>
                                    <option value="other">Lainnya</option>
                                </select>
                            </div>
                        </div>

                        <!-- API URL & Method -->
                        <div class="form-row">
                            <div class="form-group flex-2">
                                <label>API URL *</label>
                                <input type="url" class="form-control" id="apiUrl" name="api_url" required placeholder="http://openapi.hopewindcloud.eu/openApi/getStationData">
                            </div>
                            <div class="form-group">
                                <label>Method *</label>
                                <select class="form-control" id="apiMethod" name="api_method" required>
                                    <option value="GET">GET</option>
                                    <option value="POST">POST</option>
                                </select>
                            </div>
                        </div>

                        <!-- Authentication -->
                        <div class="form-row">
                            <div class="form-group">
                                <label>Username / API Key</label>
                                <input type="text" class="form-control" id="apiUsername" name="api_username" placeholder="Username atau Client ID">
                            </div>
                            <div class="form-group">
                                <label>Password / Secret</label>
                                <input type="password" class="form-control" id="apiPassword" name="api_password" placeholder="Password atau API Secret">
                            </div>
                        </div>

                        <!-- Custom Headers -->
                        <div class="form-group">
                            <label>Custom Headers (JSON Format - Optional)</label>
                            <textarea class="form-control" id="apiHeader" name="api_header" rows="2" placeholder='{"Authorization": "Bearer xxx", "X-Custom-Header": "value"}'></textarea>
                            <small class="form-text">Format JSON untuk custom headers tambahan</small>
                        </div>

                        <!-- Response Paths -->
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> 
                            <strong>Response Path:</strong> Gunakan dot notation untuk navigasi JSON. 
                            Contoh: <code>data.pac</code> untuk <code>{"data": {"pac": 123.45}}</code>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Path untuk kWh *</label>
                                <input type="text" class="form-control" id="responsePathKwh" name="response_path_kwh" required placeholder="data.pac">
                            </div>
                            <div class="form-group">
                                <label>Path untuk Voltage</label>
                                <input type="text" class="form-control" id="responsePathVoltage" name="response_path_voltage" placeholder="data.gridVoltage">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Path untuk Current</label>
                                <input type="text" class="form-control" id="responsePathCurrent" name="response_path_current" placeholder="data.gridCurrent">
                            </div>
                            <div class="form-group">
                                <label>Path untuk Power</label>
                                <input type="text" class="form-control" id="responsePathPower" name="response_path_power" placeholder="data.pac">
                            </div>
                        </div>

                        <!-- Sync Settings -->
                        <div class="form-row">
                            <div class="form-group">
                                <label>Interval Sync (detik) *</label>
                                <input type="number" class="form-control" id="syncInterval" name="sync_interval" required value="300" min="60" max="86400">
                                <small class="form-text">Minimum 60 detik (1 menit)</small>
                            </div>
                            <div class="form-group">
                                <label>Status *</label>
                                <select class="form-control" id="isActive" name="is_active" required>
                                    <option value="1">Aktif</option>
                                    <option value="0">Nonaktif</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="testKoneksiAPI()">
                        <i class="fas fa-vial"></i> Test Koneksi
                    </button>
                    <button type="button" class="btn btn-light" onclick="tutupModal()">
                        <i class="fas fa-times"></i> Batal
                    </button>
                    <button type="button" class="btn btn-primary" onclick="simpanKoneksi()">
                        <i class="fas fa-save"></i> Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom JS -->
    <script src="assets/js/api-integration.js"></script>
</body>
</html>