<?php
// Mencegah caching halaman sensitif (tambahan penting untuk logout)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Tanggal di masa lalu

session_start();
require '../config/db.php'; // Pastikan path ini benar (relatif terhadap file ini)

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

date_default_timezone_set('Asia/Jakarta');

// Fungsi untuk menyiapkan pesan alert (tetap di dashboard untuk pesan umum)
function set_alert_message($type, $message) {
    global $alert_message;
    $_SESSION['alert_type'] = $type;
    $_SESSION['alert_message'] = $message;
}

// Update absensi (jika masih ingin di dashboard, biarkan; jika tidak, pindahkan ke absensi.php)
// Untuk saat ini, saya akan hapus logika update/delete absensi dari dashboard agar lebih bersih.
// Jika Anda ingin tetap memiliki modal edit absensi di dashboard, Anda bisa mengaktifkan kembali bagian ini.
/*
if (isset($_POST['update_absen'])) {
    $id = $_POST['id_absen'];
    $tanggal = $_POST['tanggal'];
    $waktu = $_POST['waktu'];
    $keterangan = $_POST['keterangan'];
    $latlon_parts = explode(",", $_POST['latlon'] ?? '0,0');
    $lat = (float)($latlon_parts[0] ?? 0);
    $lon = (float)($latlon_parts[1] ?? 0);

    $stmt = $conn->prepare("UPDATE absensi SET tanggal=?, waktu=?, keterangan=?, latitude=?, longitude=? WHERE id=?");
    $stmt->bind_param("sssddi", $tanggal, $waktu, $keterangan, $lat, $lon, $id);
    if ($stmt->execute()) {
        set_alert_message("success", "Data absensi berhasil diupdate.");
    } else {
        set_alert_message("danger", "Gagal mengupdate data absensi.");
    }
    header("Location: dashboard.php");
    exit;
}

if (isset($_GET['hapus_absen'])) {
    $id = $_GET['hapus_absen'];
    $stmt = $conn->prepare("DELETE FROM absensi WHERE id=?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        set_alert_message("success", "Data absensi berhasil dihapus.");
    } else {
        set_alert_message("danger", "Gagal menghapus data absensi.");
    }
    header("Location: dashboard.php");
    exit;
}
*/

// Ambil lokasi saat ini (untuk form lokasi)
$lokasi = $conn->query("SELECT * FROM lokasi_absen LIMIT 1")->fetch_assoc();

// Proses simpan lokasi
if (isset($_POST['simpan_lokasi'])) {
    $nama = trim($_POST['nama'] ?? '');
    $lat = (float)($_POST['latitude'] ?? 0);
    $lon = (float)($_POST['longitude'] ?? 0);
    $radius = (int)($_POST['radius_meter'] ?? 0);

    if (empty($nama) || $lat == 0 || $lon == 0 || $radius <= 0) {
        set_alert_message("warning", "Pastikan semua field lokasi diisi dengan benar.");
    } else {
        $cek = $conn->query("SELECT id FROM lokasi_absen LIMIT 1");
        if ($cek->num_rows > 0) {
            $id = $cek->fetch_assoc()['id'];
            $stmt = $conn->prepare("UPDATE lokasi_absen SET nama=?, latitude=?, longitude=?, radius_meter=? WHERE id=?");
            $stmt->bind_param("sddii", $nama, $lat, $lon, $radius, $id);
        } else {
            $stmt = $conn->prepare("INSERT INTO lokasi_absen (nama, latitude, longitude, radius_meter) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sddi", $nama, $lat, $lon, $radius);
        }
        if ($stmt->execute()) {
            set_alert_message("success", "Lokasi berhasil disimpan.");
            $lokasi = $conn->query("SELECT * FROM lokasi_absen LIMIT 1")->fetch_assoc(); // Refresh data
        } else {
            set_alert_message("danger", "Gagal menyimpan lokasi.");
        }
    }
    header("Location: dashboard.php"); // Redirect agar alert muncul
    exit;
}

// Ambil data dari SESSION untuk alert
$alert_type = '';
$alert_message = '';
if (isset($_SESSION['alert_type']) && isset($_SESSION['alert_message'])) {
    $alert_type = $_SESSION['alert_type'];
    $alert_message = $_SESSION['alert_message'];
    unset($_SESSION['alert_type']);
    unset($_SESSION['alert_message']);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin | Sistem Absensi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }
        .navbar {
            background-color: #2c3e50;
            box-shadow: 0 4px 8px rgba(0,0,0,.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .navbar-brand, .navbar-text {
            color: #ecf0f1 !important;
            font-weight: 600;
        }
        .navbar-nav .nav-link {
            color: #bdc3c7 !important;
            transition: color 0.3s ease;
        }
        .navbar-nav .nav-link:hover {
            color: #ecf0f1 !important;
        }
        .btn-outline-light {
            border-color: #ecf0f1;
            color: #ecf0f1;
            transition: all 0.3s ease;
        }
        .btn-outline-light:hover {
            background-color: #ecf0f1;
            color: #2c3e50;
        }

        .container {
            padding-top: 30px;
            padding-bottom: 30px;
        }

        .card {
            border-radius: 0.75rem;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
            border: none;
            overflow: hidden;
        }
        .card-header {
            border-top-left-radius: 0.75rem;
            border-top-right-radius: 0.75rem;
            padding: 1.25rem 1.75rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            font-size: 1.15rem;
            gap: 10px;
        }
        .card-header i {
            margin-right: 0.5rem;
        }
        .card-body {
            padding: 1.75rem;
        }
        .form-label {
            font-weight: 500;
            color: #555;
            margin-bottom: 0.5rem;
        }
        .form-control, .form-select {
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
        }
        .btn {
            border-radius: 0.5rem;
            padding: 0.75rem 1.25rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn i {
            margin-right: 0.5rem;
        }

        /* Alerts */
        .alert-custom {
            border-radius: 0.75rem;
            padding: 1.25rem 1.75rem;
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 1rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }
        .alert-custom i {
            font-size: 1.4rem;
        }
        .alert-custom .btn-close {
            font-size: 0.9rem;
        }

        /* Responsive */
        @media (max-width: 767.98px) {
            .card-header {
                flex-direction: column;
                align-items: flex-start !important;
            }
            .card-header .d-flex {
                width: 100%;
                justify-content: flex-start !important;
                margin-top: 0.5rem;
            }
            .card-header .d-flex .btn {
                width: 100%;
                margin-bottom: 0.5rem;
            }
        }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark px-3">
    <div class="container-fluid">
        <a class="navbar-brand" href="#"><i class="fas fa-user-shield me-2"></i> Admin Panel</a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">
                <li class="nav-item">
                    <a class="nav-link active" aria-current="page" href="dashboard.php"><i class="fas fa-tachometer-alt me-1"></i> Dashboard</a>
                </li>
                 <li class="nav-item">
                    <a class="nav-link" href="users.php"><i class="fas fa-users me-1"></i> Kelola Pengguna</a>
                </li>
                 <li class="nav-item">
                    <a class="nav-link" href="absensi.php"><i class="fas fa-calendar-check me-1"></i> Data Absensi</a>
                </li>
                <li class="nav-item">
                    <span class="navbar-text me-lg-3 text-white py-2 py-lg-0">
                        <i class="fas fa-user-circle me-2"></i> Halo, <?= htmlspecialchars($_SESSION['user']['full_name'] ?? $_SESSION['user']['username']) ?>
                    </span>
                </li>
                <li class="nav-item">
                    <a href="../logout.php" class="btn btn-outline-light rounded-pill px-3"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <div class="alert alert-info shadow-sm mb-4 alert-custom" role="alert">
                <i class="fas fa-hand-sparkles"></i> Selamat datang, <strong><?= htmlspecialchars($_SESSION['user']['full_name'] ?? $_SESSION['user']['username']) ?></strong>! Anda memiliki akses penuh ke fitur admin.
            </div>
        </div>

        <?php if ($alert_message): ?>
            <div class="col-12">
                <div class="alert alert-<?= htmlspecialchars($alert_type) ?> alert-dismissible fade show shadow-sm alert-custom" role="alert">
                    <i class="fas fa-<?= $alert_type == 'success' ? 'check-circle' : ($alert_type == 'warning' ? 'exclamation-triangle' : ($alert_type == 'danger' ? 'times-circle' : 'info-circle')) ?> me-2"></i>
                    <div><?= $alert_message ?></div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <h2 class="mb-4 text-center text-primary"><i class="fas fa-cogs me-3"></i> Pengaturan Admin Utama</h2>
    <hr class="mb-5">

    <div class="card shadow-sm mb-5">
        <div class="card-header bg-primary text-white">
            <i class="fas fa-map-marker-alt"></i> Pengaturan Lokasi Absensi Default
        </div>
        <div class="card-body">
            <p class="text-muted mb-4">Atur lokasi latitude, longitude, dan radius toleransi untuk absensi. Ini akan menjadi lokasi pusat tempat siswa/i dapat melakukan absensi.</p>
            <form method="POST">
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label for="nama_lokasi" class="form-label">Nama Lokasi</label>
                        <input type="text" class="form-control" id="nama_lokasi" name="nama" value="<?= htmlspecialchars($lokasi['nama'] ?? '') ?>" placeholder="Mis: Kantor Pusat" required>
                    </div>
                    <div class="col-md-4">
                        <label for="latitude" class="form-label">Latitude</label>
                        <input type="text" class="form-control" id="latitude" name="latitude" value="<?= htmlspecialchars($lokasi['latitude'] ?? '') ?>" placeholder="Mis: -6.1753924" required>
                    </div>
                    <div class="col-md-4">
                        <label for="longitude" class="form-label">Longitude</label>
                        <input type="text" class="form-control" id="longitude" name="longitude" value="<?= htmlspecialchars($lokasi['longitude'] ?? '') ?>" placeholder="Mis: 106.8271528" required>
                    </div>
                </div>
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="radius_meter" class="form-label">Radius Maksimal (meter)</label>
                        <input type="number" class="form-control" id="radius_meter" name="radius_meter" value="<?= htmlspecialchars($lokasi['radius_meter'] ?? 100) ?>" required min="1" placeholder="Mis: 100">
                    </div>
                    <div class="col-md-4 d-grid">
                        <button type="button" class="btn btn-info text-white" onclick="ambilLokasi()"><i class="fas fa-crosshairs me-2"></i>Gunakan Lokasi Saya</button>
                    </div>
                    <div class="col-md-4 d-grid">
                        <button type="submit" name="simpan_lokasi" class="btn btn-primary"><i class="fas fa-save me-2"></i> Simpan Lokasi</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm mb-5">
        <div class="card-header bg-secondary text-white">
            <i class="fas fa-link"></i> Navigasi Cepat
        </div>
        <div class="card-body text-center d-flex flex-wrap justify-content-around align-items-center gap-3">
            <a href="users.php" class="btn btn-success btn-lg flex-fill m-2" style="max-width: 300px;">
                <i class="fas fa-users me-2"></i> Kelola Pengguna
            </a>
            <a href="absensi.php" class="btn btn-dark btn-lg flex-fill m-2" style="max-width: 300px;">
                <i class="fas fa-calendar-check me-2"></i> Data Absensi
            </a>
            </div>
    </div>

</div> <div class="modal fade" id="editAbsenModal" tabindex="-1" aria-labelledby="editAbsenModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="editAbsenModalLabel"><i class="fas fa-edit me-2"></i> Edit Data Absensi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id_absen" id="edit_id_absen">
                    <div class="mb-3">
                        <label for="edit_tanggal" class="form-label">Tanggal</label>
                        <input type="date" name="tanggal" id="edit_tanggal" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_waktu" class="form-label">Waktu</label>
                        <input type="time" name="waktu" id="edit_waktu" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_keterangan" class="form-label">Keterangan</label>
                        <select name="keterangan" id="edit_keterangan" class="form-select" required>
                            <option value="Hadir">Hadir</option>
                            <option value="Izin">Izin</option>
                            <option value="Sakit">Sakit</option>
                            <option value="Alpha">Alpha</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_latlon" class="form-label">Latitude, Longitude</label>
                        <input type="text" name="latlon" id="edit_latlon" class="form-control" placeholder="Contoh: -6.1234,106.5678" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fas fa-times-circle me-2"></i> Batal</button>
                    <button type="submit" name="update_absen" class="btn btn-primary"><i class="fas fa-save me-2"></i> Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Fungsi untuk mendapatkan lokasi pengguna
    function ambilLokasi() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(pos) {
                document.getElementById('latitude').value = pos.coords.latitude;
                document.getElementById('longitude').value = pos.coords.longitude;
                alert("Lokasi berhasil diambil: " + pos.coords.latitude + ", " + pos.coords.longitude);
            }, function(err) {
                let errorMessage = "Gagal mendapatkan lokasi. ";
                if (err.code === err.PERMISSION_DENIED) {
                    errorMessage += "Pastikan izin lokasi diaktifkan di browser Anda.";
                } else if (err.code === err.POSITION_UNAVAILABLE) {
                    errorMessage += "Informasi lokasi tidak tersedia.";
                } else if (err.code === err.TIMEOUT) {
                    errorMessage += "Waktu habis untuk mendapatkan lokasi.";
                }
                alert(errorMessage);
            }, {
                enableHighAccuracy: true,
                timeout: 5000,
                maximumAge: 0
            });
        } else {
            alert("Browser Anda tidak mendukung fitur lokasi (Geolocation).");
        }
    }

    // Modal Edit Absensi Script (jika tetap ada modal ini di dashboard tapi tidak dipicu)
    // var editAbsenModal = document.getElementById('editAbsenModal');
    // if (editAbsenModal) {
    //     editAbsenModal.addEventListener('show.bs.modal', function (event) {
    //         var button = event.relatedTarget;
    //         var id = button.getAttribute('data-id');
    //         var tanggal = button.getAttribute('data-tanggal');
    //         var waktu = button.getAttribute('data-waktu');
    //         var keterangan = button.getAttribute('data-keterangan');
    //         var latlon = button.getAttribute('data-latlon');

    //         var modalId = editAbsenModal.querySelector('#edit_id_absen');
    //         var modalTanggal = editAbsenModal.querySelector('#edit_tanggal');
    //         var modalWaktu = editAbsenModal.querySelector('#edit_waktu');
    //         var modalKeterangan = editAbsenModal.querySelector('#edit_keterangan');
    //         var modalLatlon = editAbsenModal.querySelector('#edit_latlon');

    //         modalId.value = id;
    //         modalTanggal.value = tanggal;
    //         modalWaktu.value = waktu;
    //         modalKeterangan.value = keterangan;
    //         modalLatlon.value = latlon;
    //     });
    // }
</script>

</body>
</html>