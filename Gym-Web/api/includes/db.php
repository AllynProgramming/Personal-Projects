<?php
// includes/db.php
// Database connection file with security settings

$servername = getenv('DB_HOST') ?: '';
$username = getenv('DB_USERNAME') ?: '';
$password = getenv('DB_PASSWORD') ?: '';
$database = getenv('DB_NAME') ?: '';
$port = getenv('DB_PORT') ?: '';

// Create connection
$conn = new mysqli($servername, $username, $password, $database, $port);

// Check connection
if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]));
}

// Set charset to UTF-8 (prevent SQL injection via character encoding)
$conn->set_charset('utf8mb4');

// Set timezone
date_default_timezone_set('UTC');
?>
