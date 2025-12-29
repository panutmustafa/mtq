<?php
require_once __DIR__.'/../includes/auth.php';
if ($_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

require_once __DIR__.'/../config/database.php';

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

$full_name = htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Admin'); // Ensure $full_name is defined for admin_navbar.php
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Peserta - <?= htmlspecialchars($competition['name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        .participant-photo {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            background-color: #f0f0f0;
        }
    </style>
</head>
<body>
    <?php include __DIR__.'/../includes/admin_navbar.php'; ?>
    
    <div class="container py-4">
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

    </div> <!-- Close container -->
    <footer class="mt-5 py-3 bg-light">
        <div class="container">
          <p class="text-center mb-0">Developed by <b>Panut, S.Pd.</b> | SD Negeri Jomblang 2 &copy; 2025</p>
        </div>
    </footer>
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