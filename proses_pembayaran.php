<?php
require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/config/security.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: dashboard.php");
    exit;
}

$requestId = (int) ($_POST["request_id"] ?? 0);

if ($requestId <= 0 || !validasiCsrf()) {
    header("Location: dashboard.php?status=error&pesan=" . urlencode("ID Permintaan tidak valid."));
    exit;
}

$cek = mysqli_prepare($conn, "SELECT user_id FROM pickup_requests WHERE request_id = ?");
mysqli_stmt_bind_param($cek, "i", $requestId);
mysqli_stmt_execute($cek);
$request = mysqli_fetch_assoc(mysqli_stmt_get_result($cek));

if (!$request) {
    header("Location: dashboard.php?status=error&pesan=" . urlencode("Permintaan tidak ditemukan."));
    exit;
}

$sessionUserId = $_SESSION["user_id"] ?? null;
$sessionRole = $_SESSION["role"] ?? "";
$lastRequestId = (int) ($_SESSION["last_request_id"] ?? 0);
$isOwner = !empty($request["user_id"]) && $sessionUserId === $request["user_id"];
$isAdmin = $sessionRole === "ADMIN";
$isGuestRecentRequest = empty($request["user_id"]) && $lastRequestId === $requestId;

if (!$isOwner && !$isAdmin && !$isGuestRecentRequest) {
    header("Location: auth/login.php");
    exit;
}

$sql = "UPDATE pickup_transactions SET payment_status = 'LUNAS' WHERE request_id = ?";
$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    header("Location: dashboard.php?status=error&pesan=" . urlencode("Gagal menyiapkan query update pembayaran."));
    exit;
}

mysqli_stmt_bind_param($stmt, "i", $requestId);

if (mysqli_stmt_execute($stmt)) {
    header("Location: dashboard.php?status=success&pesan=" . urlencode("Pembayaran Anda berhasil dikonfirmasi."));
} else {
    header("Location: dashboard.php?status=error&pesan=" . urlencode("Gagal mengkonfirmasi pembayaran. Silakan coba lagi."));
}
exit;
