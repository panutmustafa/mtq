<?php
// Pastikan session dan auth berjalan
session_start();
require_once __DIR__ . '/../includes/auth.php'; // Pastikan path ini benar
if ($_SESSION['role'] !== 'user') {
    header('Location: ../login.php');
    exit();
}

// Koneksi database
require_once __DIR__ . '/../config/database.php'; // Pastikan path ini benar

// Atur zona waktu ke WIB
date_default_timezone_set('Asia/Jakarta');

// Inisialisasi pesan
$message = '';

// Ambil pengumuman aktif
$announcements = $pdo->query("SELECT a.*, u.full_name AS created_by_name FROM announcements a JOIN users u ON a.created_by = u.id WHERE a.is_active = 1 ORDER BY a.created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Sekolah | Sistem Penilaian Lomba</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f6f5;
            color: #2f3349;
            margin: 0;
            padding: 0;
        }
        .navbar {
            background-color: #ffffff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 0.5rem 1rem;
        }
        .navbar-brand {
            font-weight: 600;
            color: #2f3349 !important;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .navbar .nav-link {
            color: #6c7288 !important;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        .navbar .nav-link:hover {
            color: #2f3349 !important;
        }
        .btn-outline-light {
            border-color: #e5e7eb;
            color: #2f3349;
            transition: all 0.3s ease;
        }
        .btn-outline-light:hover {
            background-color: #2f3349;
            color: #ffffff;
        }
        .sidebar {
            width: 250px;
            background-color: #ffffff;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding-top: 70px;
            box-shadow: 2px 0 5px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
        }
        .sidebar .nav-link {
            color: #6c7288;
            font-weight: 500;
            padding: 10px 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background-color: #f5f6f5;
            color: #2f3349;
        }
        .content-wrapper {
            margin-left: 250px;
            padding: 20px;
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .content-wrapper {
                margin-left: 0;
            }
            .navbar-toggler {
                display: block !important;
            }
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            overflow: hidden;
            transition: transform 0.2s ease;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .card-header {
            background-color: #2f3349;
            color: #ffffff;
            font-weight: 600;
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .card-body {
            padding: 1.5rem;
        }
        .alert {
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .list-group-item {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            margin-bottom: 10px;
            padding: 15px;
            transition: all 0.2s ease;
        }
        .list-group-item:hover {
            background-color: #f5f6f5;
            transform: translateY(-2px);
        }
        .table {
            border-radius: 8px;
            overflow: hidden;
        }
        .table th, .table td {
            padding: 1rem;
            vertical-align: middle;
        }
        .table thead {
            background-color: #f5f6f5;
        }
        .btn {
            border-radius: 8px;
            font-weight: 500;
        }
        .form-control, .form-select {
            border-radius: 8px;
            box-shadow: none;
            border: 1px solid #e5e7eb;
        }
        .modal-content {
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .modal-header {
            background-color: #2f3349;
            color: #ffffff;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar d-none d-md-block">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link active" href="dashboard_user.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            </li>
          	<li class="nav-item">
                <a class="nav-link" href="register_competition.php"><i class="fas fa-pencil-alt"></i> Daftar Lomba</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="hasil_kejuaraan.php"><i class="fas fa-trophy"></i> Hasil kejuaraan</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="registration_list.php"><i class="fas fa-list-alt"></i> Daftar Pendaftaran</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </li>
        </ul>
    </div>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <a class="navbar-brand" href="dashboard_user.php"><i class="fas fa-school"></i> Dashboard Sekolah</a>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-lg-center">
                    <li class="nav-item">
                        <span class="navbar-text me-lg-3 py-2 py-lg-0">
                            <i class="fas fa-user-circle"></i> Halo, <?= htmlspecialchars($_SESSION['full_name']) ?>
                        </span>
                    </li>
                    <li class="nav-item">
                        <a href="../logout.php" class="btn btn-outline-light"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register_competition.php"><i class="fas fa-pencil-alt"></i> Daftar Lomba</a>
                    </li>
                    <li class="nav-item">
                        <a href="hasil_kejuaraan.php" class="nav-link"><i class="fas fa-trophy"></i> Hasil Kejuaraan</a>
                    </li>
                    <li class="nav-item">
                        <a href="registration_list.php" class="nav-link"><i class="fas fa-list-alt"></i> Daftar Pendaftaran</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Content -->
    <div class="content-wrapper">
        <div class="container-fluid">
            <?php if (!empty($message)) echo $message; ?>

            <!-- Pengumuman -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-bullhorn"></i> Pengumuman
                        </div>
                        <div class="card-body">
                            <?php if (empty($announcements)): ?>
                                <div class="alert alert-info" role="alert">
                                    <i class="fas fa-info-circle me-2"></i>Tidak ada pengumuman saat ini.
                                </div>
                            <?php else: ?>
                                <?php foreach ($announcements as $ann): ?>
                                    <?php
                                    $datetime = new DateTime($ann['created_at'], new DateTimeZone('UTC'));
                                    $datetime->setTimezone(new DateTimeZone('Asia/Jakarta'));
                                    $formatted_time = $datetime->format('d M Y H:i');
                                    ?>
                                    <div class="alert alert-light shadow-sm mb-3">
                                        <h5 class="alert-heading"><i class="fas fa-bullhorn me-2"></i><?= htmlspecialchars($ann['title']) ?></h5>
                                        <p class="mb-2"><?= htmlspecialchars($ann['content']) ?></p>
                                        <small class="text-muted">Dibuat oleh: <?= htmlspecialchars($ann['created_by_name']) ?> | <?= $formatted_time ?></small>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <footer class="mt-4 text-center text-muted">
                <p>Developed by <a href="https://panutmustafa.my.id">Panut, S.Pd.</a> | SDN Jomblang 2</p>
            </footer>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar on mobile
        document.querySelector('.navbar-toggler').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });
    </script>
</body>
</html>
