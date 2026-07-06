@extends('layout.app')

@section('title', 'Setting')
@section('page-title', 'Setting')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/setting.css') }}">
@endpush

@section('content')
<div class="setting-wrapper">

    {{-- Page Header --}}
    <div class="page-header" style="margin-bottom:20px">
        <div class="page-header-left">
            <h2 style="display:flex;align-items:center;gap:12px;font-family:'Rajdhani',sans-serif;font-size:24px;font-weight:700;margin-bottom:4px">
                <span style="display:inline-flex;align-items:center;justify-content:center;width:42px;height:42px;border-radius:12px;background:linear-gradient(135deg,#475569,#64748b);box-shadow:0 0 18px rgba(100,116,139,0.4);flex-shrink:0;animation:pageIconPulse 3s ease-in-out infinite">
                    <i class="fas fa-cog" style="font-size:20px;color:#fff"></i>
                </span>
                <span style="background:linear-gradient(135deg,#f1f5f9,#94a3b8);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent">
                    Setting
                </span>
            </h2>
            <p style="font-size:12px;color:#94a3b8">Konfigurasi sistem dan preferensi akun</p>
        </div>
    </div>

    {{-- TAB NAVIGATION --}}
    <div class="setting-tabs">
        <button class="tab-btn active" onclick="switchTab('profil', this)">
            <i class="fas fa-user"></i> Profil
        </button>
        <button class="tab-btn" onclick="switchTab('password', this)">
            <i class="fas fa-lock"></i> Password
        </button>
        @if(Auth::user()->role === 'admin')
        <button class="tab-btn" onclick="switchTab('threshold', this)">
            <i class="fas fa-sliders-h"></i> Threshold Alert
        </button>
        <button class="tab-btn" onclick="switchTab('app', this)">
            <i class="fas fa-cogs"></i> Aplikasi
        </button>
        <button class="tab-btn" onclick="switchTab('backup', this)">
            <i class="fas fa-database"></i> Backup
        </button>
        @endif
        <button class="tab-btn" onclick="switchTab('tema', this)">
            <i class="fas fa-paint-brush"></i> Tema
        </button>
        <button class="tab-btn" onclick="switchTab('about', this)">
            <i class="fas fa-circle-info"></i> Tentang Sistem
        </button>
    </div>

    {{-- PROFIL --}}
    <div class="tab-content active" id="tab-profil">
        <div class="setting-card">
            <h3><i class="fas fa-user"></i> Profil / Akun</h3>
            <form method="POST" action="{{ route('setting.profile') }}" enctype="multipart/form-data">
                @csrf @method('PUT')

                {{-- FOTO PROFIL --}}
                <div class="profile-photo-section">
                    <div class="profile-photo-wrapper">
                        @if(Auth::user()->photo)
                            <img src="{{ asset('uploads/photos/' . Auth::user()->photo) }}"
                                 id="previewPhoto"
                                 class="profile-photo"
                                 onclick="openPhotoModal(this.src)"
                                 title="Klik untuk lihat foto">
                        @else
                            <div class="profile-photo-placeholder" id="previewPlaceholder">
                                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                            </div>
                            <img src="" id="previewPhoto" class="profile-photo" style="display:none">
                        @endif
                        <label for="photoInput" class="photo-edit-btn" title="Ganti Foto">
                            <i class="fas fa-camera"></i>
                        </label>
                    </div>
                    <input type="file" id="photoInput" name="photo" accept="image/*" style="display:none">
                    <p class="photo-name">{{ Auth::user()->name }}</p>
                    <p class="photo-role">{{ ucfirst(Auth::user()->role) }}</p>
                    <p class="photo-hint"><i class="fas fa-info-circle"></i> Klik foto untuk lihat, klik kamera untuk ganti. Max 2MB (JPG, PNG)</p>
                </div>

                {{-- FORM DATA --}}
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Nama</label>
                        <input type="text" name="name" value="{{ Auth::user()->name }}" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> Email</label>
                        <input type="email" name="email" value="{{ Auth::user()->email }}" class="form-control" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-phone"></i> Nomor Telepon</label>
                        <input type="text" name="phone" value="{{ Auth::user()->phone }}" class="form-control" placeholder="Contoh: 08123456789">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-shield-alt"></i> Role</label>
                        <input type="text" value="{{ ucfirst(Auth::user()->role) }}" class="form-control" disabled>
                    </div>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-align-left"></i> Bio</label>
                    <textarea name="bio" class="form-control" rows="3" placeholder="Tulis bio singkat tentang kamu...">{{ Auth::user()->bio }}</textarea>
                </div>

                <button type="submit" class="btn-save"><i class="fas fa-save"></i> Simpan Profil</button>
            </form>
        </div>
    </div>

    {{-- PASSWORD --}}
    <div class="tab-content" id="tab-password">
        <div class="setting-card">
            <h3><i class="fas fa-lock"></i> Ganti Password</h3>
            <div class="security-info">
                <i class="fas fa-shield-alt"></i>
                Gunakan password minimal 8 karakter dengan kombinasi huruf, angka, dan simbol untuk keamanan lebih baik.
            </div>
            <form method="POST" action="{{ route('setting.password') }}">
                @csrf @method('PUT')
                <div class="form-group">
                    <label>Password Lama</label>
                    <div class="input-password">
                        <input type="password" name="current_password" id="currentPass" class="form-control" required>
                        <button type="button" class="toggle-pass" onclick="togglePass('currentPass')"><i class="fas fa-eye"></i></button>
                    </div>
                    @error('current_password')<span class="text-danger">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label>Password Baru</label>
                    <div class="input-password">
                        <input type="password" name="password" id="newPass" class="form-control" required>
                        <button type="button" class="toggle-pass" onclick="togglePass('newPass')"><i class="fas fa-eye"></i></button>
                    </div>
                </div>
                <div class="form-group">
                    <label>Konfirmasi Password Baru</label>
                    <div class="input-password">
                        <input type="password" name="password_confirmation" id="confirmPass" class="form-control" required>
                        <button type="button" class="toggle-pass" onclick="togglePass('confirmPass')"><i class="fas fa-eye"></i></button>
                    </div>
                </div>
                <button type="submit" class="btn-save"><i class="fas fa-key"></i> Ganti Password</button>
            </form>

            {{-- Panel OTP Verifikasi dari dalam sistem --}}
            <div style="margin-top:24px;padding:18px 20px;background:rgba(6,182,212,0.06);border:1px solid rgba(6,182,212,0.2);border-radius:12px">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px">
                    <div style="width:36px;height:36px;border-radius:9px;background:linear-gradient(135deg,#1d4ed8,#06b6d4);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                        <i class="fas fa-shield-halved" style="color:#fff;font-size:16px"></i>
                    </div>
                    <div>
                        <div style="font-size:14px;font-weight:700;color:#f1f5f9">Verifikasi OTP</div>
                        <div style="font-size:11px;color:#94a3b8">Kirim kode OTP ke email untuk verifikasi identitas</div>
                    </div>
                </div>

                <div style="font-size:12px;color:#94a3b8;margin-bottom:14px;line-height:1.6">
                    Kode OTP akan dikirim ke: <strong style="color:#22d3ee">{{ Auth::user()->email }}</strong><br>
                    Berlaku <strong style="color:#f1f5f9">10 menit</strong> dan hanya bisa dipakai sekali.
                </div>

                <button type="button" id="btnSendOtp" onclick="sendOtpFromSetting()"
                    style="display:inline-flex;align-items:center;gap:8px;padding:10px 20px;background:linear-gradient(135deg,#1d4ed8,#06b6d4);color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit;transition:all 0.2s">
                    <i class="fas fa-paper-plane"></i> Kirim Kode OTP ke Email
                </button>

                {{-- Hasil OTP --}}
                <div id="otpResult" style="display:none;margin-top:14px">
                    <div id="otpSuccess" style="display:none;padding:12px 16px;background:rgba(16,185,129,0.1);border:1px solid rgba(16,185,129,0.3);border-radius:8px;color:#34d399;font-size:13px">
                        <i class="fas fa-check-circle" style="margin-right:8px"></i>
                        <span id="otpSuccessMsg"></span>
                    </div>
                    <div id="otpDemo" style="display:none;margin-top:10px;padding:14px 16px;background:rgba(255,215,0,0.08);border:1px solid rgba(255,215,0,0.25);border-radius:8px">
                        <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#94a3b8;margin-bottom:6px">Mode Demo — Kode OTP Anda</div>
                        <div id="otpDemoCode" style="font-family:'Rajdhani',sans-serif;font-size:32px;font-weight:800;letter-spacing:10px;color:#FFD700"></div>
                        <div style="font-size:11px;color:#64748b;margin-top:4px">Di production, kode ini dikirim ke email Anda</div>
                    </div>
                    <div id="otpError" style="display:none;padding:12px 16px;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.25);border-radius:8px;color:#f87171;font-size:13px">
                        <i class="fas fa-exclamation-circle" style="margin-right:8px"></i>
                        <span id="otpErrorMsg"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(Auth::user()->role === 'admin')
    {{-- THRESHOLD ALERT --}}
    <div class="tab-content" id="tab-threshold">
        <div class="setting-card">
            <h3><i class="fas fa-sliders-h"></i> Batas Threshold Alert</h3>
            <div class="security-info">
                <i class="fas fa-info-circle"></i>
                Pengaturan ini menentukan kapan sistem akan memunculkan peringatan (alert) secara otomatis.
            </div>
            <form method="POST" action="{{ route('setting.threshold') }}">
                @csrf @method('PUT')
                <div class="form-group">
                    <label><i class="fas fa-bolt"></i> Batas Energi Tinggi (kWh)</label>
                    <input type="number" name="threshold_energi" value="{{ $threshold['energi_kwh'] }}" class="form-control" min="1" required>
                    <small>Alert muncul otomatis jika konsumsi energi melebihi nilai ini</small>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-clock"></i> Batas Tidak Aktif (Hari)</label>
                    <input type="number" name="threshold_hari" value="{{ $threshold['inactive_days'] }}" class="form-control" min="1" required>
                    <small>Alert muncul otomatis jika stasiun tidak aktif lebih dari nilai ini</small>
                </div>
                <button type="submit" class="btn-save"><i class="fas fa-save"></i> Simpan Threshold</button>
            </form>
        </div>
    </div>

    {{-- KONFIGURASI APLIKASI --}}
    <div class="tab-content" id="tab-app">
        <div class="setting-card">
            <h3><i class="fas fa-cogs"></i> Konfigurasi Aplikasi</h3>
            <form method="POST" action="{{ route('setting.app') }}">
                @csrf @method('PUT')
                <div class="form-group">
                    <label>Nama Aplikasi</label>
                    <input type="text" name="app_name" value="{{ $appConfig['app_name'] }}" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Timezone</label>
                    <select name="timezone" class="form-control">
                        <option value="Asia/Jakarta"  {{ $appConfig['timezone'] === 'Asia/Jakarta'  ? 'selected' : '' }}>Asia/Jakarta (WIB)</option>
                        <option value="Asia/Makassar" {{ $appConfig['timezone'] === 'Asia/Makassar' ? 'selected' : '' }}>Asia/Makassar (WITA)</option>
                        <option value="Asia/Jayapura" {{ $appConfig['timezone'] === 'Asia/Jayapura' ? 'selected' : '' }}>Asia/Jayapura (WIT)</option>
                    </select>
                </div>
                <button type="submit" class="btn-save"><i class="fas fa-save"></i> Simpan</button>
            </form>
        </div>
    </div>

    {{-- BACKUP --}}
    <div class="tab-content" id="tab-backup">
        <div class="setting-card">
            <h3><i class="fas fa-database"></i> Backup Database</h3>
            <div class="security-info">
                <i class="fas fa-info-circle"></i>
                Backup akan mendownload file SQL yang berisi semua data sistem. Simpan di tempat yang aman.
            </div>
            <form method="POST" action="{{ route('setting.backup') }}">
                @csrf
                <button type="submit" class="btn-save"><i class="fas fa-download"></i> Download Backup Sekarang</button>
            </form>
        </div>
    </div>
    @endif

    {{-- TEMA --}}
    <div class="tab-content" id="tab-tema">
        <div class="setting-card">
            <h3><i class="fas fa-paint-brush"></i> Tema Tampilan</h3>
            <form method="POST" action="{{ route('setting.theme') }}">
                @csrf @method('PUT')
                <div class="theme-options">
                    <label class="theme-option {{ $theme === 'dark' ? 'active' : '' }}">
                        <input type="radio" name="theme" value="dark" {{ $theme === 'dark' ? 'checked' : '' }} style="display:none">
                        <div class="theme-preview theme-dark-preview">
                            <div class="preview-sidebar"></div>
                            <div class="preview-content">
                                <div class="preview-bar"></div>
                                <div class="preview-card"></div>
                            </div>
                        </div>
                        <span>Dark Mode</span>
                    </label>
                    <label class="theme-option {{ $theme === 'light' ? 'active' : '' }}">
                        <input type="radio" name="theme" value="light" {{ $theme === 'light' ? 'checked' : '' }} style="display:none">
                        <div class="theme-preview theme-light-preview">
                            <div class="preview-sidebar"></div>
                            <div class="preview-content">
                                <div class="preview-bar"></div>
                                <div class="preview-card"></div>
                            </div>
                        </div>
                        <span>Light Mode</span>
                    </label>
                </div>
                <button type="submit" class="btn-save mt-3"><i class="fas fa-paint-brush"></i> Terapkan Tema</button>
            </form>
        </div>
    </div>

    {{-- TENTANG SISTEM --}}
    <div class="tab-content" id="tab-about">
        <div class="setting-card">
            <h3><i class="fas fa-circle-info"></i> Tentang Sistem</h3>

            {{-- Hero --}}
            <div style="display:flex;align-items:center;gap:18px;padding:22px;background:linear-gradient(135deg,rgba(29,78,216,0.12),rgba(6,182,212,0.08));border:1px solid rgba(6,182,212,0.25);border-radius:14px;margin-bottom:22px">
                <div style="width:64px;height:64px;border-radius:16px;background:linear-gradient(135deg,#1d4ed8,#06b6d4);display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 0 24px rgba(6,182,212,0.4)">
                    <i class="fas fa-bolt-lightning" style="color:#fff;font-size:30px"></i>
                </div>
                <div>
                    <div style="font-family:'Rajdhani',sans-serif;font-size:22px;font-weight:800;color:#f1f5f9;letter-spacing:0.5px">EV Smart Energy Control Center</div>
                    <div style="font-size:12px;color:#94a3b8;margin-top:4px">Sistem monitoring, kontrol, dan analitik konsumsi energi stasiun pengisian kendaraan listrik</div>
                    <div style="display:inline-flex;align-items:center;gap:6px;margin-top:8px;padding:4px 10px;background:rgba(16,185,129,0.15);border:1px solid rgba(16,185,129,0.3);border-radius:20px">
                        <i class="fas fa-circle" style="font-size:7px;color:#10b981;animation:pageIconPulse 2s infinite"></i>
                        <span style="font-size:10px;font-weight:700;color:#34d399;letter-spacing:1px">VERSI 1.0</span>
                    </div>
                </div>
            </div>

            {{-- Deskripsi --}}
            <div style="padding:18px 20px;background:rgba(15,23,42,0.4);border:1px solid rgba(100,116,139,0.2);border-radius:12px;margin-bottom:22px">
                <div style="font-size:13px;color:#cbd5e1;line-height:1.75">
                    Sistem ini dibangun untuk membantu admin dan operator memantau performa stasiun pengisian EV secara real-time, menganalisis pola konsumsi energi, mendeteksi anomali pemakaian, mengelola pengguna dan tim teknisi, serta mengintegrasikan data dari berbagai sumber API eksternal. Seluruh data yang ditampilkan terhubung langsung ke database MySQL dan diperbarui secara live.
                </div>
            </div>

            {{-- 11 Modul Utama --}}
            <h4 style="font-family:'Rajdhani',sans-serif;font-size:16px;font-weight:700;color:#f1f5f9;margin:0 0 14px;display:flex;align-items:center;gap:8px">
                <i class="fas fa-layer-group" style="color:#06b6d4"></i> 11 Modul Utama
            </h4>

            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:12px;margin-bottom:24px">

                <div style="padding:14px;background:rgba(15,23,42,0.4);border:1px solid rgba(100,116,139,0.2);border-radius:10px;transition:all 0.2s" onmouseover="this.style.borderColor='rgba(6,182,212,0.4)'" onmouseout="this.style.borderColor='rgba(100,116,139,0.2)'">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px">
                        <div style="width:32px;height:32px;border-radius:8px;background:linear-gradient(135deg,#1d4ed8,#06b6d4);display:flex;align-items:center;justify-content:center;flex-shrink:0"><i class="fas fa-tachometer-alt" style="color:#fff;font-size:13px"></i></div>
                        <div style="font-size:13px;font-weight:700;color:#f1f5f9">Dashboard</div>
                    </div>
                    <div style="font-size:11px;color:#94a3b8;line-height:1.6;padding-left:42px">Ringkasan KPI sistem: total stasiun, sesi pengisian, total kWh, dan grafik tren konsumsi 7-30 hari terakhir.</div>
                </div>

                <div style="padding:14px;background:rgba(15,23,42,0.4);border:1px solid rgba(100,116,139,0.2);border-radius:10px;transition:all 0.2s" onmouseover="this.style.borderColor='rgba(6,182,212,0.4)'" onmouseout="this.style.borderColor='rgba(100,116,139,0.2)'">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px">
                        <div style="width:32px;height:32px;border-radius:8px;background:linear-gradient(135deg,#10b981,#34d399);display:flex;align-items:center;justify-content:center;flex-shrink:0"><i class="fas fa-charging-station" style="color:#fff;font-size:13px"></i></div>
                        <div style="font-size:13px;font-weight:700;color:#f1f5f9">Stasiun Pengisian</div>
                    </div>
                    <div style="font-size:11px;color:#94a3b8;line-height:1.6;padding-left:42px">CRUD stasiun pengisian, status aktivitas, koordinat lokasi, dan badge tier aktivitas (Sangat Aktif s/d Normal).</div>
                </div>

                <div style="padding:14px;background:rgba(15,23,42,0.4);border:1px solid rgba(100,116,139,0.2);border-radius:10px;transition:all 0.2s" onmouseover="this.style.borderColor='rgba(6,182,212,0.4)'" onmouseout="this.style.borderColor='rgba(100,116,139,0.2)'">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px">
                        <div style="width:32px;height:32px;border-radius:8px;background:linear-gradient(135deg,#f59e0b,#fbbf24);display:flex;align-items:center;justify-content:center;flex-shrink:0"><i class="fas fa-history" style="color:#fff;font-size:13px"></i></div>
                        <div style="font-size:13px;font-weight:700;color:#f1f5f9">Riwayat Pengisian</div>
                    </div>
                    <div style="font-size:11px;color:#94a3b8;line-height:1.6;padding-left:42px">Log seluruh sesi pengisian energi: stasiun, waktu mulai, waktu selesai, dan jumlah kWh yang terpakai.</div>
                </div>

                <div style="padding:14px;background:rgba(15,23,42,0.4);border:1px solid rgba(100,116,139,0.2);border-radius:10px;transition:all 0.2s" onmouseover="this.style.borderColor='rgba(6,182,212,0.4)'" onmouseout="this.style.borderColor='rgba(100,116,139,0.2)'">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px">
                        <div style="width:32px;height:32px;border-radius:8px;background:linear-gradient(135deg,#8b5cf6,#a78bfa);display:flex;align-items:center;justify-content:center;flex-shrink:0"><i class="fas fa-chart-line" style="color:#fff;font-size:13px"></i></div>
                        <div style="font-size:13px;font-weight:700;color:#f1f5f9">Analytics</div>
                    </div>
                    <div style="font-size:11px;color:#94a3b8;line-height:1.6;padding-left:42px">Analisis pola konsumsi, deteksi anomali, top stasiun paling aktif, dan prediksi tren menggunakan data historis.</div>
                </div>

                <div style="padding:14px;background:rgba(15,23,42,0.4);border:1px solid rgba(100,116,139,0.2);border-radius:10px;transition:all 0.2s" onmouseover="this.style.borderColor='rgba(6,182,212,0.4)'" onmouseout="this.style.borderColor='rgba(100,116,139,0.2)'">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px">
                        <div style="width:32px;height:32px;border-radius:8px;background:linear-gradient(135deg,#ef4444,#f87171);display:flex;align-items:center;justify-content:center;flex-shrink:0"><i class="fas fa-bell" style="color:#fff;font-size:13px"></i></div>
                        <div style="font-size:13px;font-weight:700;color:#f1f5f9">Alert &amp; Notifikasi</div>
                    </div>
                    <div style="font-size:11px;color:#94a3b8;line-height:1.6;padding-left:42px">Sistem peringatan otomatis untuk konsumsi berlebih dan stasiun yang tidak aktif sesuai threshold yang dikonfigurasi admin.</div>
                </div>

                <div style="padding:14px;background:rgba(15,23,42,0.4);border:1px solid rgba(100,116,139,0.2);border-radius:10px;transition:all 0.2s" onmouseover="this.style.borderColor='rgba(6,182,212,0.4)'" onmouseout="this.style.borderColor='rgba(100,116,139,0.2)'">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px">
                        <div style="width:32px;height:32px;border-radius:8px;background:linear-gradient(135deg,#0ea5e9,#38bdf8);display:flex;align-items:center;justify-content:center;flex-shrink:0"><i class="fas fa-map-location-dot" style="color:#fff;font-size:13px"></i></div>
                        <div style="font-size:13px;font-weight:700;color:#f1f5f9">Map &amp; GPS Tracking</div>
                    </div>
                    <div style="font-size:11px;color:#94a3b8;line-height:1.6;padding-left:42px">Peta interaktif lokasi stasiun, perhitungan rute via OSRM, serta tracking real-time posisi karyawan teknisi di lapangan.</div>
                </div>

                <div style="padding:14px;background:rgba(15,23,42,0.4);border:1px solid rgba(100,116,139,0.2);border-radius:10px;transition:all 0.2s" onmouseover="this.style.borderColor='rgba(6,182,212,0.4)'" onmouseout="this.style.borderColor='rgba(100,116,139,0.2)'">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px">
                        <div style="width:32px;height:32px;border-radius:8px;background:linear-gradient(135deg,#ec4899,#f472b6);display:flex;align-items:center;justify-content:center;flex-shrink:0"><i class="fas fa-shield-halved" style="color:#fff;font-size:13px"></i></div>
                        <div style="font-size:13px;font-weight:700;color:#f1f5f9">Security</div>
                    </div>
                    <div style="font-size:11px;color:#94a3b8;line-height:1.6;padding-left:42px">Login attempt monitoring, account lock otomatis, OTP via email, dan reset password aman untuk admin.</div>
                </div>

                <div style="padding:14px;background:rgba(15,23,42,0.4);border:1px solid rgba(100,116,139,0.2);border-radius:10px;transition:all 0.2s" onmouseover="this.style.borderColor='rgba(6,182,212,0.4)'" onmouseout="this.style.borderColor='rgba(100,116,139,0.2)'">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px">
                        <div style="width:32px;height:32px;border-radius:8px;background:linear-gradient(135deg,#64748b,#94a3b8);display:flex;align-items:center;justify-content:center;flex-shrink:0"><i class="fas fa-clipboard-list" style="color:#fff;font-size:13px"></i></div>
                        <div style="font-size:13px;font-weight:700;color:#f1f5f9">Audit Trail</div>
                    </div>
                    <div style="font-size:11px;color:#94a3b8;line-height:1.6;padding-left:42px">Pencatatan jejak aktivitas semua aksi user di sistem (CRUD, login, perubahan setting) lengkap dengan IP dan timestamp.</div>
                </div>

                <div style="padding:14px;background:rgba(15,23,42,0.4);border:1px solid rgba(100,116,139,0.2);border-radius:10px;transition:all 0.2s" onmouseover="this.style.borderColor='rgba(6,182,212,0.4)'" onmouseout="this.style.borderColor='rgba(100,116,139,0.2)'">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px">
                        <div style="width:32px;height:32px;border-radius:8px;background:linear-gradient(135deg,#14b8a6,#5eead4);display:flex;align-items:center;justify-content:center;flex-shrink:0"><i class="fas fa-plug" style="color:#fff;font-size:13px"></i></div>
                        <div style="font-size:13px;font-weight:700;color:#f1f5f9">API Integration</div>
                    </div>
                    <div style="font-size:11px;color:#94a3b8;line-height:1.6;padding-left:42px">Integrasi data eksternal: discovery API, sync otomatis, dan log proses sinkronisasi antar sistem.</div>
                </div>

                <div style="padding:14px;background:rgba(15,23,42,0.4);border:1px solid rgba(100,116,139,0.2);border-radius:10px;transition:all 0.2s" onmouseover="this.style.borderColor='rgba(6,182,212,0.4)'" onmouseout="this.style.borderColor='rgba(100,116,139,0.2)'">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px">
                        <div style="width:32px;height:32px;border-radius:8px;background:linear-gradient(135deg,#f97316,#fb923c);display:flex;align-items:center;justify-content:center;flex-shrink:0"><i class="fas fa-file-lines" style="color:#fff;font-size:13px"></i></div>
                        <div style="font-size:13px;font-weight:700;color:#f1f5f9">Report</div>
                    </div>
                    <div style="font-size:11px;color:#94a3b8;line-height:1.6;padding-left:42px">Laporan ringkas konsumsi energi, performa stasiun, dan ekspor data untuk keperluan dokumentasi.</div>
                </div>

                <div style="padding:14px;background:rgba(15,23,42,0.4);border:1px solid rgba(100,116,139,0.2);border-radius:10px;transition:all 0.2s" onmouseover="this.style.borderColor='rgba(6,182,212,0.4)'" onmouseout="this.style.borderColor='rgba(100,116,139,0.2)'">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px">
                        <div style="width:32px;height:32px;border-radius:8px;background:linear-gradient(135deg,#475569,#64748b);display:flex;align-items:center;justify-content:center;flex-shrink:0"><i class="fas fa-cog" style="color:#fff;font-size:13px"></i></div>
                        <div style="font-size:13px;font-weight:700;color:#f1f5f9">Setting</div>
                    </div>
                    <div style="font-size:11px;color:#94a3b8;line-height:1.6;padding-left:42px">Kelola profil, password, threshold alert, konfigurasi aplikasi, backup database, dan pemilihan tema.</div>
                </div>

            </div>

            {{-- Teknologi --}}
            <h4 style="font-family:'Rajdhani',sans-serif;font-size:16px;font-weight:700;color:#f1f5f9;margin:0 0 14px;display:flex;align-items:center;gap:8px">
                <i class="fas fa-microchip" style="color:#06b6d4"></i> Teknologi yang Dipakai
            </h4>

            <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:24px">
                <span style="padding:6px 14px;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);border-radius:20px;font-size:11px;font-weight:600;color:#fca5a5"><i class="fab fa-laravel"></i> Laravel 12</span>
                <span style="padding:6px 14px;background:rgba(139,92,246,0.1);border:1px solid rgba(139,92,246,0.3);border-radius:20px;font-size:11px;font-weight:600;color:#c4b5fd"><i class="fab fa-php"></i> PHP 8.3</span>
                <span style="padding:6px 14px;background:rgba(14,165,233,0.1);border:1px solid rgba(14,165,233,0.3);border-radius:20px;font-size:11px;font-weight:600;color:#7dd3fc"><i class="fas fa-database"></i> MySQL</span>
                <span style="padding:6px 14px;background:rgba(245,158,11,0.1);border:1px solid rgba(245,158,11,0.3);border-radius:20px;font-size:11px;font-weight:600;color:#fcd34d"><i class="fab fa-js"></i> JavaScript</span>
                <span style="padding:6px 14px;background:rgba(16,185,129,0.1);border:1px solid rgba(16,185,129,0.3);border-radius:20px;font-size:11px;font-weight:600;color:#6ee7b7"><i class="fas fa-chart-area"></i> Chart.js</span>
                <span style="padding:6px 14px;background:rgba(6,182,212,0.1);border:1px solid rgba(6,182,212,0.3);border-radius:20px;font-size:11px;font-weight:600;color:#67e8f9"><i class="fas fa-map"></i> Leaflet.js</span>
                <span style="padding:6px 14px;background:rgba(236,72,153,0.1);border:1px solid rgba(236,72,153,0.3);border-radius:20px;font-size:11px;font-weight:600;color:#f9a8d4"><i class="fas fa-route"></i> OSRM</span>
                <span style="padding:6px 14px;background:rgba(100,116,139,0.1);border:1px solid rgba(100,116,139,0.3);border-radius:20px;font-size:11px;font-weight:600;color:#cbd5e1"><i class="fab fa-html5"></i> HTML5 / CSS3</span>
            </div>

            {{-- Credit --}}
            <div style="padding:20px;background:linear-gradient(135deg,rgba(29,78,216,0.08),rgba(139,92,246,0.06));border:1px solid rgba(139,92,246,0.2);border-radius:12px">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px">
                    <i class="fas fa-code" style="color:#a78bfa"></i>
                    <span style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;color:#a78bfa">Pengembang</span>
                </div>
                <div style="font-family:'Rajdhani',sans-serif;font-size:18px;font-weight:800;color:#f1f5f9;margin-bottom:4px">TEO VERNANDO PAUYONO</div>
                <div style="font-size:12px;color:#94a3b8;margin-bottom:14px">Developer &amp; System Engineer</div>
                <div style="padding-top:14px;border-top:1px solid rgba(100,116,139,0.2)">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
                        <i class="fas fa-building" style="color:#06b6d4;font-size:11px"></i>
                        <span style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#06b6d4">Perusahaan Mitra</span>
                    </div>
                    <div style="font-size:13px;font-weight:700;color:#f1f5f9">PT Sahabat Mitra Intrabuana</div>
                </div>
            </div>

        </div>
    </div>

</div>

{{-- MODAL PREVIEW FOTO --}}
<div class="modal-overlay" id="modalFoto" onclick="closeModal('modalFoto')">
    <div class="modal-foto-box" onclick="event.stopPropagation()">
        <button class="modal-foto-close" onclick="closeModal('modalFoto')">
            <i class="fas fa-times"></i>
        </button>
        <img id="modalFotoImg" src="" alt="Foto Profil">
        <p id="modalFotoName">{{ Auth::user()->name }}</p>
    </div>
</div>

@endsection

@push('scripts')
<script>
function switchTab(name, btn) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
    document.getElementById('tab-' + name).classList.add('active');
    btn.classList.add('active');
}

// ── Kirim OTP dari Setting ──
async function sendOtpFromSetting() {
    const btn = document.getElementById('btnSendOtp');
    const result = document.getElementById('otpResult');
    const success = document.getElementById('otpSuccess');
    const demo = document.getElementById('otpDemo');
    const error = document.getElementById('otpError');

    // Loading state
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Mengirim...';
    result.style.display = 'none';
    success.style.display = 'none';
    demo.style.display = 'none';
    error.style.display = 'none';

    try {
        const res = await fetch('{{ route("setting.send-otp") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
        });
        const j = await res.json();

        result.style.display = 'block';

        if (j.success) {
            success.style.display = 'block';
            document.getElementById('otpSuccessMsg').textContent =
                j.message + ' · Berlaku sampai ' + j.expires_at;

            if (j.demo_otp) {
                demo.style.display = 'block';
                document.getElementById('otpDemoCode').textContent = j.demo_otp;
            }
        } else {
            error.style.display = 'block';
            document.getElementById('otpErrorMsg').textContent = j.message;
        }
    } catch (e) {
        result.style.display = 'block';
        error.style.display = 'block';
        document.getElementById('otpErrorMsg').textContent = 'Koneksi gagal: ' + e.message;
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane"></i> Kirim Kode OTP ke Email';
    }
}

function togglePass(id) {
    const input = document.getElementById(id);
    input.type = input.type === 'password' ? 'text' : 'password';
}

function openPhotoModal(src) {
    document.getElementById('modalFotoImg').src = src;
    document.getElementById('modalFoto').style.display = 'flex';
}

// Theme option click
document.querySelectorAll('.theme-option').forEach(option => {
    option.addEventListener('click', function() {
        document.querySelectorAll('.theme-option').forEach(o => o.classList.remove('active'));
        this.classList.add('active');
        this.querySelector('input[type="radio"]').checked = true;
    });
});

// Preview foto sebelum upload
document.addEventListener('DOMContentLoaded', function() {
    const photoInput = document.getElementById('photoInput');
    if (photoInput) {
        photoInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview     = document.getElementById('previewPhoto');
                    const placeholder = document.getElementById('previewPlaceholder');
                    preview.src           = e.target.result;
                    preview.style.display = 'block';
                    if (placeholder) placeholder.style.display = 'none';
                }
                reader.readAsDataURL(file);
            }
        });
    }
});
</script>
@endpush