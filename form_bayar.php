<?php
require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/config/security.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$requestId = (int) ($_GET["id"] ?? 0);
if ($requestId <= 0) {
    header("Location: dashboard.php");
    exit;
}

// Fetch request and transaction details
$query = "SELECT pr.*, pt.payment_status, pt.transaction_id
          FROM pickup_requests pr
          LEFT JOIN pickup_transactions pt ON pr.request_id = pt.request_id
          WHERE pr.request_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $requestId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$request = mysqli_fetch_assoc($result);

if (!$request) {
    header("Location: dashboard.php?status=error&pesan=" . urlencode("Permintaan tidak ditemukan."));
    exit;
}

$sessionUserId = $_SESSION["user_id"] ?? null;
$sessionRole = $_SESSION["role"] ?? "";
$lastRequestId = (int) ($_SESSION["last_request_id"] ?? 0);
$isOwner = !empty($request["user_id"]) && $sessionUserId === $request["user_id"];
$isAdmin = $sessionRole === "ADMIN";
$isGuestRecentRequest = empty($request["user_id"]) && $lastRequestId === $requestId;

if (!$isOwner && !$isAdmin && !$isGuestRecentRequest) {
    header("Location: auth/login.php");
    exit;
}

// If payment is already LUNAS or method is COD, redirect
if ($request['payment_status'] === 'LUNAS' || $request['payment_method'] === 'COD') {
    header("Location: dashboard.php?status=info&pesan=" . urlencode("Pembayaran sudah lunas atau menggunakan COD."));
    exit;
}

function rupiah($nilai)
{
    return "Rp " . number_format((int) $nilai, 0, ",", ".");
}

$scheme = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ? "https" : "http";
$host = $_SERVER["HTTP_HOST"] ?? "localhost";
$basePath = rtrim(str_replace("\\", "/", dirname($_SERVER["SCRIPT_NAME"] ?? "")), "/");
$confirmationUrl = $scheme . "://" . $host . $basePath . "/form_bayar.php?id=" . $requestId;
$paymentCode = "ECO-" . str_pad((string) $requestId, 6, "0", STR_PAD_LEFT);
$qrPayload = "EcoPickup Palembang\n"
    . "Kode Pembayaran: " . $paymentCode . "\n"
    . "No Permintaan: #" . $requestId . "\n"
    . "Nama: " . $request["full_name"] . "\n"
    . "Metode: " . $request["payment_method"] . "\n"
    . "Nominal: " . rupiah($request["total_price"]) . "\n"
    . "Konfirmasi: " . $confirmationUrl;
$qrImageUrl = "https://api.qrserver.com/v1/create-qr-code/?size=320x320&margin=14&data=" . rawurlencode($qrPayload);
$qrDownloadUrl = "https://api.qrserver.com/v1/create-qr-code/?size=720x720&margin=18&download=1&data=" . rawurlencode($qrPayload);

// Payment instructions based on method
$paymentInstructions = [
    'QRIS' => [
        'title' => 'Pembayaran via QRIS',
        'details' => 'Scan kode QR pembayaran di bawah ini menggunakan aplikasi pembayaran atau mobile banking. Pastikan nominal dan kode pembayaran sesuai sebelum konfirmasi.',
        'image' => $qrImageUrl
    ],
    'DANA' => [
        'title' => 'Pembayaran via DANA',
        'details' => 'Scan kode QR di bawah untuk melihat detail pembayaran, lalu transfer ke nomor DANA: <strong>0812-3456-7890</strong> (a.n. EcoPickup).',
        'image' => $qrImageUrl
    ],
    'OVO' => [
        'title' => 'Pembayaran via OVO',
        'details' => 'Scan kode QR di bawah untuk melihat detail pembayaran, lalu transfer ke nomor OVO: <strong>0812-3456-7890</strong> (a.n. EcoPickup).',
        'image' => $qrImageUrl
    ],
    'GoPay' => [
        'title' => 'Pembayaran via GoPay',
        'details' => 'Scan kode QR di bawah untuk melihat detail pembayaran, lalu transfer ke nomor GoPay: <strong>0812-3456-7890</strong> (a.n. EcoPickup).',
        'image' => $qrImageUrl
    ],
    'Transfer Bank' => [
        'title' => 'Pembayaran via Transfer Bank',
        'details' => 'Scan kode QR di bawah untuk melihat detail pembayaran, lalu transfer ke rekening BCA: <strong>1234567890</strong> (a.n. PT EcoPickup Indonesia).',
        'image' => $qrImageUrl
    ],
    // COD is handled by redirecting earlier
];

$currentInstruction = $paymentInstructions[$request['payment_method']] ?? [
    'title' => 'Instruksi Pembayaran',
    'details' => 'Silakan ikuti instruksi pembayaran untuk metode ' . htmlspecialchars($request['payment_method']) . '.',
    'image' => null
];

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Pesanan #<?php echo $requestId; ?> - EcoPickup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="ui.css">
</head>
<body class="bg-light">
    <nav class="navbar navbar-dark bg-success shadow-sm mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="dashboard.php"><i class="fa-solid fa-arrow-left me-2"></i>Kembali ke Dashboard</a>
        </div>
    </nav>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold text-success"><i class="fa-solid fa-wallet me-2"></i>Detail Pembayaran Pesanan #<?php echo $requestId; ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info border-0 shadow-sm d-flex align-items-center mb-4">
                            <i class="fa-solid fa-info-circle fs-4 me-3 text-info"></i>
                            <div>Mohon selesaikan pembayaran Anda agar permintaan penjemputan dapat segera diproses.</div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <div class="payment-card">
                                    <strong>Total yang Harus Dibayar</strong>
                                    <h4 class="text-success fw-bold mb-0"><?php echo rupiah($request['total_price']); ?></h4>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="payment-card">
                                    <strong>Metode Pembayaran</strong>
                                    <h4 class="mb-0"><?php echo htmlspecialchars($request['payment_method']); ?></h4>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="payment-card">
                                    <strong>Kode Pembayaran</strong>
                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                        <code class="fs-5 text-success"><?php echo htmlspecialchars($paymentCode); ?></code>
                                        <button type="button" class="btn btn-sm btn-outline-success" onclick="copyPaymentCode('<?php echo htmlspecialchars($paymentCode, ENT_QUOTES); ?>')">
                                            <i class="fa-solid fa-copy me-1"></i>Salin
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <h6 class="fw-bold text-muted mb-3"><i class="fa-solid fa-clipboard-list me-2"></i>Instruksi Pembayaran</h6>
                        <div class="card bg-light p-3 mb-4">
                            <h5 class="fw-bold text-dark"><?php echo $currentInstruction['title']; ?></h5>
                            <p class="mb-0"><?php echo $currentInstruction['details']; ?></p>
                            <?php if ($currentInstruction['image']): ?>
                                <div class="text-center mt-3">
                                    <div class="bg-white border rounded-3 d-inline-block p-3 shadow-sm">
                                        <img src="<?php echo htmlspecialchars($currentInstruction['image']); ?>" alt="Kode QR pembayaran <?php echo htmlspecialchars($paymentCode); ?>" class="img-fluid" style="max-width: 280px;">
                                    </div>
                                    <p class="small text-muted mt-2 mb-2">Scan QR Code di atas untuk melihat detail pembayaran.</p>
                                    <a class="btn btn-outline-success btn-sm" href="<?php echo htmlspecialchars($qrDownloadUrl); ?>" target="_blank" rel="noopener">
                                        <i class="fa-solid fa-download me-1"></i>Download QR
                                    </a>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="copyPaymentPayload()">
                                        <i class="fa-solid fa-copy me-1"></i>Salin Detail
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>

                        <h6 class="fw-bold text-muted mb-3"><i class="fa-solid fa-receipt me-2"></i>Detail Pesanan</h6>
                        <ul class="list-group list-group-flush mb-4">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Jenis Sampah
                                <span><?php echo htmlspecialchars($request['waste_type']); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Berat Estimasi
                                <span><?php echo number_format($request['weight_kg'], 2, ',', '.') . ' Kg'; ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Jadwal Penjemputan
                                <span><?php echo htmlspecialchars($request['pickup_date']) . ' ' . substr($request['pickup_time'], 0, 5); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Alamat Penjemputan
                                <span><?php echo htmlspecialchars($request['pickup_address']); ?></span>
                            </li>
                        </ul>

                        <form action="proses_pembayaran.php" method="POST">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="request_id" value="<?php echo $requestId; ?>">
                            <button type="submit" class="btn btn-success w-100 py-2 fw-bold">
                                <i class="fa-solid fa-check-circle me-2"></i>Saya Sudah Bayar
                            </button>
                        </form>
                        <div class="text-center mt-3">
                            <a href="dashboard.php" class="btn btn-link text-muted">Kembali ke Dashboard</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        const paymentPayload = <?php echo json_encode($qrPayload); ?>;

        function copyPaymentCode(code) {
            navigator.clipboard.writeText(code).then(function () {
                alert("Kode pembayaran berhasil disalin.");
            });
        }

        function copyPaymentPayload() {
            navigator.clipboard.writeText(paymentPayload).then(function () {
                alert("Detail pembayaran berhasil disalin.");
            });
        }
    </script>
</body>
</html>
