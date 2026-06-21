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

$res = $mysqli->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_notifications' AND COLUMN_NAME = 'read_at'");
if ($res && $res->num_rows > 0) {
    echo "read_at already exists.\n";
} else {
    if ($mysqli->query("ALTER TABLE user_notifications ADD COLUMN read_at TIMESTAMP NULL DEFAULT NULL")) {
        echo "Added read_at column successfully.\n";
    } else {
        echo "Failed to add read_at: " . $mysqli->error . "\n";
    }
}

$mysqli->close();
