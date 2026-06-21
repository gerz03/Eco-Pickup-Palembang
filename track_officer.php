<?php
require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/config/auth.php";
wajibLogin();

$requestId = (int) ($_GET["id"] ?? 0);
if ($requestId <= 0) {
    header("Location: dashboard.php");
    exit;
}

$stmt = mysqli_prepare($conn, "SELECT * FROM pickup_requests WHERE request_id = ?");
mysqli_stmt_bind_param($stmt, "i", $requestId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$request = mysqli_fetch_assoc($result);

if (!$request || $request['request_status'] !== 'Diproses') {
    header("Location: dashboard.php");
    exit;
}

$sessionRole = $_SESSION["role"] ?? "";
$sessionUserId = $_SESSION["user_id"] ?? "";
if ($sessionRole !== "ADMIN" && $request["user_id"] !== $sessionUserId) {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Lacak Petugas - EcoPickup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="ui.css">
    <style>
        #map { height: 450px; width: 100%; border-radius: 12px; }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-dark bg-success shadow-sm mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="dashboard.php"><i class="fa-solid fa-arrow-left me-2"></i>Kembali ke Dashboard</a>
        </div>
    </nav>

    <div class="container pb-5">
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold text-success"><i class="fa-solid fa-truck-ramp-box me-2"></i>Pelacakan Real-time</h5>
                        <span class="badge bg-primary px-3 py-2"><i class="fa-solid fa-circle-dot fa-fade me-2"></i>Petugas Sedang Menuju Lokasi</span>
                    </div>
                    <div class="card-body p-0">
                        <div id="map"></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3 text-muted small">DETAIL PENJEMPUTAN</h6>
                        <div class="mb-3">
                            <label class="small text-muted d-block">No Permintaan</label>
                            <span class="fw-bold text-success">#<?php echo $request['request_id']; ?></span>
                        </div>
                        <div class="mb-3">
                            <label class="small text-muted d-block">Status</label>
                            <span class="badge bg-primary text-white">PETUGAS MENUJU LOKASI</span>
                        </div>
                        <div class="mb-3">
                            <label class="small text-muted d-block">Alamat Penjemputan</label>
                            <span class="fw-bold small"><?php echo htmlspecialchars($request['pickup_address']); ?></span>
                        </div>
                        <hr>
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" width="45" alt="Avatar">
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-0 fw-bold small">Driver EcoPickup</h6>
                                <p class="small text-muted mb-0">Dalam perjalanan ke lokasi Anda</p>
                            </div>
                            <a href="tel:08123456789" class="btn btn-success btn-sm rounded-circle shadow-sm">
                                <i class="fa-solid fa-phone"></i>
                            </a>
                        </div>
                        <hr>
                        <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($request['pickup_address']); ?>" target="_blank" class="btn btn-outline-secondary btn-sm w-100">
                            <i class="fa-solid fa-up-right-from-square me-2"></i>Buka di Google Maps
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function initMap() {
            // Titik koordinat simulasi (Pusat Palembang ke arah lokasi user)
            const officerPos = { lat: -2.9761, lng: 104.7754 }; // Petugas
            const userPos = { lat: -2.9900, lng: 104.7600 };    // Simulasi lokasi user

            const map = new google.maps.Map(document.getElementById("map"), {
                zoom: 14,
                center: officerPos,
                styles: [
                    { "featureType": "poi", "stylers": [{ "visibility": "off" }] }
                ]
            });

            // Marker Petugas (Ikon Truk)
            const officerMarker = new google.maps.Marker({
                position: officerPos,
                map: map,
                title: "Posisi Petugas",
                icon: {
                    url: "https://cdn-icons-png.flaticon.com/512/1048/1048329.png",
                    scaledSize: new google.maps.Size(40, 40)
                }
            });

            // Marker User (Lokasi Penjemputan)
            new google.maps.Marker({
                position: userPos,
                map: map,
                title: "Lokasi Anda",
                label: "A"
            });
        }
    </script>
    <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&callback=initMap" async defer></script>
</body>
</html>
