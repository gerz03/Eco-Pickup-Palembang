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
    // Drop primary key constraint
    if ($mysqli->query("ALTER TABLE $table DROP PRIMARY KEY")) {
        echo "Dropped PRIMARY KEY constraint on $table.\n";
    } else {
        echo "Failed to drop PRIMARY KEY on $table: " . $mysqli->error . "\n";
        continue;
    }
    
    // Modify column type
    if ($mysqli->query("ALTER TABLE $table MODIFY notification_id CHAR(18)")) {
        echo "Modified $table.notification_id to CHAR(18).\n";
    } else {
        echo "Failed to modify $table.notification_id: " . $mysqli->error . "\n";
        continue;
    }
    
    // Re-add primary key constraint
    if ($mysqli->query("ALTER TABLE $table ADD PRIMARY KEY (notification_id)")) {
        echo "Re-added PRIMARY KEY constraint on $table.\n";
    } else {
        echo "Failed to re-add PRIMARY KEY on $table: " . $mysqli->error . "\n";
    }
}

$mysqli->close();
