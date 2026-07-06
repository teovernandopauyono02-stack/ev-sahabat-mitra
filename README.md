# ⚡ EV Smart Energy Control Center

> Sistem monitoring, kontrol, dan analitik konsumsi energi stasiun pengisian kendaraan listrik berbasis web.

[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?logo=laravel)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.3+-777BB4?logo=php)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql)](https://mysql.com)
[![License](https://img.shields.io/badge/license-Proprietary-blue)]()

Dikembangkan oleh **TEO VERNANDO PAUYONO** — Magang di **PT Sahabat Mitra Intrabuana (Go Power)**.

---

## 📚 Daftar Isi

1. [Ringkasan](#-ringkasan)
2. [Konversi Mata Kuliah (20 SKS)](#-konversi-mata-kuliah-20-sks)
3. [Mapping Fitur → Mata Kuliah](#-mapping-fitur--mata-kuliah)
4. [Fitur Utama (11 Modul)](#-fitur-utama-11-modul)
5. [Fitur Baru — GPS Tracking Karyawan](#-fitur-baru--gps-tracking-karyawan)
6. [Stack Teknologi](#-stack-teknologi)
7. [Struktur Database](#-struktur-database)
8. [Instalasi](#-instalasi)
9. [Konfigurasi Keamanan](#-konfigurasi-keamanan)
10. [Troubleshooting](#-troubleshooting)
11. [Credit](#-credit)

---

## 🎯 Ringkasan

EV Smart Energy Control Center adalah platform terpusat untuk:

- 📊 Memantau performa stasiun pengisian EV secara real-time
- 📈 Menganalisis pola konsumsi energi (50.000+ record histori)
- 🚨 Mendeteksi anomali pemakaian otomatis (Z-Score)
- 🗺️ Mengarahkan teknisi ke lokasi via peta interaktif + GPS tracking
- 🔌 Mengintegrasikan data dari API eksternal (HopeWind)
- 🛡️ Mengamankan akses dengan brute-force protection + OTP via email

**Login Demo:** `admin@ev-sahabat.com` / `admin309`

---

## 🎓 Konversi Mata Kuliah (20 SKS)

Sistem ini dirancang sebagai bukti konversi 7 mata kuliah dalam program magang industri:

| # | Mata Kuliah | SKS | Status | Bukti Halaman |
|---|-------------|-----|--------|---------------|
| 1 | Audit TI | 3 | 🥇 Gold | `/audit` |
| 2 | Keamanan Data & Jaringan | 3 | 🥇 Gold | `/security` + `/password/forgot` |
| 3 | Big Data | 3 | 🥇 Gold | `/analytics` |
| 4 | Rekayasa Perangkat Lunak | 3 | 🥈 Silver+Doc | source code + dokumen UML |
| 5 | Integrasi & Migrasi Sistem | 3 | 🥇 Gold | `/api-integration` |
| 6 | Kerja Praktek | 2 | — | logbook eksternal |
| 7 | MPTI / Capstone Project | 3 | 🥈 Silver+ | seluruh sistem |

**Total: 20 SKS**

---

## 🗺️ Mapping Fitur → Mata Kuliah

Tabel ini menunjukkan fitur mana mendukung mata kuliah mana. Beberapa fitur lintas mata kuliah (cross-cutting), karena satu kemampuan teknis bisa jadi bukti di banyak mata kuliah.

### Fitur Utama dan Mata Kuliah Pendukung

| Fitur | Halaman | Mata Kuliah Utama | Pendukung |
|-------|---------|-------------------|-----------|
| **Audit Trail** | `/audit` | Audit TI | Keamanan, RPL, Capstone |
| **Security Dashboard** | `/security` | Keamanan | Audit TI, Capstone |
| **Brute-Force Protection** | (auto, di Auth) | Keamanan | Audit TI |
| **OTP Reset Password** | `/password/forgot` | Keamanan | Capstone |
| **Analytics (Stats, Anomaly, Forecast)** | `/analytics` | Big Data | RPL, Capstone |
| **Heatmap Konsumsi** | `/analytics#heatmap` | Big Data | RPL |
| **API Integration (HopeWind)** | `/api-integration` | Integrasi & Migrasi | RPL, Capstone |
| **GPS Tracking Karyawan** | `/map-view` | Capstone | RPL |
| **Map Routing (OSRM)** | `/map-view` | Capstone | RPL |
| **CRUD Stasiun + Charger** | `/station` | RPL | Capstone |
| **Energy Log + Filter** | `/energy-log` | RPL | Big Data, Capstone |
| **Report + Export PDF/Excel** | `/report` | RPL | Capstone |
| **Alert System** | `/alert` | Capstone | Keamanan, Audit TI |
| **Setting (Profile, Theme, OTP)** | `/setting` | RPL | Keamanan |

---

### 1. Audit TI (3 SKS) — 🥇 Gold

**CPMK:** Mampu menerapkan evaluasi tata kelola dan kontrol TI.

**Bukti Implementasi:**
- ✅ Audit trail otomatis di **8 controller** (Auth, Stasiun, Charger, Riwayat, Setting, Alert, Report, API Integration)
- ✅ Severity 3 level: `info`, `warning`, `critical` (konsep penilaian risiko)
- ✅ Old/New data tracking — perubahan tersimpan dalam JSON kolom `old_data` & `new_data` (traceability)
- ✅ Halaman `/audit` lengkap: filter modul/severity/tanggal/search, 4 stat cards, top 5 modul, trend 7 hari, modal diff visualization
- ✅ Export PDF audit dengan filter aktif (max 500 entry)

**File Kunci:** `App/Http/Controllers/AuditController.php`, `App/Models/AuditLog.php`
**Tabel:** `audit_logs`

---

### 2. Keamanan Data & Jaringan (3 SKS) — 🥇 Gold

**CPMK:** Mampu menerapkan keamanan data tingkat enterprise.

**Bukti Implementasi (3 Lapis Keamanan):**

**Lapis 1 — Autentikasi:**
- ✅ Login bcrypt 12 rounds + CSRF + session regen + role middleware
- ✅ Logout invalidate session

**Lapis 2 — Proteksi Serangan:**
- ✅ Brute-force IP: 5x gagal/30 detik → IP blok 30 detik
- ✅ Account Lock: 5x gagal/30 detik → akun di-lock 30 detik
- ✅ Suspicious Alert otomatis ke `/alert` tipe Keamanan
- ✅ Critical audit untuk setiap brute-force/lock event

**Lapis 3 — Recovery:**
- ✅ Lupa Password OTP 6 digit via Email (HTML template)
- ✅ OTP berlaku 10 menit, sekali pakai
- ✅ Password strength meter real-time

**File Kunci:** `App/Http/Controllers/AuthController.php`, `App/Http/Controllers/SecurityController.php`
**Tabel:** `login_attempts`, `account_locks`, `password_reset_otps`

---

### 3. Big Data (3 SKS) — 🥇 Gold

**CPMK:** Mampu mengolah dan menganalisis data skala besar.

**Bukti Implementasi:**
- ✅ **50.000+ record** riwayat pengisian (data 1 tahun ke belakang)
- ✅ **510 stasiun** tersebar di 40+ kota Indonesia
- ✅ Aggregation query: `GROUP BY` jam/hari/bulan/tahun + statistik deskriptif
- ✅ **Anomaly Detection** algoritma Z-Score (|z| > 2σ otomatis flag)
- ✅ **Heatmap Konsumsi** matriks 31 hari × 24 jam dengan 8-tier percentile coloring
- ✅ **Forecast** Simple Moving Average (SMA-14) untuk 7 hari ke depan
- ✅ **Top 10 Stations** dengan tier (Sangat Aktif → Normal)
- ✅ Trapezoid integral untuk konversi kW → kWh

**Algoritma di `App/Services/AnalyticsService.php`:**
- `basicStats()` — agregat statistik (mean, median, stddev)
- `detectAnomalies()` — Z-Score outlier detection
- `consumptionHeatmap()` — matrix 31×24 dengan percentile-based coloring
- `forecast()` — SMA prediksi
- `topStations()` — ranking konsumsi dengan tier

---

### 4. Rekayasa Perangkat Lunak (3 SKS) — 🥈 Silver+Doc

**CPMK:** Mampu merancang dan membangun perangkat lunak berkualitas.

**Bukti Implementasi:**
- ✅ Arsitektur **MVC murni** Laravel 12
- ✅ **13 migrasi** terkonsolidasi → **21 tabel** dengan relasi Eloquent
- ✅ **Service layer pattern** (`AnalyticsService`, `DashboardDataService`)
- ✅ **Validation server-side** di setiap form
- ✅ **Routing terstruktur** (auth + protected groups)
- ✅ **Dependency Injection** di constructor
- ✅ **DRY principle** — reusable AuditLog, BaseController
- ✅ **Modular CSS** — 21 file CSS terpisah per halaman
- ✅ **Modular JS** — 12 file JS terpisah per halaman

**Bukti Pendukung:** Source code repository, dokumen Word (SRS/UML — terpisah)

---

### 5. Integrasi & Migrasi Sistem (3 SKS) — 🥇 Gold

**CPMK:** Mampu mengintegrasikan sistem multi-platform.

**Bukti Implementasi:**
- ✅ Integrasi REST API HopeWind dengan signature MD5
- ✅ Auto-sync 1 menit via Laravel Scheduler
- ✅ Test connection sebelum simpan (validasi koneksi)
- ✅ Mapping path response dinamis (config-driven)
- ✅ Log lengkap setiap sync (success/failed, response time, HTTP code)
- ✅ Migrasi data API → DB lokal → tampil di dashboard real-time

**File Kunci:** `App/Http/Controllers/ApiintegrationController.php`, `App/Console/Commands/SyncApiConnections.php`
**Tabel:** `api_connections` (password encrypted), `api_synclogs`

---

### 6. Kerja Praktek (2 SKS) — Logbook Eksternal

Logbook Word + penilaian mitra industri (PT Sahabat Mitra Intrabuana). Tidak ada bukti dalam sistem.

---

### 7. MPTI / Capstone Project (3 SKS) — 🥈 Silver+

**CPMK:** Mampu mengintegrasikan multiple teknologi dalam satu proyek end-to-end.

**Bukti Implementasi:** Sistem **11 modul** terintegrasi (lihat tabel cross-link di section berikutnya)

**Cross-Link Antar Modul:**
- Klik tier "Sangat Aktif" di `/analytics` → highlight stasiun di `/station?highlight={id}&tier={label}`
- Klik notifikasi di `/alert` → buka modul terkait (`/station`, `/energy-log`, `/security`)
- Klik baris di `/audit` → modal detail dengan visualisasi old/new
- Klik foto profil di topbar → langsung ke `/setting`

---

## 🧩 Fitur Utama (11 Modul)

| # | Modul | URL | Fungsi Singkat |
|---|-------|-----|----------------|
| 1 | **Dashboard** | `/` | Ringkasan KPI, total kWh, tren 7-30 hari |
| 2 | **Stasiun** | `/station` | CRUD stasiun + tier aktivitas (Sangat Aktif → Normal) |
| 3 | **Riwayat Pengisian** | `/energy-log` | Log sesi pengisian (stasiun, kWh, durasi) |
| 4 | **Analytics** | `/analytics` | Heatmap 31×24, anomaly detection, forecast SMA-14 |
| 5 | **Alert** | `/alert` | Notifikasi konsumsi berlebih + stasiun tidak aktif |
| 6 | **Map View** | `/map-view` | Peta interaktif + routing OSRM + GPS tracking |
| 7 | **Security** | `/security` | Login attempts, account lock, OTP email |
| 8 | **Audit Trail** | `/audit` | Jejak aktivitas user (CRUD/login) + diff old vs new |
| 9 | **API Integration** | `/api-integration` | Discovery + sync API eksternal |
| 10 | **Report** | `/report` | Laporan ringkas + export PDF/Excel |
| 11 | **Setting** | `/setting` | Profil, password, threshold, backup, tema, **Tentang Sistem** |

---

## 📍 Fitur Baru — GPS Tracking Karyawan

Sistem tracking lokasi teknisi di lapangan secara **real-time** tanpa perlu install aplikasi.

### Cara Kerja

1. Admin klik **"Buat Link Tracking Karyawan"** di halaman `/map-view`
2. Sistem menghasilkan link unik (URL token-based)
3. Admin kirim link ke teknisi via WhatsApp
4. Teknisi buka link di HP → browser minta izin GPS
5. Posisi teknisi langsung muncul di peta admin & update tiap beberapa detik

### Backend Storage

- File JSON ringan di `storage/app/tracking.json` (tanpa migration baru)
- Auto-cleanup data lama untuk efisiensi storage

### Use Case

- Pantau teknisi yang dikirim untuk perbaikan stasiun rusak
- Verifikasi karyawan benar-benar di lokasi tugas
- Estimasi ETA berdasarkan posisi real-time

> **Catatan:** Untuk produksi, aplikasi harus accessible dari internet (hosting publik atau via ngrok/cloudflare tunnel) supaya HP karyawan bisa membuka link.

---

## 🛠️ Stack Teknologi

| Layer | Tools |
|-------|-------|
| **Backend** | Laravel 12.x · PHP 8.3 |
| **Database** | MySQL 8.0 |
| **Frontend** | Blade · HTML5 · CSS3 · Vanilla JS |
| **Visualisasi** | Chart.js 4.4 |
| **Peta** | Leaflet.js 1.9 + OpenStreetMap |
| **Marker Cluster** | Leaflet.markercluster 1.5 (auto-group ribuan marker) |
| **Routing** | OSRM (Open Source Routing Machine) |
| **Email** | Laravel Mail (HTML template, support SMTP) |
| **Icon** | Font Awesome 6.4 |
| **Font** | Rajdhani (Google Fonts) |

---

## 🗄️ Struktur Database

**Total 21 tabel** dari konsolidasi 13 migrasi (clean & no redundancy).

| Tabel | Fungsi | Mata Kuliah |
|-------|--------|-------------|
| `users` | Admin sistem (bcrypt 12 rounds) | Keamanan, RPL |
| `stasiun_pengisian` | Master stasiun EV (510 record) | RPL, Capstone |
| `chargers` | Unit charger per stasiun | RPL |
| `riwayat_pengisian` | Log konsumsi energi (50.017 record) | **Big Data** |
| `api_connections` | Koneksi API eksternal (encrypted) | **Integrasi** |
| `api_synclogs` | Log proses sinkronisasi API | **Integrasi** |
| `audit_logs` | Audit trail + old/new data (JSON) | **Audit TI** |
| `login_attempts` | Riwayat percobaan login | **Keamanan** |
| `account_locks` | Akun yang di-lock otomatis | **Keamanan** |
| `password_reset_otps` | OTP reset password (10 menit, sekali pakai) | **Keamanan** |
| `sessions`, `cache`, `jobs` | Laravel framework | - |

---

## 📦 Instalasi

### Prasyarat

- PHP 8.3+
- Composer 2.x
- MySQL 8.0 (atau MariaDB 10.6+)
- Web server (Laragon/XAMPP/Nginx)

### Langkah-langkah

```bash
# 1. Clone & masuk folder
git clone <repo-url>
cd ev-sahabat

# 2. Install dependencies
composer install

# 3. Setup environment
cp .env.example .env
php artisan key:generate

# 4. Edit .env — sesuaikan DB & email
# DB_DATABASE=ev_sahabat
# MAIL_MAILER=log         (untuk demo)
# MAIL_MAILER=smtp        (untuk production)
# APP_TIMEZONE=Asia/Jakarta

# 5. Migrate & seed
php artisan migrate
php artisan db:seed

# 6. (Opsional) Generate dummy data untuk demo Big Data
php artisan db:seed --class=StationDummySeeder   # 500 stasiun + 50.000 log

# 7. Jalankan server
php artisan serve
```

Akses: `http://127.0.0.1:8000` atau (Laragon) `http://ev-sahabat.test`

---

## 🔐 Konfigurasi Keamanan

Semua durasi keamanan dirancang **agresif (max 30 detik)** untuk demo & uji ketahanan:

| Parameter | Nilai |
|-----------|-------|
| Maks percobaan login | **5×** |
| Window penghitung gagal | **30 detik** |
| Durasi akun lock | **30 detik** |
| Durasi IP block | **30 detik** |
| OTP berlaku | **10 menit** |
| OTP panjang | **6 digit** |
| BCRYPT_ROUNDS | **12** |

### Fitur Keamanan

- ✅ Brute-force protection (per-IP + per-akun)
- ✅ Account auto-lock setelah 5× gagal
- ✅ OTP via email (HTML template)
- ✅ Password reset flow lengkap (forgot → verify-otp → reset)
- ✅ Password strength meter real-time
- ✅ Session regenerate setelah login
- ✅ CSRF token di semua form
- ✅ Role-based middleware (admin/operator)
- ✅ Audit trail untuk semua aksi penting

---

## 🔧 Troubleshooting

```bash
# Clear semua cache
php artisan optimize:clear

# Reset DB + seed ulang lengkap
php artisan migrate:fresh
php artisan db:seed
php artisan db:seed --class=StationDummySeeder

# Hapus dummy data saja (tetap simpan data asli)
# Jalankan via SQL:
DELETE FROM riwayat_pengisian WHERE lokasi LIKE '%[DUMMY]%' OR lokasi LIKE '%[BD-DUMMY]%';
DELETE FROM stasiun_pengisian WHERE lokasi LIKE '%[DUMMY]%';

# Test kirim email OTP via tinker
php artisan tinker
>>> Mail::raw('test', fn($m) => $m->to('admin@ev-sahabat.com')->subject('Test'));
```

### Masalah Umum

| Gejala | Solusi |
|--------|--------|
| OTP tidak terkirim ke email | Pastikan `MAIL_MAILER=smtp` & kredensial benar di `.env`, lalu `php artisan config:clear` |
| Map kosong / tile tidak load | Cek koneksi internet, OpenStreetMap butuh akses online |
| Error column `nama_stasiun` not found | Jangan kirim kolom `nama_stasiun` & `durasi` (sudah dihapus) — gunakan relasi `$log->stasiun->nama_stasiun` |
| GPS tracking link tidak bisa dibuka di HP | URL masih localhost. Pakai ngrok / hosting publik supaya accessible dari internet |
| Dashboard lambat | Sudah dioptimasi: query stasiun limited 100, marker lazy popup, cluster grouping |

---

## 📂 Struktur Folder Penting

```
ev-sahabat/
├── App/
│   ├── Http/Controllers/          # 14 controller (Auth, Charger, Map, dst)
│   ├── Models/                    # 16 model Eloquent
│   ├── Services/                  # AnalyticsService, DashboardDataService
│   └── Console/Commands/          # SyncApiConnections (scheduler)
├── database/
│   ├── migrations/                # 13 migrasi konsolidasi
│   └── seeders/                   # StationDummySeeder, dst
├── resources/
│   └── views/                     # Blade templates (21 CSS + 12 JS terpisah)
├── public/
│   ├── css/                       # File CSS modular per halaman
│   └── js/                        # File JS modular per halaman
└── storage/app/
    └── tracking.json              # Real-time GPS tracking karyawan
```

---

## 👨‍💻 Credit

**Pengembang:**
- 🧑 **TEO VERNANDO PAUYONO** — Developer & System Engineer

**Mitra Industri:**
- 🏢 **PT Sahabat Mitra Intrabuana (Go Power)**

**Konteks Akademik:**
- 🎓 Konversi 20 SKS — 7 mata kuliah

---

<div align="center">

⚡ **EV Smart Energy Control Center** ⚡

*Membangun masa depan transportasi listrik yang lebih cerdas, aman, dan terhubung.*

</div>
