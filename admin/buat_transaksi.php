<?php
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../config/auth.php";
require_once __DIR__ . "/../config/notifications.php";
require_once __DIR__ . "/../config/security.php";
wajibAdmin();

$requestId = (int) ($_GET["id"] ?? 0);
if ($requestId <= 0) {
    header("Location: permintaan.php");
    exit;
}

$stmt = mysqli_prepare($conn, "SELECT * FROM pickup_requests WHERE request_id = ?");
mysqli_stmt_bind_param($stmt, "i", $requestId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$request = mysqli_fetch_assoc($result);

if (!$request) {
    header("Location: permintaan.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!validasiCsrf()) {
        $error = "Token keamanan tidak valid. Muat ulang halaman dan coba lagi.";
    }

    $totalWeight = (float) ($_POST["total_weight_kg"] ?? 0);
    $totalAmount = (int) ($_POST["total_amount"] ?? 0);
    $paymentMethod = $_POST["payment_method"] ?? "";
    $paymentStatus = $_POST["payment_status"] ?? "";
    $note = trim($_POST["transaction_note"] ?? "");

    $paymentMethods = ["QRIS", "DANA", "OVO", "GoPay", "Transfer Bank", "COD"];
    $paymentStatuses = ["PENDING", "LUNAS", "BATAL"];

    if ($error !== "") {
        // Error CSRF sudah disiapkan.
    } elseif ($totalWeight <= 0 || $totalAmount <= 0) {
        $error = "Berat akhir dan total pembayaran harus lebih dari 0.";
    } elseif (!in_array($paymentMethod, $paymentMethods, true) || !in_array($paymentStatus, $paymentStatuses, true)) {
        $error = "Data pembayaran tidak valid.";
    } else {
        $cek = mysqli_prepare($conn, "SELECT transaction_id FROM pickup_transactions WHERE request_id = ?");
        mysqli_stmt_bind_param($cek, "i", $requestId);
        mysqli_stmt_execute($cek);
        mysqli_stmt_store_result($cek);

        if (mysqli_stmt_num_rows($cek) > 0) {
            $error = "Permintaan ini sudah memiliki transaksi.";
        } else {
            mysqli_begin_transaction($conn);

            $insert = mysqli_prepare($conn, "INSERT INTO pickup_transactions (request_id, total_weight_kg, total_amount, payment_method, payment_status, transaction_note) VALUES (?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($insert, "idisss", $requestId, $totalWeight, $totalAmount, $paymentMethod, $paymentStatus, $note);
            $okInsert = mysqli_stmt_execute($insert);

            $update = mysqli_prepare($conn, "UPDATE pickup_requests SET request_status = 'Selesai' WHERE request_id = ?");
            mysqli_stmt_bind_param($update, "i", $requestId);
            $okUpdate = mysqli_stmt_execute($update);

            if ($okInsert && $okUpdate) {
                tambahNotifikasi(
                    $conn,
                    $request["user_id"],
                    $requestId,
                    "Penjemputan selesai",
                    "Permintaan penjemputan Anda telah selesai."
                );
                mysqli_commit($conn);
                header("Location: transaksi.php");
                exit;
            }

            mysqli_rollback($conn);
            $error = "Transaksi gagal disimpan.";
        }
    }
}

function rupiah($nilai)
{
    return "Rp " . number_format((int) $nilai, 0, ",", ".");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Buat Transaksi</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="../ui.css">
</head>
<body>
<nav class="navbar navbar-dark bg-success shadow-sm">
<div class="container">
<a class="navbar-brand fw-bold" href="permintaan.php"><i class="fa-solid fa-arrow-left me-2"></i>Kembali ke Permintaan</a>
</div>
</nav>

<main class="container py-5">
<section class="app-hero">
<h1 class="h3 fw-bold mb-2">Buat Transaksi</h1>
<p>Catat berat akhir, total pembayaran, dan status pelunasan dari permintaan penjemputan.</p>
</section>

<?php if ($error): ?>
<div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="row g-4">
<div class="col-lg-5">
<div class="app-card h-100">
<div class="app-card-body">
<h2 class="h5 fw-bold mb-3">Ringkasan Permintaan</h2>
<div class="row g-3">
<div class="col-12"><div class="payment-card"><strong>Pelanggan</strong><?php echo htmlspecialchars($request["full_name"]); ?></div></div>
<div class="col-6"><div class="payment-card"><strong>Jenis</strong><?php echo htmlspecialchars($request["waste_type"]); ?></div></div>
<div class="col-6"><div class="payment-card"><strong>Estimasi</strong><?php echo rupiah($request["total_price"]); ?></div></div>
<div class="col-6"><div class="payment-card"><strong>Berat</strong><?php echo number_format((float) $request["weight_kg"], 2, ",", "."); ?> Kg</div></div>
<div class="col-6"><div class="payment-card"><strong>Jarak</strong><?php echo number_format((float) $request["distance_km"], 2, ",", "."); ?> Km</div></div>
<div class="col-12"><div class="payment-card"><strong>Alamat</strong><?php echo htmlspecialchars($request["pickup_address"]); ?></div></div>
</div>
</div>
</div>
</div>

<div class="col-lg-7">
<div class="app-card">
<div class="app-card-body">
<h2 class="h5 fw-bold mb-3">Detail Transaksi</h2>
<form method="post">
<?php echo csrfField(); ?>
<div class="row">
<div class="col-md-6 mb-3">
<label class="form-label">Berat Akhir (Kg)</label>
<input type="number" name="total_weight_kg" class="form-control" min="0.1" step="0.1" value="<?php echo htmlspecialchars($request["weight_kg"]); ?>" required>
</div>
<div class="col-md-6 mb-3">
<label class="form-label">Total Pembayaran</label>
<input type="number" name="total_amount" class="form-control" min="1" value="<?php echo (int) $request["total_price"]; ?>" required>
</div>
</div>
<div class="row">
<div class="col-md-6 mb-3">
<label class="form-label">Metode Pembayaran</label>
<select name="payment_method" class="form-control" required>
<option <?php if ($request["payment_method"] === "QRIS") echo "selected"; ?>>QRIS</option>
<option <?php if ($request["payment_method"] === "DANA") echo "selected"; ?>>DANA</option>
<option <?php if ($request["payment_method"] === "OVO") echo "selected"; ?>>OVO</option>
<option <?php if ($request["payment_method"] === "GoPay") echo "selected"; ?>>GoPay</option>
<option <?php if ($request["payment_method"] === "Transfer Bank") echo "selected"; ?>>Transfer Bank</option>
<option <?php if ($request["payment_method"] === "COD") echo "selected"; ?>>COD</option>
</select>
</div>
<div class="col-md-6 mb-3">
<label class="form-label">Status Pembayaran</label>
<select name="payment_status" class="form-control" required>
<option>LUNAS</option>
<option>PENDING</option>
<option>BATAL</option>
</select>
</div>
</div>
<div class="mb-3">
<label class="form-label">Catatan</label>
<textarea name="transaction_note" class="form-control" rows="4" placeholder="Catatan petugas atau pembayaran"></textarea>
</div>
<button class="btn btn-success"><i class="fa-solid fa-floppy-disk me-2"></i>Simpan Transaksi</button>
<a href="permintaan.php" class="btn btn-secondary">Batal</a>
</form>
</div>
</div>
</div>
</div>
</main>
</body>
</html>
