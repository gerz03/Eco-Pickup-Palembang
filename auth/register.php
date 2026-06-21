<?php
require_once __DIR__ . "/../config/database.php";

function buatUserId()
{
    return "USR" . str_pad((string) random_int(1, 99999), 5, "0", STR_PAD_LEFT);
}

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fullName = trim($_POST["full_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phoneNumber = trim($_POST["phone_number"] ?? "");
    $address = trim($_POST["address"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";

    if ($fullName === "" || $email === "" || $phoneNumber === "" || $password === "") {
        $error = "Nama, email, nomor HP, dan password wajib diisi.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid.";
    } elseif ($password !== $confirmPassword) {
        $error = "Konfirmasi password tidak sama.";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter.";
    } else {
        $cek = mysqli_prepare($conn, "SELECT user_id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($cek, "s", $email);
        mysqli_stmt_execute($cek);
        mysqli_stmt_store_result($cek);

        if (mysqli_stmt_num_rows($cek) > 0) {
            $error = "Email sudah terdaftar.";
        } else {
            $jumlahUser = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users");
            $totalUser = (int) mysqli_fetch_assoc($jumlahUser)["total"];
            $role = $totalUser === 0 ? "ADMIN" : "USER";
            $userId = buatUserId();
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $authProvider = "LOCAL";

            $sql = "INSERT INTO users (user_id, full_name, email, phone_number, address, password_hash, auth_provider, role)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ssssssss", $userId, $fullName, $email, $phoneNumber, $address, $passwordHash, $authProvider, $role);

            if (mysqli_stmt_execute($stmt)) {
                $success = "Registrasi berhasil. Silakan login dengan akun baru Anda.";
            } else {
                $error = "Registrasi gagal disimpan.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register EcoPickup</title>
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
<div class="auth-badge"><i class="fa-solid fa-leaf"></i> Akun EcoPickup</div>
<h1 class="mt-4">Daftar untuk mulai mengatur penjemputan.</h1>
<p class="mt-3">Akun pertama otomatis menjadi admin, akun berikutnya menjadi pengguna biasa.</p>
</div>
<a class="btn btn-outline-light" href="../uiRPL.html">Kembali ke Beranda</a>
</aside>
</div>

<div class="col-lg-7">
<section class="auth-form">
<h2>Register</h2>
<p class="text-muted mb-4">Lengkapi data berikut agar permintaan penjemputan mudah dilacak.</p>

<?php if ($error): ?>
<div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<?php if ($success): ?>
<div class="alert alert-success"><?php echo htmlspecialchars($success); ?> <a href="login.php">Login sekarang</a>.</div>
<?php endif; ?>

<a class="btn google-btn w-100" href="google_login.php">
<span class="google-mark">G</span>
Daftar dengan Google
</a>

<div class="auth-divider">atau daftar manual</div>

<form method="post">
<div class="row">
<div class="col-md-6 mb-3">
<label class="form-label">Nama Lengkap</label>
<input type="text" name="full_name" class="form-control" placeholder="Nama Anda" required>
</div>
<div class="col-md-6 mb-3">
<label class="form-label">Email</label>
<input type="email" name="email" class="form-control" placeholder="nama@email.com" required>
</div>
</div>
<div class="mb-3">
<label class="form-label">No HP</label>
<input type="text" name="phone_number" class="form-control" placeholder="08xxxxxxxxxx" required>
</div>
<div class="mb-3">
<label class="form-label">Alamat</label>
<textarea name="address" class="form-control" rows="3" placeholder="Alamat penjemputan utama"></textarea>
</div>
<div class="row">
<div class="col-md-6 mb-3">
<label class="form-label">Password</label>
<input type="password" name="password" class="form-control" placeholder="Minimal 6 karakter" required>
</div>
<div class="col-md-6 mb-3">
<label class="form-label">Konfirmasi Password</label>
<input type="password" name="confirm_password" class="form-control" placeholder="Ulangi password" required>
</div>
</div>
<button class="btn btn-success w-100">Daftar</button>
</form>

<p class="mt-4 mb-0 text-center">Sudah punya akun? <a href="login.php">Login</a></p>
</section>
</div>
</div>
</div>
</main>
</body>
</html>
