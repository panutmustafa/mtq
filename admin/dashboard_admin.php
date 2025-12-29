<?php
require_once __DIR__ . '/../includes/auth.php'; // Pastikan file ini menangani session_start()
require_once __DIR__ . '/../config/database.php'; // Opsional, hanya jika dashboard memerlukan data DB langsung

// Pengecekan role spesifik
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // Redirect langsung ke login dengan pesan error jika tidak otorisasi
    header('Location: ../login.php?error=unauthorized');
    exit();
}

// Ambil data user dari session
$full_name = htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Admin');

// Fetch statistics
try {
    // Total Users
    $totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $totalAdmins = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
    $totalJuries = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'jury'")->fetchColumn();
    $totalRegularUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();

    // Total Competitions
    $totalCompetitions = $pdo->query("SELECT COUNT(*) FROM competitions")->fetchColumn();
    $activeCompetitions = $pdo->query("SELECT COUNT(*) FROM competitions WHERE status = 'open'")->fetchColumn();

    // Total Participants
    $totalParticipants = $pdo->query("SELECT COUNT(*) FROM participants")->fetchColumn();

    // Total Scores
    $totalScores = $pdo->query("SELECT COUNT(*) FROM scores")->fetchColumn();

    // Total Championship Results
    $totalChampionshipResults = $pdo->query("SELECT COUNT(*) FROM championships")->fetchColumn();

} catch (PDOException $e) {
    // Log error or set a message for admin
    error_log("Database error fetching statistics: " . $e->getMessage());
    $statsError = "Gagal memuat statistik: " . htmlspecialchars($e->getMessage());
    // Initialize counts to 0 on error
    $totalUsers = $totalAdmins = $totalJuries = $totalRegularUsers = 0;
    $totalCompetitions = $activeCompetitions = $totalParticipants = $totalScores = $totalChampionshipResults = 0;
}
?>
<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Dashboard Admin | Sistem Penilaian Lomba</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <style>
            body {
                font-family: 'Poppins', sans-serif;
                background-color: #f0f2f5; /* Light grey background */
                color: #333;
            }        .navbar {
            background-color: #2c3e50; /* Dark blue/grey for navbar */
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .navbar-brand {
            font-weight: 700;
            color: #ecf0f1 !important; /* Light text for brand */
        }
        .navbar .nav-link {
            color: #bdc3c7 !important; /* Lighter text for links */
            transition: color 0.3s ease;
        }
        .navbar .nav-link:hover {
            color: #ecf0f1 !important; /* White on hover */
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
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
            overflow: hidden; /* For rounded corners on card-header */
        }

        .card-header {
            background-color: #3498db; /* Blue for card headers */
            color: white;
            font-weight: 600;
            padding: 1.25rem 1.5rem;
            font-size: 1.15rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .card-header.bg-dark { background-color: #34495e; } /* Dark Grey */

        .card-body {
            padding: 1.5rem;
        }
        
        .list-group-item {
            border: 1px solid rgba(0,0,0,.08);
            margin-bottom: 5px;
            border-radius: 8px !important;
            transition: all 0.2s ease-in-out;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .list-group-item:hover {
            background-color: #f8f9fa;
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .list-group-item span {
            flex-grow: 1; /* Allows text to take available space */
        }
        .list-group-item a {
            text-decoration: none;
            color: #007bff;
            font-weight: 500;
        }
        /* Specific link colors for menu items */
        .list-group-item.link-primary { color: #0d6efd; }
        .list-group-item.link-success { color: #198754; }
        .list-group-item.link-warning { color: #ffc107; }
        .list-group-item.link-info { color: #0dcaf0; }
        .list-group-item.link-danger { color: #dc3545; }
        .list-group-item.link-dark { color: #212529; } /* For Championship Results */

    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/admin_navbar.php'; ?>
    <div class="container mt-4">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-dark text-white">
                <i class="fas fa-home me-2"></i> Dashboard Administrator
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0">Selamat datang, <span class="text-primary"><?= $full_name ?></span>!</h5>
                    <span class="badge bg-primary rounded-pill px-3 py-2"><i class="fas fa-user-shield me-2"></i><?= htmlspecialchars(ucfirst($_SESSION['role'])) ?></span>
                </div>
                <p class="card-text text-muted">Anda memiliki kontrol penuh atas sistem kejuaraan ini.</p>
                
                <hr class="my-4">
                
                <!-- Statistik Umum -->
                <h4 class="mb-3"><i class="fas fa-chart-line me-2 text-primary"></i> Statistik Umum:</h4>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card bg-primary text-white h-100">
                            <div class="card-body d-flex flex-column justify-content-between">
                                <h5 class="card-title"><i class="fas fa-users me-2"></i> Total Pengguna</h5>
                                <p class="card-text display-4 fw-bold"><?= $totalUsers ?></p>
                                <p class="card-text small">Admin: <?= $totalAdmins ?>, Juri: <?= $totalJuries ?>, User: <?= $totalRegularUsers ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-success text-white h-100">
                            <div class="card-body d-flex flex-column justify-content-between">
                                <h5 class="card-title"><i class="fas fa-trophy me-2"></i> Total Lomba</h5>
                                <p class="card-text display-4 fw-bold"><?= $totalCompetitions ?></p>
                                <p class="card-text small">Aktif: <?= $activeCompetitions ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-info text-white h-100">
                            <div class="card-body d-flex flex-column justify-content-between">
                                <h5 class="card-title"><i class="fas fa-user-friends me-2"></i> Total Peserta</h5>
                                <p class="card-text display-4 fw-bold"><?= $totalParticipants ?></p>
                                <p class="card-text small">Terdaftar di semua lomba</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-warning text-dark h-100">
                            <div class="card-body d-flex flex-column justify-content-between">
                                <h5 class="card-title"><i class="fas fa-calculator me-2"></i> Total Nilai</h5>
                                <p class="card-text display-4 fw-bold"><?= $totalScores ?></p>
                                <p class="card-text small">Total skor yang dimasukkan juri</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-danger text-white h-100">
                            <div class="card-body d-flex flex-column justify-content-between">
                                <h5 class="card-title"><i class="fas fa-medal me-2"></i> Hasil Kejuaraan</h5>
                                <p class="card-text display-4 fw-bold"><?= $totalChampionshipResults ?></p>
                                <p class="card-text small">Total hasil kejuaraan yang tercatat</p>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End Statistik Umum -->

                <hr class="my-4">
                  <div id="notification-area">
                      </div>
                <h4 class="mb-3"><i class="fas fa-bars me-2 text-info"></i> Menu Admin:</h4>
                <div class="row g-3">
                    <div class="col-md-6 col-lg-4">
                        <a href="manage_competitions.php" class="list-group-item d-flex align-items-center justify-content-between p-3 link-primary">
                            <span><i class="fas fa-trophy me-2"></i> Kelola Lomba</span>
                            <i class="fas fa-chevron-right text-muted"></i>
                        </a>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <a href="manage_users.php" class="list-group-item d-flex align-items-center justify-content-between p-3 link-success">
                            <span><i class="fas fa-users-cog me-2"></i> Kelola Pengguna</span>
                            <i class="fas fa-chevron-right text-muted"></i>
                        </a>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <a href="manage_championship_results.php" class="list-group-item d-flex align-items-center justify-content-between p-3 link-dark">
                            <span><i class="fas fa-medal me-2"></i> Kelola Hasil Kejuaraan</span>
                            <i class="fas fa-chevron-right text-muted"></i>
                        </a>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <a href="reports.php" class="list-group-item d-flex align-items-center justify-content-between p-3 link-warning">
                            <span><i class="fas fa-chart-line me-2"></i> Laporan</span>
                            <i class="fas fa-chevron-right text-muted"></i>
                        </a>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <a href="manage_participants.php" class="list-group-item d-flex align-items-center justify-content-between p-3 link-info">
                            <span><i class="fas fa-users me-2"></i> Kelola Data Peserta</span>
                            <i class="fas fa-chevron-right text-muted"></i>
                        </a>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <a href="assign_jury.php" class="list-group-item d-flex align-items-center justify-content-between p-3 link-danger">
                            <span><i class="fas fa-gavel me-2"></i> Tugaskan Juri</span>
                            <i class="fas fa-chevron-right text-muted"></i>
                        </a>
                    </div>
                  	<div class="col-md-6 col-lg-4">
                        <a href="manage_announcements.php" class="list-group-item d-flex align-items-center justify-content-between p-3 link-secondary">
                            <span><i class="fas fa-bullhorn me-2"></i> Kelola Pengumuman</span>
                            <i class="fas fa-chevron-right text-muted"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function loadNotifications() {
    // Menggunakan Fetch API untuk mengambil notifikasi dari get_notifications.php
    fetch('get_notifications.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok ' + response.statusText);
            }
            return response.json();
        })
        .then(data => {
            const notificationArea = document.getElementById('notification-area');
            if (notificationArea && data.notifications) {
                if (data.notifications.length > 0) {
                    let htmlContent = `<div class="alert alert-info alert-dismissible fade show alert-custom shadow-sm" role="alert">
                                        <h5 class="alert-heading"><i class="fas fa-bell me-2"></i> Notifikasi Baru!</h5>
                                        <ul class="list-unstyled mb-2">`;
                    data.notifications.forEach(notif => {
                        let icon = '';
                        switch(notif.type) {
                            case 'success': icon = '<i class="fas fa-check-circle text-success me-2"></i>'; break;
                            case 'danger': icon = '<i class="fas fa-times-circle text-danger me-2"></i>'; break;
                            case 'warning': icon = '<i class="fas fa-exclamation-triangle text-warning me-2"></i>'; break;
                            default: icon = '<i class="fas fa-info-circle text-info me-2"></i>'; break;
                        }
                        htmlContent += `<li>${icon} ${notif.message} <span class="text-muted small ms-2">(${notif.created_at_formatted})</span></li>`;
                    });
                    htmlContent += `</ul>
                                    <hr class="my-4">
                                    <a href="mark_notifications_read.php" class="btn btn-sm btn-outline-info">Tandai Semua Sudah Dibaca</a>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>`;
                    notificationArea.innerHTML = htmlContent;
                } else {
                    // Jika tidak ada notifikasi baru, kosongkan area notifikasi
                    notificationArea.innerHTML = '';
                }
            }
        })
        .catch(error => {
            console.error('Error loading notifications:', error);
            const notificationArea = document.getElementById('notification-area');
            if(notificationArea) {
                notificationArea.innerHTML = `<div class="alert alert-danger">Gagal memuat notifikasi. Silakan refresh halaman.</div>`;
            }
        });
}

// Muat notifikasi saat halaman pertama kali dimuat
document.addEventListener('DOMContentLoaded', loadNotifications);

// Muat ulang notifikasi setiap 30 detik (Anda bisa sesuaikan intervalnya)
// Jangan terlalu sering agar tidak membebani server
setInterval(loadNotifications, 30000); // 30000 ms = 30 detik
</script>
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
<?php include __DIR__.'/../includes/footer.php'; ?>
</body>
</html>