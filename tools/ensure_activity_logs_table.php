<?php
require_once __DIR__ . '/../config/env.php';
loadEnvFile(__DIR__ . '/../.env');

$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASSWORD') ?: '';
$dbName = getenv('DB_NAME') ?: 'bank_sampah_palembang';

$mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($mysqli->connect_errno) {
    echo "Connect failed: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
    exit(1);
}

$sql = "CREATE TABLE IF NOT EXISTS activity_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id CHAR(8) DEFAULT NULL,
    role ENUM('USER', 'OFFICER', 'ADMIN', 'GUEST') NOT NULL DEFAULT 'GUEST',
    action VARCHAR(80) NOT NULL,
    description TEXT NOT NULL,
    request_id INT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (request_id) REFERENCES pickup_requests(request_id) ON DELETE SET NULL
)";

if ($mysqli->query($sql)) {
    echo "activity_logs table ensured.\n";
} else {
    echo "Failed to ensure activity_logs table: " . $mysqli->error . "\n";
}

$mysqli->close();
