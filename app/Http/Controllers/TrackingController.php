<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\StasiunPengisian;
use App\Models\AuditLog;

/**
 * TrackingController — Real-time GPS tracking karyawan
 * Storage: file JSON di storage/app/tracking.json (tidak pakai database)
 * Format: { "token": { nama, stasiun_id, lat, lng, last_update, status } }
 */
class TrackingController extends Controller
{
    private string $file = 'tracking.json';
    private int $offlineThreshold = 300; // 5 menit (detik)

    /** Baca semua data tracking dari file */
    private function readData(): array
    {
        if (!Storage::disk('local')->exists($this->file)) {
            return [];
        }
        $content = Storage::disk('local')->get($this->file);
        $data    = json_decode($content, true);
        return is_array($data) ? $data : [];
    }

    /** Simpan data tracking ke file */
    private function writeData(array $data): void
    {
        Storage::disk('local')->put($this->file, json_encode($data, JSON_PRETTY_PRINT));
    }

    /** Hitung status berdasarkan last_update */
    private function calcStatus(?int $lastUpdate): string
    {
        if (!$lastUpdate) return 'offline';
        $diff = time() - $lastUpdate;
        if ($diff > $this->offlineThreshold) return 'offline';
        if ($diff > 30) return 'idle';
        return 'on_the_way';
    }

    // ==================================================================
    // ADMIN — Buat link tracking baru untuk karyawan
    // ==================================================================
    public function create(Request $request)
    {
        $request->validate([
            'nama_karyawan'    => 'required|string|max:100',
            'stasiun_tujuan_id' => 'nullable|exists:stasiun_pengisian,id',
        ]);

        $token = Str::lower(Str::random(12));
        $stasiun = $request->stasiun_tujuan_id
            ? StasiunPengisian::find($request->stasiun_tujuan_id)
            : null;

        $data = $this->readData();
        $data[$token] = [
            'token'             => $token,
            'nama_karyawan'     => $request->nama_karyawan,
            'stasiun_tujuan_id' => $request->stasiun_tujuan_id,
            'stasiun_nama'      => $stasiun?->nama_stasiun,
            'stasiun_lokasi'    => $stasiun?->lokasi,
            'stasiun_lat'       => $stasiun?->latitude,
            'stasiun_lng'       => $stasiun?->longitude,
            'latitude'          => null,
            'longitude'         => null,
            'accuracy'          => null,
            'speed'             => null,
            'heading'           => null,
            'last_update'       => null,
            'created_at'        => time(),
        ];
        $this->writeData($data);

        AuditLog::record(
            'Buat Link Tracking',
            'Tracking',
            "Generate link tracking untuk {$request->nama_karyawan}" .
            ($stasiun ? " menuju {$stasiun->nama_stasiun}" : ''),
            'info'
        );

        // Generate URL pakai host yang sebenarnya bisa diakses dari HP karyawan.
        // Prioritas: APP_URL kalau sudah disetting bukan localhost,
        // kalau masih localhost/ev-sahabat.test → coba pakai IP LAN admin
        $url = $this->buildPublicUrl($request, '/track/' . $token);

        // Cek apakah URL akan accessible dari HP karyawan
        $isLocal = $this->isLocalHost($url);

        return response()->json([
            'success'  => true,
            'token'    => $token,
            'url'      => $url,
            'is_local' => $isLocal,
            'warning'  => $isLocal
                ? 'PERINGATAN: Link ini hanya bisa diakses dari komputer ini. Untuk HP karyawan, hosting aplikasi atau pakai ngrok/cloudflare tunnel agar accessible dari internet.'
                : null,
            'message'  => 'Link tracking berhasil dibuat. Kirim link ini ke karyawan.',
        ]);
    }

    /**
     * Bangun URL public yang sebisa mungkin bisa diakses dari HP karyawan.
     * Logika:
     * 1. Kalau APP_URL bukan localhost → pakai APP_URL
     * 2. Kalau request masuk via hostname yang bukan localhost → pakai itu
     * 3. Kalau dapatnya tetap localhost → coba deteksi IP LAN server
     */
    private function buildPublicUrl(Request $request, string $path): string
    {
        $appUrl = config('app.url');
        $reqHost = $request->getSchemeAndHttpHost();

        // 1. APP_URL bagus (bukan localhost / ev-sahabat.test)
        if (!$this->isLocalHost($appUrl)) {
            return rtrim($appUrl, '/') . $path;
        }

        // 2. Request host bagus
        if (!$this->isLocalHost($reqHost)) {
            return rtrim($reqHost, '/') . $path;
        }

        // 3. Fallback: coba deteksi IP LAN
        $lanIp = $this->detectLanIp();
        if ($lanIp) {
            $port = $request->getPort();
            $portPart = ($port && !in_array($port, [80, 443])) ? ":{$port}" : '';
            return "http://{$lanIp}{$portPart}{$path}";
        }

        // 4. Last resort: pakai request host (walau localhost)
        return rtrim($reqHost, '/') . $path;
    }

    /**
     * Cek apakah host adalah local-only (tidak accessible dari device lain).
     */
    private function isLocalHost(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST) ?: $url;
        $host = strtolower($host);
        if ($host === 'localhost') return true;
        if ($host === '127.0.0.1') return true;
        if (str_ends_with($host, '.test')) return true;
        if (str_ends_with($host, '.local')) return true;
        return false;
    }

    /**
     * Deteksi IP LAN server (misal 192.168.x.x).
     * Pakai gethostbyname(gethostname()) yang biasanya kasih IP LAN aktif.
     */
    private function detectLanIp(): ?string
    {
        try {
            $hostname = gethostname();
            if (!$hostname) return null;
            $ip = gethostbyname($hostname);
            // Validasi: harus IP private (192.168, 10., 172.16-31)
            if ($ip && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                return $ip;
            }
        } catch (\Throwable $e) {
            // ignore
        }
        return null;
    }

    // ==================================================================
    // PUBLIC — Halaman tracking yang dibuka karyawan di HP
    // ==================================================================
    public function track(string $token)
    {
        $data = $this->readData();
        if (!isset($data[$token])) {
            abort(404, 'Link tracking tidak valid atau sudah expired.');
        }
        $info = $data[$token];
        return view('track', compact('token', 'info'));
    }

    // ==================================================================
    // API — Karyawan kirim GPS dari HP-nya
    // ==================================================================
    public function update(Request $request, string $token)
    {
        $request->validate([
            'latitude'  => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy'  => 'nullable|numeric',
            'speed'     => 'nullable|numeric',
            'heading'   => 'nullable|numeric|between:0,360',
        ]);

        $data = $this->readData();
        if (!isset($data[$token])) {
            return response()->json(['success' => false, 'message' => 'Token tidak valid'], 404);
        }

        $data[$token]['latitude']    = (float) $request->latitude;
        $data[$token]['longitude']   = (float) $request->longitude;
        $data[$token]['accuracy']    = $request->accuracy ? (float) $request->accuracy : null;
        $data[$token]['speed']       = $request->speed ? (float) $request->speed : null;
        $data[$token]['heading']     = $request->heading ? (int) $request->heading : null;
        $data[$token]['last_update'] = time();

        $this->writeData($data);

        return response()->json([
            'success' => true,
            'message' => 'Posisi diterima',
            'time'    => date('H:i:s', $data[$token]['last_update']),
        ]);
    }

    // ==================================================================
    // API — Admin tarik semua posisi karyawan untuk peta
    // ==================================================================
    public function getAll()
    {
        $data    = $this->readData();
        $now     = time();
        $cleaned = [];
        $changed = false;

        foreach ($data as $token => $entry) {
            // Auto-cleanup: hapus entry yang lebih dari 1 jam tanpa update
            if ($entry['last_update'] && ($now - $entry['last_update']) > 3600) {
                $changed = true;
                continue;
            }
            // Tambah status calculated
            $entry['status']         = $this->calcStatus($entry['last_update']);
            $entry['last_update_ago'] = $entry['last_update']
                ? $now - $entry['last_update']
                : null;
            $cleaned[$token] = $entry;
        }

        if ($changed) {
            $this->writeData($cleaned);
        }

        return response()->json([
            'success' => true,
            'data'    => array_values($cleaned),
            'count'   => count($cleaned),
        ]);
    }

    // ==================================================================
    // ADMIN — Hapus tracking
    // ==================================================================
    public function destroy(string $token)
    {
        $data = $this->readData();
        if (isset($data[$token])) {
            $nama = $data[$token]['nama_karyawan'] ?? 'Unknown';
            unset($data[$token]);
            $this->writeData($data);

            AuditLog::record(
                'Hapus Link Tracking',
                'Tracking',
                "Menghapus tracking karyawan: {$nama}",
                'warning'
            );
        }
        return response()->json(['success' => true]);
    }
}
