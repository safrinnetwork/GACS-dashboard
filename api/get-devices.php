<?php
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');
requireLogin();

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

$devicesResult = $genieacs->getDevices();

if ($devicesResult['success']) {
    $devices = [];

    foreach ($devicesResult['data'] as $device) {
        $parsed = $genieacs->parseDeviceData($device);
        $devices[] = $parsed;
    }

    jsonResponse(['success' => true, 'devices' => $devices]);
} else {
    jsonResponse(['success' => false, 'message' => 'Gagal mengambil data devices']);
}
