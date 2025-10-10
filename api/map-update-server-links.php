<?php
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');
requireLogin();

$data = json_decode(file_get_contents('php://input'), true);
$itemId = $data['item_id'] ?? null;
$ispLink = $data['isp_link'] ?? '';
$mikrotikLink = $data['mikrotik_link'] ?? '';
$oltLink = $data['olt_link'] ?? '';
$ponOutputPower = $data['pon_output_power'] ?? 2;

if (!$itemId) {
    jsonResponse(['success' => false, 'message' => 'Item ID required']);
}

$conn = getDBConnection();

// Get current properties
$stmt = $conn->prepare("SELECT properties FROM map_items WHERE id = ? AND item_type = 'server'");
$stmt->bind_param("i", $itemId);
$stmt->execute();
$resultSet = $stmt->get_result();
$result = $resultSet->fetch_assoc();

if (!$result) {
    jsonResponse(['success' => false, 'message' => 'Server not found']);
}

$properties = $result['properties'] ? json_decode($result['properties'], true) : [];
$properties['isp_link'] = $ispLink;
$properties['mikrotik_link'] = $mikrotikLink;
$properties['olt_link'] = $oltLink;
$properties['pon_output_power'] = $ponOutputPower;

$propertiesJson = json_encode($properties);

$stmtUpdate = $conn->prepare("UPDATE map_items SET properties = ? WHERE id = ?");
$stmtUpdate->bind_param("si", $propertiesJson, $itemId);
$stmtUpdate->execute();

jsonResponse(['success' => true, 'message' => 'Server links updated successfully']);
