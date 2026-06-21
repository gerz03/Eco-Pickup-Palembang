<?php
require_once __DIR__ . "/../config/database.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function buatUserIdGoogle()
{
    return "USR" . str_pad((string) random_int(1, 99999), 5, "0", STR_PAD_LEFT);
}

function kembaliLogin($pesan)
{
    header("Location: login.php?google=" . urlencode($pesan));
    exit;
}

$google = require __DIR__ . "/../config/google.php";
$state = $_GET["state"] ?? "";
$code = $_GET["code"] ?? "";

if ($state === "" || $state !== ($_SESSION["google_oauth_state"] ?? "")) {
    kembaliLogin("state_tidak_valid");
}

if ($code === "") {
    kembaliLogin("kode_tidak_valid");
}

$tokenPayload = [
    "code" => $code,
    "client_id" => $google["client_id"],
    "client_secret" => $google["client_secret"],
    "redirect_uri" => $google["redirect_uri"],
    "grant_type" => "authorization_code"
];

$curl = curl_init("https://oauth2.googleapis.com/token");
curl_setopt_array($curl, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query($tokenPayload),
    CURLOPT_HTTPHEADER => ["Content-Type: application/x-www-form-urlencoded"]
]);
$tokenResponse = curl_exec($curl);
curl_close($curl);

$tokenData = json_decode($tokenResponse, true);
if (empty($tokenData["access_token"])) {
    kembaliLogin("token_gagal");
}

$curl = curl_init("https://www.googleapis.com/oauth2/v2/userinfo");
curl_setopt_array($curl, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ["Authorization: Bearer " . $tokenData["access_token"]]
]);
$profileResponse = curl_exec($curl);
curl_close($curl);

$profile = json_decode($profileResponse, true);
$googleId = $profile["id"] ?? "";
$email = $profile["email"] ?? "";
$fullName = $profile["name"] ?? "Pengguna Google";

if ($googleId === "" || $email === "") {
    kembaliLogin("profil_gagal");
}

$stmt = mysqli_prepare($conn, "SELECT user_id, full_name, role FROM users WHERE email = ? OR google_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "ss", $email, $googleId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if (!$user) {
    $jumlahUser = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users");
    $totalUser = (int) mysqli_fetch_assoc($jumlahUser)["total"];
    $role = $totalUser === 0 ? "ADMIN" : "USER";
    $userId = buatUserIdGoogle();
    $passwordHash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
    $phoneNumber = "-";
    $address = "";
    $authProvider = "GOOGLE";

    $insert = mysqli_prepare($conn, "INSERT INTO users (user_id, full_name, email, google_id, phone_number, address, password_hash, auth_provider, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($insert, "sssssssss", $userId, $fullName, $email, $googleId, $phoneNumber, $address, $passwordHash, $authProvider, $role);

    if (!mysqli_stmt_execute($insert)) {
        kembaliLogin("simpan_user_gagal");
    }

    $user = [
        "user_id" => $userId,
        "full_name" => $fullName,
        "role" => $role
    ];
} else {
    $update = mysqli_prepare($conn, "UPDATE users SET google_id = ?, auth_provider = 'GOOGLE' WHERE user_id = ?");
    mysqli_stmt_bind_param($update, "ss", $googleId, $user["user_id"]);
    mysqli_stmt_execute($update);
}

$_SESSION["user_id"] = $user["user_id"];
$_SESSION["full_name"] = $user["full_name"];
$_SESSION["role"] = $user["role"];
unset($_SESSION["google_oauth_state"]);

if ($user["role"] === "ADMIN") {
    header("Location: ../admin/dashboard.php");
} else {
    header("Location: ../user/dashboard.php");
}
exit;
