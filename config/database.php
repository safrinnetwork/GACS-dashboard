<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'gacs-dev');
define('DB_PASS', 'pA1kl5U8G5Na6ABe99A7');
define('DB_NAME', 'gacs-dev');

// Create database connection
function getDBConnection() {
    static $conn = null;

    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        $conn->set_charset("utf8mb4");
    }

    return $conn;
}
