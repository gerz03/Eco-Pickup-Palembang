<?php
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../config/auth.php";
wajibAdmin();

$result = mysqli_query($conn, "SELECT user_id, full_name, email, phone_number, address, auth_provider, role, created_at FROM users ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kelola Pengguna</title>
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
<div class="section-kicker">Akun</div>
<h2 class="section-title">Kelola Pengguna</h2>
<div class="table-responsive table-clean">
<table class="table table-striped table-hover align-middle">
<thead class="table-success">
<tr>
<th>ID</th>
<th>Pengguna</th>
<th>No HP</th>
<th>Alamat</th>
<th>Login</th>
<th>Role</th>
<th>Terdaftar</th>
</tr>
</thead>
<tbody>
<?php if ($result && mysqli_num_rows($result) > 0): ?>
<?php while ($row = mysqli_fetch_assoc($result)): ?>
<tr>
<td><?php echo htmlspecialchars($row["user_id"]); ?></td>
<td>
<strong><?php echo htmlspecialchars($row["full_name"]); ?></strong><br>
<small class="text-muted"><?php echo htmlspecialchars($row["email"]); ?></small>
</td>
<td><?php echo htmlspecialchars($row["phone_number"]); ?></td>
<td><?php echo htmlspecialchars($row["address"]); ?></td>
<td><span class="status-badge <?php echo $row["auth_provider"] === "GOOGLE" ? "status-dijemput" : "status-pending"; ?>"><?php echo htmlspecialchars($row["auth_provider"]); ?></span></td>
<td><strong><?php echo htmlspecialchars($row["role"]); ?></strong></td>
<td><?php echo htmlspecialchars($row["created_at"]); ?></td>
</tr>
<?php endwhile; ?>
<?php else: ?>
<tr><td colspan="7" class="text-center py-4">Belum ada pengguna.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
</main>
</body>
</html>
