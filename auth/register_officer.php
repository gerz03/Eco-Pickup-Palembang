<?php
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../config/auth.php";
require_once __DIR__ . "/../config/security.php";
wajibAdmin();

function buatOfficerId()
{
    return "OFC" . str_pad((string) random_int(1, 99999), 5, "0", STR_PAD_LEFT);
}

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!validasiCsrf()) {
        $error = "Token keamanan tidak valid. Muat ulang halaman dan coba lagi.";
    }

    $fullName = trim($_POST["full_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phoneNumber = trim($_POST["phone_number"] ?? "");
    $address = trim($_POST["address"] ?? "");
    $serviceArea = trim($_POST["service_area"] ?? "");
    $vehicleType = trim($_POST["vehicle_type"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($error !== "") {
        // Error CSRF sudah disiapkan.
    } elseif ($fullName === "" || $email === "" || $phoneNumber === "" || $serviceArea === "" || $vehicleType === "" || $password === "") {
        $error = "Semua field wajib diisi.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid.";
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
            mysqli_begin_transaction($conn);

            $officerId = buatOfficerId();
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $authProvider = "LOCAL";
            $role = "OFFICER";

            $insertUser = mysqli_prepare(
                $conn,
                "INSERT INTO users (user_id, full_name, email, phone_number, address, password_hash, auth_provider, role)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            mysqli_stmt_bind_param($insertUser, "ssssssss", $officerId, $fullName, $email, $phoneNumber, $address, $passwordHash, $authProvider, $role);
            $okUser = mysqli_stmt_execute($insertUser);

            $insertOfficer = mysqli_prepare($conn, "INSERT INTO officers (officer_id, service_area, vehicle_type) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($insertOfficer, "sss", $officerId, $serviceArea, $vehicleType);
            $okOfficer = mysqli_stmt_execute($insertOfficer);

            if ($okUser && $okOfficer) {
                mysqli_commit($conn);
                $success = "Petugas berhasil didaftarkan.";
            } else {
                mysqli_rollback($conn);
                $error = "Data petugas gagal disimpan.";
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
<title>Register Petugas</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="../ui.css">
</head>
<body>
<nav class="navbar navbar-dark bg-success shadow-sm">
<div class="container">
<a class="navbar-brand fw-bold" href="../admin/users.php"><i class="fa-solid fa-arrow-left me-2"></i>Register Petugas</a>
</div>
</nav>

<main class="container py-5">
<section class="app-hero">
<h1 class="h3 fw-bold mb-2">Tambah Akun Petugas</h1>
<p>Akun ini akan muncul di dropdown penugasan permintaan penjemputan.</p>
</section>

<?php if ($error): ?>
<div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<?php if ($success): ?>
<div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<div class="app-card">
<div class="app-card-body">
<form method="post">
<?php echo csrfField(); ?>
<div class="row">
<div class="col-md-6 mb-3">
<label class="form-label">Nama Lengkap</label>
<input type="text" name="full_name" class="form-control" required>
</div>
<div class="col-md-6 mb-3">
<label class="form-label">Email</label>
<input type="email" name="email" class="form-control" required>
</div>
</div>
<div class="row">
<div class="col-md-6 mb-3">
<label class="form-label">No HP</label>
<input type="text" name="phone_number" class="form-control" required>
</div>
<div class="col-md-6 mb-3">
<label class="form-label">Password</label>
<input type="password" name="password" class="form-control" required>
</div>
</div>
<div class="mb-3">
<label class="form-label">Alamat</label>
<textarea name="address" class="form-control" rows="3"></textarea>
</div>
<div class="row">
<div class="col-md-6 mb-3">
<label class="form-label">Area Layanan</label>
<input type="text" name="service_area" class="form-control" placeholder="Contoh: Ilir Barat I" required>
</div>
<div class="col-md-6 mb-3">
<label class="form-label">Jenis Kendaraan</label>
<input type="text" name="vehicle_type" class="form-control" placeholder="Contoh: Motor bak sampah" required>
</div>
</div>
<button class="btn btn-success"><i class="fa-solid fa-user-plus me-2"></i>Simpan Petugas</button>
<a class="btn btn-secondary" href="../admin/users.php">Kembali</a>
</form>
</div>
</div>
</main>
</body>
</html>
