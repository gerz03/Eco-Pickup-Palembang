<?php
require_once __DIR__ . "/env.php";
loadEnvFile(__DIR__ . "/../.env");

$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASSWORD') ?: '';
$dbName = getenv('DB_NAME') ?: 'bank_sampah_palembang';

$conn = mysqli_connect($dbHost, $dbUser, $dbPass, $dbName);
if (!$conn) {
    die(mysqli_connect_error());
}
mysqli_set_charset($conn, 'utf8mb4');
?>
