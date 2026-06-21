<?php
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../config/auth.php";
wajibAdmin();

$sql = "SELECT t.*, r.full_name, r.phone_number, r.waste_type, r.weight_kg
        FROM pickup_transactions t
        INNER JOIN pickup_requests r ON r.request_id = t.request_id
        ORDER BY t.created_at DESC";
$result = mysqli_query($conn, $sql);

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
<title>Data Transaksi</title>
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
<div class="section-kicker">Keuangan</div>
<h2 class="section-title mb-0">Data Transaksi</h2>
</div>
<a class="btn btn-outline-success" href="permintaan.php">Lihat Permintaan</a>
</div>

<div class="table-responsive table-clean">
<table class="table table-striped table-hover align-middle">
<thead class="table-success">
<tr>
<th>ID</th>
<th>Permintaan</th>
<th>Pelanggan</th>
<th>Jenis</th>
<th>Berat Akhir</th>
<th>Total</th>
<th>Pembayaran</th>
<th>Status</th>
<th>Tanggal</th>
</tr>
</thead>
<tbody>
<?php if ($result && mysqli_num_rows($result) > 0): ?>
<?php while ($row = mysqli_fetch_assoc($result)): ?>
<tr>
<td><?php echo (int) $row["transaction_id"]; ?></td>
<td>#<?php echo (int) $row["request_id"]; ?></td>
<td>
<strong><?php echo htmlspecialchars($row["full_name"]); ?></strong><br>
<small class="text-muted"><?php echo htmlspecialchars($row["phone_number"]); ?></small>
</td>
<td><?php echo htmlspecialchars($row["waste_type"]); ?></td>
<td><?php 
    $berat = $row["total_weight_kg"] ?? $row["weight_kg"] ?? 0;
    echo number_format((float) $berat, 2, ",", "."); 
?> Kg</td>
<td><strong><?php echo rupiah($row["total_amount"]); ?></strong></td>
<td><?php echo htmlspecialchars($row["payment_method"]); ?></td>
<td><span class="status-badge <?php echo $row["payment_status"] === "LUNAS" ? "status-selesai" : "status-pending"; ?>"><?php echo htmlspecialchars($row["payment_status"]); ?></span></td>
<td><?php echo htmlspecialchars($row["created_at"]); ?></td>
</tr>
<?php endwhile; ?>
<?php else: ?>
<tr><td colspan="9" class="text-center py-4">Belum ada transaksi.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
</main>
</body>
</html>
