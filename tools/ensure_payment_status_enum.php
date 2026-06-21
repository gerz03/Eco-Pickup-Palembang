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

$sql = "ALTER TABLE pickup_transactions MODIFY COLUMN payment_status ENUM('PENDING', 'LUNAS', 'BATAL') NOT NULL DEFAULT 'PENDING'";
if ($mysqli->query($sql)) {
    echo "Updated payment_status enum to PENDING, LUNAS, BATAL.\n";
} else {
    echo "Failed to update payment_status enum: " . $mysqli->error . "\n";
}

$mysqli->close();
