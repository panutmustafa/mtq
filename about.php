<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in to show appropriate navigation
$isLoggedIn = isset($_SESSION['user_id']);
$userRole = $isLoggedIn ? $_SESSION['role'] : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tentang Kami - Sistem Manajemen Lomba</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --accent-color: #e74c3c;
        }
        
        .hero-about {
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            color: white;
            padding: 4rem 0;
            position: relative;
        }
        
        .feature-icon {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .team-card {
            transition: transform 0.3s;
        }
        .team-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-trophy-fill"></i> CompetitionHub
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="competitions.php">Lomba</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="about.php">Tentang Kami</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">Kontak</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <?php if ($isLoggedIn): ?>
                        <a href="<?= $userRole ?>/dashboard_<?= $userRole ?>.php" class="btn btn-outline-light me-2">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                        <a href="logout.php" class="btn btn-danger">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-primary">
                            <i class="bi bi-box-arrow-in-right"></i> Login
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-about">
        <div class="container text-center">
            <h1 class="display-4 fw-bold">Tentang CompetitionHub</h1>
            <p class="lead">Platform profesional untuk mengelola berbagai jenis kompetisi dengan mudah</p>
        </div>
    </section>

    <!-- About Content -->
    <section class="py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <h2 class="fw-bold mb-4">Visi Kami</h2>
                    <p>Menjadi platform terdepan dalam menyediakan solusi manajemen kompetisi yang inovatif, efisien, dan mudah digunakan bagi penyelenggara dan peserta lomba di seluruh Indonesia.</p>
                    
                    <h2 class="fw-bold mt-5 mb-4">Misi Kami</h2>
                    <ul class="list-unstyled">
                        <li class="mb-3"><i class="bi bi-check-circle-fill text-primary me-2"></i> Menyederhanakan proses administrasi kompetisi</li>
                        <li class="mb-3"><i class="bi bi-check-circle-fill text-primary me-2"></i> Meningkatkan pengalaman peserta dan penyelenggara</li>
                        <li class="mb-3"><i class="bi bi-check-circle-fill text-primary me-2"></i> Menyediakan alat analisis untuk pengambilan keputusan</li>
                        <li class="mb-3"><i class="bi bi-check-circle-fill text-primary me-2"></i> Mendukung transparansi dalam penilaian kompetisi</li>
                    </ul>
                </div>
                <div class="col-lg-6">
                    <img src="https://placehold.co/600x400" alt="Tim CompetitionHub" class="img-fluid rounded shadow">
                </div>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Keunggulan Platform Kami</h2>
                <p class="text-muted">Solusi lengkap untuk kebutuhan kompetisi Anda</p>
            </div>
            
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon">
                                <i class="bi bi-people-fill"></i>
                            </div>
                            <h4>Manajemen Peserta</h4>
                            <p>Kelola pendaftaran peserta dengan mudah melalui sistem terintegrasi kami.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon">
                                <i class="bi bi-clipboard-check-fill"></i>
                            </div>
                            <h4>Penilaian Digital</h4>
                            <p>Sistem penilaian online yang akurat dan transparan untuk juri dan peserta.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon">
                                <i class="bi bi-bar-chart-line-fill"></i>
                            </div>
                            <h4>Analisis Real-time</h4>
                            <p>Pantau perkembangan kompetisi dan hasil penilaian secara real-time.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Team Section -->
    <section class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Tim Kami</h2>
                <p class="text-muted">Orang-orang di balik kesuksesan CompetitionHub</p>
            </div>
            
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card team-card h-100 border-0 shadow-sm">
                        <img src="https://placehold.co/400x300" class="card-img-top" alt="Team Member">
                        <div class="card-body text-center">
                            <h5 class="card-title">John Doe</h5>
                            <p class="text-muted">Founder & CEO</p>
                            <p class="card-text">Pengembang utama platform dengan pengalaman 10 tahun di bidang teknologi pendidikan.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card team-card h-100 border-0 shadow-sm">
                        <img src="https://placehold.co/400x300" alt="Team Member" class="card-img-top">
                        <div class="card-body text-center">
                            <h5 class="card-title">Jane Smith</h5>
                            <p class="text-muted">CTO</p>
                            <p class="card-text">Ahli sistem dengan fokus pada keamanan dan skalabilitas platform.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card team-card h-100 border-0 shadow-sm">
                        <img src="https://placehold.co/400x300" alt="Team Member" class="card-img-top">
                        <div class="card-body text-center">
                            <h5 class="card-title">Michael Johnson</h5>
                            <p class="text-muted">Head of Design</p>
                            <p class="card-text">Mendesain pengalaman pengguna yang intuitif dan menarik untuk platform kami.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-5 bg-primary text-white">
        <div class="container text-center">
            <h2 class="fw-bold mb-4">Siap Menggunakan Platform Kami?</h2>
            <p class="lead mb-5">Bergabunglah dengan ratusan penyelenggara kompetisi yang telah mempercayai sistem kami</p>
            <div class="d-flex justify-content-center gap-3">
                <?php if ($isLoggedIn): ?>
                    <a href="<?= $userRole ?>/dashboard_<?= $userRole ?>.php" class="btn btn-light btn-lg px-4">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                <?php else: ?>
                    <a href="register.php" class="btn btn-light btn-lg px-4">
                        <i class="bi bi-person-plus"></i> Daftar Sekarang
                    </a>
                    <a href="contact.php" class="btn btn-outline-light btn-lg px-4">
                        <i class="bi bi-envelope"></i> Hubungi Kami
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4 mb-md-0">
                    <h5>CompetitionHub</h5>
                    <p class="text-muted">Solusi lengkap untuk manajemen kompetisi Anda.</p>
                </div>
                <div class="col-md-2 mb-4 mb-md-0">
                    <h5>Menu</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-muted">Home</a></li>
                        <li><a href="competitions.php" class="text-muted">Lomba</a></li>
                        <li><a href="about.php" class="text-muted">Tentang</a></li>
                        <li><a href="contact.php" class="text-muted">Kontak</a></li>
                    </ul>
                </div>
                <div class="col-md-4 mb-4 mb-md-0">
                    <h5>Kontak</h5>
                    <ul class="list-unstyled text-muted">
                        <li><i class="bi bi-envelope me-2"></i> info@competitionhub.com</li>
                        <li><i class="bi bi-telephone me-2"></i> (021) 1234-5678</li>
                        <li><i class="bi bi-geo-alt me-2"></i> Jakarta, Indonesia</li>
                    </ul>
                </div>
                <div class="col-md-2">
                    <h5>Sosial Media</h5>
                    <div class="d-flex gap-3">
                        <a href="#" class="text-muted"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="text-muted"><i class="bi bi-twitter"></i></a>
                        <a href="#" class="text-muted"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="text-muted"><i class="bi bi-linkedin"></i></a>
                    </div>
                </div>
            </div>
            <hr class="my-4">
            <div class="text-center text-muted">
                <small>&copy; 2023 CompetitionHub. All rights reserved.</small>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
