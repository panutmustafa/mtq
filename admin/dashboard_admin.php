<?php 
$page_title = 'Dashboard Admin';
require_once __DIR__ . '/../includes/admin_header.php'; 

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
<?php include __DIR__ . '/../includes/admin_sidebar.php'; ?>
<div class="content-wrapper">
    <?php include __DIR__ . '/../includes/admin_content_header.php'; ?>
    <div class="container-fluid">
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
<?php include __DIR__.'/../includes/admin_footer.php'; ?>