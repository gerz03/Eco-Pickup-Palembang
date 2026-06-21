<?php
function catatAktivitas($conn, $userId, $role, $action, $description, $requestId = null)
{
    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO activity_logs (user_id, role, action, description, request_id, ip_address, user_agent)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );

    if (!$stmt) {
        return false;
    }

    $ipAddress = $_SERVER["REMOTE_ADDR"] ?? "";
    $userAgent = substr($_SERVER["HTTP_USER_AGENT"] ?? "", 0, 255);
    mysqli_stmt_bind_param($stmt, "ssssiss", $userId, $role, $action, $description, $requestId, $ipAddress, $userAgent);
    return mysqli_stmt_execute($stmt);
}
