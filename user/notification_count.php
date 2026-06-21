<?php
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../config/auth.php";
wajibLogin();

header("Content-Type: application/json");

$user = userLogin();
$stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM user_notifications WHERE recipient_id = ? AND read_at IS NULL");
mysqli_stmt_bind_param($stmt, "s", $user["user_id"]);
mysqli_stmt_execute($stmt);
$row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

echo json_encode(["total" => (int) ($row["total"] ?? 0)]);
