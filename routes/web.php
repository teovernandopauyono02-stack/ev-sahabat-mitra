<?php

/**
 * ============================================================
 * EV Smart Energy Control Center — Web Routes
 * Single-admin system: semua route protected dengan middleware auth
 * ============================================================
 */

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\StasiunController;
use App\Http\Controllers\ChargerController;
use App\Http\Controllers\RiwayatController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\SecurityController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\ApiintegrationController;
use App\Http\Controllers\TrackingController;

// ================================================================
// AUTH — Tidak memerlukan login
// ================================================================
Route::get('/login',  [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ── Lupa Password (OTP via email/WhatsApp) ──
Route::get('/password/forgot',         [AuthController::class, 'showForgotPassword'])->name('password.forgot');
Route::post('/password/send-otp',      [AuthController::class, 'sendOtp'])->name('password.send-otp');
Route::get('/password/verify-otp',     [AuthController::class, 'showVerifyOtp'])->name('password.verify-otp');
Route::post('/password/verify-otp',    [AuthController::class, 'verifyOtp'])->name('password.verify-otp.post');
Route::get('/password/reset',          [AuthController::class, 'showResetForm'])->name('password.reset.form');
Route::post('/password/reset',         [AuthController::class, 'resetPassword'])->name('password.reset');

// ── PUBLIC TRACKING — karyawan buka di HP, kirim GPS ──
Route::get('/track/{token}',           [TrackingController::class, 'track'])->name('tracking.show');
Route::post('/track/{token}/update',   [TrackingController::class, 'update'])->name('tracking.update');

// ================================================================
// PROTECTED — Semua route di bawah memerlukan login
// ================================================================
Route::middleware('auth')->group(function () {

    // ── Dashboard ──────────────────────────────────────────────
    Route::get('/',                     [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard',            [DashboardController::class, 'index']);
    Route::get('/dashboard/chart-data', [DashboardController::class, 'chartData'])->name('dashboard.chart');

    // ── Station (Stasiun Pengisian) ─────────────────────────────
    Route::get('/station',         [StasiunController::class, 'index'])->name('station.index');
    Route::get('/station/{id}',    [StasiunController::class, 'show'])->name('station.show');
    Route::post('/station',        [StasiunController::class, 'store'])->name('station.store');
    Route::put('/station/{id}',    [StasiunController::class, 'update'])->name('station.update');
    Route::delete('/station/{id}', [StasiunController::class, 'destroy'])->name('station.destroy');

    // ── Charger Unit ───────────────────────────────────────────
    Route::post('/charger',        [ChargerController::class, 'store'])->name('charger.store');
    Route::put('/charger/{id}',    [ChargerController::class, 'update'])->name('charger.update');
    Route::delete('/charger/{id}', [ChargerController::class, 'destroy'])->name('charger.destroy');

    // ── Energy Log (Riwayat Pengisian) ──────────────────────────
    Route::get('/energy-log',         [RiwayatController::class, 'index'])->name('energy-log.index');
    Route::post('/energy-log',        [RiwayatController::class, 'store'])->name('energy-log.store');
    Route::put('/energy-log/{id}',    [RiwayatController::class, 'update'])->name('energy-log.update');
    Route::delete('/energy-log/{id}', [RiwayatController::class, 'destroy'])->name('energy-log.destroy');

    // ── Report (Laporan Energi) ─────────────────────────────────
    Route::get('/report',              [ReportController::class, 'index'])->name('report.index');
    Route::get('/report/export-pdf',   [ReportController::class, 'exportPdf'])->name('report.export-pdf');
    Route::get('/report/export-excel', [ReportController::class, 'exportExcel'])->name('report.export-excel');

    // ── Map View ────────────────────────────────────────────────
    Route::get('/map-view', [MapController::class, 'index'])->name('map-view.index');
    Route::post('/map-view/sync-coordinates', [MapController::class, 'syncCoordinates'])->name('map-view.sync-coordinates');

    // ── Tracking Karyawan (admin endpoints) ─────────────────────
    Route::post('/tracking/create',         [TrackingController::class, 'create'])->name('tracking.create');
    Route::get('/tracking/all',             [TrackingController::class, 'getAll'])->name('tracking.all');
    Route::delete('/tracking/{token}',      [TrackingController::class, 'destroy'])->name('tracking.destroy');

    // ── Analytics (Big Data) ────────────────────────────────────
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');

    // ── Alert (Sistem Peringatan) ───────────────────────────────
    Route::get('/alert',                     [AlertController::class, 'index'])->name('alert.index');
    Route::get('/alert/export-pdf',          [AlertController::class, 'exportPdf'])->name('alert.export-pdf');
    Route::post('/alert/resolve',            [AlertController::class, 'resolve'])->name('alert.resolve');
    Route::post('/alert/reset-resolved',     [AlertController::class, 'resetResolved'])->name('alert.reset-resolved');

    // ── Security (Keamanan Data & Jaringan) ─────────────────────
    Route::get('/security', [SecurityController::class, 'index'])->name('security.index');

    // ── Audit Trail (Audit TI) ──────────────────────────────────
    Route::get('/audit',                  [App\Http\Controllers\AuditController::class, 'index'])->name('audit.index');
    Route::get('/audit/export-pdf',       [App\Http\Controllers\AuditController::class, 'exportPdf'])->name('audit.export-pdf');
    Route::get('/audit/{id}',             [App\Http\Controllers\AuditController::class, 'show'])->name('audit.show');

    // ── API Integration (Integrasi Sistem) ─────────────────────
    // Catatan: Route statis HARUS sebelum route dinamis {id}
    Route::get('/api-integration',                       [ApiintegrationController::class, 'index'])->name('api-integration.index');
    Route::get('/api-integration/stats',                 [ApiintegrationController::class, 'getStats'])->name('api-integration.stats');
    Route::get('/api-integration/connections',           [ApiintegrationController::class, 'getConnections'])->name('api-integration.connections');
    Route::get('/api-integration/logs',                  [ApiintegrationController::class, 'getSyncLogs'])->name('api-integration.logs');
    Route::get('/api-integration/export/excel',          [ApiintegrationController::class, 'exportExcel'])->name('api-integration.export.excel');
    Route::get('/api-integration/export/pdf',            [ApiintegrationController::class, 'exportPdf'])->name('api-integration.export.pdf');
    Route::post('/api-integration',                      [ApiintegrationController::class, 'store'])->name('api-integration.store');
    Route::post('/api-integration/sync-all',             [ApiintegrationController::class, 'syncAll'])->name('api-integration.sync-all');
    Route::post('/api-integration/test-connection',      [ApiintegrationController::class, 'testConnection'])->name('api-integration.test');
    Route::post('/api-integration/logs/delete-all',      [ApiintegrationController::class, 'destroyAllLogs'])->name('api-integration.logs.destroy-all');
    Route::get('/api-integration/{id}/last-response',    [ApiintegrationController::class, 'getLastResponse'])->name('api-integration.last-response');
    Route::post('/api-integration/{id}/set-yield-path',  [ApiintegrationController::class, 'setYieldPath'])->name('api-integration.set-yield-path');
    Route::post('/api-integration/log/{id}/delete',      [ApiintegrationController::class, 'destroyLog'])->name('api-integration.log.destroy');
    Route::post('/api-integration/{id}/update',          [ApiintegrationController::class, 'update'])->name('api-integration.update');
    Route::post('/api-integration/{id}/delete',          [ApiintegrationController::class, 'destroy'])->name('api-integration.destroy');
    Route::post('/api-integration/{id}/fetch',           [ApiintegrationController::class, 'fetchData'])->name('api-integration.fetch');
    Route::get('/api-integration/{id}/chart-data',       [ApiintegrationController::class, 'getChartData'])->name('api-integration.chart');

    // ── Setting (Konfigurasi Sistem) ────────────────────────────
    Route::get('/setting',           [SettingController::class, 'index'])->name('setting.index');
    Route::put('/setting/profile',   [SettingController::class, 'updateProfile'])->name('setting.profile');
    Route::put('/setting/password',  [SettingController::class, 'updatePassword'])->name('setting.password');
    Route::put('/setting/app',       [SettingController::class, 'updateApp'])->name('setting.app');
    Route::put('/setting/theme',     [SettingController::class, 'updateTheme'])->name('setting.theme');
    Route::put('/setting/threshold', [SettingController::class, 'updateThreshold'])->name('setting.threshold');
    Route::post('/setting/backup',   [SettingController::class, 'backup'])->name('setting.backup');
    Route::post('/setting/send-otp', [SettingController::class, 'sendOtpFromSetting'])->name('setting.send-otp');

});
