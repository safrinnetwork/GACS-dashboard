<?php
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');
requireLogin();

// Set time limit for this script to handle large datasets
set_time_limit(120);

if (!isGenieACSConfigured()) {
    jsonResponse(['success' => false, 'message' => 'GenieACS belum dikonfigurasi']);
}

$conn = getDBConnection();
$result = $conn->query("SELECT * FROM genieacs_credentials WHERE is_connected = 1 ORDER BY id DESC LIMIT 1");
$credentials = $result->fetch_assoc();

if (!$credentials) {
    jsonResponse(['success' => false, 'message' => 'GenieACS tidak terhubung']);
}

use App\GenieACS;

$genieacs = new GenieACS(
    $credentials['host'],
    $credentials['port'],
    $credentials['username'],
    $credentials['password']
);

// Get pagination parameters (optional)
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 0; // 0 = no limit (get all)
$skip = isset($_GET['skip']) ? (int)$_GET['skip'] : 0;

try {
    $devicesResult = $genieacs->getDevices([], $limit, $skip);

    if ($devicesResult['success']) {
        $devices = [];

        foreach ($devicesResult['data'] as $device) {
            $parsed = $genieacs->parseDeviceData($device);
            $devices[] = $parsed;
        }

        $response = [
            'success' => true,
            'devices' => $devices,
            'count' => count($devices)
        ];

        // Add pagination info if used
        if ($limit > 0) {
            $response['pagination'] = [
                'limit' => $limit,
                'skip' => $skip,
                'returned' => count($devices)
            ];
        }

        jsonResponse($response);
    } else {
        jsonResponse([
            'success' => false,
            'message' => 'Gagal mengambil data devices',
            'error' => $devicesResult['error'] ?? 'Unknown error'
        ]);
    }
} catch (Exception $e) {
    jsonResponse([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ], 500);
}
