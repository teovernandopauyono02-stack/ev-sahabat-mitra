<?php

// File: api-integration-handler.php
// Handler untuk API Integration - menggunakan Laravel routing

require_once __DIR__ . '/../../vendor/autoload.php';

use Illuminate\Http\Request;

// Bootstrap Laravel application
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Capture request
$request = Request::capture();

// Boot Laravel
$response = $kernel->handle($request);

// Get action parameter
$action = $request->input('action') ?? $request->query('action');

// Response helper
function sendResponse($data, $statusCode = 200) {
    header('Content-Type: application/json');
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

// Route actions
try {
    switch ($action) {
        case 'get_koneksi':
            // Get all API connections
            $url = url('/api-integration/koneksi');
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $apiResponse = curl_exec($ch);
            curl_close($ch);
            echo $apiResponse;
            break;

        case 'get_statistik':
            // Get statistics
            $url = url('/api-integration/statistik');
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $apiResponse = curl_exec($ch);
            curl_close($ch);
            echo $apiResponse;
            break;

        case 'simpan_koneksi':
            // Save connection
            $url = url('/api-integration/simpan');
            $postData = file_get_contents('php://input');
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'X-CSRF-TOKEN: ' . csrf_token()
            ]);
            $apiResponse = curl_exec($ch);
            curl_close($ch);
            echo $apiResponse;
            break;

        case 'hapus_koneksi':
            // Delete connection
            $url = url('/api-integration/hapus');
            $postData = file_get_contents('php://input');
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'X-CSRF-TOKEN: ' . csrf_token()
            ]);
            $apiResponse = curl_exec($ch);
            curl_close($ch);
            echo $apiResponse;
            break;

        case 'test_koneksi':
            // Test connection
            $url = url('/api-integration/test');
            $postData = file_get_contents('php://input');
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'X-CSRF-TOKEN: ' . csrf_token()
            ]);
            $apiResponse = curl_exec($ch);
            curl_close($ch);
            echo $apiResponse;
            break;

        case 'sync_koneksi':
            // Sync single connection
            $id = $request->input('id') ?? $request->query('id');
            $url = url("/api-integration/sync/{$id}");
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'X-CSRF-TOKEN: ' . csrf_token()
            ]);
            $apiResponse = curl_exec($ch);
            curl_close($ch);
            echo $apiResponse;
            break;

        case 'sync_semua':
            // Sync all connections
            $url = url('/api-integration/sync-all');
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'X-CSRF-TOKEN: ' . csrf_token()
            ]);
            $apiResponse = curl_exec($ch);
            curl_close($ch);
            echo $apiResponse;
            break;

        case 'get_data_energi':
            // Get energy data
            $koneksiId = $request->input('koneksi_id') ?? $request->query('koneksi_id');
            $limit = $request->input('limit') ?? $request->query('limit', 100);
            $url = url("/api-integration/data-energi?koneksi_id={$koneksiId}&limit={$limit}");
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $apiResponse = curl_exec($ch);
            curl_close($ch);
            echo $apiResponse;
            break;

        case 'get_log_sync':
            // Get sync logs
            $limit = $request->input('limit') ?? $request->query('limit', 50);
            $url = url("/api-integration/log-sync?limit={$limit}");
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $apiResponse = curl_exec($ch);
            curl_close($ch);
            echo $apiResponse;
            break;

        default:
            sendResponse([
                'success' => false,
                'message' => 'Action tidak valid: ' . ($action ?? 'null')
            ], 400);
    }

} catch (\Exception $e) {
    sendResponse([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ], 500);
}

// Terminate request
$kernel->terminate($request, $response);