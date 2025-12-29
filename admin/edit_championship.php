<?php
// File: /var/www/yourdomain.com/admin/edit_championship.php
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../config/database.php'; // Pastikan ini ada

// Aktifkan pelaporan kesalahan
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Pengecekan role spesifik
if ($_SESSION['role'] !== 'admin') {
    header('Location: ../login.php?error=unauthorized');
    exit();
}

// Ambil data hasil kejuaraan berdasarkan ID
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM championships WHERE id = ?");
    $stmt->execute([$id]);
    $championship = $stmt->fetch();

    if (!$championship) {
        die("Data tidak ditemukan.");
    }
}

// Proses update hasil kejuaraan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_championship_result'])) {
    $participant_name = trim($_POST['participant_name']);
    $position = (int)$_POST['position'];
    $score = (float)$_POST['score'];
    $school = trim($_POST['school']);
    
    try {
        $stmt = $pdo->prepare("UPDATE championships 
            SET participant_name = ?, position = ?, score = ?, school = ? 
            WHERE id = ?");
        $stmt->execute([$participant_name, $position, $score, $school, $id]);
        
        $_SESSION['success'] = "Hasil kejuaraan berhasil diperbarui!";
        header("Location: manage_championship_results.php");
        exit();
    } catch (PDOException $e) {
        // Debugging: Tampilkan pesan kesalahan
        echo "<div style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</div>";
        exit(); // Hentikan eksekusi setelah menampilkan kesalahan
    }
}

$full_name = htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Admin'); // Ensure $full_name is defined for admin_navbar.php
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Hasil Kejuaraan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
    </style>
</head>
<body>
    <?php include __DIR__.'/../includes/admin_navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Edit Hasil Kejuaraan</h2>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-pencil"></i> Edit Data
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nama Peserta</label>
                            <input type="text" name="participant_name" class="form-control" value="<?= htmlspecialchars($championship['participant_name']) ?>" required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Posisi</label>
                            <select name="position" class="form-select" required>
                                <option value="1" <?= $championship['position'] == 1 ? 'selected' : '' ?>>Juara 1</option>
                                <option value="2" <?= $championship['position'] == 2 ? 'selected' : '' ?>>Juara 2</option>
                                <option value="3" <?= $championship['position'] == 3 ? 'selected' : '' ?>>Juara 3</option>
                                <option value="4" <?= $championship['position'] == 4 ? 'selected' : '' ?>>Harapan 1</option>
                                <option value="5" <?= $championship['position'] == 5 ? 'selected' : '' ?>>Harapan 2</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Skor</label>
                            <input type="number" name="score" step="0.01" class="form-control" value="<?= htmlspecialchars($championship['score']) ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Asal Sekolah</label>
                            <input type="text" name="school" class="form-control" value="<?= htmlspecialchars($championship['school']) ?>" required>
                        </div>

                        <div class="col-12">
                            <button type="submit" name="update_championship_result" class="btn btn-primary">
                                <i class="bi bi-save"></i> Perbarui Hasil
                            </button>
                        </div>
                    </div>
                </form>
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