<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function csrfToken()
{
    if (empty($_SESSION["csrf_token"])) {
        $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
    }

    return $_SESSION["csrf_token"];
}

function csrfField()
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken(), ENT_QUOTES, "UTF-8") . '">';
}

function validasiCsrf()
{
    $token = $_POST["csrf_token"] ?? "";
    return is_string($token) && hash_equals($_SESSION["csrf_token"] ?? "", $token);
}

function bersihkanInput($value)
{
    return trim((string) $value);
}

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}
