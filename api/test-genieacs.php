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

// Check if record exists
$result = $conn->query("SELECT id FROM genieacs_credentials LIMIT 1");
$existing = $result->fetch_assoc();

if ($existing) {
    // Update existing record
    $stmt = $conn->prepare("UPDATE genieacs_credentials SET host = ?, port = ?, username = ?, password = ?, is_connected = 1, last_test = NOW(), updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("sissi", $host, $port, $username, $password, $existing['id']);
} else {
    // Insert new record (first time setup)
    $stmt = $conn->prepare("INSERT INTO genieacs_credentials (host, port, username, password, is_connected, last_test) VALUES (?, ?, ?, ?, 1, NOW())");
    $stmt->bind_param("siss", $host, $port, $username, $password);
}

$stmt->execute();

jsonResponse(['success' => true, 'message' => 'Connected to GenieACS successfully!']);
