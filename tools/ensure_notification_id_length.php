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
    // First, check if column already exists and its size
    $checkCol = $mysqli->query("SHOW COLUMNS FROM $table WHERE Field = 'notification_id'");
    $colInfo = mysqli_fetch_assoc($checkCol);
    
    if ($colInfo && strpos($colInfo['Type'], 'CHAR(18)') !== false) {
        echo "$table.notification_id is already CHAR(18). Skipping.\n";
        continue;
    }
    
    echo "Updating $table.notification_id...\n";
    
    // For existing tables with data, we need to:
    // 1. Add a temporary column
    // 2. Copy data
    // 3. Drop the old primary key
    // 4. Drop the old column
    // 5. Rename temp column
    // 6. Re-add primary key
    
    $tempCol = 'notification_id_temp';
    
    // Step 1: Add temporary column
    $addTemp = "ALTER TABLE $table ADD COLUMN $tempCol CHAR(18)";
    if (!$mysqli->query($addTemp)) {
        // If it already exists, drop it first
        $mysqli->query("ALTER TABLE $table DROP COLUMN $tempCol");
        $mysqli->query($addTemp);
    }
    
    // Step 2: Copy data from old column to temp
    if ($mysqli->query("UPDATE $table SET $tempCol = notification_id")) {
        echo "  - Copied data to temporary column\n";
    } else {
        echo "  - Failed to copy data: " . $mysqli->error . "\n";
        continue;
    }
    
    // Step 3: Drop the primary key
    if ($mysqli->query("ALTER TABLE $table DROP PRIMARY KEY")) {
        echo "  - Dropped primary key\n";
    } else {
        echo "  - Failed to drop primary key: " . $mysqli->error . "\n";
        continue;
    }
    
    // Step 4: Drop the old column
    if ($mysqli->query("ALTER TABLE $table DROP COLUMN notification_id")) {
        echo "  - Dropped old column\n";
    } else {
        echo "  - Failed to drop old column: " . $mysqli->error . "\n";
        continue;
    }
    
    // Step 5: Rename temp column to original name
    if ($mysqli->query("ALTER TABLE $table CHANGE COLUMN $tempCol notification_id CHAR(18)")) {
        echo "  - Renamed temporary column\n";
    } else {
        echo "  - Failed to rename column: " . $mysqli->error . "\n";
        continue;
    }
    
    // Step 6: Re-add primary key
    if ($mysqli->query("ALTER TABLE $table ADD PRIMARY KEY (notification_id)")) {
        echo "  - Re-added primary key\n";
        echo "✓ Successfully updated $table.notification_id to CHAR(18)\n";
    } else {
        echo "  - Failed to re-add primary key: " . $mysqli->error . "\n";
    }
}

$mysqli->close();
