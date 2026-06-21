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
    header("Location: permintaan.php?pesan=assign_gagal");
    exit;
}

$requestId = (int) ($_POST["request_id"] ?? 0);
$officerId = trim($_POST["officer_id"] ?? "");

if ($requestId <= 0 || $officerId === "") {
    header("Location: permintaan.php?pesan=assign_gagal");
    exit;
}

$cekPetugas = mysqli_prepare($conn, "SELECT officer_id FROM officers WHERE officer_id = ?");
mysqli_stmt_bind_param($cekPetugas, "s", $officerId);
mysqli_stmt_execute($cekPetugas);
mysqli_stmt_store_result($cekPetugas);

if (mysqli_stmt_num_rows($cekPetugas) === 0) {
    header("Location: permintaan.php?pesan=assign_gagal");
    exit;
}

$cekRequest = mysqli_prepare($conn, "SELECT user_id FROM pickup_requests WHERE request_id = ?");
mysqli_stmt_bind_param($cekRequest, "i", $requestId);
mysqli_stmt_execute($cekRequest);
$request = mysqli_fetch_assoc(mysqli_stmt_get_result($cekRequest));

if (!$request) {
    header("Location: permintaan.php?pesan=assign_gagal");
    exit;
}

mysqli_begin_transaction($conn);

$stmt = mysqli_prepare($conn, "UPDATE pickup_requests SET officer_id = ?, request_status = 'Diproses' WHERE request_id = ?");
mysqli_stmt_bind_param($stmt, "si", $officerId, $requestId);
$okUpdate = mysqli_stmt_execute($stmt);

$okNotif = tambahNotifikasi(
    $conn,
    $request["user_id"],
    $requestId,
    "Petugas ditugaskan",
    "Permintaan Anda sedang diproses petugas."
);
$okOfficerNotif = tambahNotifikasi(
    $conn,
    $officerId,
    $requestId,
    "Tugas penjemputan baru",
    "Anda menerima tugas penjemputan baru.",
    "../petugas/dashboard.php"
);

if ($okUpdate && $okNotif && $okOfficerNotif) {
    $adminId = $_SESSION["user_id"] ?? null;
    catatAktivitas($conn, $adminId, "ADMIN", "ASSIGN_OFFICER", "Menugaskan petugas ke permintaan.", $requestId);
    mysqli_commit($conn);
    header("Location: permintaan.php?pesan=assign_berhasil");
    exit;
}

mysqli_rollback($conn);
header("Location: permintaan.php?pesan=assign_gagal");
exit;
