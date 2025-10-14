<?php
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');
requireLogin();

$conn = getDBConnection();

// Get PON ports from Server items that are NOT already connected to ODC
// Exclude ports that have ODC connected (via odc_config.server_pon_port)
$result = $conn->query("
    SELECT pp.id, pp.port_number, pp.output_power, mi.id as server_id, mi.name as server_name
    FROM server_pon_ports pp
    JOIN map_items mi ON pp.map_item_id = mi.id
    LEFT JOIN odc_config oc ON oc.server_pon_port = pp.port_number AND oc.map_item_id IN (
        SELECT id FROM map_items WHERE parent_id = mi.id AND item_type = 'odc'
    )
    WHERE mi.item_type = 'server' AND oc.id IS NULL
    ORDER BY mi.name, pp.port_number
");

$ports = [];
while ($row = $result->fetch_assoc()) {
    $ports[] = [
        'id' => $row['id'],
        'pon_number' => $row['port_number'],
        'output_power' => $row['output_power'],
        'server_id' => $row['server_id'],
        'olt_name' => $row['server_name'] // Keep 'olt_name' for backward compatibility
    ];
}

jsonResponse(['success' => true, 'ports' => $ports]);
