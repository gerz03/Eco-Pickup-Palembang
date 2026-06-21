<?php
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../config/auth.php";
require_once __DIR__ . "/../config/notifications.php";
require_once __DIR__ . "/../config/security.php";
require_once __DIR__ . "/../config/activity.php";
wajibAdmin();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: permintaan.php");
    exit;
}

if (!validasiCsrf()) {
    header("Location: permintaan.php?pesan=status_gagal");
    exit;
}

$requestId = (int) ($_POST["request_id"] ?? 0);
$status = $_POST["request_status"] ?? "";
$allowedStatus = ["Menunggu", "Diproses", "Terangkut", "Selesai", "Dibatalkan"];

if ($requestId <= 0 || !in_array($status, $allowedStatus, true)) {
    header("Location: permintaan.php?pesan=status_gagal");
    exit;
}

$cek = mysqli_prepare($conn, "SELECT user_id FROM pickup_requests WHERE request_id = ?");
mysqli_stmt_bind_param($cek, "i", $requestId);
mysqli_stmt_execute($cek);
$request = mysqli_fetch_assoc(mysqli_stmt_get_result($cek));

if (!$request) {
    header("Location: permintaan.php?pesan=status_gagal");
    exit;
}

$stmt = mysqli_prepare($conn, "UPDATE pickup_requests SET request_status = ? WHERE request_id = ?");
mysqli_stmt_bind_param($stmt, "si", $status, $requestId);

if (mysqli_stmt_execute($stmt)) {
    if ($status === "Selesai") {
        tambahNotifikasi(
            $conn,
            $request["user_id"],
            $requestId,
            "Penjemputan selesai",
            "Permintaan penjemputan Anda telah selesai."
        );
    }

    catatAktivitas($conn, $_SESSION["user_id"] ?? null, "ADMIN", "UPDATE_STATUS", "Mengubah status permintaan menjadi " . $status . ".", $requestId);

    header("Location: permintaan.php?pesan=status_berhasil");
} else {
    header("Location: permintaan.php?pesan=status_gagal");
}
exit;
