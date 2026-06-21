<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
$conn = mysqli_connect('localhost', 'root', '', 'bank_sampah_palembang');
if (!$conn) {
    echo 'CONNECT ERROR: ' . mysqli_connect_error() . PHP_EOL;
    exit(1);
}
mysqli_set_charset($conn, 'utf8mb4');
$sql = "INSERT INTO pickup_requests (
    user_id,
    full_name,
    phone_number,
    pickup_address,
    notes,
    photo_path,
    waste_type,
    price_per_kg,
    weight_kg,
    distance_km,
    distance_fee,
    total_price,
    pickup_date,
    pickup_time,
    payment_method
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    echo mysqli_errno($conn) . ': ' . mysqli_error($conn) . PHP_EOL;
} else {
    echo 'OK' . PHP_EOL;
}
?>