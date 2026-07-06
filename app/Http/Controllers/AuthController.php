<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Models\LoginAttempt;
use App\Models\AuditLog;
use App\Models\AccountLock;
use App\Models\PasswordResetOtp;
use App\Models\User;

class AuthController extends Controller
{
    /** Konfigurasi keamanan login — semua durasi dalam detik (max 30s) */
    private const MAX_FAILED_ATTEMPTS    = 5;    // 5x gagal → akun di-lock
    private const ACCOUNT_LOCK_SECONDS   = 30;   // durasi lock akun: 30 detik
    private const ATTEMPT_WINDOW_SECONDS = 300;  // window hitung gagal: 5 menit (300 detik)
    private const IP_BLOCK_SECONDS       = 30;   // durasi IP block: 30 detik

    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        $stats = [
            'total_stasiun' => \App\Models\StasiunPengisian::count(),
            'total_charger' => \App\Models\Charger::count(),
        ];

        return view('auth.login', compact('stats'));
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $ip    = $request->ip();
        $email = $request->email;
        $ua    = substr($request->userAgent() ?? '', 0, 255);

        // ============================================================
        // 1. Cek apakah AKUN (email) sedang dikunci
        // ============================================================
        if (AccountLock::isLocked($email)) {
            $sisa = AccountLock::secondsUntilUnlock($email);
            LoginAttempt::create([
                'email'          => $email,
                'ip_address'     => $ip,
                'user_agent'     => $ua,
                'status'         => 'failed',
                'failure_reason' => "Akun terkunci, sisa {$sisa} detik",
            ]);
            return back()->withErrors([
                'email' => "Akun terkunci karena terlalu banyak percobaan gagal. Coba lagi dalam {$sisa} detik atau gunakan Lupa Password.",
            ])->onlyInput('email');
        }

        // ============================================================
        // 2. Cek brute-force IP
        // ============================================================
        if (LoginAttempt::isBruteForce($ip)) {
            LoginAttempt::create([
                'email'          => $email,
                'ip_address'     => $ip,
                'user_agent'     => $ua,
                'status'         => 'failed',
                'failure_reason' => 'IP diblokir sementara — terlalu banyak percobaan dari IP ini',
            ]);

            // Catat sebagai aktivitas mencurigakan ke audit log
            AuditLog::record(
                'IP Mencurigakan Diblokir',
                'Security',
                "IP {$ip} diblokir sementara karena terlalu banyak percobaan login gagal (brute-force).",
                'critical'
            );

            return back()->withErrors([
                'email' => 'Terlalu banyak percobaan dari alamat ini. Coba lagi dalam 30 detik.',
            ])->onlyInput('email');
        }

        $remember = $request->has('remember');

        // ============================================================
        // 3. Coba autentikasi
        // ============================================================
        if (Auth::attempt($request->only('email', 'password'), $remember)) {
            $request->session()->regenerate();
            $user = Auth::user();

            LoginAttempt::create([
                'email'      => $email,
                'ip_address' => $ip,
                'user_agent' => $ua,
                'status'     => 'success',
                'user_id'    => $user->id,
            ]);

            // Reset lock kalau login berhasil
            AccountLock::where('email', $email)->delete();

            AuditLog::record(
                'Login Berhasil',
                'Authentication',
                "User {$user->name} ({$user->email}) login dari IP {$ip}",
                'info'
            );

            return redirect()->intended(route('dashboard'));
        }

        // ============================================================
        // 4. Login gagal — catat & cek apakah perlu lock akun
        // ============================================================
        LoginAttempt::create([
            'email'          => $email,
            'ip_address'     => $ip,
            'user_agent'     => $ua,
            'status'         => 'failed',
            'failure_reason' => 'Email atau password salah',
        ]);

        // Hitung berapa kali email ini gagal dalam window waktu (30 detik)
        $failCount = LoginAttempt::where('email', $email)
            ->where('status', 'failed')
            ->where('created_at', '>=', now()->subSeconds(self::ATTEMPT_WINDOW_SECONDS))
            ->count();

        if ($failCount >= self::MAX_FAILED_ATTEMPTS) {
            // Lock akun & catat critical alert
            $lock = AccountLock::lockEmail(
                $email,
                self::ACCOUNT_LOCK_SECONDS,
                $ip,
                "Gagal login {$failCount}x dalam " . self::ATTEMPT_WINDOW_SECONDS . " detik"
            );

            $severity = $lock->isSuspicious() ? 'critical' : 'warning';
            AuditLog::record(
                $lock->isSuspicious() ? 'AKUN MENCURIGAKAN — Sering Dikunci' : 'Akun Dikunci Sementara',
                'Security',
                "Akun {$email} dikunci selama " . self::ACCOUNT_LOCK_SECONDS . " detik — gagal login {$failCount}x dari IP {$ip}. Lock ke-{$lock->lock_count}.",
                $severity
            );

            return back()->withErrors([
                'email' => "Akun terkunci selama " . self::ACCOUNT_LOCK_SECONDS . " detik karena {$failCount}x percobaan gagal. Gunakan Lupa Password jika perlu.",
            ])->onlyInput('email');
        }

        $sisa = self::MAX_FAILED_ATTEMPTS - $failCount;

        return back()->withErrors([
            'email' => 'Email atau password salah.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        $user = Auth::user();

        if ($user) {
            AuditLog::record(
                'Logout',
                'Authentication',
                "User {$user->name} logout dari sistem",
                'info'
            );
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    // ============================================================
    // FORGOT PASSWORD — OTP via Email/WhatsApp
    // ============================================================

    /** Halaman: input email untuk minta OTP */
    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    /** Kirim OTP ke email/WA */
    public function sendOtp(Request $request)
    {
        $request->validate([
            'email'     => 'required|email',
            'channel'   => 'required|in:email,whatsapp',
            'wa_number' => 'required_if:channel,whatsapp|nullable|string|min:10|max:15',
        ], [
            'wa_number.required_if' => 'Nomor WhatsApp wajib diisi jika memilih channel WhatsApp.',
            'wa_number.min'         => 'Nomor WhatsApp minimal 10 digit.',
        ]);

        $email = $request->email;
        $user  = User::where('email', $email)->first();

        if (!$user) {
            AuditLog::record(
                'Reset Password — Email Tidak Terdaftar',
                'Security',
                "Percobaan reset password untuk email tidak terdaftar: {$email} dari IP {$request->ip()}",
                'warning'
            );
            return redirect()->route('password.verify-otp', ['email' => $email])
                ->with('info', 'Jika email terdaftar, kode OTP telah dikirim. Cek inbox/spam Anda.');
        }

        // Simpan nomor WA ke profil user jika diisi
        if ($request->channel === 'whatsapp' && $request->filled('wa_number')) {
            $user->update(['phone' => $request->wa_number]);
        }

        // Generate OTP
        $otp = PasswordResetOtp::createForEmail($email, $request->channel, $request->ip());

        // Kirim OTP via channel yang dipilih
        if ($request->channel === 'email') {
            $this->sendOtpViaEmail($email, $otp->otp_code, $user->name);
        } else {
            // Gunakan nomor yang diinput user, fallback ke nomor di profil
            $phone = $request->wa_number ?? $user->phone ?? '';
            $this->sendOtpViaWhatsApp($phone, $otp->otp_code, $user->name);
        }

        AuditLog::record(
            'OTP Reset Password Dikirim',
            'Security',
            "OTP reset password dikirim ke {$email} via {$request->channel}",
            'info'
        );

        // Mode demo: tampilkan OTP di session flash
        $isLogMail = config('mail.default') === 'log' || env('MAIL_MAILER') === 'log';
        $demoFlash = $isLogMail
            ? "Mode Demo — OTP: {$otp->otp_code} (di production akan dikirim ke " . ($request->channel === 'email' ? 'email' : 'WhatsApp') . " Anda)"
            : null;

        return redirect()->route('password.verify-otp', ['email' => $email])
            ->with('success', 'Kode OTP berhasil dikirim. Cek ' . ($request->channel === 'email' ? 'inbox email' : 'WhatsApp nomor ' . $request->wa_number) . ' Anda.')
            ->with('demo_otp', $demoFlash);
    }

    /** Halaman verifikasi OTP */
    public function showVerifyOtp(Request $request)
    {
        $email = $request->query('email');
        if (!$email) return redirect()->route('password.forgot');
        return view('auth.verify-otp', compact('email'));
    }

    /** Verifikasi kode OTP */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp'   => 'required|string|size:6',
        ]);

        $otp = PasswordResetOtp::where('email', $request->email)
            ->where('otp_code', $request->otp)
            ->where('is_used', false)
            ->latest()->first();

        if (!$otp || !$otp->isValid()) {
            AuditLog::record(
                'OTP Reset Password Salah',
                'Security',
                "Percobaan verifikasi OTP salah untuk email {$request->email} dari IP {$request->ip()}",
                'warning'
            );
            return back()->withErrors(['otp' => 'Kode OTP salah atau sudah kedaluwarsa.'])->withInput();
        }

        // Tandai OTP terverifikasi (belum dipakai sampai password diganti)
        session(['otp_verified_email' => $request->email, 'otp_verified_id' => $otp->id]);

        return redirect()->route('password.reset.form');
    }

    /** Halaman reset password setelah OTP verified */
    public function showResetForm()
    {
        if (!session('otp_verified_email')) {
            return redirect()->route('password.forgot')
                ->with('error', 'Sesi reset password telah berakhir. Silakan mulai ulang.');
        }
        return view('auth.reset-password', ['email' => session('otp_verified_email')]);
    }

    /** Simpan password baru */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $email = session('otp_verified_email');
        $otpId = session('otp_verified_id');

        if (!$email || !$otpId) {
            return redirect()->route('password.forgot')
                ->with('error', 'Sesi reset password telah berakhir.');
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            return redirect()->route('password.forgot')
                ->with('error', 'User tidak ditemukan.');
        }

        // Update password
        $user->update(['password' => Hash::make($request->password)]);

        // Tandai OTP sudah dipakai
        PasswordResetOtp::where('id', $otpId)->update(['is_used' => true]);

        // Bersihkan lock akun
        AccountLock::where('email', $email)->delete();

        // Bersihkan session
        session()->forget(['otp_verified_email', 'otp_verified_id']);

        AuditLog::record(
            'Password Berhasil Direset',
            'Security',
            "User {$user->name} ({$email}) berhasil mereset password via OTP dari IP {$request->ip()}",
            'info'
        );

        return redirect()->route('login')
            ->with('success', 'Password berhasil direset. Silakan login dengan password baru Anda.');
    }

    // ============================================================
    // HELPER — kirim OTP
    // ============================================================

    private function sendOtpViaEmail(string $email, string $otp, string $name): void
    {
        try {
            Mail::raw(
                "Halo {$name},\n\nKode OTP Anda untuk reset password EV Smart Energy Control Center:\n\n" .
                "    {$otp}\n\n" .
                "Kode ini berlaku selama 10 menit. Jangan bagikan kode ini kepada siapa pun.\n\n" .
                "Jika Anda tidak meminta reset password, abaikan email ini.\n\n" .
                "— EV Smart Energy Control Center\nPT. Sahabat Mitra Intrabuana",
                function ($message) use ($email) {
                    $message->to($email)
                            ->subject('Kode OTP Reset Password — EV Smart Energy');
                }
            );
        } catch (\Exception $e) {
            Log::warning('Gagal kirim OTP email: ' . $e->getMessage());
        }
    }

    /**
     * Kirim OTP via WhatsApp.
     * Saat ini placeholder — log saja. Production bisa integrasi:
     * - WhatsApp Business API
     * - Fonnte / Wablas / Watzap (gateway lokal Indonesia)
     */
    private function sendOtpViaWhatsApp(string $phone, string $otp, string $name): void
    {
        $message = "*EV Smart Energy*\n\nHalo {$name},\nKode OTP reset password Anda: *{$otp}*\n\nBerlaku 10 menit. Jangan bagikan kode ini.";

        // Placeholder: log kalau gateway WA belum dikonfigurasi
        if (!env('WA_GATEWAY_URL') || !env('WA_GATEWAY_TOKEN')) {
            Log::info("[WA OTP DEMO] Phone: {$phone} | OTP: {$otp}");
            return;
        }

        try {
            \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Bearer ' . env('WA_GATEWAY_TOKEN'),
            ])->post(env('WA_GATEWAY_URL'), [
                'target'  => $phone,
                'message' => $message,
            ]);
        } catch (\Exception $e) {
            Log::warning('Gagal kirim OTP WhatsApp: ' . $e->getMessage());
        }
    }
}
