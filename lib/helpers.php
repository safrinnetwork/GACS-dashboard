<?php
/**
 * Helper Functions for GACS Dashboard
 */

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Redirect helper
function redirect($url) {
    header("Location: " . $url);
    exit;
}

// Check login and redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        redirect('/login.php');
    }
}

// Sanitize input
function clean($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// JSON response helper
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Get configuration value
function getConfig($key) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT config_value FROM configurations WHERE config_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        return $row['config_value'];
    }

    return null;
}

// Set configuration value
function setConfig($key, $value) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("INSERT INTO configurations (config_key, config_value) VALUES (?, ?)
                           ON DUPLICATE KEY UPDATE config_value = ?");
    $stmt->bind_param("sss", $key, $value, $value);
    return $stmt->execute();
}

// Check if GenieACS is configured
function isGenieACSConfigured() {
    $conn = getDBConnection();
    $result = $conn->query("SELECT COUNT(*) as count FROM genieacs_credentials WHERE is_connected = 1");
    $row = $result->fetch_assoc();
    return $row['count'] > 0;
}

// Format timestamp
function formatTime($timestamp) {
    return date('Y-m-d H:i:s', strtotime($timestamp));
}

// Calculate time ago
function timeAgo($timestamp) {
    $time = strtotime($timestamp);
    $diff = time() - $time;

    if ($diff < 60) {
        return $diff . " detik lalu";
    } elseif ($diff < 3600) {
        return floor($diff / 60) . " menit lalu";
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . " jam lalu";
    } else {
        return floor($diff / 86400) . " hari lalu";
    }
}

// Format bytes to human readable
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];

    for ($i = 0; $bytes > 1024; $i++) {
        $bytes /= 1024;
    }

    return round($bytes, $precision) . ' ' . $units[$i];
}

// Generate CSRF token
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
