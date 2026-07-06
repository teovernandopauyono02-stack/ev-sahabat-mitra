<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\StasiunPengisian;
use Carbon\Carbon;

/**
 * RealisticEnergyLogSeeder
 *
 * Generate data log energi yang REALISTIS dan SALING TERHUBUNG:
 * - Nama stasiun & lokasi diambil langsung dari tabel stasiun_pengisian
 * - Energi kWh sesuai tipe charger (AC 22kW, DC 50kW, Fast 150kW)
 * - Waktu mulai/selesai realistis (durasi 30 menit - 3 jam)
 * - Distribusi waktu mengikuti pola penggunaan nyata (peak pagi & sore)
 * - Data langsung masuk ke tabel riwayat_pengisian (terhubung ke DB)
 */
class RealisticEnergyLogSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🔄 Generating realistic energy log data...');

        $stations = StasiunPengisian::all();

        if ($stations->isEmpty()) {
            $this->command->error('❌ Tidak ada stasiun. Tambah stasiun dulu.');
            return;
        }

        // Hapus data lama yang dibuat seeder ini
        DB::table('riwayat_pengisian')
            ->whereIn('stasiun_pengisian_id', $stations->pluck('id'))
            ->where('created_at', '<', now()->subDays(1))
            ->delete();

        $batch    = [];
        $batchSize = 200;
        $total    = 500; // 500 data realistis — cukup untuk demo, tidak lag
        $progress = 0;

        // Konfigurasi daya per tipe charger
        $chargerTypes = [
            ['tipe' => 'AC 22kW',   'min_kw' => 7.0,  'max_kw' => 22.0],
            ['tipe' => 'DC 50kW',   'min_kw' => 25.0, 'max_kw' => 50.0],
            ['tipe' => 'Fast 150kW','min_kw' => 80.0, 'max_kw' => 150.0],
        ];

        // Range 6 bulan ke belakang
        $startDate = Carbon::now()->subMonths(6)->startOfDay();
        $endDate   = Carbon::now()->subHours(1);

        for ($i = 0; $i < $total; $i++) {
            // Pilih stasiun secara bergilir (bukan random) agar merata
            $station = $stations[$i % $stations->count()];

            // Pilih tipe charger — AC 22kW paling umum (60%), DC (30%), Fast (10%)
            $rand = mt_rand(1, 100);
            if ($rand <= 60) {
                $charger = $chargerTypes[0]; // AC 22kW
            } elseif ($rand <= 90) {
                $charger = $chargerTypes[1]; // DC 50kW
            } else {
                $charger = $chargerTypes[2]; // Fast 150kW
            }

            // Generate waktu mulai realistis
            $waktuMulai = $this->generateRealisticTime($startDate, $endDate);

            // Durasi pengisian: 30 menit - 3 jam (tergantung tipe charger)
            $maxDurasi = $charger['tipe'] === 'Fast 150kW' ? 60 : ($charger['tipe'] === 'DC 50kW' ? 90 : 180);
            $minDurasi = $charger['tipe'] === 'Fast 150kW' ? 20 : 30;
            $durasiMenit = mt_rand($minDurasi, $maxDurasi);

            $waktuSelesai = (clone $waktuMulai)->addMinutes($durasiMenit);

            // Energi kWh = daya rata-rata × waktu (jam)
            $dayaRata = mt_rand((int)($charger['min_kw'] * 10), (int)($charger['max_kw'] * 10)) / 10;
            $energiKwh = round($dayaRata * ($durasiMenit / 60), 1);

            $batch[] = [
                'stasiun_pengisian_id' => $station->id,
                'nama_stasiun'         => $station->nama_stasiun,
                'lokasi'               => $station->lokasi,
                'energi_kwh'           => $energiKwh,
                'waktu_mulai'          => $waktuMulai->format('Y-m-d H:i:s'),
                'waktu_selesai'        => $waktuSelesai->format('Y-m-d H:i:s'),
                'durasi'               => $durasiMenit,
                'created_at'           => $waktuMulai->format('Y-m-d H:i:s'),
                'updated_at'           => $waktuSelesai->format('Y-m-d H:i:s'),
            ];

            if (count($batch) >= $batchSize) {
                DB::table('riwayat_pengisian')->insert($batch);
                $batch = [];
                $progress += $batchSize;
                $this->command->info("   ✓ {$progress}/{$total} record");
            }
        }

        if (!empty($batch)) {
            DB::table('riwayat_pengisian')->insert($batch);
        }

        $total_db = DB::table('riwayat_pengisian')->count();
        $this->command->info("✅ Selesai! Total di database: {$total_db} record");
        $this->command->info("   📊 Data terhubung ke " . $stations->count() . " stasiun");
    }

    /**
     * Generate waktu realistis dengan distribusi:
     * - Peak pagi: 07:00-09:00 (20%)
     * - Peak sore: 17:00-20:00 (30%)
     * - Normal siang: 10:00-16:00 (35%)
     * - Malam/dini hari: 21:00-06:00 (15%)
     */
    private function generateRealisticTime(Carbon $start, Carbon $end): Carbon
    {
        $totalDays = max(1, (int) $start->diffInDays($end));
        $randomDay = mt_rand(0, $totalDays);
        $date = (clone $start)->addDays($randomDay);

        $rand = mt_rand(1, 100);
        if ($rand <= 20) {
            $hour = mt_rand(7, 9);
        } elseif ($rand <= 50) {
            $hour = mt_rand(17, 20);
        } elseif ($rand <= 85) {
            $hour = mt_rand(10, 16);
        } else {
            $hour = mt_rand(21, 23);
        }

        return $date->setTime($hour, mt_rand(0, 59), mt_rand(0, 59));
    }
}
