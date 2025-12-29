<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Tanggal di masa lalu

session_start();
require '../config/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'user') {
    header("Location: ../login.php");
    exit;
}

$id_user = $_SESSION['user']['id'];

date_default_timezone_set('Asia/Jakarta');

// Ambil lokasi pusat absen dari database
$lokasi = $conn->query("SELECT * FROM lokasi_absen LIMIT 1")->fetch_assoc();
$lat_sekolah = $lokasi['latitude'];
$lon_sekolah = $lokasi['longitude'];
$radius_maks = $lokasi['radius_meter'];

function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $radius = 6371000; // Earth's radius in meters
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2) * sin($dLat/2) +
          cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
          sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return $radius * $c;
}

$alert = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['latitude'], $_POST['longitude'])) {
    $ket = $_POST['keterangan'];
    $lat = $_POST['latitude'];
    $lon = $_POST['longitude'];

    if ($lat && $lon) {
        $jarak = calculateDistance($lat_sekolah, $lon_sekolah, $lat, $lon);
        $tanggal = date("Y-m-d");
        $waktu   = date("H:i:s");

        // Cek apakah sudah absen hari ini
        $stmt_check = $conn->prepare("SELECT COUNT(*) FROM absensi WHERE user_id = ? AND tanggal = ?");
        $stmt_check->bind_param("is", $id_user, $tanggal);
        $stmt_check->execute();
        $absen_count = $stmt_check->get_result()->fetch_row()[0];

        if ($absen_count > 0) {
            $alert = "warning|Anda sudah absen hari ini.";
        } else {
            if ($jarak <= $radius_maks) {
                $stmt = $conn->prepare("INSERT INTO absensi (user_id, tanggal, waktu, keterangan, latitude, longitude)
                VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isssdd", $id_user, $tanggal, $waktu, $ket, $lat, $lon);
                if ($stmt->execute()) {
                    $alert = "success|Absen berhasil! Jarak: " . round($jarak, 1) . " meter.";
                } else {
                    $alert = "danger|Gagal menyimpan absen ke database.";
                }
            } else {
                $alert = "danger|Gagal absen! Anda di luar radius " . $radius_maks . " meter (Jarak: " . round($jarak, 1) . " m).";
            }
        }
    } else {
        $alert = "danger|Lokasi tidak terdeteksi. Pastikan GPS aktif.";
    }
}

$stmt = $conn->prepare("SELECT * FROM absensi WHERE user_id = ? ORDER BY tanggal DESC, waktu DESC LIMIT 10"); // Batasi riwayat
$stmt->bind_param("i", $id_user);
$stmt->execute();
$riwayat = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <title>Dashboard User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #e9ecef; /* Light background */
        }    .navbar {
            box-shadow: 0 2px 4px rgba(0,0,0,.08);
        }
        .container {
            max-width: 960px; /* Batasi lebar container utama */
        }
        .card {
            border-radius: 0.75rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
        }
        .card-header {
            background-color: #0d6efd; /* Primary blue for header */
            color: white;
            padding: 1rem 1.5rem;
            font-weight: 600;
            border-top-left-radius: 0.75rem;
            border-top-right-radius: 0.75rem;
        }
        .card-title {
            color: #0d6efd; /* Warna teks judul card body */
            font-weight: 600;
        }
        #mapframe {
            width: 100%;
            height: 250px; /* Tingkatkan tinggi peta sedikit */
            border: none;
            display: block; /* Selalu tampilkan, kecuali ada JS error */
            border-radius: 0.5rem;
            box-shadow: inset 0 1px 3px rgba(0,0,0,.1);
            background-color: #f8f9fa; /* Warna latar belakang peta jika belum dimuat */
        }
        #location-status {
            font-weight: bold;
            text-align: center;
            padding: 0.5rem 0;
        }
        .alert-info {
            background-color: #e0f7fa;
            color: #007bb6;
            border-color: #b2ebf2;
            font-size: 0.95rem;
        }
        .btn-success {
            font-weight: 600;
        }
        .table thead th {
            text-align: center;
            vertical-align: middle;
        }
        .table tbody td {
            vertical-align: middle;
        }
        /* Responsif untuk tabel */
        @media (max-width: 767.98px) {
            .table-responsive {
                border: 1px solid #dee2e6; /* Border untuk div responsive */
                border-radius: 0.5rem;
            }
            .table thead {
                display: none; /* Sembunyikan header tabel di mobile */
            }
            .table tbody tr {
                display: block; /* Buat setiap baris menjadi block */
                margin-bottom: 0.75rem;
                border: 1px solid #dee2e6;
                border-radius: 0.5rem;
                background-color: #fff;
                box-shadow: 0 2px 5px rgba(0,0,0,.05);
            }
            .table tbody td {
                display: flex; /* Gunakan flexbox untuk label dan value */
                justify-content: space-between;
                padding: 0.75rem 1rem;
                border: none;
                border-bottom: 1px solid #eee; /* Pemisah antar baris virtual */
            }
            .table tbody td:last-child {
                border-bottom: none;
            }
            .table tbody td::before {
                content: attr(data-label); /* Tampilkan label dari data-label attribute */
                font-weight: bold;
                margin-right: 0.5rem;
                color: #495057;
            }
            .table-striped tbody tr:nth-of-type(odd) {
                background-color: #fff; /* Hilangkan stripe di mobile, pakai shadow */
            }
            .table-striped tbody tr:nth-of-type(odd) td {
                 background-color: #fff;
            }
        }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary px-3">
    <div class="container-fluid">
        <a class="navbar-brand" href="#"><i class="fas fa-fingerprint me-2"></i>Absensi App</a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarUserMenu" aria-controls="navbarUserMenu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarUserMenu">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <li class="nav-item d-flex align-items-center text-white me-lg-3 py-2 py-lg-0">
                    <i class="fas fa-user-circle me-2"></i> Halo, <?= htmlspecialchars($_SESSION['user']['username']) ?>
                </li>
                <li class="nav-item">
                    <a href="../logout.php" class="btn btn-outline-light rounded-pill px-3"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-12">
            <div class="alert alert-info shadow-sm mb-4 text-center" role="alert">
                <i class="fas fa-hand-sparkles me-2"></i> Selamat datang, <strong><?= htmlspecialchars($_SESSION['user']['full_name']) ?></strong>! Semoga harimu menyenangkan <i class="far fa-sun"></i>
            </div>
        </div>

        <?php if ($alert): list($type, $msg) = explode('|', $alert); ?>
            <div class="col-12">
                <div class="alert alert-<?= htmlspecialchars($type) ?> alert-dismissible fade show shadow-sm" role="alert">
                    <i class="fas fa-<?= $type == 'success' ? 'check-circle' : ($type == 'warning' ? 'exclamation-triangle' : 'times-circle') ?> me-2"></i>
                    <?= htmlspecialchars($msg) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        <?php endif; ?>

        <div class="col-md-8 col-lg-6"> <div class="card">
                <div class="card-header bg-primary">
                    <h5 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i> Form Absensi Online</h5>
                </div>
                <div class="card-body">
                    <p id="location-status" class="text-secondary mb-3"><i class="fas fa-sync-alt fa-spin me-2"></i> Mencari lokasi Anda...</p>
                    
                    <iframe id="mapframe" class="mb-3" src="about:blank"></iframe> <form method="POST" id="absenForm">
                        <div class="mb-3">
                            <label for="keterangan" class="form-label"><i class="fas fa-info-circle me-1"></i> Keterangan Absen</label>
                            <select name="keterangan" id="keterangan" class="form-select form-select-lg" required>
                                <option value="Hadir">Hadir</option>
                                <option value="Izin">Izin</option>
                                <option value="Sakit">Sakit</option>
                                <option value="Alpha">Alpha</option>
                            </select>
                        </div>
                        <input type="hidden" name="latitude" id="latitude">
                        <input type="hidden" name="longitude" id="longitude">
                        <button type="submit" class="btn btn-success btn-lg w-100" id="absenBtn" disabled>
                            <i class="fas fa-check-circle me-2"></i> Absen Sekarang
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-secondary">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i> Riwayat Absensi Terakhir</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Waktu</th>
                                    <th>Keterangan</th>
                                    <th>Lokasi (Lat, Lon)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($riwayat->num_rows > 0): ?>
                                    <?php while($row = $riwayat->fetch_assoc()): ?>
                                        <tr>
                                            <td data-label="Tanggal"><?= htmlspecialchars($row['tanggal']) ?></td>
                                            <td data-label="Waktu"><?= htmlspecialchars(date('H:i:s', strtotime($row['waktu']))) ?></td>
                                            <td data-label="Keterangan">
                                                <?php 
                                                    $status_color = '';
                                                    switch ($row['keterangan']) {
                                                        case 'Hadir': $status_color = 'text-success'; break;
                                                        case 'Izin': $status_color = 'text-info'; break;
                                                        case 'Sakit': $status_color = 'text-warning'; break;
                                                        case 'Alpha': $status_color = 'text-danger'; break;
                                                    }
                                                ?>
                                                <span class="fw-bold <?= $status_color ?>"><?= htmlspecialchars($row['keterangan']) ?></span>
                                            </td>
                                            <td data-label="Lokasi">
                                                <a href="https://www.google.com/maps?q=<?= htmlspecialchars($row['latitude']) ?>,<?= htmlspecialchars($row['longitude']) ?>" target="_blank" class="text-decoration-none">
                                                    <?= round($row['latitude'], 5) ?>, <?= round($row['longitude'], 5) ?> <i class="fas fa-external-link-alt ms-1"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="text-center py-3">Belum ada data riwayat absensi.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const statusDiv = document.getElementById('location-status');
    const mapFrame = document.getElementById('mapframe');
    const latInput = document.getElementById('latitude');
    const lonInput = document.getElementById('longitude');
    const absenBtn = document.getElementById('absenBtn');

    // Fungsi untuk mendapatkan lokasi
    function getLocation() {
        statusDiv.innerHTML = '<i class="fas fa-sync-alt fa-spin me-2"></i> Mencari lokasi Anda...';
        statusDiv.classList.remove('text-danger', 'text-success');
        statusDiv.classList.add('text-secondary');
        absenBtn.disabled = true;

        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    const lat = position.coords.latitude;
                    const lon = position.coords.longitude;

                    latInput.value = lat;
                    lonInput.value = lon;
                    absenBtn.disabled = false;

                    statusDiv.classList.remove('text-secondary');
                    statusDiv.classList.add('text-success');
                    statusDiv.innerHTML = '<i class="fas fa-check-circle me-2"></i> Lokasi Terdeteksi ✅';

                    // Update map with current location
                    // Menggunakan embed Google Maps yang lebih standar
                    mapFrame.src = `https://maps.google.com/maps?q=${lat},${lon}&hl=id&z=16&output=embed`;
                    mapFrame.style.display = "block";
                },
                function(error) {
                    let errorMessage = "Gagal mendeteksi lokasi.";
                    if (error.code === error.PERMISSION_DENIED) {
                        errorMessage = "Izin lokasi ditolak. Harap izinkan akses lokasi di pengaturan browser Anda.";
                    } else if (error.code === error.POSITION_UNAVAILABLE) {
                        errorMessage = "Informasi lokasi tidak tersedia.";
                    } else if (error.code === error.TIMEOUT) {
                        errorMessage = "Waktu habis untuk mendapatkan lokasi. Coba lagi.";
                    }
                    statusDiv.classList.remove('text-secondary');
                    statusDiv.classList.add('text-danger');
                    statusDiv.innerHTML = `<i class="fas fa-times-circle me-2"></i> ${errorMessage}`;
                    alert(errorMessage);
                    mapFrame.style.display = "none"; // Sembunyikan peta jika gagal
                },
                {
                    enableHighAccuracy: true, // Coba dapatkan lokasi seakurat mungkin
                    timeout: 10000,           // Batas waktu 10 detik
                    maximumAge: 0             // Jangan gunakan cache lokasi lama
                }
            );
        } else {
            statusDiv.classList.remove('text-secondary');
            statusDiv.classList.add('text-danger');
            statusDiv.innerHTML = '<i class="fas fa-times-circle me-2"></i> Geolocation tidak didukung oleh browser Anda.';
            alert("Geolocation tidak didukung oleh browser ini.");
            mapFrame.style.display = "none"; // Sembunyikan peta jika tidak didukung
        }
    }

    getLocation(); // Panggil fungsi untuk mendapatkan lokasi saat halaman dimuat
});
</script> 
</body>
</html>