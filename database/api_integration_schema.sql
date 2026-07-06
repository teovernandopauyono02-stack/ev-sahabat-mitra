-- ============================================
-- API Integration Schema for EV Sahabat
-- Compatible with existing ev_sahabat database
-- ============================================

USE ev_sahabat;

-- Table untuk konfigurasi API eksternal
CREATE TABLE IF NOT EXISTS api_koneksi (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama_koneksi VARCHAR(100) NOT NULL,
    tipe_sistem ENUM('solar_panel', 'charging_station', 'energy_meter', 'other') DEFAULT 'solar_panel',
    api_url VARCHAR(500) NOT NULL,
    api_method ENUM('GET', 'POST') DEFAULT 'GET',
    api_username VARCHAR(100) COMMENT 'Username untuk Basic Auth atau API',
    api_password VARCHAR(255) COMMENT 'Password untuk Basic Auth atau API Key',
    api_header TEXT COMMENT 'JSON format untuk custom headers',
    response_path_kwh VARCHAR(255) DEFAULT 'data.kwh' COMMENT 'JSON path untuk ambil data kWh',
    response_path_voltage VARCHAR(255) COMMENT 'JSON path untuk voltage (optional)',
    response_path_current VARCHAR(255) COMMENT 'JSON path untuk current (optional)',
    response_path_power VARCHAR(255) COMMENT 'JSON path untuk power (optional)',
    is_active TINYINT(1) DEFAULT 1,
    sync_interval INT DEFAULT 300 COMMENT 'Interval sync dalam detik (default 5 menit)',
    waktu_sync_terakhir DATETIME COMMENT 'Waktu sync terakhir (konsisten dengan waktu_mulai)',
    waktu_dibuat TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    waktu_diupdate TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_active (is_active),
    INDEX idx_tipe (tipe_sistem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Konfigurasi koneksi API eksternal';

-- Table untuk menyimpan data kWh dari API
CREATE TABLE IF NOT EXISTS api_data_energi (
    id INT PRIMARY KEY AUTO_INCREMENT,
    koneksi_id INT NOT NULL,
    nilai_kwh DECIMAL(10,2) NOT NULL COMMENT 'Nilai kWh yang diambil',
    voltage DECIMAL(10,2) COMMENT 'Tegangan (V)',
    current_ampere DECIMAL(10,2) COMMENT 'Arus (A)',
    power_watt DECIMAL(10,2) COMMENT 'Daya (W)',
    waktu_data DATETIME NOT NULL COMMENT 'Timestamp data dari API',
    waktu_mulai DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Waktu data masuk ke sistem',
    raw_response TEXT COMMENT 'Raw JSON response untuk debugging',
    FOREIGN KEY (koneksi_id) REFERENCES api_koneksi(id) ON DELETE CASCADE,
    INDEX idx_koneksi_waktu (koneksi_id, waktu_data),
    INDEX idx_waktu_mulai (waktu_mulai)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Data energi yang diambil dari API eksternal';

-- Table untuk log sync API
CREATE TABLE IF NOT EXISTS api_log_sync (
    id INT PRIMARY KEY AUTO_INCREMENT,
    koneksi_id INT NOT NULL,
    status ENUM('success', 'failed', 'warning') NOT NULL,
    pesan TEXT,
    response_time_ms INT COMMENT 'Waktu response dalam milidetik',
    http_code INT COMMENT 'HTTP status code',
    waktu_mulai DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (koneksi_id) REFERENCES api_koneksi(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_waktu (waktu_mulai)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Log sinkronisasi API';

-- Sample data untuk HopeWind Solar Panel API
INSERT INTO api_koneksi (
    nama_koneksi, 
    tipe_sistem, 
    api_url, 
    api_method, 
    api_username,
    api_password,
    response_path_kwh,
    response_path_voltage,
    response_path_current,
    response_path_power,
    sync_interval
) VALUES (
    'HopeWind Solar Panel',
    'solar_panel',
    'http://openapi.hopewindcloud.eu/openApi/getStationData',
    'POST',
    'your_username',
    'your_password',
    'data.pac',
    'data.gridVoltage',
    'data.gridCurrent',
    'data.pac',
    300
);

-- Create view untuk monitoring
CREATE OR REPLACE VIEW view_api_monitoring AS
SELECT 
    k.id,
    k.nama_koneksi,
    k.tipe_sistem,
    k.is_active,
    k.waktu_sync_terakhir,
    (SELECT COUNT(*) FROM api_data_energi WHERE koneksi_id = k.id) as total_data,
    (SELECT nilai_kwh FROM api_data_energi WHERE koneksi_id = k.id ORDER BY waktu_data DESC LIMIT 1) as kwh_terakhir,
    (SELECT waktu_data FROM api_data_energi WHERE koneksi_id = k.id ORDER BY waktu_data DESC LIMIT 1) as waktu_data_terakhir,
    (SELECT COUNT(*) FROM api_log_sync WHERE koneksi_id = k.id AND status = 'success' AND DATE(waktu_mulai) = CURDATE()) as sync_success_hari_ini,
    (SELECT COUNT(*) FROM api_log_sync WHERE koneksi_id = k.id AND status = 'failed' AND DATE(waktu_mulai) = CURDATE()) as sync_failed_hari_ini
FROM api_koneksi k;