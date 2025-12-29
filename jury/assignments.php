<?php
session_start();
require_once __DIR__.'/../includes/auth.php';
if ($_SESSION['role'] !== 'jury') {
    header('Location: ../login.php');
    exit();
}

require_once __DIR__.'/../config/database.php';

// Get jury ID
$jury_id = $_SESSION['user_id'];

// Query untuk mengambil lomba yang ditugaskan ke juri
$stmt = $pdo->prepare("
    SELECT c.id, c.name, c.description, c.status, 
           COUNT(p.id) as total_participants,
           COUNT(s.id) as scored_participants
    FROM competitions c
    JOIN jury_assignments ja ON c.id = ja.competition_id
    LEFT JOIN participants p ON c.id = p.competition_id
    LEFT JOIN scores s ON p.id = s.participant_id AND s.jury_id = ?
    WHERE ja.jury_id = ? AND c.status IN ('open', 'closed')
    GROUP BY c.id
");
$stmt->execute([$jury_id, $jury_id]);

// Debugging: Cek jumlah baris yang dikembalikan
if ($stmt->rowCount() === 0) {
    // echo "Tidak ada lomba yang ditugaskan untuk juri ini."; // Removed for cleaner output
    $assignments = []; // Set assignments ke array kosong
} else {
    $assignments = $stmt->fetchAll();
}

// Calculate scoring progress
foreach ($assignments as &$assignment) {
    $assignment['progress'] = $assignment['total_participants'] > 0 
        ? round(($assignment['scored_participants'] / $assignment['total_participants']) * 100) 
        : 0;
}
unset($assignment);

$full_name = htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Jury'); // Ensure $full_name is defined for jury_navbar.php
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Penugasan - Juri</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        .progress {
            height: 25px;
        }
        .assignment-card {
            transition: all 0.3s ease;
        }
        .assignment-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .status-badge {
            font-size: 0.8rem;
            padding: 5px 10px;
            border-radius: 20px;
        }
        .status-open { background-color: #28a745; color: white; }
        .status-closed { background-color: #dc3545; color: white; }
    </style>
</head>
<body>
    <?php include __DIR__.'/../includes/jury_navbar.php'; // Use a new jury specific navbar ?>
    
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-list-task"></i> Daftar Penugasan</h2>
            <span class="badge bg-primary">Juri: <?= htmlspecialchars($_SESSION['full_name']) ?></span>
        </div>

        <!-- Tombol Kembali ke Dashboard -->
        <a href="dashboard_jury.php" class="btn btn-secondary mb-3">
            <i class="bi bi-arrow-left"></i> Kembali ke Dashboard
        </a>

        <?php if (empty($assignments)): ?>
            <div class="alert alert-info">
                Anda belum ditugaskan untuk menilai lomba apapun.
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($assignments as $assignment): ?>
                    <div class="col-md-6">
                        <div class="card assignment-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <h5 class="card-title"><?= htmlspecialchars($assignment['name']) ?></h5>
                                    <span class="status-badge status-<?= $assignment['status'] ?>">
                                        <?= strtoupper($assignment['status']) ?>
                                    </span>
                                </div>
                                <p class="card-text"><?= nl2br(htmlspecialchars($assignment['description'])) ?></p>
                                
                                <div class="mt-4">
                                    <h6>Progress Penilaian:</h6>
                                    <div class="progress mb-2">
                                        <div class="progress-bar 
                                            <?= $assignment['progress'] == 100 ? 'bg-success' : 'bg-primary' ?>" 
                                            role="progressbar" 
                                            style="width: <?= $assignment['progress'] ?>%" 
                                            aria-valuenow="<?= $assignment['progress'] ?>" 
                                            aria-valuemin="0" 
                                            aria-valuemax="100">
                                            <?= $assignment['progress'] ?>%
                                        </div>
                                    </div>
                                    <p class="small text-muted">
                                        <?= $assignment['scored_participants'] ?> dari <?= $assignment['total_participants'] ?> peserta telah dinilai
                                    </p>
                                </div>
                            </div>
                            <div class="card-footer bg-white">
                                <a href="scoring.php?competition_id=<?= $assignment['id'] ?>" 
                                   class="btn btn-primary w-100">
                                    <i class="bi bi-pencil-square"></i> Mulai Penilaian
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
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