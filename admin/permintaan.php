<?php
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../config/auth.php";
require_once __DIR__ . "/../config/security.php";
wajibAdmin();

$search = trim($_GET["q"] ?? "");
$filterStatus = trim($_GET["status"] ?? "");
$allowedFilters = ["", "Menunggu", "Diproses", "Terangkut", "Selesai", "Dibatalkan"];
if (!in_array($filterStatus, $allowedFilters, true)) {
    $filterStatus = "";
}

$sql = "SELECT r.*, u.full_name AS officer_name
        FROM pickup_requests r
        LEFT JOIN users u ON u.user_id = r.officer_id
        WHERE (? = '' OR r.full_name LIKE CONCAT('%', ?, '%') OR r.phone_number LIKE CONCAT('%', ?, '%') OR r.pickup_address LIKE CONCAT('%', ?, '%'))
        AND (? = '' OR r.request_status = ?)
        ORDER BY r.created_at DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ssssss", $search, $search, $search, $search, $filterStatus, $filterStatus);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$officers = mysqli_query(
    $conn,
    "SELECT o.officer_id, u.full_name, o.service_area
     FROM officers o
     INNER JOIN users u ON u.user_id = o.officer_id
     ORDER BY u.full_name ASC"
);
$officerOptions = [];
if ($officers) {
    while ($officer = mysqli_fetch_assoc($officers)) {
        $officerOptions[] = $officer;
    }
}
$message = $_GET["pesan"] ?? "";

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
<title>Data Permintaan Penjemputan</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="../ui.css">
</head>
<body>
<nav class="navbar navbar-dark bg-success shadow-sm">
<div class="container">
<a class="navbar-brand fw-bold" href="dashboard.php"><i class="fa-solid fa-arrow-left me-2"></i>Admin EcoPickup</a>
</div>
</nav>

<main class="container py-5">
<div class="d-flex justify-content-between align-items-center mb-3">
<div>
<div class="section-kicker">Operasional</div>
<h2 class="section-title mb-0">Data Permintaan Penjemputan</h2>
</div>
<a class="btn btn-outline-success" href="dashboard.php">Dashboard</a>
</div>

<form class="row g-2 mb-3" method="get">
<div class="col-md-7">
<input type="search" name="q" class="form-control" value="<?php echo e($search); ?>" placeholder="Cari nama, nomor HP, atau alamat">
</div>
<div class="col-md-3">
<select name="status" class="form-select">
<option value="">Semua status</option>
<?php foreach (["Menunggu", "Diproses", "Terangkut", "Selesai", "Dibatalkan"] as $statusOption): ?>
<option value="<?php echo e($statusOption); ?>" <?php if ($filterStatus === $statusOption) echo "selected"; ?>><?php echo e($statusOption); ?></option>
<?php endforeach; ?>
</select>
</div>
<div class="col-md-2 d-grid">
<button class="btn btn-success"><i class="fa-solid fa-magnifying-glass me-1"></i>Filter</button>
</div>
</form>

<?php if ($message === "status_berhasil"): ?>
<div class="alert alert-success">Status permintaan berhasil diperbarui.</div>
<?php elseif ($message === "status_gagal"): ?>
<div class="alert alert-danger">Status permintaan gagal diperbarui.</div>
<?php elseif ($message === "assign_berhasil"): ?>
<div class="alert alert-success">Petugas berhasil ditugaskan dan user sudah menerima notifikasi.</div>
<?php elseif ($message === "assign_gagal"): ?>
<div class="alert alert-danger">Penugasan petugas gagal diproses.</div>
<?php endif; ?>

<div class="table-responsive table-clean">
<table class="table table-striped table-hover align-middle">
<thead class="table-success">
<tr>
<th>ID</th>
<th>Nama</th>
<th>No HP</th>
<th>Jenis</th>
<th>Foto</th>
<th>Berat</th>
<th>Jarak</th>
<th>Jadwal</th>
<th>Total</th>
<th>Status</th>
<th>Petugas</th>
<th>Verifikasi</th>
<th>Aksi</th>
</tr>
</thead>
<tbody>
<?php if ($result && mysqli_num_rows($result) > 0): ?>
<?php while ($row = mysqli_fetch_assoc($result)): ?>
<tr>
<td><?php echo (int) $row["request_id"]; ?></td>
<td>
<strong><?php echo htmlspecialchars($row["full_name"]); ?></strong><br>
<small class="text-muted"><?php echo htmlspecialchars($row["pickup_address"]); ?></small>
<?php if (!empty($row["notes"])): ?>
<br><small class="text-muted">Catatan: <?php echo htmlspecialchars($row["notes"]); ?></small>
<?php endif; ?>
</td>
<td><?php echo htmlspecialchars($row["phone_number"]); ?></td>
<td><?php echo htmlspecialchars($row["waste_type"]); ?></td>
<td>
<?php if (!empty($row["photo_path"])): ?>
<a href="../<?php echo htmlspecialchars($row["photo_path"]); ?>" target="_blank">
<img class="waste-photo" src="../<?php echo htmlspecialchars($row["photo_path"]); ?>" alt="Foto sampah">
</a>
<?php else: ?>
<span class="text-muted">-</span>
<?php endif; ?>
</td>
<td><?php echo number_format((float) $row["weight_kg"], 2, ",", "."); ?> Kg</td>
<td><?php echo number_format((float) $row["distance_km"], 2, ",", "."); ?> Km</td>
<td><?php echo htmlspecialchars($row["pickup_date"]); ?> <?php echo htmlspecialchars(substr($row["pickup_time"], 0, 5)); ?></td>
<td><?php echo rupiah($row["total_price"]); ?></td>
<td><span class="status-badge <?php echo statusClass($row["request_status"]); ?>"><?php echo htmlspecialchars($row["request_status"]); ?></span></td>
<td>
<form class="mini-form" method="post" action="assign_officer.php">
<?php echo csrfField(); ?>
<input type="hidden" name="request_id" value="<?php echo (int) $row["request_id"]; ?>">
<select name="officer_id" class="form-select form-select-sm" required>
<option value="">Pilih petugas</option>
<?php foreach ($officerOptions as $officer): ?>
<option value="<?php echo htmlspecialchars($officer["officer_id"]); ?>" <?php if ($row["officer_id"] === $officer["officer_id"]) echo "selected"; ?>>
<?php echo htmlspecialchars($officer["full_name"]); ?> - <?php echo htmlspecialchars($officer["service_area"]); ?>
</option>
<?php endforeach; ?>
</select>
<button class="btn btn-sm btn-outline-success">Tugaskan</button>
</form>
<?php if (!empty($row["officer_name"])): ?>
<small class="text-muted d-block mt-1">Saat ini: <?php echo htmlspecialchars($row["officer_name"]); ?></small>
<?php endif; ?>
</td>
<td>
<?php if ($row["request_status"] === "Terangkut"): ?>
<form method="post" action="update_status.php">
<?php echo csrfField(); ?>
<input type="hidden" name="request_id" value="<?php echo (int) $row["request_id"]; ?>">
<input type="hidden" name="request_status" value="Selesai">
<button class="btn btn-sm btn-success"><i class="fa-solid fa-circle-check me-1"></i>Selesai</button>
</form>
<?php elseif ($row["request_status"] === "Selesai"): ?>
<span class="text-success fw-semibold">Terverifikasi</span>
<?php else: ?>
<span class="text-muted">Menunggu petugas</span>
<?php endif; ?>
</td>
<td>
<?php if ($row["request_status"] !== "Selesai" && $row["request_status"] !== "Dibatalkan"): ?>
<a class="btn btn-sm btn-success" href="buat_transaksi.php?id=<?php echo (int) $row["request_id"]; ?>">Transaksi</a>
<?php else: ?>
<span class="text-muted">Tidak ada</span>
<?php endif; ?>
</td>
</tr>
<?php endwhile; ?>
<?php else: ?>
<tr><td colspan="13" class="text-center py-4">Belum ada permintaan.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
</main>
</body>
</html>
