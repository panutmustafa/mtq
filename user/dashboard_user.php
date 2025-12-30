<?php
$page_title = 'Dashboard Sekolah';
require_once __DIR__ . '/../includes/user_header.php';

// Inisialisasi pesan
$message = '';

// Get logged-in user's ID
$user_id = $_SESSION['user_id'];

// Fetch user-specific statistics
try {
    // Total registered competitions
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT competition_id) FROM participants WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $total_registered_competitions = $stmt->fetchColumn();

    // Total results available
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT c.competition_id) FROM championships c JOIN participants p ON c.participant_id = p.id WHERE p.user_id = ?");
    $stmt->execute([$user_id]);
    $total_results_available = $stmt->fetchColumn();

    // Latest competition result
    $stmt = $pdo->prepare("SELECT ch.competition_name, ch.position, ch.score FROM championships ch JOIN participants p ON ch.participant_id = p.id WHERE p.user_id = ? ORDER BY ch.created_at DESC LIMIT 1");
    $stmt->execute([$user_id]);
    $latest_result = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Database error fetching user statistics: " . $e->getMessage());
    $message = '<div class="alert alert-danger" role="alert">Error fetching statistics.</div>';
    $total_registered_competitions = 0;
    $total_results_available = 0;
    $latest_result = null;
}

// Ambil pengumuman aktif
$announcements = $pdo->query("SELECT a.*, u.full_name AS created_by_name FROM announcements a JOIN users u ON a.created_by = u.id WHERE a.is_active = 1 ORDER BY a.created_at DESC")->fetchAll();
?>
<?php include __DIR__ . '/../includes/user_sidebar.php'; ?>
<div class="content-wrapper">
    <?php include __DIR__ . '/../includes/user_content_navbar.php'; ?>
    <div class="container-fluid">
        <?php if (!empty($message)) echo $message; ?>

        <h2 class="mb-4"><i class="fas fa-tachometer-alt me-2"></i> Dashboard Sekolah</h2>

        <!-- Statistik Pengguna -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card text-white bg-primary h-100">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <h5 class="card-title"><i class="fas fa-list-alt me-2"></i> Lomba Didaftar</h5>
                        <p class="card-text display-4 fw-bold"><?= $total_registered_competitions ?></p>
                        <p class="card-text small">Total lomba yang Anda ikuti.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-success h-100">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <h5 class="card-title"><i class="fas fa-medal me-2"></i> Hasil Tersedia</h5>
                        <p class="card-text display-4 fw-bold"><?= $total_results_available ?></p>
                        <p class="card-text small">Jumlah lomba dengan hasil kejuaraan.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-info h-100">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <h5 class="card-title"><i class="fas fa-trophy me-2"></i> Hasil Terbaru</h5>
                        <?php if ($latest_result): ?>
                            <p class="card-text fs-5">Lomba: <strong><?= htmlspecialchars($latest_result['competition_name']) ?></strong></p>
                            <p class="card-text fs-5">Posisi: <strong><?= htmlspecialchars($latest_result['position']) ?></strong></p>
                            <p class="card-text fs-5">Skor: <strong><?= number_format($latest_result['score'], 2) ?></strong></p>
                        <?php else: ?>
                            <p class="card-text">Belum ada hasil kejuaraan.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

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
    </div>
<?php include __DIR__ . '/../includes/user_footer.php'; ?>
