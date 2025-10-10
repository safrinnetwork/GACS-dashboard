<?php
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

requireLogin();

use App\PONCalculator;
use App\GenieACS;

function getGenieACSConnection($conn) {
    $result = $conn->query("SELECT * FROM genieacs_credentials WHERE is_connected = 1 ORDER BY id DESC LIMIT 1");
    $creds = $result->fetch_assoc();
    if ($creds) return new GenieACS($creds['host'], $creds['port']);
    return null;
}

$data = json_decode(file_get_contents('php://input'), true);

$itemType = $data['item_type'] ?? '';
$parentId = $data['parent_id'] ?? null;
$name = $data['name'] ?? '';
$latitude = $data['latitude'] ?? 0;
$longitude = $data['longitude'] ?? 0;
$genieacsDeviceId = $data['genieacs_device_id'] ?? null;

if (empty($itemType) || empty($name)) {
    jsonResponse(['success' => false, 'message' => 'Item type dan name harus diisi']);
}

// Validate ONU: check if genieacs_device_id is unique
if ($itemType === 'onu' && !empty($genieacsDeviceId)) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT id FROM map_items WHERE genieacs_device_id = ? AND item_type = 'onu'");
    $stmt->bind_param("s", $genieacsDeviceId);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        jsonResponse(['success' => false, 'message' => 'GenieACS device sudah digunakan oleh ONU lain']);
    }
}

// For ONU, get parent_id from parent_odp field
if ($itemType === 'onu' && isset($data['parent_odp'])) {
    $parentId = $data['parent_odp'];
}

// Store additional properties
$properties = [];
if ($itemType === 'server') {
    $properties['isp_link'] = $data['isp_link'] ?? '';
    $properties['mikrotik_device_id'] = $data['mikrotik_device_id'] ?? '';
    $properties['olt_link'] = $data['olt_link'] ?? '';
    $properties['pon_output_power'] = $data['pon_output_power'] ?? 2;
}
if ($itemType === 'olt' && isset($data['olt_link'])) {
    $properties['olt_link'] = $data['olt_link'];
}

$conn = getDBConnection();
$stmt = $conn->prepare("INSERT INTO map_items (item_type, parent_id, name, latitude, longitude, genieacs_device_id, properties, status)
                       VALUES (?, ?, ?, ?, ?, ?, ?, 'unknown')");
$propertiesJson = json_encode($properties);
$stmt->bind_param("sisddss", $itemType, $parentId, $name, $latitude, $longitude, $genieacsDeviceId, $propertiesJson);

if (!$stmt->execute()) {
    jsonResponse(['success' => false, 'message' => 'Gagal menambahkan item: ' . $conn->error]);
}

$itemId = $conn->insert_id;

// Handle specific item type configurations
try {
    switch ($itemType) {
        case 'server':
            // Save PON ports configuration
            $ponPortCount = $data['pon_port_count'] ?? 4;

            // Insert PON ports
            for ($i = 1; $i <= $ponPortCount; $i++) {
                $ponPower = $data["pon_port_{$i}_power"] ?? 2.00;
                $stmt = $conn->prepare("INSERT INTO server_pon_ports (map_item_id, port_number, output_power) VALUES (?, ?, ?)");
                $stmt->bind_param("iid", $itemId, $i, $ponPower);
                $stmt->execute();
            }

            // Create ODC as a real database item (for ODP parent relation) but mark as hidden
            if (!empty($data['odc_name']) && !empty($data['odc_pon_port'])) {
                $odcName = $data['odc_name'];
                $odcPonPort = $data['odc_pon_port'];
                $odcPortCount = $data['odc_port_count'] ?? 4;

                // Get PON power from the selected server PON port
                $stmt = $conn->prepare("SELECT output_power FROM server_pon_ports WHERE map_item_id = ? AND port_number = ?");
                $stmt->bind_param("ii", $itemId, $odcPonPort);
                $stmt->execute();
                $result = $stmt->get_result();
                $ponPowerRow = $result->fetch_assoc();
                $ponPower = $ponPowerRow['output_power'] ?? 2.0;

                // Calculate ODC power = PON power
                $attenuationOltToOdc = 5.8;
                $odcCalculatedPower = $ponPower - $attenuationOltToOdc;

                // Insert ODC as real item (same coordinates as Server for simplicity)
                $odcProperties = json_encode(['hidden_marker' => true]); // Mark as hidden from map
                $stmt = $conn->prepare("INSERT INTO map_items (item_type, parent_id, name, latitude, longitude, properties, status) VALUES ('odc', ?, ?, ?, ?, ?, 'unknown')");
                $stmt->bind_param("isdds", $itemId, $odcName, $latitude, $longitude, $odcProperties);
                $stmt->execute();
                $odcItemId = $conn->insert_id;

                // Insert ODC config
                $stmt = $conn->prepare("INSERT INTO odc_config (map_item_id, olt_pon_port_id, server_pon_port, port_count, parent_attenuation_db, calculated_power) VALUES (?, NULL, ?, ?, ?, ?)");
                $stmt->bind_param("iiidd", $odcItemId, $odcPonPort, $odcPortCount, $attenuationOltToOdc, $odcCalculatedPower);
                $stmt->execute();

                // Also store in server properties for chain visualization reference
                $properties['odc_id'] = $odcItemId;
                $properties['odc_name'] = $odcName;
                $properties['odc_pon_port'] = $odcPonPort;

                // Update map_items with new properties
                $propertiesJson = json_encode($properties);
                $stmt = $conn->prepare("UPDATE map_items SET properties = ? WHERE id = ?");
                $stmt->bind_param("si", $propertiesJson, $itemId);
                $stmt->execute();
            }
            break;

        case 'olt':
            $ponCount = $data['pon_count'] ?? 1;
            $oltLink = $data['olt_link'] ?? null;

            $stmt = $conn->prepare("INSERT INTO olt_config (map_item_id, output_power, pon_count, attenuation_db, olt_link) VALUES (?, 0, ?, 0, ?)");
            $stmt->bind_param("iis", $itemId, $ponCount, $oltLink);
            $stmt->execute();

            // Insert PON ports
            for ($i = 1; $i <= $ponCount; $i++) {
                $ponPower = $data["pon_power_$i"] ?? 9;
                $stmt = $conn->prepare("INSERT INTO olt_pon_ports (olt_item_id, pon_number, output_power) VALUES (?, ?, ?)");
                $stmt->bind_param("iid", $itemId, $i, $ponPower);
                $stmt->execute();
            }
            break;

        case 'odc':
            $portCount = $data['port_count'] ?? 4;
            $oltPonPortId = $data['olt_pon_port_id'] ?? null;

            $ponPower = 0;
            $serverPonPort = null;
            $serverId = null;
            if ($oltPonPortId) {
                // Get PON power and port number from server_pon_ports (not olt_pon_ports)
                $stmt = $conn->prepare("SELECT output_power, port_number, map_item_id FROM server_pon_ports WHERE id = ?");
                $stmt->bind_param("i", $oltPonPortId);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    $ponPower = $row['output_power'];
                    $serverPonPort = $row['port_number'];
                    $serverId = $row['map_item_id'];

                    // Update parent_id to the Server item
                    $updateStmt = $conn->prepare("UPDATE map_items SET parent_id = ? WHERE id = ?");
                    $updateStmt->bind_param("ii", $serverId, $itemId);
                    $updateStmt->execute();
                }
            }

            // Calculate ODC power = PON power - attenuation (5.8 dB)
            $attenuationOltToOdc = 5.8;
            $calculatedPower = $ponPower - $attenuationOltToOdc;

            $stmt = $conn->prepare("INSERT INTO odc_config (map_item_id, olt_pon_port_id, server_pon_port, port_count, parent_attenuation_db, calculated_power) VALUES (?, NULL, ?, ?, ?, ?)");
            $stmt->bind_param("iiidd", $itemId, $serverPonPort, $portCount, $attenuationOltToOdc, $calculatedPower);
            $stmt->execute();
            break;

        case 'odp':
            $portCount = $data['port_count'] ?? 8;
            $odcPort = $data['odc_port'] ?? null;
            $parentOdpPort = $data['parent_odp_port'] ?? null;
            $useSplitter = $data['use_splitter'] ?? 0;
            $splitterRatio = $data['splitter_ratio'] ?? '1:8';

            // Determine parent type (ODC or ODP)
            $inputPower = 0; // Power before splitter (untuk cascading)
            if ($parentId) {
                // Check if parent is ODC
                $stmt = $conn->prepare("SELECT item_type FROM map_items WHERE id = ?");
                $stmt->bind_param("i", $parentId);
                $stmt->execute();
                $result = $stmt->get_result();
                $parentItem = $result->fetch_assoc();

                if ($parentItem['item_type'] === 'odc') {
                    // Validate ODC port not already used
                    if ($odcPort) {
                        $stmt = $conn->prepare("
                            SELECT COUNT(*) as count FROM odp_config
                            WHERE map_item_id IN (SELECT id FROM map_items WHERE parent_id = ?)
                            AND odc_port = ?
                        ");
                        $stmt->bind_param("ii", $parentId, $odcPort);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $row = $result->fetch_assoc();
                        if ($row['count'] > 0) {
                            $conn->query("DELETE FROM map_items WHERE id = $itemId");
                            jsonResponse(['success' => false, 'message' => "ODC Port $odcPort sudah digunakan oleh ODP lain"]);
                        }
                    }

                    // Parent is ODC - use calculated_power from ODC
                    $stmt = $conn->prepare("SELECT calculated_power FROM odc_config WHERE map_item_id = ?");
                    $stmt->bind_param("i", $parentId);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($row = $result->fetch_assoc()) {
                        $inputPower = $row['calculated_power'];
                    }
                } elseif ($parentItem['item_type'] === 'odp') {
                    // Validate ODP port not already used
                    if ($parentOdpPort) {
                        $stmt = $conn->prepare("
                            SELECT COUNT(*) as count FROM odp_config
                            WHERE map_item_id IN (SELECT id FROM map_items WHERE parent_id = ?)
                            AND parent_odp_port = ?
                        ");
                        $stmt->bind_param("is", $parentId, $parentOdpPort);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $row = $result->fetch_assoc();
                        if ($row['count'] > 0) {
                            $conn->query("DELETE FROM map_items WHERE id = $itemId");
                            jsonResponse(['success' => false, 'message' => "Parent ODP port $parentOdpPort sudah digunakan oleh ODP lain"]);
                        }
                    }

                    // Parent is ODP with custom ratio
                    // IMPORTANT: Use input_power (before splitter), NOT calculated_power (after splitter)
                    $stmt = $conn->prepare("SELECT input_power, splitter_ratio FROM odp_config WHERE map_item_id = ?");
                    $stmt->bind_param("i", $parentId);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($row = $result->fetch_assoc()) {
                        $parentInputPower = $row['input_power']; // Power BEFORE parent's splitter
                        $parentRatio = $row['splitter_ratio'];

                        // Calculate power from selected port (e.g., "80%" from 20:80 ratio)
                        if ($parentOdpPort && $parentRatio) {
                            $calculator = new PONCalculator();
                            $inputPower = $calculator->calculateCustomRatioPort($parentInputPower, $parentRatio, $parentOdpPort);
                        }
                    }
                }
            }

            $calculator = new PONCalculator();

            // Calculate power using standard method (works for both standard and custom ratios)
            // Custom ratio loss values already include internal distribution to ports
            $calculatedPower = $calculator->calculateODPPower($inputPower, $useSplitter ? $splitterRatio : null);

            // Store both input_power (before splitter) and calculated_power (after splitter)
            $stmt = $conn->prepare("INSERT INTO odp_config (map_item_id, port_count, odc_port, input_power, parent_odp_port, use_splitter, splitter_ratio, calculated_power) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iiidsisd", $itemId, $portCount, $odcPort, $inputPower, $parentOdpPort, $useSplitter, $splitterRatio, $calculatedPower);
            $stmt->execute();
            break;

        case 'onu':
            $odpPort = $data['odp_port'] ?? null;
            $customerName = $data['customer_name'] ?? '';

            if (empty($odpPort)) {
                $conn->query("DELETE FROM map_items WHERE id = $itemId");
                jsonResponse(['success' => false, 'message' => 'ODP Port harus dipilih']);
            }

            // Check if port is already occupied
            $stmt = $conn->prepare("SELECT id FROM onu_config WHERE map_item_id IN (SELECT id FROM map_items WHERE parent_id = ?) AND odp_port = ?");
            $stmt->bind_param("ii", $parentId, $odpPort);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $conn->query("DELETE FROM map_items WHERE id = $itemId");
                jsonResponse(['success' => false, 'message' => 'Port ODP sudah terpakai']);
            }

            $stmt = $conn->prepare("INSERT INTO onu_config (map_item_id, odp_port, customer_name, genieacs_device_id) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $itemId, $odpPort, $customerName, $genieacsDeviceId);
            $stmt->execute();

            // Update ODP port RX power from GenieACS
            if (!empty($genieacsDeviceId)) {
                $genieacs = getGenieACSConnection($conn);
                if ($genieacs) {
                    try {
                        $deviceData = $genieacs->getDevice($genieacsDeviceId);
                        $parsed = $genieacs->parseDeviceData($deviceData);
                        $rxPower = $parsed['VirtualParameters.RXPower'] ?? $parsed['InternetGatewayDevice.WANDevice.1.X_CT-COM_EponInterfaceConfig.RXPower'] ?? null;

                        if ($rxPower !== null) {
                            // Convert raw to dBm if needed
                            if ($rxPower > 100) {
                                $rxPower = ($rxPower / 100) - 40;
                            }

                            // Get current port_rx_power
                            $stmt = $conn->prepare("SELECT port_rx_power FROM odp_config WHERE map_item_id = ?");
                            $stmt->bind_param("i", $parentId);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $odpConfig = $result->fetch_assoc();

                            $portRxPower = $odpConfig['port_rx_power'] ? json_decode($odpConfig['port_rx_power'], true) : [];
                            $portRxPower[$odpPort] = round($rxPower, 2);

                            // Update ODP config
                            $portRxPowerJson = json_encode($portRxPower);
                            $stmt = $conn->prepare("UPDATE odp_config SET port_rx_power = ? WHERE map_item_id = ?");
                            $stmt->bind_param("si", $portRxPowerJson, $parentId);
                            $stmt->execute();
                        }
                    } catch (Exception $e) {
                        // Silently fail if GenieACS error
                    }
                }
            }
            break;
    }

    jsonResponse(['success' => true, 'message' => 'Item berhasil ditambahkan', 'item_id' => $itemId]);

} catch (Exception $e) {
    // Rollback on error
    $conn->query("DELETE FROM map_items WHERE id = $itemId");
    jsonResponse(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
