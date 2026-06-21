<?php
require_once __DIR__ . "/config/database.php";

$requestId = (int) ($_GET["id"] ?? 0);
if ($requestId <= 0) {
    header("Location: uiRPL.html");
    exit;
}

$stmt = mysqli_prepare($conn, "SELECT * FROM pickup_requests WHERE request_id = ?");
mysqli_stmt_bind_param($stmt, "i", $requestId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$request = mysqli_fetch_assoc($result);

if (!$request) {
    header("Location: uiRPL.html");
    exit;
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
<title>Permintaan Berhasil</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="ui.css">
</head>
<body>
<nav class="navbar navbar-dark bg-success shadow-sm">
<div class="container">
<a class="navbar-brand fw-bold" href="uiRPL.html"><i class="fa-solid fa-recycle me-2"></i>EcoPickup Palembang</a>
</div>
</nav>

<main class="container py-5">
<section class="app-hero">
<h1 class="h3 fw-bold mb-2"><i class="fa-solid fa-circle-check me-2"></i>Permintaan Berhasil Dikirim</h1>
<p>Petugas akan menghubungi Anda sesuai jadwal penjemputan. Simpan nomor permintaan untuk pengecekan.</p>
</section>

<div class="card form-card">
<div class="card-body">
<div class="row g-3">
<div class="col-md-4"><div class="payment-card"><strong>No Permintaan</strong>#<?php echo (int) $request["request_id"]; ?></div></div>
<div class="col-md-4"><div class="payment-card"><strong>Status</strong><?php echo htmlspecialchars($request["request_status"]); ?></div></div>
<div class="col-md-4"><div class="payment-card"><strong>Total Tarif</strong><?php echo rupiah($request["total_price"]); ?></div></div>
<div class="col-md-6"><div class="payment-card"><strong>Nama</strong><?php echo htmlspecialchars($request["full_name"]); ?></div></div>
<div class="col-md-6"><div class="payment-card"><strong>No HP</strong><?php echo htmlspecialchars($request["phone_number"]); ?></div></div>
<div class="col-md-12"><div class="payment-card"><strong>Alamat</strong><?php echo htmlspecialchars($request["pickup_address"]); ?></div></div>
<div class="col-md-4"><div class="payment-card"><strong>Jenis Sampah</strong><?php echo htmlspecialchars($request["waste_type"]); ?></div></div>
<div class="col-md-4"><div class="payment-card"><strong>Berat</strong><?php echo number_format((float) $request["weight_kg"], 2, ",", "."); ?> Kg</div></div>
<div class="col-md-4"><div class="payment-card"><strong>Jadwal</strong><?php echo htmlspecialchars($request["pickup_date"]); ?> <?php echo htmlspecialchars(substr($request["pickup_time"], 0, 5)); ?></div></div>
</div>

<div class="mt-4 d-flex gap-2 flex-wrap">
<a href="uiRPL.html" class="btn btn-success">Kembali ke Beranda</a>
<a href="auth/login.php" class="btn btn-outline-success">Login untuk Melihat Riwayat</a>
</div>
</div>
</div>
</main>
</body>
</html>
