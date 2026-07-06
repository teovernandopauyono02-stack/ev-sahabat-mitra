<?php

namespace App\Services;

use App\Models\ApiDirectory;
use App\Models\ApiSyncData;
use App\Models\UserApiConnection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;

class ApiDiscoveryService
{
    /**
     * Cari API berdasarkan nama perusahaan
     */
    public function searchApi($keyword)
    {
        try {
            $results = ApiDirectory::active()
                ->search($keyword)
                ->with('latestSync')
                ->get();
            return ['success' => true, 'data' => $results];
        } catch (Exception $e) {
            Log::error('ApiDiscovery searchApi Error: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Test koneksi ke API
     */
    public function testApiConnection($apiDirectoryId)
    {
        try {
            $api = ApiDirectory::findOrFail($apiDirectoryId);
            $headers = [];
            if ($api->api_key) {
                $headers['Authorization'] = 'Bearer ' . $api->api_key;
            }
            $response = Http::withHeaders($headers)->timeout(10)->{strtolower($api->metode)}($api->api_url);
            return ['success' => $response->successful(), 'status' => $response->status()];
        } catch (Exception $e) {
            Log::error('ApiDiscovery testConnection Error: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Fetch data dari API dan simpan
     */
    public function fetchAndSaveData($apiDirectoryId)
    {
        try {
            $api = ApiDirectory::findOrFail($apiDirectoryId);
            $headers = [];
            if ($api->api_key) {
                $headers['Authorization'] = 'Bearer ' . $api->api_key;
            }
            $response = Http::withHeaders($headers)->timeout(30)->{strtolower($api->metode)}($api->api_url);

            if ($response->successful()) {
                $rawData = $response->json();
                ApiSyncData::create([
                    'api_directory_id' => $apiDirectoryId,
                    'entity_type'      => 'data',
                    'raw_data'         => $rawData,
                    'sync_time'        => now(),
                    'sync_status'      => 'success',
                ]);
                $api->update(['last_sync' => now(), 'total_sync' => $api->total_sync + 1]);
                return ['success' => true, 'data' => $rawData];
            }

            ApiSyncData::create([
                'api_directory_id' => $apiDirectoryId,
                'entity_type'      => 'unknown',
                'raw_data'         => [],
                'sync_time'        => now(),
                'sync_status'      => 'failed',
                'sync_message'     => 'HTTP ' . $response->status(),
            ]);
            return ['success' => false, 'message' => 'HTTP ' . $response->status()];
        } catch (Exception $e) {
            Log::error('ApiDiscovery fetchData Error: ' . $e->getMessage());
            ApiSyncData::create([
                'api_directory_id' => $apiDirectoryId,
                'entity_type'      => 'unknown',
                'raw_data'         => [],
                'sync_time'        => now(),
                'sync_status'      => 'failed',
                'sync_message'     => $e->getMessage(),
            ]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Connect user ke API
     */
    public function connectUserToApi($userId, $apiDirectoryId)
    {
        try {
            DB::beginTransaction();
            UserApiConnection::where('user_id', $userId)->update(['is_active' => false]);
            $connection = UserApiConnection::updateOrCreate(
                ['user_id' => $userId, 'api_directory_id' => $apiDirectoryId],
                ['is_active' => true, 'connected_at' => now()]
            );
            $fetchResult = $this->fetchAndSaveData($apiDirectoryId);
            DB::commit();
            return ['success' => true, 'connection' => $connection, 'fetch' => $fetchResult];
        } catch (Exception $e) {
            DB::rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Disconnect user dari API
     */
    public function disconnectUserFromApi($userId, $apiDirectoryId)
    {
        try {
            UserApiConnection::where('user_id', $userId)
                ->where('api_directory_id', $apiDirectoryId)
                ->update(['is_active' => false]);
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Ambil koneksi aktif user
     */
    public function getActiveConnection($userId)
    {
        return UserApiConnection::where('user_id', $userId)
            ->where('is_active', true)
            ->with(['apiDirectory', 'apiDirectory.latestSync'])
            ->first();
    }
}
