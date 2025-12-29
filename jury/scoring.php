<?php
session_start();
require_once __DIR__.'/../includes/auth.php';
if ($_SESSION['role'] !== 'jury') {
    header('Location: ../login.php');
    exit();
}

// Pastikan zona waktu disetel ke waktu lokal Indonesia (WIB)
// Ini adalah langkah KRUSIAL untuk konsistensi waktu
date_default_timezone_set('Asia/Jakarta');

require_once __DIR__.'/../config/database.php';

// Get competition ID from query string
$competition_id = isset($_GET['competition_id']) ? (int)$_GET['competition_id'] : 0;
if ($competition_id <= 0) {
    header('Location: assignments.php');
    exit();
}

$jury_id = $_SESSION['user_id'];
$jury_name = htmlspecialchars($_SESSION['full_name'] ?? 'Juri Tidak Dikenal'); // Ambil nama juri

// Check if this competition is assigned to the jury (improved check if needed for actual assignments)
// For now, it just checks if the competition exists and has participants
$stmt = $pdo->prepare("
    SELECT c.id, c.name 
    FROM competitions c
    WHERE c.id = ? 
    LIMIT 1
");
$stmt->execute([$competition_id]);
$competition = $stmt->fetch();

if (!$competition) {
    // If competition not found or not assigned to jury
    $_SESSION['error'] = "Kompetisi tidak ditemukan atau tidak ditugaskan kepada Anda.";
    header('Location: assignments.php');
    exit();
}

// Get participants for this competition
$stmt = $pdo->prepare("
    SELECT p.id, p.full_name, p.nisn, p.school, 
           s.score, s.notes, s.id as score_id
    FROM participants p
    LEFT JOIN scores s ON p.id = s.participant_id AND s.jury_id = ? AND s.competition_id = ?
    WHERE p.competition_id = ?
    ORDER BY p.full_name
");
$stmt->execute([$jury_id, $competition_id, $competition_id]);
$participants = $stmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $participant_id = (int)$_POST['participant_id'];
    $score = (float)$_POST['score'];
    $notes = trim($_POST['notes'] ?? '');

    // Validate score (between 0 and 100)
    if ($score < 0 || $score > 100) {
        $_SESSION['error'] = "Nilai harus antara 0 dan 100.";
        header("Location: scoring.php?competition_id=$competition_id");
        exit();
    }
    
    // Dapatkan waktu saat ini dalam format MySQL yang sesuai dengan zona waktu yang sudah disetel
    $current_time = date('Y-m-d H:i:s'); 
    
    try {
        // Check if score already exists for this participant, jury, and competition
        $stmt = $pdo->prepare("
            SELECT id FROM scores 
            WHERE participant_id = ? 
            AND jury_id = ? 
            AND competition_id = ?
        ");
        $stmt->execute([$participant_id, $jury_id, $competition_id]);
        $existing_score = $stmt->fetch();
        
        if ($existing_score) {
            // Update existing score: Hanya submitted_at yang diubah
            $stmt = $pdo->prepare("
                UPDATE scores 
                SET score = ?, 
                    notes = ?, 
                    submitted_at = ?  -- Update hanya submitted_at
                WHERE id = ?
            ");
            $stmt->execute([$score, $notes, $current_time, $existing_score['id']]); 
        } else {
            // Insert new score: created_at dan submitted_at diisi
            $stmt = $pdo->prepare("
                INSERT INTO scores (
                    participant_id, 
                    jury_id, 
                    competition_id, 
                    score, 
                    notes, 
                    created_at,     -- Tambahkan created_at
                    submitted_at    -- Tetap gunakan submitted_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?) -- Tambahkan placeholder untuk created_at
            ");
            $stmt->execute([
                $participant_id, 
                $jury_id, 
                $competition_id, 
                $score, 
                $notes, 
                $current_time,  // Nilai untuk created_at
                $current_time   // Nilai untuk submitted_at
            ]);
        }
        
        $_SESSION['success'] = "Nilai berhasil disimpan!";

        // --- Start of Notification Logic ---

        // 1. Get total number of participants in this competition
        $stmt_total_participants = $pdo->prepare("SELECT COUNT(id) FROM participants WHERE competition_id = ?");
        $stmt_total_participants->execute([$competition_id]);
        $total_participants_to_score = $stmt_total_participants->fetchColumn();

        // 2. Get number of scores this jury has submitted for this competition
        $stmt_scores_submitted = $pdo->prepare("SELECT COUNT(id) FROM scores WHERE jury_id = ? AND competition_id = ?");
        $stmt_scores_submitted->execute([$jury_id, $competition_id]);
        $scores_submitted_by_jury = $stmt_scores_submitted->fetchColumn();

        // 3. Check if jury has finished all scoring for this competition
        if ($scores_submitted_by_jury > 0 && $scores_submitted_by_jury == $total_participants_to_score) {
            // Jury has completed scoring for this competition!
            // Check if notification already exists to avoid duplicates
            $notif_message_pattern = "Juri **{$jury_name}** telah menyelesaikan semua penilaian untuk kompetisi ID: **{$competition_id}**.";
            
            $stmt_check_notif = $pdo->prepare("SELECT id FROM notifications 
                                                WHERE related_entity_type = 'jury_completion' 
                                                AND related_entity_id = ? 
                                                AND message = ?");
            $stmt_check_notif->execute([$competition_id, $notif_message_pattern]);
            $notif_exists = $stmt_check_notif->fetchColumn();

            if (!$notif_exists) {
                // Insert new notification for admin
                $stmt_insert_notif = $pdo->prepare("INSERT INTO notifications 
                    (recipient_user_id, message, type, related_entity_id, related_entity_type) 
                    VALUES (?, ?, ?, ?, ?)");
                $stmt_insert_notif->execute([
                    null, // recipient_user_id is NULL for all admins to see
                    $notif_message_pattern,
                    'success', 
                    $competition_id, 
                    'jury_completion' 
                ]);
            }
        }

        // --- End of Notification Logic ---

    } catch (PDOException $e) {
        $_SESSION['error'] = "Gagal menyimpan nilai: " . $e->getMessage();
        // Log the error for debugging, but don't show raw error to user in production
        error_log("Scoring error: " . $e->getMessage());
    }
    
    header("Location: scoring.php?competition_id=$competition_id");
    exit();
}

$full_name = htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Jury'); // Ensure $full_name is defined for jury_navbar.php
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penilaian Peserta - Juri</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        .scoring-form {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-top: 15px;
        }
        .participant-card {
            transition: all 0.3s ease;
            margin-bottom: 20px;
            border: 1px solid #dee2e6;
        }
        .participant-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .score-input {
            max-width: 100px;
        }
    </style>
</head>
<body>
    <?php include __DIR__.'/../includes/jury_navbar.php'; ?>
    
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-clipboard2-check"></i> Penilaian: <?= htmlspecialchars($competition['name']) ?></h2>
            <a href="assignments.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Kembali ke Daftar Penugasan
            </a>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); ?>
        
        <?php elseif (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (empty($participants)): ?>
            <div class="alert alert-info">
                Tidak ada peserta yang terdaftar untuk lomba ini.
            </div>
        <?php else: ?>
            <?php foreach ($participants as $participant): ?>
                <div class="card participant-card">
                    <div class="card-body">
                        <h5 class="card-title">
                            <?= htmlspecialchars($participant['full_name']) ?>
                        </h5>
                        <div class="row">
                            <div class="col-md-6">
                                <p class="card-text">
                                    <strong>NISN:</strong> <?= htmlspecialchars($participant['nisn']) ?>
                                </p>
                                <p class="card-text">
                                    <strong>Sekolah:</strong> <?= htmlspecialchars($participant['school']) ?>
                                </p>
                            </div>
                        </div>
                        
                        <form method="post" class="scoring-form">
                            <input type="hidden" name="participant_id" value="<?= $participant['id'] ?>">
                            <input type="hidden" name="competition_id" value="<?= $competition_id ?>"> 
                            <div class="row g-3 align-items-center">
                                <div class="col-md-3">
                                    <label for="score" class="form-label">
                                        <strong>Nilai (0-100)</strong>
                                    </label>
                                    <input type="number" name="score" 
                                           class="form-control score-input" 
                                           min="0" max="100" step="0.1" 
                                           value="<?= htmlspecialchars($participant['score'] ?? '') ?>" 
                                           required>
                                </div>
                                <div class="col-md-7">
                                    <label for="notes" class="form-label">
                                        <strong>Catatan</strong>
                                    </label>
                                    <textarea name="notes" class="form-control" rows="2"><?= htmlspecialchars($participant['notes'] ?? '') ?></textarea>
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save"></i> Simpan
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
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
