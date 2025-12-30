<?php
$page_title = 'Daftar Peserta';
require_once __DIR__.'/../includes/admin_header.php';

// Validasi dan ambil competition_id dari URL
$competition_id = isset($_GET['competition_id']) ? (int)$_GET['competition_id'] : 0;

if ($competition_id <= 0) {
    header('Location: manage_competitions.php');
    exit();
}

// Dapatkan detail lomba
$stmt = $pdo->prepare("SELECT * FROM competitions WHERE id = ?");
$stmt->execute([$competition_id]);
$competition = $stmt->fetch();

if (!$competition) {
    header('Location: manage_competitions.php');
    exit();
}

// Dapatkan daftar peserta
$participants = [];
try {
    $stmt = $pdo->prepare("SELECT u.id, u.full_name, u.username, p.registration_date 
                          FROM participants p 
                          JOIN users u ON p.user_id = u.id 
                          WHERE p.competition_id = ? 
                          ORDER BY p.registration_date DESC");
    $stmt->execute([$competition_id]);
    $participants = $stmt->fetchAll();
} catch (PDOException $e) {
    $message = '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
}

// Hitung total peserta
$total_participants = count($participants);
?>
<?php include __DIR__ . '/../includes/admin_sidebar.php'; ?>
<div class="content-wrapper">
    <?php include __DIR__ . '/../includes/admin_content_header.php'; ?>
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2><i class="bi bi-people-fill"></i> Daftar Peserta</h2>
                <h4 class="text-muted"><?= htmlspecialchars($competition['name']) ?></h4>
                <p class="text-muted">
                    Total peserta: <?= $total_participants ?>
                    |
                    Periode: <?= date('d M Y', strtotime($competition['start_date'])) ?> - <?= date('d M Y', strtotime($competition['end_date'])) ?>
                </p>
            </div>
            <div>
                <a href="manage_competitions.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
                <button class="btn btn-primary" onclick="window.print()">
                    <i class="bi bi-printer"></i> Cetak
                </button>
            </div>
        </div>

        <!-- Tombol Kembali ke Dashboard -->
        <div class="mb-3">
            <a href="dashboard_admin.php" class="btn btn-outline-primary">
                <i class="bi bi-house"></i> Kembali ke Dashboard
            </a>
        </div>

        <!-- Pesan Notifikasi -->
        <?php if (!empty($message)) echo $message; ?>

        <!-- Tabel Peserta -->
        <div class="card">
            <div class="card-body">
                <?php if (empty($participants)): ?>
                    <div class="alert alert-info">
                        Belum ada peserta yang mendaftar untuk lomba ini.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Foto</th>
                                    <th>Nama Peserta</th>
                                    <th>Username</th>
                                    <th>Tanggal Daftar</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($participants as $index => $participant): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td>
                                            <img src="https://placehold.co/100?text=<?= substr($participant['full_name'], 0, 1) ?>" 
                                                 class="participant-photo" 
                                                 alt="Foto <?= htmlspecialchars($participant['full_name']) ?>">
                                        </td>
                                        <td><?= htmlspecialchars($participant['full_name']) ?></td>
                                        <td><?= htmlspecialchars($participant['username']) ?></td>
                                        <td><?= date('d M Y H:i', strtotime($participant['registration_date'])) ?></td>
                                        <td>
                                            <a href="view_participant.php?user_id=<?= $participant['id'] ?>&competition_id=<?= $competition_id ?>" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i> Detail
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php include __DIR__.'/../includes/admin_footer.php'; ?>