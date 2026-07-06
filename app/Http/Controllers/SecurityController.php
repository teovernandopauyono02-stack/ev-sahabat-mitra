<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LoginAttempt;
use App\Models\AuditLog;
use Carbon\Carbon;

/**
 * SecurityController — Keamanan Data & Jaringan (Makul No.2)
 * Monitoring akses, login attempts, checklist keamanan, SOP keamanan
 */
class SecurityController extends Controller
{
    public function index()
    {
        // Statistik login
        $totalLogin      = LoginAttempt::where('status', 'success')->count();
        $totalGagal      = LoginAttempt::where('status', 'failed')->count();
        $loginHariIni    = LoginAttempt::whereDate('created_at', today())->count();
        $percobaanGagal  = LoginAttempt::where('status', 'failed')
            ->where('created_at', '>=', now()->subHours(24))->count();

        // Riwayat login terbaru
        $loginLog = LoginAttempt::latest()->take(30)->get();

        // IP yang mencurigakan (>3 gagal dalam 24 jam)
        $suspiciousIp = LoginAttempt::where('status', 'failed')
            ->where('created_at', '>=', now()->subHours(24))
            ->selectRaw('ip_address, COUNT(*) as total_gagal')
            ->groupBy('ip_address')
            ->having('total_gagal', '>=', 3)
            ->orderByDesc('total_gagal')
            ->get();

        // Akun yang sedang/pernah dikunci
        $accountLocks = \App\Models\AccountLock::orderByDesc('lock_count')->take(20)->get();

        // Audit log terbaru
        $auditLog = AuditLog::latest()->take(50)->get();

        // Data grafik login 7 hari untuk chart
        $loginChart7Days = collect(range(6, 0))->map(function($i) {
            $date = now()->subDays($i)->toDateString();
            return [
                'label'   => now()->subDays($i)->setTimezone('Asia/Jakarta')->format('d/m/Y'),
                'success' => LoginAttempt::where('status','success')->whereDate('created_at', $date)->count(),
                'failed'  => LoginAttempt::where('status','failed')->whereDate('created_at', $date)->count(),
            ];
        })->values()->toArray();

        // Checklist keamanan sistem
        $checklist = $this->getSecurityChecklist();

        return view('security', compact(
            'totalLogin', 'totalGagal', 'loginHariIni', 'percobaanGagal',
            'loginLog', 'suspiciousIp', 'accountLocks', 'auditLog', 'checklist',
            'loginChart7Days'
        ));
    }

    /** Checklist keamanan — bukti implementasi keamanan sistem */
    private function getSecurityChecklist(): array
    {
        return [
            [
                'kategori' => 'Autentikasi & Akses',
                'items' => [
                    ['item' => 'Sistem login dengan email + password', 'status' => true, 'keterangan' => 'Diimplementasikan di AuthController'],
                    ['item' => 'Session regeneration setelah login', 'status' => true, 'keterangan' => '$request->session()->regenerate()'],
                    ['item' => 'CSRF Protection aktif', 'status' => true, 'keterangan' => 'VerifyCsrfToken middleware'],
                    ['item' => 'Role-based access control', 'status' => true, 'keterangan' => 'CheckRole middleware (admin/user)'],
                    ['item' => 'Password hashing (bcrypt)', 'status' => true, 'keterangan' => 'Hash::make() di semua password'],
                    ['item' => 'Brute-force protection (IP)', 'status' => true, 'keterangan' => 'Blokir IP setelah 5 gagal / 15 menit'],
                    ['item' => 'Akun lock otomatis (30 detik)', 'status' => true, 'keterangan' => 'Lock per email setelah 5x gagal — tabel account_locks'],
                    ['item' => 'Lupa Password dengan OTP 6 digit', 'status' => true, 'keterangan' => 'Email/WhatsApp + tabel password_reset_otps'],
                    ['item' => 'Logout invalidate session', 'status' => true, 'keterangan' => 'session()->invalidate() saat logout'],
                ],
            ],
            [
                'kategori' => 'Keamanan Data',
                'items' => [
                    ['item' => 'API Password dienkripsi di database', 'status' => true, 'keterangan' => "Cast 'encrypted' di model ApiConnection"],
                    ['item' => 'Input validation di semua form', 'status' => true, 'keterangan' => '$request->validate() di semua controller'],
                    ['item' => 'SQL Injection protection (Eloquent ORM)', 'status' => true, 'keterangan' => 'Laravel Eloquent & Query Builder'],
                    ['item' => 'XSS Protection (Blade templating)', 'status' => true, 'keterangan' => '{{ }} di Blade otomatis escape HTML'],
                    ['item' => 'Database backup tersedia', 'status' => true, 'keterangan' => 'Fitur backup di Setting'],
                ],
            ],
            [
                'kategori' => 'Monitoring & Audit',
                'items' => [
                    ['item' => 'Login attempt logging', 'status' => true, 'keterangan' => 'Tabel login_attempts'],
                    ['item' => 'Audit trail semua aksi user', 'status' => true, 'keterangan' => 'Tabel audit_logs (semua CRUD modul)'],
                    ['item' => 'Alert sistem untuk stasiun bermasalah', 'status' => true, 'keterangan' => 'Fitur Alert dengan notifikasi'],
                    ['item' => 'Alert keamanan otomatis (suspicious IP & akun lock)', 'status' => true, 'keterangan' => 'Suspicious activity → Alert Security'],
                    ['item' => 'API sync logging', 'status' => true, 'keterangan' => 'Tabel api_synclogs'],
                ],
            ],
            [
                'kategori' => 'Keamanan Jaringan',
                'items' => [
                    ['item' => 'HTTPS ready (konfigurasi .env)', 'status' => false, 'keterangan' => 'Perlu SSL certificate di production'],
                    ['item' => 'Environment variables untuk credentials', 'status' => true, 'keterangan' => 'Semua credential di .env'],
                    ['item' => '.env tidak di-commit ke repository', 'status' => true, 'keterangan' => '.env ada di .gitignore'],
                    ['item' => 'API key tersimpan aman (encrypted)', 'status' => true, 'keterangan' => 'api_password di-encrypt Laravel'],
                ],
            ],
        ];
    }
}
