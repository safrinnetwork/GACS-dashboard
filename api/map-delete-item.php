<?php
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');
requireLogin();

$data = json_decode(file_get_contents('php://input'), true);
$itemId = $data['item_id'] ?? null;

if (!$itemId) {
    jsonResponse(['success' => false, 'message' => 'Item ID required']);
}

$conn = getDBConnection();

// Get item type
$stmt = $conn->prepare("SELECT item_type FROM map_items WHERE id = ?");
$stmt->bind_param("i", $itemId);
$stmt->execute();
$resultSet = $stmt->get_result();
$result = $resultSet->fetch_assoc();

if (!$result) {
    jsonResponse(['success' => false, 'message' => 'Item not found']);
}

$itemType = $result['item_type'];

// Recursive delete function
function deleteItemRecursive($conn, $itemId, $itemType) {
    // Find and delete all children first
    $stmt = $conn->prepare("SELECT id, item_type FROM map_items WHERE parent_id = ?");
    $stmt->bind_param("i", $itemId);
    $stmt->execute();
    $children = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    foreach ($children as $child) {
        deleteItemRecursive($conn, $child['id'], $child['item_type']);
    }

    // Delete type-specific config using prepared statements
    switch ($itemType) {
        case 'olt':
            $stmtDel = $conn->prepare("DELETE FROM olt_config WHERE map_item_id = ?");
            $stmtDel->bind_param("i", $itemId);
            $stmtDel->execute();
            break;
        case 'odc':
            $stmtDel = $conn->prepare("DELETE FROM odc_config WHERE map_item_id = ?");
            $stmtDel->bind_param("i", $itemId);
            $stmtDel->execute();
            break;
        case 'odp':
            $stmtDel = $conn->prepare("DELETE FROM odp_config WHERE map_item_id = ?");
            $stmtDel->bind_param("i", $itemId);
            $stmtDel->execute();
            break;
        case 'onu':
            $stmtDel = $conn->prepare("DELETE FROM onu_config WHERE map_item_id = ?");
            $stmtDel->bind_param("i", $itemId);
            $stmtDel->execute();
            break;
    }

    // Delete from map_items using prepared statement
    $stmtDel = $conn->prepare("DELETE FROM map_items WHERE id = ?");
    $stmtDel->bind_param("i", $itemId);
    $stmtDel->execute();
}

// Start deletion
deleteItemRecursive($conn, $itemId, $itemType);

jsonResponse(['success' => true, 'message' => 'Item and all children deleted successfully']);
