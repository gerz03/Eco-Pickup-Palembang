<?php
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../config/auth.php";
require_once __DIR__ . "/../config/notifications.php";
require_once __DIR__ . "/../config/security.php";
require_once __DIR__ . "/../config/activity.php";
wajibPetugas();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: dashboard.php");
    exit;
}

if (!validasiCsrf()) {
    header("Location: dashboard.php?pesan=terangkut_gagal");
    exit;
}

$user = userLogin();
$requestId = (int) ($_POST["request_id"] ?? 0);

if ($requestId <= 0) {
    header("Location: dashboard.php?pesan=terangkut_gagal");
    exit;
}

$cek = mysqli_prepare($conn, "SELECT user_id FROM pickup_requests WHERE request_id = ? AND officer_id = ?");
mysqli_stmt_bind_param($cek, "is", $requestId, $user["user_id"]);
mysqli_stmt_execute($cek);
$request = mysqli_fetch_assoc(mysqli_stmt_get_result($cek));

if (!$request) {
    header("Location: dashboard.php?pesan=terangkut_gagal");
    exit;
}

mysqli_begin_transaction($conn);

$stmt = mysqli_prepare($conn, "UPDATE pickup_requests SET request_status = 'Terangkut' WHERE request_id = ? AND officer_id = ?");
mysqli_stmt_bind_param($stmt, "is", $requestId, $user["user_id"]);
$okUpdate = mysqli_stmt_execute($stmt);

$okNotif = tambahNotifikasi(
    $conn,
    $request["user_id"],
    $requestId,
    "Sampah terangkut",
    "Sampah Anda telah berhasil diangkut."
);
$okNotifAdmin = tambahNotifikasiRole(
    $conn,
    "ADMIN",
    $requestId,
    "Permintaan terangkut",
    "Petugas telah menandai sampah sebagai Terangkut. Silakan verifikasi.",
    "../admin/permintaan.php"
);

if ($okUpdate && $okNotif && $okNotifAdmin) {
    catatAktivitas($conn, $user["user_id"], "OFFICER", "MARK_PICKED_UP", "Menandai permintaan sebagai Terangkut.", $requestId);
    mysqli_commit($conn);
    header("Location: dashboard.php?pesan=terangkut_berhasil");
    exit;
}

mysqli_rollback($conn);
header("Location: dashboard.php?pesan=terangkut_gagal");
exit;
