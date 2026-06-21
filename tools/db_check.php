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

header('Content-Type: text/plain');
echo "Connected to DB: $dbName on $dbHost\n\nTables:\n";
$res = $mysqli->query("SHOW TABLES");
while ($row = $res->fetch_array()) {
    echo " - " . $row[0] . "\n";
}

$res2 = $mysqli->query("SHOW TABLES LIKE 'user_notifications'");
$exists = ($res2 && $res2->num_rows > 0) ? 'YES' : 'NO';
echo "\nuser_notifications exists: $exists\n";

$mysqli->close();
