<?php
require_once __DIR__ . "/../config/database.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = "";
$googleMessage = "";

if (isset($_GET["google"])) {
    $googleMessages = [
        "belum_diatur" => "Login Google belum aktif. Isi Client ID dan Client Secret di config/google.php.",
        "state_tidak_valid" => "Sesi login Google tidak valid. Silakan coba lagi.",
        "kode_tidak_valid" => "Kode login Google tidak ditemukan.",
        "token_gagal" => "Gagal mengambil token dari Google.",
        "profil_gagal" => "Gagal mengambil profil Google.",
        "simpan_user_gagal" => "Akun Google gagal disimpan."
    ];
    $googleMessage = $googleMessages[$_GET["google"]] ?? "Login Google gagal diproses.";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    $stmt = mysqli_prepare($conn, "SELECT user_id, full_name, password_hash, role FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    if ($user && password_verify($password, $user["password_hash"])) {
        $_SESSION["user_id"] = $user["user_id"];
        $_SESSION["full_name"] = $user["full_name"];
        $_SESSION["role"] = $user["role"];

        if ($user["role"] === "ADMIN") {
            header("Location: ../admin/dashboard.php");
        } elseif ($user["role"] === "OFFICER") {
            header("Location: ../petugas/dashboard.php");
        } else {
            header("Location: ../user/dashboard.php");
        }
        exit;
    }

    $error = "Email atau password salah.";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login EcoPickup</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="../ui.css">
</head>
<body class="auth-page">
<main class="container">
<div class="auth-shell">
<div class="row g-0">
<div class="col-lg-5">
<aside class="auth-panel">
<div>
<div class="auth-badge"><i class="fa-solid fa-recycle"></i> EcoPickup Palembang</div>
<h1 class="mt-4">Masuk untuk memantau penjemputan sampah.</h1>
<p class="mt-3">Kelola jadwal, lihat status permintaan, dan akses transaksi dengan akun yang sama.</p>
</div>
<a class="btn btn-outline-light" href="../uiRPL.html">Kembali ke Beranda</a>
</aside>
</div>

<div class="col-lg-7">
<section class="auth-form">
<h2>Login</h2>
<p class="text-muted mb-4">Gunakan email dan password yang sudah terdaftar.</p>

<?php if ($error): ?>
<div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<?php if ($googleMessage): ?>
<div class="alert alert-warning"><?php echo htmlspecialchars($googleMessage); ?></div>
<?php endif; ?>

<a class="btn google-btn w-100" href="google_login.php">
<span class="google-mark">G</span>
Login dengan Google
</a>

<div class="auth-divider">atau login dengan email</div>

<form method="post">
<div class="mb-3">
<label class="form-label">Email</label>
<input type="email" name="email" class="form-control" placeholder="nama@email.com" required>
</div>
<div class="mb-3">
<label class="form-label">Password</label>
<input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
</div>
<button class="btn btn-success w-100">Login</button>
</form>

<p class="mt-4 mb-0 text-center">Belum punya akun? <a href="register.php">Daftar sekarang</a></p>
</section>
</div>
</div>
</div>
</main>
</body>
</html>
