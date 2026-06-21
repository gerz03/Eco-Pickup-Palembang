<?php
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../config/auth.php";
require_once __DIR__ . "/../config/security.php";
wajibPetugas();

$user = userLogin();
$summaryStmt = mysqli_prepare(
    $conn,
    "SELECT
        SUM(CASE WHEN request_status IN ('Diproses', 'Menunggu') THEN 1 ELSE 0 END) AS active_total,
        SUM(CASE WHEN request_status IN ('Terangkut', 'Selesai') THEN 1 ELSE 0 END) AS completed_total,
        COUNT(*) AS history_total
     FROM pickup_requests
     WHERE officer_id = ?"
);
mysqli_stmt_bind_param($summaryStmt, "s", $user["user_id"]);
mysqli_stmt_execute($summaryStmt);
$summary = mysqli_fetch_assoc(mysqli_stmt_get_result($summaryStmt));

$stmt = mysqli_prepare(
    $conn,
    "SELECT r.*, u.full_name AS customer_name, u.phone_number AS customer_phone, u.address AS customer_address
     FROM pickup_requests r
     LEFT JOIN users u ON u.user_id = r.user_id
     WHERE r.officer_id = ?
     ORDER BY r.pickup_date ASC, r.pickup_time ASC"
);
mysqli_stmt_bind_param($stmt, "s", $user["user_id"]);
mysqli_stmt_execute($stmt);
$requests = mysqli_stmt_get_result($stmt);
$message = $_GET["pesan"] ?? "";

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
<title>Dashboard Petugas</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="../ui.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-success shadow-sm sticky-top">
<div class="container">
<a class="navbar-brand fw-bold" href="dashboard.php"><i class="fa-solid fa-truck-fast me-2"></i>Dashboard Petugas</a>
<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#officerNav"><span class="navbar-toggler-icon"></span></button>
<div class="collapse navbar-collapse" id="officerNav">
<div class="navbar-nav ms-auto align-items-lg-center">
<span class="navbar-text text-white px-lg-2"><?php echo htmlspecialchars($user["full_name"]); ?></span>
<a class="nav-link text-white" href="../auth/logout.php">Logout</a>
</div>
</div>
</div>
</nav>

<main class="container py-5">
<section class="app-hero">
<h1 class="h3 fw-bold mb-2">Tugas Penjemputan</h1>
<p>Daftar permintaan yang sudah ditugaskan kepada Anda.</p>
</section>

<div class="row g-3 mb-4">
<div class="col-md-4 col-6"><div class="stat-card"><h3><?php echo (int) ($summary["active_total"] ?? 0); ?></h3><p class="mb-0">Tugas Aktif</p></div></div>
<div class="col-md-4 col-6"><div class="stat-card"><h3><?php echo (int) ($summary["completed_total"] ?? 0); ?></h3><p class="mb-0">Tugas Selesai</p></div></div>
<div class="col-md-4"><div class="stat-card"><h3><?php echo (int) ($summary["history_total"] ?? 0); ?></h3><p class="mb-0">Riwayat Tugas</p></div></div>
</div>

<?php if ($message === "terangkut_berhasil"): ?>
<div class="alert alert-success">Status penjemputan berhasil diubah menjadi Terangkut.</div>
<?php elseif ($message === "terangkut_gagal"): ?>
<div class="alert alert-danger">Status penjemputan gagal diperbarui.</div>
<?php endif; ?>

<div class="table-responsive table-clean">
<table class="table table-striped table-hover align-middle">
<thead class="table-success">
<tr>
<th>ID</th>
<th>Nama Pelanggan</th>
<th>Alamat Lengkap</th>
<th>No Telepon</th>
<th>Jenis</th>
<th>Berat</th>
<th>Foto</th>
<th>Tanggal Penjemputan</th>
<th>Status</th>
<th>Aksi</th>
</tr>
</thead>
<tbody>
<?php if ($requests && mysqli_num_rows($requests) > 0): ?>
<?php while ($row = mysqli_fetch_assoc($requests)): ?>
<tr>
<td>#<?php echo (int) $row["request_id"]; ?></td>
<td><?php echo htmlspecialchars($row["customer_name"] ?: $row["full_name"]); ?></td>
<td><?php echo htmlspecialchars($row["pickup_address"]); ?></td>
<td><?php echo htmlspecialchars($row["customer_phone"] ?: $row["phone_number"]); ?></td>
<td><?php echo htmlspecialchars($row["waste_type"]); ?></td>
<td><?php echo number_format((float) $row["weight_kg"], 2, ",", "."); ?> Kg</td>
<td>
<?php if (!empty($row["photo_path"])): ?>
<a href="../<?php echo htmlspecialchars($row["photo_path"]); ?>" target="_blank">
<img class="waste-photo" src="../<?php echo htmlspecialchars($row["photo_path"]); ?>" alt="Foto sampah">
</a>
<?php else: ?>
<span class="text-muted">-</span>
<?php endif; ?>
</td>
<td><?php echo htmlspecialchars($row["pickup_date"]); ?> <?php echo htmlspecialchars(substr($row["pickup_time"], 0, 5)); ?></td>
<td><span class="status-badge <?php echo statusClass($row["request_status"]); ?>"><?php echo htmlspecialchars($row["request_status"]); ?></span></td>
<td>
<?php if ($row["request_status"] === "Diproses" || $row["request_status"] === "Menunggu"): ?>
<form method="post" action="update_terangkut.php">
<?php echo csrfField(); ?>
<input type="hidden" name="request_id" value="<?php echo (int) $row["request_id"]; ?>">
<button class="btn btn-sm btn-success"><i class="fa-solid fa-check me-1"></i>Terangkut</button>
</form>
<a class="btn btn-sm btn-outline-primary mt-1" target="_blank" href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($row["pickup_address"]); ?>">
<i class="fa-solid fa-location-dot me-1"></i>Lokasi
</a>
<?php else: ?>
<a class="btn btn-sm btn-outline-primary" target="_blank" href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($row["pickup_address"]); ?>">
<i class="fa-solid fa-location-dot me-1"></i>Lokasi
</a>
<?php endif; ?>
</td>
</tr>
<?php endwhile; ?>
<?php else: ?>
<tr><td colspan="10" class="text-center py-4">Belum ada tugas penjemputan.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
