<?php
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../config/auth.php";
require_once __DIR__ . "/../config/security.php";
wajibPetugas();

$user = userLogin();
$today = date('Y-m-d');

// Get comprehensive summary stats
$summaryStmt = mysqli_prepare(
    $conn,
    "SELECT
        SUM(CASE WHEN request_status = 'Diproses' THEN 1 ELSE 0 END) AS processing,
        SUM(CASE WHEN request_status = 'Menunggu' THEN 1 ELSE 0 END) AS waiting,
        SUM(CASE WHEN request_status = 'Terangkut' THEN 1 ELSE 0 END) AS carried,
        SUM(CASE WHEN request_status = 'Selesai' THEN 1 ELSE 0 END) AS completed,
        SUM(CASE WHEN DATE(pickup_date) = ? THEN 1 ELSE 0 END) AS today_total,
        SUM(CASE WHEN DATE(pickup_date) = ? AND request_status IN ('Diproses', 'Terangkut') THEN 1 ELSE 0 END) AS today_active,
        SUM(CASE WHEN DATE(pickup_date) = ? AND request_status = 'Selesai' THEN 1 ELSE 0 END) AS today_completed,
        SUM(CASE WHEN DATE(pickup_date) <= ? THEN weight_kg ELSE 0 END) AS today_weight,
        COUNT(*) AS total_all
     FROM pickup_requests
     WHERE officer_id = ?"
);
mysqli_stmt_bind_param($summaryStmt, "sssss", $today, $today, $today, $today, $user["user_id"]);
mysqli_stmt_execute($summaryStmt);
$summary = mysqli_fetch_assoc(mysqli_stmt_get_result($summaryStmt));

// Get today's tasks
$todayStmt = mysqli_prepare(
    $conn,
    "SELECT r.*, u.full_name AS customer_name, u.phone_number AS customer_phone, u.address AS customer_address
     FROM pickup_requests r
     LEFT JOIN users u ON u.user_id = r.user_id
     WHERE r.officer_id = ? AND DATE(r.pickup_date) = ?
     ORDER BY r.pickup_time ASC"
);
mysqli_stmt_bind_param($todayStmt, "ss", $user["user_id"], $today);
mysqli_stmt_execute($todayStmt);
$todayRequests = mysqli_stmt_get_result($todayStmt);

// Get all pending tasks
$stmt = mysqli_prepare(
    $conn,
    "SELECT r.*, u.full_name AS customer_name, u.phone_number AS customer_phone, u.address AS customer_address
     FROM pickup_requests r
     LEFT JOIN users u ON u.user_id = r.user_id
     WHERE r.officer_id = ? AND r.request_status IN ('Diproses', 'Menunggu')
     ORDER BY r.pickup_date ASC, r.pickup_time ASC
     LIMIT 50"
);
mysqli_stmt_bind_param($stmt, "s", $user["user_id"]);
mysqli_stmt_execute($stmt);
$pendingRequests = mysqli_stmt_get_result($stmt);

// Get recent completed
$completedStmt = mysqli_prepare(
    $conn,
    "SELECT r.*, u.full_name AS customer_name
     FROM pickup_requests r
     LEFT JOIN users u ON u.user_id = r.user_id
     WHERE r.officer_id = ? AND r.request_status = 'Selesai'
     ORDER BY r.pickup_date DESC
     LIMIT 10"
);
mysqli_stmt_bind_param($completedStmt, "s", $user["user_id"]);
mysqli_stmt_execute($completedStmt);
$completedRequests = mysqli_stmt_get_result($completedStmt);

$message = $_GET["pesan"] ?? "";

function statusClass($status)
{
    return "status-" . strtolower($status);
}

function getStatusBadgeColor($status) {
    switch($status) {
        case 'Diproses': return 'warning';
        case 'Menunggu': return 'info';
        case 'Terangkut': return 'secondary';
        case 'Selesai': return 'success';
        default: return 'secondary';
    }
}

function getStatusIcon($status) {
    switch($status) {
        case 'Diproses': return 'fa-hourglass-start';
        case 'Menunggu': return 'fa-clock';
        case 'Terangkut': return 'fa-truck';
        case 'Selesai': return 'fa-check-circle';
        default: return 'fa-question-circle';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Petugas - EcoPickup</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="../ui.css">
<style>
    :root {
        --primary: #198754;
        --light-primary: #e8f5e9;
        --dark-bg: #f8f9fa;
    }
    
    body {
        background-color: var(--dark-bg);
    }
    
    .navbar {
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .dashboard-header {
        background: linear-gradient(135deg, var(--primary) 0%, #0d5f2f 100%);
        color: white;
        padding: 2rem;
        border-radius: 8px;
        margin-bottom: 2rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    
    .dashboard-header h1 {
        font-weight: 700;
        margin-bottom: 0.5rem;
    }
    
    .dashboard-header p {
        opacity: 0.95;
        margin: 0;
    }
    
    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        border-left: 4px solid var(--primary);
        transition: transform 0.2s, box-shadow 0.2s;
        height: 100%;
    }
    
    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.12);
    }
    
    .stat-card h3 {
        font-size: 2rem;
        font-weight: 700;
        color: var(--primary);
        margin-bottom: 0.5rem;
    }
    
    .stat-card p {
        color: #6c757d;
        font-size: 0.95rem;
        margin: 0;
    }
    
    .stat-card small {
        color: #adb5bd;
        font-size: 0.85rem;
    }
    
    .stat-card.warning { border-left-color: #ffc107; }
    .stat-card.warning h3 { color: #ffc107; }
    
    .stat-card.success { border-left-color: #198754; }
    .stat-card.success h3 { color: #198754; }
    
    .stat-card.info { border-left-color: #0dcaf0; }
    .stat-card.info h3 { color: #0dcaf0; }
    
    .card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
    }
    
    .card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.12);
    }
    
    .card-header {
        background-color: white;
        border-bottom: 2px solid var(--light-primary);
        padding: 1.25rem;
        border-radius: 12px 12px 0 0 !important;
    }
    
    .card-header h5 {
        margin: 0;
        color: var(--primary);
        font-weight: 600;
    }
    
    .task-item {
        padding: 1rem;
        border-bottom: 1px solid #f0f0f0;
        border-radius: 8px;
        margin-bottom: 0.5rem;
        background: white;
        transition: all 0.2s ease;
    }
    
    .task-item:hover {
        background-color: var(--light-primary);
    }
    
    .task-item:last-child {
        border-bottom: none;
        margin-bottom: 0;
    }
    
    .task-time {
        font-weight: 600;
        color: var(--primary);
        font-size: 0.9rem;
    }
    
    .task-customer {
        font-weight: 600;
        color: #333;
        margin-bottom: 0.25rem;
    }
    
    .task-address {
        font-size: 0.9rem;
        color: #6c757d;
        margin-bottom: 0.5rem;
    }
    
    .status-badge {
        display: inline-block;
        padding: 0.4rem 0.8rem;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
    }
    
    .status-diproses {
        background-color: #fff3cd;
        color: #856404;
    }
    
    .status-menunggu {
        background-color: #d1ecf1;
        color: #0c5460;
    }
    
    .status-terangkut {
        background-color: #e2e3e5;
        color: #383d41;
    }
    
    .status-selesai {
        background-color: #d4edda;
        color: #155724;
    }
    
    .btn-action {
        padding: 0.4rem 0.8rem;
        font-size: 0.85rem;
        border-radius: 6px;
        transition: all 0.2s ease;
    }
    
    .btn-action:hover {
        transform: scale(1.05);
    }
    
    .quick-action-btn {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 1.5rem;
        background: white;
        border: 2px solid transparent;
        border-radius: 12px;
        text-decoration: none;
        color: var(--primary);
        transition: all 0.2s ease;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    
    .quick-action-btn:hover {
        border-color: var(--primary);
        background-color: var(--light-primary);
        color: var(--primary);
        transform: translateY(-4px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.12);
    }
    
    .quick-action-btn i {
        font-size: 2rem;
        margin-bottom: 0.5rem;
    }
    
    .quick-action-btn span {
        font-weight: 600;
        font-size: 0.95rem;
    }
    
    .page-section {
        margin-bottom: 2rem;
    }
    
    .section-title {
        color: #333;
        font-weight: 700;
        font-size: 1.25rem;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .section-title i {
        color: var(--primary);
    }
    
    .empty-state {
        text-align: center;
        padding: 3rem 1rem;
        color: #6c757d;
    }
    
    .empty-state i {
        font-size: 3rem;
        color: #dee2e6;
        margin-bottom: 1rem;
    }
    
    .task-table {
        font-size: 0.95rem;
    }
    
    .task-table thead {
        background-color: var(--light-primary);
    }
    
    .task-table tbody tr {
        border-bottom: 1px solid #f0f0f0;
        transition: all 0.2s ease;
    }
    
    .task-table tbody tr:hover {
        background-color: var(--light-primary);
    }
    
    .badge-icon {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.4rem 0.8rem;
        background-color: var(--light-primary);
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--primary);
    }
    
    .modal-header {
        background-color: var(--light-primary);
        border-bottom: 2px solid var(--primary);
    }
    
    .modal-header .btn-close {
        filter: invert(0.5);
    }
    
    @media (max-width: 768px) {
        .dashboard-header {
            padding: 1.5rem;
        }
        
        .dashboard-header h1 {
            font-size: 1.5rem;
        }
        
        .stat-card h3 {
            font-size: 1.5rem;
        }
    }
</style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-success sticky-top">
<div class="container-fluid">
    <a class="navbar-brand fw-bold" href="dashboard.php">
        <i class="fa-solid fa-truck-fast me-2"></i>EcoPickup - Petugas
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#officeNav">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="officeNav">
        <div class="navbar-nav ms-auto align-items-lg-center">
            <span class="navbar-text text-white px-2">
                <i class="fa-solid fa-user-circle me-1"></i>
                <?php echo htmlspecialchars($user["full_name"]); ?>
            </span>
            <a class="nav-link text-white" href="../auth/logout.php">
                <i class="fa-solid fa-sign-out-alt me-1"></i>Logout
            </a>
        </div>
    </div>
</div>
</nav>

<main class="container-fluid py-4">
    <!-- Header -->
    <div class="dashboard-header">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1><i class="fa-solid fa-chart-line me-2"></i>Dashboard Petugas</h1>
                <p>Kelola tugas penjemputan sampah Anda dengan mudah</p>
            </div>
            <div class="col-md-6 text-md-end">
                <p class="mb-1"><i class="fa-solid fa-calendar-today me-2"></i><strong><?php echo date('d F Y', strtotime($today)); ?></strong></p>
                <p class="mb-0"><i class="fa-solid fa-clock me-2"></i><strong id="current-time"><?php echo date('H:i:s'); ?></strong></p>
            </div>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if ($message === "terangkut_berhasil"): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-check-circle me-2"></i>
            <strong>Berhasil!</strong> Status penjemputan telah diubah menjadi Terangkut.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif ($message === "terangkut_gagal"): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-exclamation-circle me-2"></i>
            <strong>Error!</strong> Gagal memperbarui status penjemputan.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Statistics -->
    <div class="page-section">
        <div class="row g-3">
            <div class="col-md-6 col-lg-3">
                <div class="stat-card">
                    <h3><?php echo (int)($summary["today_active"] ?? 0); ?></h3>
                    <p>Tugas Hari Ini</p>
                    <small><?php echo (int)($summary["today_completed"] ?? 0); ?> selesai</small>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="stat-card warning">
                    <h3><?php echo (int)($summary["processing"] ?? 0); ?></h3>
                    <p>Sedang Diproses</p>
                    <small>Menunggu penyelesaian</small>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="stat-card success">
                    <h3><?php echo (int)($summary["completed"] ?? 0); ?></h3>
                    <p>Telah Selesai</p>
                    <small>Total sepanjang waktu</small>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="stat-card info">
                    <h3><?php echo number_format((float)($summary["today_weight"] ?? 0), 1, ",", "."); ?></h3>
                    <p>Berat (Kg)</p>
                    <small>Total hari ini</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Today's Tasks -->
        <div class="col-lg-7">
            <div class="page-section">
                <h2 class="section-title">
                    <i class="fa-solid fa-clipboard-list"></i>
                    Tugas Hari Ini
                </h2>
                <div class="card">
                    <?php if ($todayRequests && mysqli_num_rows($todayRequests) > 0): ?>
                        <div class="card-body p-0">
                            <?php while ($row = mysqli_fetch_assoc($todayRequests)): ?>
                                <div class="task-item">
                                    <div class="row align-items-start g-2">
                                        <div class="col-md-7">
                                            <div class="task-time">
                                                <i class="fa-solid fa-clock me-1"></i><?php echo substr($row["pickup_time"], 0, 5); ?> WIB
                                            </div>
                                            <div class="task-customer">
                                                <i class="fa-solid fa-user me-1"></i><?php echo htmlspecialchars($row["customer_name"] ?: "Pelanggan"); ?>
                                            </div>
                                            <div class="task-address">
                                                <i class="fa-solid fa-location-dot me-1"></i><?php echo htmlspecialchars(substr($row["pickup_address"], 0, 50)); ?>...
                                            </div>
                                            <div style="font-size: 0.85rem; color: #6c757d;">
                                                <i class="fa-solid fa-weight me-1"></i><?php echo number_format((float)$row["weight_kg"], 2, ",", "."); ?> Kg | 
                                                <i class="fa-solid fa-leaf me-1"></i><?php echo htmlspecialchars($row["waste_type"]); ?>
                                            </div>
                                        </div>
                                        <div class="col-md-5 text-md-end">
                                            <div class="mb-2">
                                                <span class="status-badge status-<?php echo strtolower(str_replace(" ", "", $row["request_status"])); ?>">
                                                    <i class="fa-solid <?php echo getStatusIcon($row["request_status"]); ?> me-1"></i>
                                                    <?php echo htmlspecialchars($row["request_status"]); ?>
                                                </span>
                                            </div>
                                            <div>
                                                <?php if ($row["request_status"] === "Diproses" || $row["request_status"] === "Menunggu"): ?>
                                                    <form method="post" action="update_terangkut.php" class="d-inline">
                                                        <?php echo csrfField(); ?>
                                                        <input type="hidden" name="request_id" value="<?php echo (int) $row["request_id"]; ?>">
                                                        <button class="btn btn-sm btn-success btn-action" title="Tandai sebagai Terangkut">
                                                            <i class="fa-solid fa-check me-1"></i>Terangkut
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                <a class="btn btn-sm btn-outline-primary btn-action" target="_blank" 
                                                   href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($row["pickup_address"]); ?>" 
                                                   title="Buka lokasi di Google Maps">
                                                    <i class="fa-solid fa-map me-1"></i>Peta
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fa-solid fa-calendar-check"></i>
                            <p><strong>Tidak ada tugas hari ini</strong></p>
                            <p class="small">Anda tidak memiliki penjemputan yang dijadwalkan untuk hari ini.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Pending Tasks -->
            <div class="page-section">
                <h2 class="section-title">
                    <i class="fa-solid fa-hourglass-end"></i>
                    Tugas Menunggu
                </h2>
                <div class="card">
                    <div class="table-responsive">
                        <table class="table table-hover task-table mb-0">
                            <thead>
                                <tr>
                                    <th>Jadwal</th>
                                    <th>Pelanggan</th>
                                    <th>Lokasi</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($pendingRequests && mysqli_num_rows($pendingRequests) > 0): ?>
                                    <?php while ($row = mysqli_fetch_assoc($pendingRequests)): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo date('d/m', strtotime($row["pickup_date"])); ?></strong>
                                                <br>
                                                <small class="text-muted"><?php echo substr($row["pickup_time"], 0, 5); ?> WIB</small>
                                            </td>
                                            <td><?php echo htmlspecialchars(substr($row["customer_name"] ?: "Pelanggan", 0, 20)); ?></td>
                                            <td class="text-muted" style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;">
                                                <small><?php echo htmlspecialchars(substr($row["pickup_address"], 0, 30)); ?>...</small>
                                            </td>
                                            <td>
                                                <span class="status-badge status-<?php echo strtolower(str_replace(" ", "", $row["request_status"])); ?>">
                                                    <?php echo htmlspecialchars($row["request_status"]); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($row["request_status"] === "Diproses" || $row["request_status"] === "Menunggu"): ?>
                                                    <form method="post" action="update_terangkut.php" class="d-inline">
                                                        <?php echo csrfField(); ?>
                                                        <input type="hidden" name="request_id" value="<?php echo (int) $row["request_id"]; ?>">
                                                        <button class="btn btn-xs btn-success btn-action" title="Tandai Terangkut">
                                                            <i class="fa-solid fa-check"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                <a class="btn btn-xs btn-outline-primary btn-action" target="_blank" 
                                                   href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($row["pickup_address"]); ?>">
                                                    <i class="fa-solid fa-map"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">
                                            <i class="fa-solid fa-check-circle me-2"></i>Tidak ada tugas yang menunggu!
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-5">
            <!-- Recently Completed -->
            <div class="page-section">
                <h2 class="section-title">
                    <i class="fa-solid fa-check-double"></i>
                    Tugas Selesai (10 Terakhir)
                </h2>
                <div class="card">
                    <?php if ($completedRequests && mysqli_num_rows($completedRequests) > 0): ?>
                        <div class="card-body p-0">
                            <?php while ($row = mysqli_fetch_assoc($completedRequests)): ?>
                                <div style="padding: 1rem; border-bottom: 1px solid #f0f0f0;">
                                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem;">
                                        <strong style="color: #333; font-size: 0.95rem;">
                                            <?php echo htmlspecialchars(substr($row["customer_name"] ?: "Pelanggan", 0, 25)); ?>
                                        </strong>
                                        <span style="font-size: 0.8rem; color: #198754; background: var(--light-primary); padding: 0.25rem 0.6rem; border-radius: 12px; font-weight: 600;">
                                            Selesai
                                        </span>
                                    </div>
                                    <p style="font-size: 0.85rem; color: #6c757d; margin: 0;">
                                        <i class="fa-solid fa-calendar-alt me-1"></i><?php echo date('d/m/Y', strtotime($row["pickup_date"])); ?>
                                    </p>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fa-solid fa-inbox"></i>
                            <p><strong>Belum ada tugas selesai</strong></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="page-section">
                <h2 class="section-title">
                    <i class="fa-solid fa-bolt"></i>
                    Aksi Cepat
                </h2>
                <div class="row g-2">
                    <div class="col-6">
                        <a href="https://www.google.com/maps" class="quick-action-btn" target="_blank" title="Buka Google Maps">
                            <i class="fa-solid fa-map"></i>
                            <span>Google Maps</span>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="tel:" class="quick-action-btn" title="Hubungi Pusat">
                            <i class="fa-solid fa-phone"></i>
                            <span>Hubungi Pusat</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Profile Card -->
            <div class="page-section">
                <div class="card" style="background: linear-gradient(135deg, var(--primary) 0%, #0d5f2f 100%); color: white; border: none;">
                    <div class="card-body">
                        <h5 class="card-title mb-3">
                            <i class="fa-solid fa-user-circle me-2"></i><?php echo htmlspecialchars($user["full_name"]); ?>
                        </h5>
                        <p class="mb-2">
                            <small><i class="fa-solid fa-phone me-2"></i><?php echo htmlspecialchars($user["phone_number"] ?? "Belum diisi"); ?></small>
                        </p>
                        <p class="mb-0">
                            <small><i class="fa-solid fa-badge me-2"></i><?php echo htmlspecialchars($user["user_id"]); ?></small>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<footer style="background-color: #f8f9fa; padding: 2rem; text-align: center; color: #6c757d; margin-top: 3rem; border-top: 1px solid #dee2e6;">
    <p style="margin: 0;">
        <i class="fa-solid fa-leaf me-1"></i>EcoPickup Palembang - Sistem Penjemputan Sampah Digital
    </p>
    <p style="margin: 0; font-size: 0.9rem;">© <?php echo date('Y'); ?> All rights reserved.</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Update time every second
setInterval(() => {
    const now = new Date();
    document.getElementById('current-time').textContent = 
        String(now.getHours()).padStart(2, '0') + ':' +
        String(now.getMinutes()).padStart(2, '0') + ':' +
        String(now.getSeconds()).padStart(2, '0');
}, 1000);

// Auto-hide alerts after 5 seconds
document.addEventListener('DOMContentLoaded', () => {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
});
</script>
</body>
</html>
