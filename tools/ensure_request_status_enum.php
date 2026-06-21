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

// Update old values to the new application status values if needed.
$updateOld = "UPDATE pickup_requests SET request_status = CASE request_status
    WHEN 'PENDING' THEN 'Menunggu'
    WHEN 'DIJEMPUT' THEN 'Terangkut'
    WHEN 'SELESAI' THEN 'Selesai'
    WHEN 'DIBATALKAN' THEN 'Dibatalkan'
    ELSE request_status
END";
if (!$mysqli->query($updateOld)) {
    echo "Failed to normalize old request_status values: " . $mysqli->error . "\n";
    $mysqli->close();
    exit(1);
}

$sql = "ALTER TABLE pickup_requests MODIFY request_status ENUM('Menunggu', 'Diproses', 'Terangkut', 'Selesai', 'Dibatalkan') NOT NULL DEFAULT 'Menunggu'";
if ($mysqli->query($sql)) {
    echo "Updated request_status enum to Menunggu, Diproses, Terangkut, Selesai, Dibatalkan.\n";
} else {
    echo "Failed to update request_status enum: " . $mysqli->error . "\n";
}

$mysqli->close();
