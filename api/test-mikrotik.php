<?php
require_once __DIR__ . '/../config/config.php';

// Set JSON header first
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['host']) || empty($data['username']) || empty($data['password'])) {
    jsonResponse(['success' => false, 'message' => 'Host, Username, dan Password harus diisi']);
    exit;
}

$host = $data['host'];
$username = $data['username'];
$password = $data['password'];
$port = $data['port'] ?? 8728;

// Test connection to MikroTik
try {
    $config = new \RouterOS\Config();
    $config->set('host', $host);
    $config->set('user', $username);
    $config->set('pass', $password);
    $config->set('port', (int)$port);

    $client = new \RouterOS\Client($config);

    // Try a simple query to verify connection
    $query = new \RouterOS\Query('/system/identity/print');
    $response = $client->query($query)->read();

    if (empty($response)) {
        jsonResponse(['success' => false, 'message' => 'Connected but no response from router']);
        exit;
    }

    // Save credentials if test successful
    $conn = getDBConnection();
    $stmt = $conn->prepare("INSERT INTO mikrotik_credentials (host, port, username, password, is_connected, last_test) VALUES (?, ?, ?, ?, 1, NOW())
                           ON DUPLICATE KEY UPDATE host = ?, port = ?, username = ?, password = ?, is_connected = 1, last_test = NOW()");
    $stmt->bind_param("sissisis", $host, $port, $username, $password, $host, $port, $username, $password);
    $stmt->execute();

    jsonResponse(['success' => true, 'message' => 'Connected to MikroTik successfully!']);

} catch (\Exception $e) {
    jsonResponse(['success' => false, 'message' => 'Connection failed: ' . $e->getMessage()]);
}
