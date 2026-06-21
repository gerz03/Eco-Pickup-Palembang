<?php
require_once __DIR__ . "/config/auth.php";

wajibLogin();

$role = $_SESSION["role"] ?? "USER";

if ($role === "ADMIN") {
    header("Location: admin/dashboard.php");
    exit;
}

if ($role === "OFFICER") {
    header("Location: petugas/dashboard.php");
    exit;
}

header("Location: user/dashboard.php");
exit;
