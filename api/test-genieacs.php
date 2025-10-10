<?php
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');
requireLogin();

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['host']) || empty($data['port'])) {
    jsonResponse(['success' => false, 'message' => 'Host dan Port harus diisi']);
}

$host = $data['host'];
$port = $data['port'];
$username = $data['username'] ?? '';
$password = $data['password'] ?? '';

// Test connection to GenieACS
$url = "http://{$host}:{$port}/devices?limit=1";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

if (!empty($username)) {
    curl_setopt($ch, CURLOPT_USERPWD, "{$username}:{$password}");
}

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    jsonResponse(['success' => false, 'message' => "Connection error: {$error}"]);
}

if ($httpCode !== 200) {
    jsonResponse(['success' => false, 'message' => "HTTP Error {$httpCode}"]);
}

// Save credentials if test successful
$conn = getDBConnection();
$stmt = $conn->prepare("INSERT INTO genieacs_credentials (host, port, username, password, is_connected) VALUES (?, ?, ?, ?, 1)
                       ON DUPLICATE KEY UPDATE host = ?, port = ?, username = ?, password = ?, is_connected = 1, updated_at = NOW()");
$stmt->bind_param("sissisis", $host, $port, $username, $password, $host, $port, $username, $password);
$stmt->execute();

jsonResponse(['success' => true, 'message' => 'Connected to GenieACS successfully!']);
