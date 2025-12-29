<?php
// Mencegah caching halaman sensitif
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Tanggal di masa lalu

session_start();
require '../config/db.php'; // Pastikan path ini benar

// Pastikan hanya admin yang bisa mengakses halaman ini
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

date_default_timezone_set('Asia/Jakarta');

// Ambil pesan alert dari session (jika ada)
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
    <title>Upload Data Pengguna | Sistem Absensi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }
        .navbar {
            background-color: #2c3e50; /* Dark Blue */
            box-shadow: 0 4px 8px rgba(0,0,0,.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .navbar-brand, .navbar-text {
            color: #ecf0f1 !important; /* Light Gray */
            font-weight: 600;
        }
        .navbar-nav .nav-link {
            color: #bdc3c7 !important; /* Muted Gray */
            transition: color 0.3s ease;
        }
        .navbar-nav .nav-link:hover {
            color: #ecf0f1 !important; /* Light Gray on hover */
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

        /* File Input Styling (Custom) */
        .custom-file-input {
            border: 2px dashed #007bff; /* Blue border */
            border-radius: 0.5rem;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background-color: #e9f5ff; /* Light blue background */
            color: #007bff;
        }
        .custom-file-input:hover {
            background-color: #d1e7ff; /* Lighter blue on hover */
            border-color: #0056b3; /* Darker blue border on hover */
        }
        .custom-file-input input[type="file"] {
            display: none; /* Sembunyikan input file asli */
        }
        .custom-file-input .file-icon {
            font-size: 3rem; /* Ukuran ikon */
            margin-bottom: 0.75rem;
        }
        .custom-file-input .file-text {
            font-size: 1.1rem;
            font-weight: 500;
        }
        .custom-file-input .file-name {
            display: block;
            margin-top: 0.5rem;
            font-size: 0.9rem;
            color: #555;
            font-weight: normal;
        }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark px-3">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php"><i class="fas fa-user-shield me-2"></i> Admin Panel</a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt me-1"></i> Dashboard</a>
                </li>
                 <li class="nav-item">
                    <a class="nav-link active" aria-current="page" href="users.php"><i class="fas fa-users me-1"></i> Kelola Pengguna</a>
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
    <div class="row justify-content-center">
        <div class="col-md-8">
            <h2 class="mb-4 text-center text-info"><i class="fas fa-upload me-3"></i> Upload Data Pengguna (.xlsx)</h2>
            <hr class="mb-5">

            <?php if ($alert_message): ?>
                <div class="alert alert-<?= htmlspecialchars($alert_type) ?> alert-dismissible fade show shadow-sm alert-custom" role="alert">
                    <i class="fas fa-<?= $alert_type == 'success' ? 'check-circle' : ($alert_type == 'warning' ? 'exclamation-triangle' : ($alert_type == 'danger' ? 'times-circle' : 'info-circle')) ?> me-2"></i>
                    <div><?= $alert_message ?></div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <i class="fas fa-file-excel me-2"></i> Unggah File Excel
                </div>
                <div class="card-body">
                    <form action="proses-upload-users.php" method="post" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="file_excel" class="form-label d-block text-muted mb-3">Pilih file Excel (.xlsx) yang berisi data pengguna.</label>
                            
                            <label for="file_excel_input" class="custom-file-input d-flex flex-column align-items-center justify-content-center mb-3">
                                <i class="fas fa-cloud-upload-alt file-icon"></i>
                                <span class="file-text" id="file_name_display">Seret & Lepas File di Sini atau Klik untuk Memilih</span>
                                <input type="file" name="file_excel" id="file_excel_input" accept=".xlsx" required>
                                <span class="file-name text-muted" id="selected_file_name">Belum ada file dipilih</span>
                            </label>

                            <small class="form-text text-muted">Pastikan format file Excel Anda benar (misal: kolom username, full_name, asal_sekolah, password, role).</small>
                        </div>
                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-upload me-2"></i> Upload Data
                            </button>
                        </div>
                    </form>
                    <hr class="my-4">
                    <div class="text-center">
                        <a href="users.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i> Kembali ke Kelola Pengguna</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const fileInput = document.getElementById('file_excel_input');
        const fileNameDisplay = document.getElementById('selected_file_name');
        const fileTextInput = document.getElementById('file_name_display');

        fileInput.addEventListener('change', function() {
            if (this.files && this.files.length > 0) {
                fileNameDisplay.textContent = this.files[0].name;
                fileTextInput.textContent = 'File Dipilih:'; // Ubah teks di atas nama file
            } else {
                fileNameDisplay.textContent = 'Belum ada file dipilih';
                fileTextInput.textContent = 'Seret & Lepas File di Sini atau Klik untuk Memilih';
            }
        });
    });
</script>

</body>
</html>