<?php
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');
requireLogin();

use App\MikroTikAPI;
use App\GenieACS;

$conn = getDBConnection();

// PERFORMANCE OPTIMIZATION: Batch load all data instead of N+1 queries
// Get all items
$result = $conn->query("SELECT * FROM map_items ORDER BY id ASC");
$items = [];
$itemIds = [];

while ($row = $result->fetch_assoc()) {
    $row['properties'] = json_decode($row['properties'], true);
    $row['config'] = null; // Will be populated later
    $items[$row['id']] = $row;
    $itemIds[] = $row['id'];
}

if (empty($itemIds)) {
    jsonResponse(['success' => true, 'items' => []]);
}

// PERFORMANCE OPTIMIZATION: Batch load all configs in one query per type
$configTypes = ['server_pon_ports', 'olt_config', 'odc_config', 'odp_config', 'onu_config'];
$allConfigs = [];

foreach ($configTypes as $configType) {
    $placeholders = str_repeat('?,', count($itemIds) - 1) . '?';
    $stmt = $conn->prepare("SELECT * FROM $configType WHERE map_item_id IN ($placeholders)");
    $stmt->bind_param(str_repeat('i', count($itemIds)), ...$itemIds);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($config = $result->fetch_assoc()) {
        $allConfigs[$configType][$config['map_item_id']] = $config;
    }
}

// PERFORMANCE OPTIMIZATION: Process configs efficiently
foreach ($items as &$item) {
    $itemId = $item['id'];
    $itemType = $item['item_type'];
    
    switch ($itemType) {
        case 'server':
            if (isset($allConfigs['server_pon_ports'][$itemId])) {
                $pon_ports = [];
                foreach ($allConfigs['server_pon_ports'][$itemId] as $port) {
                    $pon_ports[$port['port_number']] = $port['output_power'];
                }
                $item['config'] = ['pon_ports' => $pon_ports];
            }
            break;
            
        case 'olt':
            $item['config'] = $allConfigs['olt_config'][$itemId] ?? null;
            break;
            
        case 'odc':
            $item['config'] = $allConfigs['odc_config'][$itemId] ?? null;
            break;
            
        case 'odp':
            $config = $allConfigs['odp_config'][$itemId] ?? null;
            if ($config && $config['port_rx_power']) {
                $config['port_rx_power'] = json_decode($config['port_rx_power'], true);
            }
            $item['config'] = $config;
            break;
            
        case 'onu':
            $item['config'] = $allConfigs['onu_config'][$itemId] ?? null;
            break;
    }
}

// PERFORMANCE OPTIMIZATION: Cache GenieACS credentials
static $genieacsCredentials = null;
if ($genieacsCredentials === null) {
    $result = $conn->query("SELECT * FROM genieacs_credentials WHERE is_connected = 1 ORDER BY id DESC LIMIT 1");
    $genieacsCredentials = $result->fetch_assoc();
}

// PERFORMANCE OPTIMIZATION: Cache MikroTik credentials and netwatch status
$netwatchStatus = [];
try {
    $mikrotikResult = $conn->query("SELECT * FROM mikrotik_credentials WHERE is_connected = 1 ORDER BY id DESC LIMIT 1");
    $mikrotikCreds = $mikrotikResult->fetch_assoc();

    if ($mikrotikCreds) {
        $mikrotik = new MikroTikAPI(
            $mikrotikCreds['host'],
            $mikrotikCreds['username'],
            $mikrotikCreds['password'],
            $mikrotikCreds['port']
        );

        if ($mikrotik->connect()) {
            $netwatch = $mikrotik->getNetwatch();
            foreach ($netwatch as $nw) {
                $host = $nw['host'] ?? '';
                $status = isset($nw['status']) && $nw['status'] === 'up' ? 'online' : 'offline';
                $netwatchStatus[$host] = $status;
            }
        }
    }
} catch (Exception $e) {
    // Silently fail if MikroTik not available
}

// PERFORMANCE OPTIMIZATION: Batch status updates
$statusUpdates = [];
$itemsArray = array_values($items);

foreach ($itemsArray as &$item) {
    $oldStatus = $item['status'];
    $newStatus = calculateItemStatus($item, $itemsArray, $netwatchStatus);
    $item['status'] = $newStatus;

    if ($oldStatus !== $newStatus) {
        $statusUpdates[] = [$newStatus, $item['id']];
    }
}

// PERFORMANCE OPTIMIZATION: Batch execute status updates
if (!empty($statusUpdates)) {
    $stmt = $conn->prepare("UPDATE map_items SET status = ? WHERE id = ?");
    foreach ($statusUpdates as $update) {
        $stmt->bind_param("si", $update[0], $update[1]);
        $stmt->execute();
    }
}

// PERFORMANCE OPTIMIZATION: Apply cascade offline effect efficiently
foreach ($itemsArray as &$item) {
    if ($item['item_type'] === 'olt' && $item['status'] === 'offline') {
        cascadeOfflineStatus($item['id'], $itemsArray);
    }
}

jsonResponse(['success' => true, 'items' => $itemsArray]);

/**
 * Calculate item status based on type and dependencies
 */
function calculateItemStatus($item, $allItems, $netwatchStatus) {
    switch ($item['item_type']) {
        case 'server':
            return calculateServerStatus($item, $netwatchStatus);

        case 'olt':
            return calculateOLTStatus($item, $allItems, $netwatchStatus);

        case 'odc':
        case 'odp':
            return calculateODPStatus($item, $allItems);

        case 'onu':
            return calculateONUStatus($item, $allItems);

        default:
            return 'unknown';
    }
}

/**
 * Calculate server status based on netwatch
 */
function calculateServerStatus($item, $netwatchStatus) {
    $host = $item['properties']['host'] ?? '';
    
    if (empty($host)) {
        return 'unknown';
    }

    return $netwatchStatus[$host] ?? 'offline';
}

/**
 * Calculate OLT status based on server and netwatch
 */
function calculateOLTStatus($item, $allItems, $netwatchStatus) {
    // Check if parent server is online
    if ($item['parent_id']) {
        foreach ($allItems as $parentItem) {
            if ($parentItem['id'] == $item['parent_id']) {
                return $parentItem['status'] === 'online' ? 'online' : 'offline';
            }
        }
    }

    return 'offline';
}

/**
 * Calculate ODP/ODC status based on parent
 */
function calculateODPStatus($item, $allItems) {
    if (!$item['parent_id']) {
        return 'unknown';
    }

    foreach ($allItems as $parentItem) {
        if ($parentItem['id'] == $item['parent_id']) {
            return $parentItem['status'] === 'online' ? 'online' : 'offline';
        }
    }

    return 'offline';
}

/**
 * Calculate ONU status based on parent ODP
 */
function calculateONUStatus($item, $allItems) {
    if (!$item['parent_id']) {
        return 'unknown';
    }

    foreach ($allItems as $parentItem) {
        if ($parentItem['id'] == $item['parent_id']) {
            return $parentItem['status'] === 'online' ? 'online' : 'offline';
        }
    }

    return 'offline';
}

/**
 * Cascade offline status to children
 */
function cascadeOfflineStatus($parentId, &$items) {
    foreach ($items as &$item) {
        if ($item['parent_id'] == $parentId) {
            $item['status'] = 'offline';
            // Recursively cascade to grandchildren
            cascadeOfflineStatus($item['id'], $items);
        }
    }
}
