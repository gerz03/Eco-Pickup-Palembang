<?php
// tools/import_sql.php
// Simple secure importer: reads Database_Bank_Sampah.sql and executes statements

require_once __DIR__ . '/../config/env.php';
loadEnvFile(__DIR__ . '/../.env');

$importKeyEnv = getenv('IMPORT_KEY');
// When accessed via web, require key if set
if (php_sapi_name() !== 'cli') {
    $key = $_GET['key'] ?? null;
    if (!empty($importKeyEnv) && $key !== $importKeyEnv) {
        http_response_code(403);
        echo "Forbidden: missing or invalid key.";
        exit;
    }
}

$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASSWORD') ?: '';
$dbName = getenv('DB_NAME') ?: 'bank_sampah_palembang';

$sqlFile = __DIR__ . '/../Database_Bank_Sampah.sql';
if (!file_exists($sqlFile)) {
    echo "SQL file not found at: $sqlFile";
    exit(1);
}

$sql = file_get_contents($sqlFile);
// Remove CREATE DATABASE / USE statements to avoid permission issues
$sql = preg_replace('/CREATE\s+DATABASE[\s\S]*?;\s*/i', '', $sql);
$sql = preg_replace('/USE\s+[^;]+;\s*/i', '', $sql);

$mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($mysqli->connect_errno) {
    echo "Connect failed: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
    exit(1);
}

// Split statements by semicolon. This assumes no delimiter inside statements.
$parts = array_filter(array_map('trim', explode(';', $sql)));
$errors = [];
foreach ($parts as $part) {
    if ($part === '') continue;
    $query = $part . ';';
    if (!$mysqli->query($query)) {
        $errors[] = [ 'error' => $mysqli->error, 'query' => substr($part, 0, 200) ];
    }
}

if (empty($errors)) {
    echo "Import completed successfully.";
} else {
    echo "Import completed with errors:\n";
    foreach ($errors as $e) {
        echo "Error: " . $e['error'] . "\nQuery snippet: " . $e['query'] . "\n\n";
    }
}

$mysqli->close();

return 0;
