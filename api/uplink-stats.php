<?php
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');
requireLogin();

// Check if GenieACS is configured
if (!isGenieACSConfigured()) {
    jsonResponse(['success' => false, 'message' => 'GenieACS belum dikonfigurasi']);
}

// Get GenieACS credentials
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

if (!$devicesResult['success']) {
    jsonResponse(['success' => false, 'message' => 'Gagal mengambil data devices']);
}

// Categorize devices by RX Power signal strength
$excellent = 0; // > -20 dBm
$good = 0;      // -20 to -25 dBm
$fair = 0;      // -25 to -28 dBm
$poor = 0;      // < -28 dBm
$noSignal = 0;  // No data

foreach ($devicesResult['data'] as $device) {
    $rxPower = null;

    // Try multiple paths for RX Power (priority order)
    $paths = [
        'VirtualParameters.RXPower',  // Already in dBm format
        'InternetGatewayDevice.WANDevice.1.X_CT-COM_EponInterfaceConfig.RXPower',  // Raw value
        'InternetGatewayDevice.WANDevice.1.X_CT-COM_WANPONInterfaceConfig.RXPower',
        'Device.Optical.Interface.1.RxPower',
        'InternetGatewayDevice.X_BROADCOM_COM_OpticalInterfacePower'
    ];

    foreach ($paths as $path) {
        $keys = explode('.', $path);
        $value = $device;

        foreach ($keys as $key) {
            if (isset($value[$key])) {
                $value = $value[$key];
            } else {
                $value = null;
                break;
            }
        }

        // Extract value from GenieACS format
        if (is_array($value) && isset($value['_value'])) {
            $rxPower = $value['_value'];
            break;
        } elseif (!is_array($value) && $value !== null) {
            $rxPower = $value;
            break;
        }
    }

    // Categorize by signal strength
    if ($rxPower === null || $rxPower === 'N/A' || $rxPower === '') {
        $noSignal++;
    } else {
        $rxPower = floatval($rxPower);

        // Convert raw value to dBm if needed (raw value > 100 indicates non-dBm format)
        // Formula: dBm = (raw_value / 100) - 40 (typical for EPON devices)
        if ($rxPower > 100) {
            $rxPower = ($rxPower / 100) - 40;
        }

        if ($rxPower > -20) {
            $excellent++;
        } elseif ($rxPower >= -25) {
            $good++;
        } elseif ($rxPower >= -28) {
            $fair++;
        } else {
            $poor++;
        }
    }
}

jsonResponse([
    'success' => true,
    'data' => [
        'excellent' => $excellent,
        'good' => $good,
        'fair' => $fair,
        'poor' => $poor,
        'no_signal' => $noSignal,
        'total' => count($devicesResult['data'])
    ]
]);
