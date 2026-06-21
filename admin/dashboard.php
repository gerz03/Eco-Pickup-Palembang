<?php
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../config/auth.php";
wajibAdmin();

$totalUsers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM users"))["total"];
$totalOfficers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE role = 'OFFICER'"))["total"];
$totalRequests = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM pickup_requests"))["total"];
$pendingRequests = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM pickup_requests WHERE request_status IN ('Menunggu', 'PENDING')"))["total"];
$completedRequests = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM pickup_requests WHERE request_status = 'Selesai'"))["total"];
$totalTransactions = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM pickup_transactions"))["total"];
$totalRevenue = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(total_amount), 0) AS total FROM pickup_transactions"))["total"];
$recentRequests = mysqli_query($conn, "SELECT request_id, full_name, waste_type, pickup_date, pickup_time, total_price, request_status FROM pickup_requests ORDER BY created_at DESC LIMIT 5");
$statusStats = mysqli_query($conn, "SELECT request_status, COUNT(*) AS total FROM pickup_requests GROUP BY request_status");
$user = userLogin();
$notifStmt = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM user_notifications WHERE recipient_id = ? AND read_at IS NULL");
mysqli_stmt_bind_param($notifStmt, "s", $user["user_id"]);
mysqli_stmt_execute($notifStmt);
$unreadNotifications = (int) mysqli_fetch_assoc(mysqli_stmt_get_result($notifStmt))["total"];

function rupiah($nilai)
{
    return "Rp " . number_format((int) $nilai, 0, ",", ".");
}

function statusClass($status)
{
    return "status-" . strtolower($status);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="../ui.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-success shadow-sm sticky-top">
<div class="container">
<a class="navbar-brand fw-bold" href="dashboard.php"><i class="fa-solid fa-recycle me-2"></i>Admin EcoPickup</a>
<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav"><span class="navbar-toggler-icon"></span></button>
<div class="collapse navbar-collapse" id="adminNav">
<div class="navbar-nav ms-auto align-items-lg-center">
<a class="nav-link text-white" href="permintaan.php">Permintaan</a>
<a class="nav-link text-white" href="users.php">Pengguna</a>
<a class="nav-link text-white" href="transaksi.php">Transaksi</a>
<a class="nav-link text-white" href="notifikasi.php">Notifikasi
<?php if ($unreadNotifications > 0): ?>
<span class="badge bg-warning text-dark ms-1"><?php echo $unreadNotifications; ?></span>
<?php endif; ?>
</a>
<span class="navbar-text text-white px-lg-2"><?php echo htmlspecialchars($user["full_name"]); ?></span>
<a class="nav-link text-white" href="../auth/logout.php">Logout</a>
</div>
</div>
</div>
</nav>

<main class="container py-5">
<section class="app-hero">
<h1 class="h3 fw-bold mb-2">Dashboard Operasional</h1>
<p>Pantau pengguna, permintaan penjemputan, transaksi, dan status layanan EcoPickup Palembang.</p>
</section>

<div class="row g-4 mb-4">
<div class="col-md-3 col-6"><div class="stat-card"><h3><?php echo (int) $totalUsers; ?></h3><p class="mb-0">Total User</p></div></div>
<div class="col-md-3 col-6"><div class="stat-card"><h3><?php echo (int) $totalOfficers; ?></h3><p class="mb-0">Total Petugas</p></div></div>
<div class="col-md-3 col-6"><div class="stat-card"><h3><?php echo (int) $totalRequests; ?></h3><p class="mb-0">Total Permintaan</p></div></div>
<div class="col-md-3 col-6"><div class="stat-card"><h3><?php echo (int) $completedRequests; ?></h3><p class="mb-0">Selesai</p></div></div>
</div>

<section class="app-card mb-4">
<div class="app-card-body">
<div class="d-flex justify-content-between align-items-center mb-3">
<h2 class="h5 fw-bold mb-0">Statistik Status</h2>
<strong class="text-success"><?php echo rupiah($totalRevenue); ?></strong>
</div>
<div class="row g-3">
<?php if ($statusStats && mysqli_num_rows($statusStats) > 0): ?>
<?php while ($stat = mysqli_fetch_assoc($statusStats)): ?>
<div class="col-md-3 col-6">
<div class="payment-card">
<strong><?php echo htmlspecialchars($stat["request_status"]); ?></strong>
<span class="h4 mb-0"><?php echo (int) $stat["total"]; ?></span>
</div>
</div>
<?php endwhile; ?>
<?php else: ?>
<div class="col-12 text-muted">Belum ada data status.</div>
<?php endif; ?>
</div>
</div>
</section>

<div class="row g-3 mb-4">
<div class="col-md-4"><a class="quick-link" href="users.php"><i class="fa-solid fa-users"></i>Kelola Pengguna</a></div>
<div class="col-md-4"><a class="quick-link" href="permintaan.php"><i class="fa-solid fa-clipboard-list"></i>Data Permintaan</a></div>
<div class="col-md-4"><a class="quick-link" href="transaksi.php"><i class="fa-solid fa-receipt"></i>Data Transaksi</a></div>
<div class="col-md-4"><a class="quick-link" href="../auth/register_officer.php"><i class="fa-solid fa-user-plus"></i>Tambah Petugas</a></div>
</div>

<section class="app-card">
<div class="app-card-body">
<div class="d-flex justify-content-between align-items-center mb-3">
<h2 class="h5 fw-bold mb-0">Permintaan Terbaru</h2>
<a href="permintaan.php" class="btn btn-outline-success btn-sm">Lihat Semua</a>
</div>
<div class="table-responsive">
<table class="table table-hover align-middle mb-0">
<thead>
<tr>
<th>ID</th>
<th>Nama</th>
<th>Jenis</th>
<th>Jadwal</th>
<th>Total</th>
<th>Status</th>
</tr>
</thead>
<tbody>
<?php if ($recentRequests && mysqli_num_rows($recentRequests) > 0): ?>
<?php while ($row = mysqli_fetch_assoc($recentRequests)): ?>
<tr>
<td><?php echo (int) $row["request_id"]; ?></td>
<td><?php echo htmlspecialchars($row["full_name"]); ?></td>
<td><?php echo htmlspecialchars($row["waste_type"]); ?></td>
<td><?php echo htmlspecialchars($row["pickup_date"]); ?> <?php echo htmlspecialchars(substr($row["pickup_time"], 0, 5)); ?></td>
<td><?php echo rupiah($row["total_price"]); ?></td>
<td><span class="status-badge <?php echo statusClass($row["request_status"]); ?>"><?php echo htmlspecialchars($row["request_status"]); ?></span></td>
</tr>
<?php endwhile; ?>
<?php else: ?>
<tr><td colspan="6" class="text-center py-4">Belum ada permintaan.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
</div>
</section>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
