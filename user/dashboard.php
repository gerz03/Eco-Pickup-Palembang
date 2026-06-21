<?php
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../config/auth.php";
wajibLogin();

$user = userLogin();
$summaryStmt = mysqli_prepare(
    $conn,
    "SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN request_status IN ('Menunggu', 'Diproses', 'Terangkut') THEN 1 ELSE 0 END) AS active_total,
        SUM(CASE WHEN request_status = 'Selesai' THEN 1 ELSE 0 END) AS completed_total
     FROM pickup_requests
     WHERE user_id = ?"
);
mysqli_stmt_bind_param($summaryStmt, "s", $user["user_id"]);
mysqli_stmt_execute($summaryStmt);
$summary = mysqli_fetch_assoc(mysqli_stmt_get_result($summaryStmt));

$latestStmt = mysqli_prepare($conn, "SELECT request_status FROM pickup_requests WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
mysqli_stmt_bind_param($latestStmt, "s", $user["user_id"]);
mysqli_stmt_execute($latestStmt);
$latest = mysqli_fetch_assoc(mysqli_stmt_get_result($latestStmt));

$stmt = mysqli_prepare($conn, "SELECT * FROM pickup_requests WHERE user_id = ? ORDER BY created_at DESC");
mysqli_stmt_bind_param($stmt, "s", $user["user_id"]);
mysqli_stmt_execute($stmt);
$requests = mysqli_stmt_get_result($stmt);

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

function timelineClass($current, $step)
{
    $order = ["Menunggu" => 1, "Diproses" => 2, "Terangkut" => 3, "Selesai" => 4];
    return ($order[$current] ?? 0) >= ($order[$step] ?? 99) ? "is-done" : "";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard User</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="../ui.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-success shadow-sm sticky-top">
<div class="container">
<a class="navbar-brand fw-bold" href="dashboard.php"><i class="fa-solid fa-recycle me-2"></i>EcoPickup User</a>
<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#userNav"><span class="navbar-toggler-icon"></span></button>
<div class="collapse navbar-collapse" id="userNav">
<div class="navbar-nav ms-auto align-items-lg-center">
<a class="nav-link text-white" href="buat_permintaan.php">Buat Permintaan</a>
<a class="nav-link text-white" href="notifikasi.php">
<i class="fa-solid fa-bell"></i> Notifikasi
<?php if ($unreadNotifications > 0): ?>
<span id="notificationBadge" class="badge bg-warning text-dark"><?php echo $unreadNotifications; ?></span>
<?php else: ?>
<span id="notificationBadge" class="badge bg-warning text-dark d-none">0</span>
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
<h1 class="h3 fw-bold mb-2">Halo, <?php echo htmlspecialchars($user["full_name"]); ?></h1>
<p>Status terbaru: <?php echo htmlspecialchars($latest["request_status"] ?? "Belum ada permintaan"); ?></p>
</section>

<div class="row g-3 mb-4">
<div class="col-md-3 col-6"><div class="stat-card"><h3><?php echo (int) ($summary["total"] ?? 0); ?></h3><p class="mb-0">Total Permintaan</p></div></div>
<div class="col-md-3 col-6"><div class="stat-card"><h3><?php echo (int) ($summary["active_total"] ?? 0); ?></h3><p class="mb-0">Aktif</p></div></div>
<div class="col-md-3 col-6"><div class="stat-card"><h3><?php echo (int) ($summary["completed_total"] ?? 0); ?></h3><p class="mb-0">Riwayat Selesai</p></div></div>
<div class="col-md-3 col-6"><div class="stat-card"><h3><?php echo (int) $unreadNotifications; ?></h3><p class="mb-0">Notifikasi Baru</p></div></div>
</div>

<div class="row g-3 mb-4">
<div class="col-md-6"><a href="buat_permintaan.php" class="quick-link"><i class="fa-solid fa-truck-fast"></i>Buat Permintaan Penjemputan</a></div>
<div class="col-md-3"><a href="notifikasi.php" class="quick-link"><i class="fa-solid fa-bell"></i>Notifikasi Saya</a></div>
<div class="col-md-3"><a href="../uiRPL.html" class="quick-link"><i class="fa-solid fa-house"></i>Kembali ke Beranda</a></div>
</div>

<section class="app-card">
<div class="app-card-body">
<h2 class="h5 fw-bold mb-3">Riwayat Permintaan Saya</h2>
<div class="table-responsive">
<table class="table table-striped table-hover align-middle mb-0">
<thead class="table-success">
<tr>
<th>ID</th>
<th>Jenis</th>
<th>Berat</th>
<th>Jarak</th>
<th>Jadwal</th>
<th>Total</th>
<th>Status</th>
<th>Progress</th>
</tr>
</thead>
<tbody>
<?php if ($requests && mysqli_num_rows($requests) > 0): ?>
<?php while ($row = mysqli_fetch_assoc($requests)): ?>
<tr>
<td>#<?php echo (int) $row["request_id"]; ?></td>
<td><?php echo htmlspecialchars($row["waste_type"]); ?></td>
<td><?php echo number_format((float) $row["weight_kg"], 2, ",", "."); ?> Kg</td>
<td><?php echo number_format((float) $row["distance_km"], 2, ",", "."); ?> Km</td>
<td><?php echo htmlspecialchars($row["pickup_date"]); ?> <?php echo htmlspecialchars(substr($row["pickup_time"], 0, 5)); ?></td>
<td><?php echo rupiah($row["total_price"]); ?></td>
<td><span class="status-badge <?php echo statusClass($row["request_status"]); ?>"><?php echo htmlspecialchars($row["request_status"]); ?></span></td>
<td>
<div class="status-timeline compact">
<span class="<?php echo timelineClass($row["request_status"], "Menunggu"); ?>">Menunggu</span>
<span class="<?php echo timelineClass($row["request_status"], "Diproses"); ?>">Diproses</span>
<span class="<?php echo timelineClass($row["request_status"], "Terangkut"); ?>">Terangkut</span>
<span class="<?php echo timelineClass($row["request_status"], "Selesai"); ?>">Selesai</span>
</div>
</td>
</tr>
<?php endwhile; ?>
<?php else: ?>
<tr><td colspan="8" class="text-center py-4">Belum ada permintaan dari akun ini.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
</div>
</section>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/app.js"></script>
</body>
</html>
