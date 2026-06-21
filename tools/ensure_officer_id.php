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

$res = $mysqli->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pickup_requests' AND COLUMN_NAME = 'officer_id'");
if ($res && $res->num_rows > 0) {
    echo "officer_id already exists.\n";
} else {
    // Add column officer_id
    if ($mysqli->query("ALTER TABLE pickup_requests ADD COLUMN officer_id CHAR(8) DEFAULT NULL")) {
        echo "Added officer_id column.\n";
        // attempt to add foreign key (may fail if constraint exists)
        // create FK name
        $fkName = 'fk_pickup_requests_officer_id';
        $addFk = "ALTER TABLE pickup_requests ADD CONSTRAINT $fkName FOREIGN KEY (officer_id) REFERENCES officers(officer_id) ON DELETE SET NULL";
        if ($mysqli->query($addFk)) {
            echo "Added foreign key constraint.\n";
        } else {
            echo "Foreign key not added or already exists: " . $mysqli->error . "\n";
        }
    } else {
        echo "Failed to add officer_id: " . $mysqli->error . "\n";
    }
}

$mysqli->close();
