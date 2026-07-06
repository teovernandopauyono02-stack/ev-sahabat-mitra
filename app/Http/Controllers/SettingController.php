<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use App\Models\User;

use App\Models\AuditLog;

class SettingController extends Controller
{
    public function index()
    {
        $user        = Auth::user();
        // Ambil log aktivitas dari DB (permanen) + fallback session
        $activityLog = AuditLog::latest()->take(50)->get()->map(function($l) {
            return [
                'user'     => $l->user_name,
                'action'   => $l->action . ($l->description ? ' — ' . $l->description : ''),
                'time'     => $l->created_at->setTimezone('Asia/Jakarta')->format('d/m/Y H:i:s'),
                'severity' => $l->severity,
                'module'   => $l->module,
            ];
        })->toArray();
        $threshold   = [
            'energi_kwh'    => env('THRESHOLD_ENERGI', 100),
            'inactive_days' => env('THRESHOLD_HARI', 7),
        ];
        $appConfig = [
            'app_name' => config('app.name', 'EV Smart Energy'),
            'timezone' => config('app.timezone', 'Asia/Jakarta'),
        ];
        $theme = session('theme', 'dark');

        return view('setting', compact(
            'user', 'activityLog', 'threshold', 'appConfig', 'theme'
        ));
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            'name'  => 'required|string|max:100',
            'email' => 'required|email|unique:users,email,' . Auth::id(),
            'phone' => 'nullable|string|max:20',
            'bio'   => 'nullable|string|max:500',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $data = $request->only('name', 'email', 'phone', 'bio');
        $oldData = Auth::user()->only(['name', 'email', 'phone', 'bio']);

        if ($request->hasFile('photo')) {
            if (Auth::user()->photo && file_exists(public_path('uploads/photos/' . Auth::user()->photo))) {
                unlink(public_path('uploads/photos/' . Auth::user()->photo));
            }
            $file          = $request->file('photo');
            $filename      = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/photos'), $filename);
            $data['photo'] = $filename;
        }

        User::where('id', Auth::id())->update($data);

        AuditLog::record(
            'Update Profil',
            'Setting',
            "Memperbarui profil akun: {$request->name} ({$request->email})",
            'info',
            $oldData,
            $data
        );

        return redirect()->route('setting.index')->with('success', 'Profil berhasil diperbarui!');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password'         => ['required', 'confirmed', Password::min(8)],
        ]);

        if (!Hash::check($request->current_password, Auth::user()->password)) {
            return back()->withErrors(['current_password' => 'Password lama tidak sesuai.']);
        }

        User::where('id', Auth::id())->update(['password' => Hash::make($request->password)]);

        AuditLog::record(
            'Ganti Password',
            'Setting',
            'Admin mengganti password akun',
            'warning',
            ['password' => '(tersembunyi)'],
            ['password' => '(diperbarui)']
        );

        return redirect()->route('setting.index')->with('success', 'Password berhasil diperbarui!');
    }

    public function storeUser(Request $request)
    {
        // Single-admin system — method ini dinonaktifkan
        return redirect()->route('setting.index');
    }

    public function updateUser(Request $request, $id)
    {
        // Single-admin system — method ini dinonaktifkan
        return redirect()->route('setting.index');
    }

    public function destroyUser($id)
    {
        // Single-admin system — method ini dinonaktifkan
        return redirect()->route('setting.index');
    }

    public function updateApp(Request $request)
    {
        $request->validate([
            'app_name' => 'required|string|max:100',
            'timezone' => 'required|string',
        ]);

        $oldName = config('app.name');
        $oldTz   = config('app.timezone');

        $this->setEnv('APP_NAME', '"' . $request->app_name . '"');
        $this->setEnv('APP_TIMEZONE', $request->timezone);

        AuditLog::record(
            'Update Konfigurasi Aplikasi',
            'Setting',
            "Nama app: {$oldName} → {$request->app_name} | Timezone: {$oldTz} → {$request->timezone}",
            'info',
            ['app_name' => $oldName, 'timezone' => $oldTz],
            ['app_name' => $request->app_name, 'timezone' => $request->timezone]
        );

        return redirect()->route('setting.index')->with('success', 'Konfigurasi berhasil diperbarui!');
    }

    public function updateNotification(Request $request)
    {
        $this->setEnv('NOTIF_EMAIL', $request->notif_email ?? '');
        $this->setEnv('NOTIF_ENABLED', $request->notif_enabled ? 'true' : 'false');
        $this->logActivity('Update notifikasi email');
        return redirect()->route('setting.index')->with('success', 'Notifikasi berhasil disimpan!');
    }

    public function backup()
    {
        $filename = 'backup_ev_sahabat_' . now()->format('Ymd_His') . '.sql';
        $dir      = storage_path('app/backups');
        $path     = $dir . DIRECTORY_SEPARATOR . $filename;

        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }

        $host     = config('database.connections.mysql.host');
        $port     = config('database.connections.mysql.port', 3306);
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $database = config('database.connections.mysql.database');

        // Coba pakai mysqldump dulu (lebih cepat dan lengkap)
        $mysqldump = $this->findMysqldump();

        if ($mysqldump) {
            $cmd = sprintf(
                '"%s" --host=%s --port=%s --user=%s %s --single-transaction --quick --skip-lock-tables --default-character-set=utf8mb4 %s > "%s" 2>&1',
                $mysqldump,
                escapeshellarg($host),
                (int) $port,
                escapeshellarg($username),
                $password ? '--password=' . escapeshellarg($password) : '',
                escapeshellarg($database),
                $path
            );

            exec($cmd, $output, $returnCode);

            if ($returnCode === 0 && file_exists($path) && filesize($path) > 0) {
                $this->logActivity('Backup database (mysqldump): ' . $filename);
                return response()->download($path)->deleteFileAfterSend(true);
            }
        }

        // Fallback: backup pakai PHP murni (PDO) — works tanpa mysqldump
        try {
            $this->backupWithPdo($path);
            AuditLog::record('Backup Database (PDO)', 'Setting', "File: {$filename}", 'info');
            return response()->download($path)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return back()->with('error', 'Backup gagal: ' . $e->getMessage());
        }
    }

    /**
     * Cari binary mysqldump di lokasi umum (Laragon, XAMPP, Linux).
     */
    private function findMysqldump(): ?string
    {
        $candidates = [
            'C:\\laragon\\bin\\mysql\\mysql-8.0.30-winx64\\bin\\mysqldump.exe',
            'C:\\laragon\\bin\\mysql\\mysql-8.0.21-winx64\\bin\\mysqldump.exe',
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',
            '/usr/bin/mysqldump',
            '/usr/local/bin/mysqldump',
            '/usr/local/mysql/bin/mysqldump',
        ];

        foreach ($candidates as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        // Cari di folder Laragon dinamis (versi MySQL berbeda-beda)
        $laragonGlob = glob('C:\\laragon\\bin\\mysql\\*\\bin\\mysqldump.exe');
        if (!empty($laragonGlob)) {
            return $laragonGlob[0];
        }

        // Cek apakah mysqldump ada di PATH
        $cmd = stripos(PHP_OS, 'WIN') === 0 ? 'where mysqldump 2>nul' : 'which mysqldump 2>/dev/null';
        $out = trim(@shell_exec($cmd) ?? '');
        if ($out && file_exists(strtok($out, "\r\n"))) {
            return strtok($out, "\r\n");
        }

        return null;
    }

    /**
     * Backup database pakai PDO murni — tanpa perlu mysqldump.
     * Lebih lambat tapi reliable di shared hosting / environment terbatas.
     */
    private function backupWithPdo(string $path): void
    {
        $pdo = DB::connection()->getPdo();
        $database = config('database.connections.mysql.database');

        $sql  = "-- ==========================================\n";
        $sql .= "-- EV Smart Energy Control Center — Database Backup\n";
        $sql .= "-- Generated: " . now()->format('d/m/Y H:i:s') . " WIB\n";
        $sql .= "-- Database: {$database}\n";
        $sql .= "-- ==========================================\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n";
        $sql .= "SET SQL_MODE=\"NO_AUTO_VALUE_ON_ZERO\";\n";
        $sql .= "SET NAMES utf8mb4;\n\n";

        $tables = $pdo->query("SHOW TABLES")->fetchAll(\PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            // Skip view
            $type = $pdo->query("SHOW FULL TABLES WHERE Tables_in_{$database} = " . $pdo->quote($table))
                        ->fetch(\PDO::FETCH_NUM);
            if (isset($type[1]) && $type[1] === 'VIEW') {
                continue;
            }

            $sql .= "-- ----------------------------------------\n";
            $sql .= "-- Table: `{$table}`\n";
            $sql .= "-- ----------------------------------------\n";
            $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";

            $create = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(\PDO::FETCH_ASSOC);
            $sql   .= ($create['Create Table'] ?? '') . ";\n\n";

            $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(\PDO::FETCH_ASSOC);
            if (empty($rows)) {
                continue;
            }

            $columns = array_map(fn($c) => "`{$c}`", array_keys($rows[0]));
            $sql    .= "INSERT INTO `{$table}` (" . implode(', ', $columns) . ") VALUES\n";

            $values = [];
            foreach ($rows as $row) {
                $rowVals = array_map(function ($v) use ($pdo) {
                    if ($v === null) return 'NULL';
                    if (is_numeric($v) && !str_starts_with((string) $v, '0') && strpos((string) $v, '.') === false) {
                        return $v;
                    }
                    return $pdo->quote($v);
                }, array_values($row));
                $values[] = '(' . implode(', ', $rowVals) . ')';
            }
            $sql .= implode(",\n", $values) . ";\n\n";
        }

        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

        file_put_contents($path, $sql);
    }

    public function updateTheme(Request $request)
    {
        $request->validate(['theme' => 'required|in:dark,light']);
        $oldTheme = session('theme', 'dark');
        session(['theme' => $request->theme]);

        AuditLog::record(
            'Ganti Tema',
            'Setting',
            "Tema diubah: {$oldTheme} → {$request->theme}",
            'info',
            ['theme' => $oldTheme],
            ['theme' => $request->theme]
        );

        return redirect()->route('setting.index')->with('success', 'Tema berhasil diubah!');
    }

    public function updateThreshold(Request $request)
    {
        $request->validate([
            'threshold_energi' => 'required|numeric|min:1',
            'threshold_hari'   => 'required|integer|min:1',
        ]);

        $oldEnergi = env('THRESHOLD_ENERGI', 100);
        $oldHari   = env('THRESHOLD_HARI', 7);

        $this->setEnv('THRESHOLD_ENERGI', $request->threshold_energi);
        $this->setEnv('THRESHOLD_HARI',   $request->threshold_hari);

        AuditLog::record(
            'Update Threshold Alert',
            'Setting',
            "Threshold energi: {$oldEnergi} → {$request->threshold_energi} kWh | Threshold hari: {$oldHari} → {$request->threshold_hari} hari",
            'info',
            ['threshold_energi' => $oldEnergi, 'threshold_hari' => $oldHari],
            ['threshold_energi' => $request->threshold_energi, 'threshold_hari' => $request->threshold_hari]
        );

        return redirect()->route('setting.index')->with('success', 'Threshold berhasil diperbarui!');
    }

    /**
     * Kirim OTP ke email admin dari dalam sistem (untuk verifikasi aksi penting).
     * Admin bisa request OTP langsung dari halaman Setting.
     */
    public function sendOtpFromSetting(Request $request)
    {
        $user = Auth::user();

        // Generate OTP
        $otp = \App\Models\PasswordResetOtp::createForEmail($user->email, 'email', $request->ip());

        // Kirim via email
        try {
            \Illuminate\Support\Facades\Mail::raw(
                "Halo {$user->name},\n\nKode OTP verifikasi Anda:\n\n    {$otp->otp_code}\n\nBerlaku 10 menit. Jangan bagikan ke siapa pun.\n\n— EV Smart Energy Control Center\nPT. Sahabat Mitra Intrabuana",
                function ($message) use ($user) {
                    $message->to($user->email)
                            ->subject('Kode OTP Verifikasi — EV Smart Energy');
                }
            );

            AuditLog::record(
                'Kirim OTP dari Setting',
                'Security',
                "OTP dikirim ke {$user->email} dari dalam sistem",
                'info'
            );

            // Kalau mode demo (MAIL_MAILER=log), tampilkan OTP di flash
            $isDemo = config('mail.default') === 'log' || env('MAIL_MAILER') === 'log';

            return response()->json([
                'success'   => true,
                'message'   => 'Kode OTP berhasil dikirim ke ' . $user->email,
                'demo_otp'  => $isDemo ? $otp->otp_code : null,
                'expires_at'=> $otp->expires_at->setTimezone('Asia/Jakarta')->format('H:i:s') . ' WIB',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal kirim OTP: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function logActivity($action, $module = 'Setting', $severity = 'info')
    {
        // Simpan ke database (permanen)
        AuditLog::record($action, $module, '', $severity);

        // Juga simpan ke session untuk backward compatibility
        $log   = session('activity_log', []);
        $log[] = [
            'user'   => Auth::user()->name,
            'action' => $action,
            'time'   => now()->format('d/m/Y H:i:s'),
        ];
        session(['activity_log' => array_slice($log, -50)]);
    }

    private function setEnv($key, $value)
    {
        $path    = base_path('.env');
        $content = file_get_contents($path);
        $content = str_contains($content, "{$key}=")
            ? preg_replace("/^{$key}=.*/m", "{$key}={$value}", $content)
            : $content . "\n{$key}={$value}";
        file_put_contents($path, $content);
    }
}

