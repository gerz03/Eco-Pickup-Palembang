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

$tables = ['notifications', 'user_notifications'];
foreach ($tables as $table) {
    $sql = "ALTER TABLE $table MODIFY notification_id CHAR(18) PRIMARY KEY";
    if ($mysqli->query($sql)) {
        echo "Updated $table.notification_id to CHAR(18).\n";
    } else {
        echo "Failed to update $table.notification_id: " . $mysqli->error . "\n";
    }
}

$mysqli->close();
