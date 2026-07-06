<?php

namespace App\Console\Commands;

use App\Models\ApiConnection;
use App\Models\ApiSynclog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Arr;

class SyncApiConnections extends Command
{
    protected $signature   = 'api:sync';
    protected $description = 'Sync semua koneksi API aktif';

    public function handle(): void
    {
        $this->info('[' . now()->format('Y-m-d H:i:s') . '] Memulai sync...');

        $connections = ApiConnection::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('last_sync_at')
                  ->orWhereRaw('TIMESTAMPDIFF(SECOND, last_sync_at, NOW()) >= sync_interval');
            })
            ->get();

        if ($connections->isEmpty()) {
            $this->info('Tidak ada koneksi yang perlu di-sync.');
            return;
        }

        $this->info("Ditemukan {$connections->count()} koneksi.");

        foreach ($connections as $connection) {
            $this->info("Syncing: {$connection->nama_koneksi}...");
            $result = $this->performSync($connection);

            if ($result['success']) {
                $this->info("  BERHASIL - kWh: " . ($result['data']['kwh'] ?? 'N/A'));
            } else {
                $this->error("  GAGAL - " . $result['message']);
            }
        }

        $this->info('Sync selesai!');
    }

    private function performSync(ApiConnection $connection): array
    {
        $startTime = microtime(true);
        $logData   = [
            'api_connection_id' => $connection->id,
            'status'            => 'failed',
        ];

        try {
            $headers = $connection->api_header_array;
            $http    = Http::timeout(15)->withHeaders($headers);

            if ($connection->api_username && $connection->api_password) {
                $http = $http->withBasicAuth(
                    $connection->api_username,
                    $connection->api_password
                );
            }

            $response = $connection->api_method === 'GET'
                ? $http->get($connection->api_url)
                : $http->post($connection->api_url);

            $responseTime             = round((microtime(true) - $startTime) * 1000);
            $logData['response_time'] = $responseTime;
            $logData['http_code']     = $response->status();

            if ($response->successful()) {
                $data                     = $response->json();
                $logData['response_data'] = json_encode($data);

                // Cek apakah API response success
                if (isset($data['success']) && $data['success'] === false) {
                    $errorMsg                 = $data['message'] ?? 'API returned error';
                    $logData['error_message'] = $errorMsg;
                    ApiSynclog::create($logData);

                    $connection->update([
                        'last_sync_at'      => now(),
                        'last_sync_status'  => 'failed',
                        'last_sync_message' => $errorMsg,
                        'last_response_time'=> $responseTime,
                        'last_http_code'    => $response->status(),
                    ]);

                    return ['success' => false, 'message' => $errorMsg];
                }

                $kwhValue     = $this->extractValue($data, $connection->response_path_kwh);
                $voltageValue = $connection->response_path_voltage
                    ? $this->extractValue($data, $connection->response_path_voltage)
                    : null;
                $currentValue = $connection->response_path_current
                    ? $this->extractValue($data, $connection->response_path_current)
                    : null;
                $powerValue   = $connection->response_path_power
                    ? $this->extractValue($data, $connection->response_path_power)
                    : null;

                $logData['status']        = 'success';
                $logData['kwh_value']     = is_numeric($kwhValue) ? $kwhValue : null;
                $logData['voltage_value'] = is_numeric($voltageValue) ? $voltageValue : null;
                $logData['current_value'] = is_numeric($currentValue) ? $currentValue : null;
                $logData['power_value']   = is_numeric($powerValue) ? $powerValue : null;

                $connection->update([
                    'last_sync_at'       => now(),
                    'last_sync_status'   => 'success',
                    'last_sync_message'  => 'Sync berhasil',
                    'last_response_time' => $responseTime,
                    'last_http_code'     => $response->status(),
                ]);

                ApiSynclog::create($logData);

                return [
                    'success' => true,
                    'message' => 'Sync berhasil',
                    'data'    => [
                        'kwh'     => $kwhValue,
                        'voltage' => $voltageValue,
                        'current' => $currentValue,
                        'power'   => $powerValue,
                    ],
                ];
            }

            $errorMsg                 = 'HTTP Error: ' . $response->status();
            $logData['error_message'] = $errorMsg;
            ApiSynclog::create($logData);

            $connection->update([
                'last_sync_at'       => now(),
                'last_sync_status'   => 'failed',
                'last_sync_message'  => $errorMsg,
                'last_response_time' => $responseTime,
                'last_http_code'     => $response->status(),
            ]);

            return ['success' => false, 'message' => $errorMsg];

        } catch (\Exception $e) {
            $logData['error_message'] = $e->getMessage();
            ApiSynclog::create($logData);

            $connection->update([
                'last_sync_at'      => now(),
                'last_sync_status'  => 'failed',
                'last_sync_message' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    private function extractValue($data, $path)
    {
        return Arr::get($data, str_replace('->', '.', $path ?? ''));
    }
}