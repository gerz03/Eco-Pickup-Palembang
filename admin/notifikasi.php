<?php
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../config/auth.php";
wajibAdmin();

$user = userLogin();
$stmt = mysqli_prepare(
    $conn,
    "SELECT n.*, r.full_name, r.request_status
     FROM user_notifications n
     LEFT JOIN pickup_requests r ON r.request_id = n.request_id
     WHERE n.recipient_id = ?
     ORDER BY n.created_at DESC"
);
mysqli_stmt_bind_param($stmt, "s", $user["user_id"]);
mysqli_stmt_execute($stmt);
$notifications = mysqli_stmt_get_result($stmt);

$mark = mysqli_prepare($conn, "UPDATE user_notifications SET read_at = NOW() WHERE recipient_id = ? AND read_at IS NULL");
mysqli_stmt_bind_param($mark, "s", $user["user_id"]);
mysqli_stmt_execute($mark);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Notifikasi Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="../ui.css">
</head>
<body>
<nav class="navbar navbar-dark bg-success shadow-sm">
<div class="container">
<a class="navbar-brand fw-bold" href="dashboard.php"><i class="fa-solid fa-arrow-left me-2"></i>Notifikasi Admin</a>
</div>
</nav>

<main class="container py-5">
<section class="app-hero">
<h1 class="h3 fw-bold mb-2">Riwayat Notifikasi</h1>
<p>Informasi operasional terbaru dari aktivitas penjemputan.</p>
</section>

<div class="table-responsive table-clean">
<table class="table table-striped table-hover align-middle mb-0">
<thead class="table-success">
<tr>
<th>Waktu</th>
<th>Permintaan</th>
<th>Pelanggan</th>
<th>Pesan</th>
<th>Baca</th>
</tr>
</thead>
<tbody>
<?php if ($notifications && mysqli_num_rows($notifications) > 0): ?>
<?php while ($row = mysqli_fetch_assoc($notifications)): ?>
<tr>
<td><?php echo htmlspecialchars($row["created_at"]); ?></td>
<td>#<?php echo (int) $row["request_id"]; ?> <span class="text-muted"><?php echo htmlspecialchars($row["request_status"] ?? ""); ?></span></td>
<td><?php echo htmlspecialchars($row["full_name"] ?? "-"); ?></td>
<td><strong><?php echo htmlspecialchars($row["title"]); ?></strong><br><span class="text-muted"><?php echo htmlspecialchars($row["message"]); ?></span></td>
<td><?php echo empty($row["read_at"]) ? '<span class="badge bg-warning text-dark">Belum dibaca</span>' : '<span class="badge bg-success">Sudah dibaca</span>'; ?></td>
</tr>
<?php endwhile; ?>
<?php else: ?>
<tr><td colspan="5" class="text-center py-4">Belum ada notifikasi.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
</main>
</body>
</html>
