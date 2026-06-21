<?php
function buatNotificationId()
{
    return "NTF" . date("ymdHis") . str_pad((string) random_int(1, 999), 3, "0", STR_PAD_LEFT);
}

function tambahNotifikasi($conn, $recipientId, $requestId, $title, $message, $linkUrl = "notifikasi.php")
{
    if (empty($recipientId)) {
        return true;
    }

    $notificationId = buatNotificationId();
    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO user_notifications (notification_id, recipient_id, request_id, title, message, link_url)
         VALUES (?, ?, ?, ?, ?, ?)"
    );

    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, "ssisss", $notificationId, $recipientId, $requestId, $title, $message, $linkUrl);
    return mysqli_stmt_execute($stmt);
}

function tambahNotifikasiRole($conn, $role, $requestId, $title, $message, $linkUrl)
{
    $stmt = mysqli_prepare($conn, "SELECT user_id FROM users WHERE role = ?");
    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, "s", $role);
    mysqli_stmt_execute($stmt);
    $users = mysqli_stmt_get_result($stmt);
    $ok = true;

    while ($user = mysqli_fetch_assoc($users)) {
        $ok = tambahNotifikasi($conn, $user["user_id"], $requestId, $title, $message, $linkUrl) && $ok;
    }

    return $ok;
}
