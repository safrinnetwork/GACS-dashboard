<?php
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');
requireLogin();

$data = json_decode(file_get_contents('php://input'), true);
$itemId = $data['item_id'] ?? null;
$ispLink = $data['isp_link'] ?? '';
$mikrotikDeviceId = $data['mikrotik_device_id'] ?? '';
$oltLink = $data['olt_link'] ?? '';

if (!$itemId) {
    jsonResponse(['success' => false, 'message' => 'Item ID required']);
}

$conn = getDBConnection();

// Get current properties and config
$stmt = $conn->prepare("SELECT properties, config FROM map_items WHERE id = ? AND item_type = 'server'");
$stmt->bind_param("i", $itemId);
$stmt->execute();
$resultSet = $stmt->get_result();
$result = $resultSet->fetch_assoc();

if (!$result) {
    jsonResponse(['success' => false, 'message' => 'Server not found']);
}

// Update properties
$properties = $result['properties'] ? json_decode($result['properties'], true) : [];
$properties['isp_link'] = $ispLink;
$properties['mikrotik_device_id'] = $mikrotikDeviceId;
$properties['olt_link'] = $oltLink;

// Update config with PON ports power
$config = $result['config'] ? json_decode($result['config'], true) : [];
$ponPorts = [];

// Extract all pon_port_X_power fields from data
foreach ($data as $key => $value) {
    if (preg_match('/^pon_port_(\d+)_power$/', $key, $matches)) {
        $portNumber = (int)$matches[1];
        $ponPorts[$portNumber] = (float)$value;
    }
}

// Save PON ports to config
if (!empty($ponPorts)) {
    $config['pon_ports'] = $ponPorts;
}

$propertiesJson = json_encode($properties);
$configJson = json_encode($config);

$stmtUpdate = $conn->prepare("UPDATE map_items SET properties = ?, config = ? WHERE id = ?");
$stmtUpdate->bind_param("ssi", $propertiesJson, $configJson, $itemId);
$stmtUpdate->execute();

jsonResponse(['success' => true, 'message' => 'Server links updated successfully']);
