<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\StasiunPengisian;
use Carbon\Carbon;

/**
 * StationDummySeeder
 * Generate 500 stasiun pengisian dummy di kota-kota besar Indonesia + 50.000 log energi.
 * Bisa dijalankan ulang aman: stasiun & log dummy lama akan dibersihkan dulu.
 */
class StationDummySeeder extends Seeder
{
    /** Target jumlah stasiun dummy yang dibuat. */
    private int $targetStations = 500;

    /** Target jumlah log energi yang disebar ke semua stasiun. */
    private int $targetLogs = 50000;

    /**
     * Master list 40 kota besar Indonesia dengan koordinat akurat (lat, lng).
     * Setiap kota akan diperbanyak jadi beberapa cabang sampai mencapai targetStations.
     */
    private array $cities = [
        ['Jakarta Pusat',    -6.1862,  106.8344],
        ['Jakarta Utara',    -6.1383,  106.8633],
        ['Jakarta Barat',    -6.1683,  106.7588],
        ['Jakarta Timur',    -6.2250,  106.9000],
        ['Bekasi',           -6.2349,  106.9896],
        ['Tangerang',        -6.1781,  106.6300],
        ['Depok',            -6.4025,  106.7942],
        ['Bogor',            -6.5944,  106.7892],
        ['Bandung',          -6.9175,  107.6191],
        ['Cirebon',          -6.7320,  108.5523],
        ['Tasikmalaya',      -7.3506,  108.2172],
        ['Sukabumi',         -6.9230,  106.9296],
        ['Semarang',         -7.0050,  110.4170],
        ['Solo',             -7.5755,  110.8243],
        ['Magelang',         -7.4798,  110.2177],
        ['Pekalongan',       -6.8898,  109.6753],
        ['Surabaya',         -7.2575,  112.7521],
        ['Malang',           -7.9666,  112.6326],
        ['Kediri',           -7.8167,  112.0167],
        ['Madiun',           -7.6298,  111.5300],
        ['Denpasar',         -8.6705,  115.2126],
        ['Mataram',          -8.5833,  116.1167],
        ['Kupang',          -10.1718,  123.6075],
        ['Pontianak',        -0.0263,  109.3425],
        ['Banjarmasin',      -3.3186,  114.5944],
        ['Balikpapan',       -1.2379,  116.8529],
        ['Samarinda',        -0.5022,  117.1536],
        ['Manado',            1.4748,  124.8421],
        ['Palu',             -0.9003,  119.8779],
        ['Kendari',          -3.9985,  122.5128],
        ['Gorontalo',         0.5435,  123.0568],
        ['Ambon',            -3.6954,  128.1814],
        ['Jayapura',         -2.5916,  140.6690],
        ['Padang',           -0.9492,  100.3543],
        ['Pekanbaru',         0.5071,  101.4478],
        ['Jambi',            -1.6101,  103.6131],
        ['Palembang',        -2.9909,  104.7566],
        ['Bengkulu',         -3.7928,  102.2608],
        ['Banda Aceh',        5.5483,   95.3238],
        ['Batam',             1.0456,  104.0305],
    ];

    public function run(): void
    {
        $this->command->info("🏗  Generating {$this->targetStations} stasiun + {$this->targetLogs} log energi...");

        // 1. Bersihkan stasiun dummy lama beserta lognya
        $oldDummyIds = StasiunPengisian::where('lokasi', 'LIKE', '%[DUMMY]%')->pluck('id');
        if ($oldDummyIds->isNotEmpty()) {
            DB::table('riwayat_pengisian')->whereIn('stasiun_pengisian_id', $oldDummyIds)->delete();
            StasiunPengisian::whereIn('id', $oldDummyIds)->delete();
            $this->command->info('   ↻ Bersihkan ' . $oldDummyIds->count() . ' stasiun dummy lama beserta lognya.');
        }

        // 2. Bersihkan log [BD-DUMMY] lama (yang pernah dibuat BigDataSeeder)
        $bdDeleted = DB::table('riwayat_pengisian')->where('lokasi', 'LIKE', '%[BD-DUMMY]%')->delete();
        if ($bdDeleted > 0) {
            $this->command->info("   ↻ Bersihkan {$bdDeleted} log [BD-DUMMY] lama.");
        }

        // 3. Generate stasiun dummy
        $startNumber = (int) (StasiunPengisian::max('id') ?? 0);
        $statuses    = ['Active','Active','Active','Active','Maintenance','Inactive']; // 4:1:1
        $stationsCreated = [];

        $totalCities = count($this->cities);
        $cabangPerKota = (int) ceil($this->targetStations / $totalCities);

        $counter = 0;
        for ($cabang = 1; $cabang <= $cabangPerKota; $cabang++) {
            foreach ($this->cities as [$kota, $lat, $lng]) {
                if ($counter >= $this->targetStations) break 2;

                // Variasi koordinat ±0.025° (≈ ±2.5 km) biar tersebar dalam kota
                $latVar = $lat + (mt_rand(-250, 250) / 10000);
                $lngVar = $lng + (mt_rand(-250, 250) / 10000);

                $kode  = 'EV-' . str_pad($startNumber + $counter + 1, 3, '0', STR_PAD_LEFT);
                $area  = $cabang === 1 ? $kota : "{$kota} - Cabang {$cabang}";

                $stationsCreated[] = [
                    'nama_stasiun' => $kode,
                    'lokasi'       => "{$area} [DUMMY]",
                    'status'       => $statuses[array_rand($statuses)],
                    'latitude'     => $latVar,
                    'longitude'    => $lngVar,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ];

                $counter++;
            }
        }

        // Insert batch stasiun
        foreach (array_chunk($stationsCreated, 100) as $chunk) {
            DB::table('stasiun_pengisian')->insert($chunk);
        }
        $this->command->info("   ✓ {$counter} stasiun dummy ditambahkan.");

        // 4. Ambil semua stasiun (lama + baru) untuk distribusi log
        $allStations = StasiunPengisian::all(['id','lokasi']);
        $stationCount = $allStations->count();
        $this->command->info("   ℹ Total stasiun di DB sekarang: {$stationCount}");

        // 5. Generate log energi proporsional, distribusi merata
        $startDate = Carbon::now()->subYear()->startOfDay();
        $endDate   = Carbon::now()->subDay()->endOfDay();
        $totalDays = (int) $startDate->diffInDays($endDate);

        $logsPerStation = (int) floor($this->targetLogs / $stationCount);
        $extraLogs      = $this->targetLogs - ($logsPerStation * $stationCount);

        $batch     = [];
        $batchSize = 1000;
        $totalLogs = 0;
        $progressMilestone = 5000;

        foreach ($allStations as $stIdx => $st) {
            // Distribusikan extra logs ke beberapa stasiun random
            $logCount = $logsPerStation + ($stIdx < $extraLogs ? 1 : 0);

            for ($i = 0; $i < $logCount; $i++) {
                // Waktu mulai random dalam 1 tahun terakhir
                $randomDay  = mt_rand(0, $totalDays);
                $hour       = mt_rand(0, 23);
                $minute     = mt_rand(0, 59);
                $waktuMulai = (clone $startDate)->addDays($randomDay)->setTime($hour, $minute);

                // Durasi 30-180 menit
                $durasi       = mt_rand(30, 180);
                $waktuSelesai = (clone $waktuMulai)->addMinutes($durasi);

                // Energi: rata-rata 22-30 kWh/jam
                $kwhPerJam = mt_rand(2000, 3500) / 100;
                $energiKwh = round(($durasi / 60) * $kwhPerJam, 2);

                // 3% data anomali (untuk demo Anomaly Detection)
                if (mt_rand(1, 100) <= 3) {
                    $energiKwh = mt_rand(0,1)
                        ? round(mt_rand(18000, 26000) / 100, 2)  // anomali tinggi
                        : round(mt_rand(50, 300) / 100, 2);      // anomali rendah
                }

                $batch[] = [
                    'stasiun_pengisian_id' => $st->id,
                    'lokasi'               => $st->lokasi,
                    'energi_kwh'           => $energiKwh,
                    'waktu_mulai'          => $waktuMulai,
                    'waktu_selesai'        => $waktuSelesai,
                    'created_at'           => $waktuMulai,
                    'updated_at'           => $waktuSelesai,
                ];

                if (count($batch) >= $batchSize) {
                    DB::table('riwayat_pengisian')->insert($batch);
                    $totalLogs += count($batch);
                    $batch = [];

                    if ($totalLogs >= $progressMilestone) {
                        $this->command->info("   ↳ {$totalLogs} log selesai...");
                        $progressMilestone += 5000;
                    }
                }
            }
        }
        if (!empty($batch)) {
            DB::table('riwayat_pengisian')->insert($batch);
            $totalLogs += count($batch);
        }

        $this->command->info("   ✓ {$totalLogs} log energi disebar merata ke semua stasiun.");
        $this->command->info('');
        $this->command->info('✅ SELESAI!');
        $this->command->info('   📊 Total stasiun  : ' . StasiunPengisian::count());
        $this->command->info('   📊 Total log      : ' . DB::table('riwayat_pengisian')->count());
        $this->command->info('   📊 Avg log/stasiun: ' . number_format(DB::table('riwayat_pengisian')->count() / max(1, StasiunPengisian::count()), 1));
    }
}
