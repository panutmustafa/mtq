<?php
session_start();
require_once '../includes/auth.php';
requireRole('jury');
require_once '../config/database.php';

$jury_id = $_SESSION['user_id'];

// Inisialisasi pesan feedback
$feedback_message = '';
$feedback_type = ''; // success, danger

// Handle score update POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_score'])) {
    $score_id = filter_input(INPUT_POST, 'score_id', FILTER_VALIDATE_INT);
    $new_score = filter_input(INPUT_POST, 'score', FILTER_VALIDATE_FLOAT);
    $new_notes = filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_STRING);

    // Basic validation
    if (!$score_id || $new_score === false) { // new_score can be 0, so check for false
        $feedback_message = "Data penilaian tidak valid.";
        $feedback_type = "danger";
    } else {
        try {
            // Ensure the jury member owns this score before updating
            $stmt_check = $pdo->prepare("SELECT jury_id FROM scores WHERE id = ?");
            $stmt_check->execute([$score_id]);
            $owner_id = $stmt_check->fetchColumn();

            if ($owner_id != $jury_id) {
                $feedback_message = "Anda tidak diizinkan mengubah penilaian ini.";
                $feedback_type = "danger";
            } else {
                $stmt_update = $pdo->prepare("UPDATE scores SET score = ?, notes = ? WHERE id = ? AND jury_id = ?");
                $stmt_update->execute([$new_score, $new_notes, $score_id, $jury_id]);
                
                $feedback_message = "Nilai berhasil diperbarui!";
                $feedback_type = "success";
            }
        } catch (PDOException $e) {
            $feedback_message = "Terjadi kesalahan saat memperbarui nilai: " . htmlspecialchars($e->getMessage());
            $feedback_type = "danger";
        }
    }
    // Redirect to prevent form resubmission and display feedback
    $_SESSION['feedback_message'] = $feedback_message;
    $_SESSION['feedback_type'] = $feedback_type;
    header('Location: view_scores.php');
    exit();
}

// Tangani pesan feedback dari SESSION setelah redirect
if (isset($_SESSION['feedback_message'])) {
    $feedback_message = $_SESSION['feedback_message'];
    $feedback_type = $_SESSION['feedback_type'];
    unset($_SESSION['feedback_message']);
    unset($_SESSION['feedback_type']);
}

// Query untuk mengambil data scoring, termasuk score.id
$stmt = $pdo->prepare("
    SELECT s.id, p.full_name, p.nisn, p.school, s.score, s.notes, c.name as competition_name
    FROM scores s
    JOIN participants p ON s.participant_id = p.id
    JOIN competitions c ON s.competition_id = c.id
    JOIN users u ON s.jury_id = u.id
    WHERE s.jury_id = ?
");
$stmt->execute([$jury_id]);
$scores = $stmt->fetchAll();

$full_name = htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Jury'); // Ensure $full_name is defined for jury_navbar.php
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Scoring Juri</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        /* Custom styling for table header with shading */
        .table-header-custom th {
            /* Gradient background for shading effect */
            background: linear-gradient(to right, #4a4a4a, #2c3e50); /* Darker gradient */
            color: white; /* White text for visibility */
            vertical-align: middle;
            padding: 1rem 0.75rem; /* More padding for better spacing */
            border-bottom: 2px solid #5a6268; /* Subtle border at the bottom */
            /* Optional: border-radius for a slightly softer look, but might conflict with table borders */
            /* border-radius: 0.25rem 0.25rem 0 0; */ 
        }
        /* Ensure table border styling is consistent */
        .table-bordered thead th {
            border-bottom-width: 2px;
        }
    </style>
</head>
<body>
    <?php include __DIR__.'/../includes/jury_navbar.php'; ?>
    
    <div class="container py-4">
        <h2>Hasil Scoring oleh Juri</h2>

        <!-- Tombol Kembali ke Dashboard -->
        <a href="dashboard_jury.php" class="btn btn-secondary mb-3">
            <i class="bi bi-arrow-left"></i> Kembali ke Dashboard
        </a>

        <?php if (empty($scores)): ?>
            <div class="alert alert-info">
                Tidak ada data scoring yang ditemukan.
            </div>
        <?php else: ?>
            <?php if ($feedback_message): ?>
                <div class="alert alert-<?= htmlspecialchars($feedback_type) ?> alert-dismissible fade show" role="alert">
                    <?= $feedback_message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <table class="table table-bordered">
                <thead class="table-header-custom"> <!-- Add custom class here -->
                    <tr>
                        <th class="text-center">Nama Peserta</th>
                        <th class="text-center">NISN</th>
                        <th class="text-center">Sekolah</th>
                        <th class="text-center">Kompetisi</th>
                        <th class="text-center">Nilai</th>
                        <th class="text-center">Catatan</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($scores as $score): ?>
                        <tr>
                            <td><?= htmlspecialchars($score['full_name']) ?></td>
                            <td><?= htmlspecialchars($score['nisn']) ?></td>
                            <td><?= htmlspecialchars($score['school']) ?></td>
                            <td><?= htmlspecialchars($score['competition_name']) ?></td>
                            <td><?= htmlspecialchars($score['score']) ?></td>
                            <td><?= htmlspecialchars($score['notes']) ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editScoreModal"
                                    data-id="<?= htmlspecialchars($score['id']) ?>"
                                    data-score="<?= htmlspecialchars($score['score']) ?>"
                                    data-notes="<?= htmlspecialchars($score['notes']) ?>">
                                    Edit
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Edit Score Modal -->
            <div class="modal fade" id="editScoreModal" tabindex="-1" aria-labelledby="editScoreModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form action="view_scores.php" method="POST">
                            <div class="modal-header">
                                <h5 class="modal-title" id="editScoreModalLabel">Edit Nilai Penilaian</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="score_id" id="edit-score-id">
                                <div class="mb-3">
                                    <label for="edit-score" class="form-label">Nilai</label>
                                    <input type="number" step="0.01" class="form-control" id="edit-score" name="score" required>
                                </div>
                                <div class="mb-3">
                                    <label for="edit-notes" class="form-label">Catatan</label>
                                    <textarea class="form-control" id="edit-notes" name="notes" rows="3"></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" name="update_score" class="btn btn-primary">Simpan Perubahan</button>
                            </div>
                        </form>
                    </div>
                </div>
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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var editScoreModal = document.getElementById('editScoreModal');
            editScoreModal.addEventListener('show.bs.modal', function (event) {
                // Button that triggered the modal
                var button = event.relatedTarget;
                // Extract info from data-bs-* attributes
                var scoreId = button.getAttribute('data-id');
                var scoreValue = button.getAttribute('data-score');
                var scoreNotes = button.getAttribute('data-notes');

                // Update the modal's content.
                var modalTitle = editScoreModal.querySelector('.modal-title');
                var modalScoreIdInput = editScoreModal.querySelector('#edit-score-id');
                var modalScoreInput = editScoreModal.querySelector('#edit-score');
                var modalNotesInput = editScoreModal.querySelector('#edit-notes');

                modalTitle.textContent = 'Edit Nilai untuk ID Penilaian: ' + scoreId;
                modalScoreIdInput.value = scoreId;
                modalScoreInput.value = scoreValue;
                modalNotesInput.value = scoreNotes;
            });
        });
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
</body>
</html>
