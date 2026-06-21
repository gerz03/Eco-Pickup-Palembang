<?php
require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/config/activity.php";
require_once __DIR__ . "/config/security.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function kembaliDenganPesan($pesan)
{
    header("Location: uiRPL.html?status=gagal&pesan=" . urlencode($pesan));
    exit;
}

function hitungBiayaJarak($jarak)
{
    if ($jarak <= 0) {
        return 0;
    }

    if ($jarak <= 3) {
        return 5000;
    }

    if ($jarak <= 7) {
        return 10000;
    }

    if ($jarak <= 10) {
        return 15000;
    }

    return 20000;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: uiRPL.html");
    exit;
}

$fullName = trim($_POST["full_name"] ?? "");
$phoneNumber = trim($_POST["phone_number"] ?? "");
$pickupAddress = trim($_POST["pickup_address"] ?? "");
$notes = bersihkanInput($_POST["notes"] ?? "");
$pricePerKg = (int) ($_POST["waste_price"] ?? 0);
$weightKg = (float) ($_POST["weight_kg"] ?? 0);
$distanceKm = (float) ($_POST["distance_km"] ?? 0);
$pickupDate = $_POST["pickup_date"] ?? "";
$pickupTime = $_POST["pickup_time"] ?? "";
$paymentMethod = $_POST["payment_method"] ?? "";
$userId = $_SESSION["user_id"] ?? null;
$role = $_SESSION["role"] ?? "GUEST";

$wasteTypes = [
    3000 => "Organik",
    5000 => "Anorganik",
    10000 => "B3"
];

$paymentMethods = ["QRIS", "DANA", "OVO", "GoPay", "Transfer Bank", "COD"];

if ($fullName === "" || $phoneNumber === "" || $pickupAddress === "") {
    kembaliDenganPesan("Nama, nomor HP, dan alamat wajib diisi.");
}

if (!array_key_exists($pricePerKg, $wasteTypes)) {
    kembaliDenganPesan("Jenis sampah tidak valid.");
}

if ($weightKg <= 0 || $distanceKm <= 0) {
    kembaliDenganPesan("Berat dan jarak harus lebih dari 0.");
}

if ($pickupDate === "" || $pickupTime === "") {
    kembaliDenganPesan("Tanggal dan jam penjemputan wajib diisi.");
}

if (!in_array($paymentMethod, $paymentMethods, true)) {
    kembaliDenganPesan("Metode pembayaran tidak valid.");
}

if (isset($_POST["csrf_token"]) && !validasiCsrf()) {
    kembaliDenganPesan("Token keamanan tidak valid. Muat ulang halaman dan coba lagi.");
}

$photoPath = null;
if (!empty($_FILES["waste_photo"]["name"])) {
    if ($_FILES["waste_photo"]["error"] !== UPLOAD_ERR_OK) {
        kembaliDenganPesan("Upload foto gagal diproses.");
    }

    if ($_FILES["waste_photo"]["size"] > 2 * 1024 * 1024) {
        kembaliDenganPesan("Ukuran foto maksimal 2MB.");
    }

    $tmpName = $_FILES["waste_photo"]["tmp_name"];
    $mimeType = mime_content_type($tmpName);
    $allowedMimes = [
        "image/jpeg" => "jpg",
        "image/png" => "png",
        "image/webp" => "webp"
    ];

    if (!isset($allowedMimes[$mimeType])) {
        kembaliDenganPesan("Format foto harus JPG, PNG, atau WebP.");
    }

    $uploadDir = __DIR__ . "/uploads/waste";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }

    $fileName = "waste_" . date("YmdHis") . "_" . bin2hex(random_bytes(4)) . "." . $allowedMimes[$mimeType];
    $targetPath = $uploadDir . "/" . $fileName;

    if (!move_uploaded_file($tmpName, $targetPath)) {
        kembaliDenganPesan("Foto gagal disimpan.");
    }

    $photoPath = "uploads/waste/" . $fileName;
}

$wasteType = $wasteTypes[$pricePerKg];
$distanceFee = hitungBiayaJarak($distanceKm);
$totalPrice = (int) (($pricePerKg * $weightKg) + $distanceFee);

$sql = "INSERT INTO pickup_requests (
    user_id,
    full_name,
    phone_number,
    pickup_address,
    notes,
    photo_path,
    waste_type,
    price_per_kg,
    weight_kg,
    distance_km,
    distance_fee,
    total_price,
    pickup_date,
    pickup_time,
    payment_method
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    kembaliDenganPesan("Query database gagal disiapkan.");
}

mysqli_begin_transaction($conn);

mysqli_stmt_bind_param(
    $stmt,
    "sssssssiddiisss",
    $userId,
    $fullName,
    $phoneNumber,
    $pickupAddress,
    $notes,
    $photoPath,
    $wasteType,
    $pricePerKg,
    $weightKg,
    $distanceKm,
    $distanceFee,
    $totalPrice,
    $pickupDate,
    $pickupTime,
    $paymentMethod
);

if (!mysqli_stmt_execute($stmt)) {
    mysqli_rollback($conn);
    kembaliDenganPesan("Data gagal disimpan ke database.");
}

$requestId = mysqli_insert_id($conn);
$_SESSION["last_request_id"] = $requestId;

// Insert into pickup_transactions
$transaction_payment_status = ($paymentMethod === 'COD') ? 'LUNAS' : 'PENDING';

$sql_trans = "INSERT INTO pickup_transactions (
    request_id,
    total_weight_kg,
    total_amount,
    payment_method,
    payment_status
) VALUES (?, ?, ?, ?, ?)";

$stmt_trans = mysqli_prepare($conn, $sql_trans);
if (!$stmt_trans) {
    mysqli_rollback($conn);
    kembaliDenganPesan("Gagal menyiapkan transaksi pembayaran.");
}

mysqli_stmt_bind_param(
    $stmt_trans,
    "iddss",
    $requestId,
    $weightKg, // total_weight_kg
    $totalPrice, // total_amount
    $paymentMethod,
    $transaction_payment_status
);

if (!mysqli_stmt_execute($stmt_trans)) {
    mysqli_rollback($conn);
    kembaliDenganPesan("Gagal menyimpan transaksi pembayaran.");
}

catatAktivitas($conn, $userId, $role, "CREATE_PICKUP_REQUEST", "Membuat permintaan penjemputan sampah.", $requestId);
mysqli_commit($conn);

if ($transaction_payment_status === 'PENDING') {
    header("Location: form_bayar.php?id=" . $requestId);
} else { // LUNAS (for COD)
    header("Location: sukses_penjemputan.php?id=" . $requestId);
}
exit;
