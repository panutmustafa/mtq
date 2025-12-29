<?php
// Pastikan session dan auth berjalan
session_start();
require_once __DIR__ . '/../includes/auth.php'; // Pastikan path ini benar
requireRole('jury'); // Hanya juri yang bisa mengakses

// Optional: include database connection if needed for future dashboard features
// require_once __DIR__.'/../config/database.php';

// Ambil data user dari session
$full_name = htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); // Fallback if full_name is not set
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Juri | Sistem Penilaian Lomba</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }
        .navbar {
            background-color: #34495e; /* Darker Blue Gray for Jury */
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
            color: #34495e;
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
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.15);
        }
        .card-body {
            padding: 2rem;
            text-align: center;
        }
        .card-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: #34495e;
        }
        .card-text {
            color: #7f8c8d;
            margin-bottom: 1.5rem;
            min-height: 48px; /* Ensure consistent height for text */
        }
        .btn {
            border-radius: 0.5rem;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            width: 100%; /* Make button full width */
        }
        .btn i {
            margin-right: 0.75rem;
        }
        
        .header-section {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 0.75rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .header-section h2 {
            color: #2c3e50;
            font-weight: 700;
            margin-bottom: 5px;
        }
        .header-section p {
            color: #555;
            font-size: 1.1rem;
            margin-bottom: 0;
        }
        .header-icon {
            font-size: 3.5rem;
            color: #2c3e50;
        }
    </style>
</head>
<body class="bg-light">

    <?php include __DIR__.'/../includes/jury_navbar.php'; // Use the new jury specific navbar ?>
    
    <div class="container py-4">
        <div class="header-section">
            <i class="fas fa-gavel header-icon"></i>
            <div>
                <h2>Dashboard Juri</h2>
                <p>Selamat datang, <b><?= $full_name ?></b>. Kelola penugasan dan berikan penilaian untuk lomba.</p>
            </div>
        </div>

        <div class="row mt-4 justify-content-center">
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-body">
                        <i class="fas fa-clipboard-list fa-3x text-primary mb-3"></i>
                        <h5 class="card-title">Penugasan Aktif</h5>
                        <p class="card-text">Lihat daftar kompetisi yang ditugaskan kepada Anda.</p>
                        <a href="assignments.php" class="btn btn-primary">
                            <i class="fas fa-eye"></i> Lihat Penugasan
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-body">
                        <i class="fas fa-edit fa-3x text-success mb-3"></i>
                        <h5 class="card-title">Nilai Peserta</h5>
                        <p class="card-text">Berikan evaluasi dan skor untuk peserta lomba.</p>
                        <a href="scoring.php" class="btn btn-success">
                            <i class="fas fa-star"></i> Beri Penilaian
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-body">
                        <i class="fas fa-chart-bar fa-3x text-info mb-3"></i>
                        <h5 class="card-title">Lihat Hasil Penilaian</h5>
                        <p class="card-text">Periksa skor yang telah Anda berikan kepada peserta.</p>
                        <a href="view_scores.php" class="btn btn-info text-white">
                            <i class="fas fa-poll"></i> Lihat Skor
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Logout Confirmation Modal -->
<div class="modal fade" id="logoutConfirmModal" tabindex="-1" aria-labelledby="logoutConfirmModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="logoutConfirmModalLabel">Konfirmasi Logout</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Apakah Anda yakin ingin keluar dari sesi Anda?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <a href="/mtq/logout.php" class="btn btn-danger">Logout</a>
      </div>
    </div>
  </div>
</div>
</body>
</html>
