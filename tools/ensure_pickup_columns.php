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

$columns = [
    'notes' => "ALTER TABLE pickup_requests ADD COLUMN notes TEXT DEFAULT NULL",
    'photo_path' => "ALTER TABLE pickup_requests ADD COLUMN photo_path VARCHAR(255) DEFAULT NULL"
];

foreach ($columns as $col => $sql) {
    $res = $mysqli->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pickup_requests' AND COLUMN_NAME = '$col'");
    if ($res && $res->num_rows > 0) {
        echo "$col already exists.\n";
        continue;
    }

    if ($mysqli->query($sql)) {
        echo "Added $col column.\n";
    } else {
        echo "Failed to add $col: " . $mysqli->error . "\n";
    }
}

$mysqli->close();
