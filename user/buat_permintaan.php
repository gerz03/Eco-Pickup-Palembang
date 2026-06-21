<?php
require_once __DIR__ . "/../config/auth.php";
require_once __DIR__ . "/../config/security.php";
wajibLogin();

$user = userLogin();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Buat Permintaan</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="../ui.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-success shadow-sm sticky-top">
<div class="container">
<a class="navbar-brand fw-bold" href="dashboard.php"><i class="fa-solid fa-arrow-left me-2"></i>Buat Permintaan</a>
<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#userNav"><span class="navbar-toggler-icon"></span></button>
<div class="collapse navbar-collapse" id="userNav">
<div class="navbar-nav ms-auto">
<a class="nav-link" href="dashboard.php">Dashboard</a>
<a class="nav-link" href="notifikasi.php">Notifikasi</a>
<a class="nav-link" href="../auth/logout.php">Logout</a>
</div>
</div>
</div>
</nav>

<main class="container py-4 py-lg-5">
<section class="app-hero">
<h1 class="h3 fw-bold mb-2">Ajukan Penjemputan Sampah</h1>
<p>Lengkapi data, unggah foto, dan pantau progresnya dari dashboard.</p>
</section>

<div class="app-card">
<div class="app-card-body">
<form method="post" action="../proses_penjemputan.php" enctype="multipart/form-data" class="needs-validation" novalidate>
<?php echo csrfField(); ?>
<div class="row">
<div class="col-md-6 mb-3">
<label class="form-label">Nama Lengkap</label>
<input type="text" name="full_name" class="form-control" value="<?php echo e($user["full_name"]); ?>" required>
<div class="invalid-feedback">Nama wajib diisi.</div>
</div>
<div class="col-md-6 mb-3">
<label class="form-label">Nomor Telepon</label>
<input type="tel" name="phone_number" class="form-control" placeholder="08xxxxxxxxxx" required minlength="10">
<div class="invalid-feedback">Nomor telepon wajib diisi.</div>
</div>
</div>

<div class="mb-3">
<label class="form-label">Alamat Lengkap</label>
<textarea name="pickup_address" class="form-control" rows="3" required placeholder="Nama jalan, nomor rumah, RT/RW, kecamatan"></textarea>
<div class="invalid-feedback">Alamat lengkap wajib diisi.</div>
</div>

<div class="mb-3">
<label class="form-label">Catatan Tambahan</label>
<textarea name="notes" class="form-control" rows="2" maxlength="500" placeholder="Contoh: sampah ada di pagar depan"></textarea>
</div>

<div class="row">
<div class="col-md-4 mb-3">
<label class="form-label">Jenis Sampah</label>
<select class="form-select" id="jenis" name="waste_price" required onchange="hitungTarif()">
<option value="3000">Organik - Rp 3.000/Kg</option>
<option value="5000">Anorganik - Rp 5.000/Kg</option>
<option value="10000">B3 - Rp 10.000/Kg</option>
</select>
</div>
<div class="col-md-4 mb-3">
<label class="form-label">Berat Sampah (Kg)</label>
<input type="number" id="berat" name="weight_kg" class="form-control" min="0.1" step="0.1" required oninput="hitungTarif()">
</div>
<div class="col-md-4 mb-3">
<label class="form-label">Jarak (Km)</label>
<input type="number" id="jarak" name="distance_km" class="form-control" min="0.1" step="0.1" required oninput="hitungTarif()">
</div>
</div>

<div class="row">
<div class="col-md-6 mb-3">
<label class="form-label">Foto Sampah</label>
<input type="file" name="waste_photo" class="form-control" accept="image/jpeg,image/png,image/webp">
<div class="form-text">Opsional. Format JPG, PNG, atau WebP. Maksimal 2MB.</div>
</div>
<div class="col-md-3 mb-3">
<label class="form-label">Tanggal</label>
<input type="date" name="pickup_date" class="form-control" required>
</div>
<div class="col-md-3 mb-3">
<label class="form-label">Jam</label>
<input type="time" name="pickup_time" class="form-control" required>
</div>
</div>

<div class="row align-items-end">
<div class="col-md-6 mb-3">
<label class="form-label">Metode Pembayaran</label>
<select class="form-select" name="payment_method" required>
<option value="" disabled selected>Pilih metode</option>
<option>QRIS</option>
<option>DANA</option>
<option>OVO</option>
<option>GoPay</option>
<option>Transfer Bank</option>
<option>COD</option>
</select>
</div>
<div class="col-md-6 mb-3">
<label class="form-label">Total Tarif</label>
<input type="text" id="totalTarif" readonly class="form-control tarif-box">
</div>
</div>

<button class="btn btn-success btn-lg w-100"><i class="fa-solid fa-paper-plane me-2"></i>Kirim Permintaan</button>
</form>
</div>
</div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../pembayar.js"></script>
<script src="../assets/js/app.js"></script>
</body>
</html>
