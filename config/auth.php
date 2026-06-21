<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function wajibLogin()
{
    if (empty($_SESSION["user_id"])) {
        header("Location: ../auth/login.php");
        exit;
    }
}

function wajibAdmin()
{
    wajibLogin();

    if (($_SESSION["role"] ?? "") !== "ADMIN") {
        header("Location: ../user/dashboard.php");
        exit;
    }
}

function wajibPetugas()
{
    wajibLogin();

    if (($_SESSION["role"] ?? "") !== "OFFICER") {
        header("Location: ../auth/login.php");
        exit;
    }
}

function userLogin()
{
    return [
        "user_id" => $_SESSION["user_id"] ?? "",
        "full_name" => $_SESSION["full_name"] ?? "",
        "role" => $_SESSION["role"] ?? ""
    ];
}
