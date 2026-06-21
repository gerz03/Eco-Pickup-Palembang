<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$google = require __DIR__ . "/../config/google.php";

if ($google["client_id"] === "ISI_CLIENT_ID_GOOGLE" || $google["client_secret"] === "ISI_CLIENT_SECRET_GOOGLE") {
    header("Location: login.php?google=belum_diatur");
    exit;
}

$state = bin2hex(random_bytes(16));
$_SESSION["google_oauth_state"] = $state;

$params = [
    "client_id" => $google["client_id"],
    "redirect_uri" => $google["redirect_uri"],
    "response_type" => "code",
    "scope" => "openid email profile",
    "state" => $state,
    "access_type" => "online",
    "prompt" => "select_account"
];

header("Location: https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query($params));
exit;
