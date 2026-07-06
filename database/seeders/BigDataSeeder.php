<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\StasiunPengisian;
use Carbon\Carbon;

/**
 * BigDataSeeder — Generate data dummy skala besar untuk Makul Big Data.
 * Menghasilkan ~50.000 record riwayat pengisian dengan pola realistis:
 * - Distribusi waktu mengikuti pola jam aktif (peak 07-09 dan 17-20)
 * - Volume harian bervariasi (Senin-Jumat lebih ramai)
 * - 3-5% data anomali (konsumsi sangat tinggi/rendah) untuk demo deteksi
 */
class BigDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🔄 Generating Big Data — 50.000 record riwayat pengisian...');

        $stations = StasiunPengisian::all();
        if ($stations->isEmpty()) {
            $this->command->error('❌ Belum ada stasiun. Jalankan migrate:fresh --seed dulu.');
            return;
        }

        // Bersihkan data dummy lama (yang dibuat seeder ini)
        DB::table('riwayat_pengisian')
            ->where('lokasi', 'LIKE', '%[BD-DUMMY]%')
            ->delete();

        $batch = [];
        $batchSize = 500;
        $total = 50000;
        $progress = 0;

        // Range data: 1 tahun ke belakang
        $startDate = Carbon::now()->subYear()->startOfDay();
        $endDate   = Carbon::now()->subDay()->endOfDay();

        for ($i = 0; $i < $total; $i++) {
            // Pilih stasiun random
            $station = $stations->random();

            // Generate waktu_mulai realistis
            $waktuMulai = $this->generateRealisticTime($startDate, $endDate);

            // Durasi pengisian: 15 menit - 4 jam (rata-rata 1-2 jam)
            $durasiMinutes = $this->generateRealisticDuration();
            $waktuSelesai  = (clone $waktuMulai)->addMinutes($durasiMinutes);

            // Energi kWh: berdasarkan durasi + variasi (rata-rata 22-30 kWh/jam)
            $kwhPerJam = mt_rand(2000, 3500) / 100; // 20-35 kWh/jam
            $energiKwh = round(($durasiMinutes / 60) * $kwhPerJam, 2);

            // 3-5% data anomali (untuk anomaly detection)
            if (mt_rand(1, 100) <= 4) {
                // Anomali tinggi: konsumsi gila (180-300 kWh)
                if (mt_rand(0, 1) === 0) {
                    $energiKwh = mt_rand(18000, 30000) / 100;
                } else {
                    // Anomali rendah: konsumsi sangat kecil (0.5-3 kWh untuk durasi panjang)
                    $energiKwh = mt_rand(50, 300) / 100;
                }
            }

            $batch[] = [
                'stasiun_pengisian_id' => $station->id,
                'lokasi'               => $station->lokasi . ' [BD-DUMMY]',
                'energi_kwh'           => $energiKwh,
                'waktu_mulai'          => $waktuMulai,
                'waktu_selesai'        => $waktuSelesai,
                'created_at'           => $waktuMulai,
                'updated_at'           => $waktuSelesai,
            ];

            if (count($batch) >= $batchSize) {
                DB::table('riwayat_pengisian')->insert($batch);
                $batch = [];
                $progress += $batchSize;
                if ($progress % 5000 === 0) {
                    $this->command->info("   ✓ {$progress}/{$total} record");
                }
            }
        }

        // Insert sisa
        if (!empty($batch)) {
            DB::table('riwayat_pengisian')->insert($batch);
        }

        $this->command->info("✅ Selesai! Total {$total} record dummy berhasil di-generate.");
        $this->command->info("   📊 Data ini sudah termasuk ~4% anomali untuk demo Anomaly Detection.");
    }

    /**
     * Generate waktu mulai realistis:
     * - Hari Senin-Jumat lebih ramai (60% lebih banyak)
     * - Jam peak: 07-09 pagi & 17-20 sore (40% data)
     * - Jam normal: 10-16 siang (35% data)
     * - Jam sepi: 21-06 (25% data)
     */
    private function generateRealisticTime(Carbon $start, Carbon $end): Carbon
    {
        $totalDays = $start->diffInDays($end);
        $randomDay = mt_rand(0, (int) $totalDays);
        $date = (clone $start)->addDays($randomDay);

        // Skip 30% weekend
        if ($date->isWeekend() && mt_rand(1, 100) <= 30) {
            $date->addDay();
        }

        // Distribusi jam
        $rand = mt_rand(1, 100);
        if ($rand <= 40) {
            // Peak hours
            $peaks = [7, 8, 9, 17, 18, 19, 20];
            $hour = $peaks[array_rand($peaks)];
        } elseif ($rand <= 75) {
            // Normal hours
            $hour = mt_rand(10, 16);
        } else {
            // Off hours
            $hour = mt_rand(0, 23);
            if ($hour >= 7 && $hour <= 20) {
                $hour = mt_rand(21, 23);
            }
        }

        return $date->setTime($hour, mt_rand(0, 59), mt_rand(0, 59));
    }

    /**
     * Durasi realistis: distribusi normal sekitar 1-2 jam
     */
    private function generateRealisticDuration(): int
    {
        $rand = mt_rand(1, 100);
        if ($rand <= 50) {
            return mt_rand(45, 90);    // 45-90 menit (50%)
        } elseif ($rand <= 80) {
            return mt_rand(91, 150);   // 91-150 menit (30%)
        } elseif ($rand <= 95) {
            return mt_rand(15, 44);    // 15-44 menit (15%)
        } else {
            return mt_rand(151, 240);  // 151-240 menit (5%)
        }
    }
}
