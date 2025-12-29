<?php
session_start();
require_once __DIR__.'/../includes/auth.php';
if ($_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

require_once __DIR__.'/../config/database.php';

// Proses penugasan juri
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $competition_id = (int)$_POST['competition_id'];
    $jury_id = (int)$_POST['jury_id'];
    
    try {
        // Cek apakah penugasan sudah ada
        $stmt = $pdo->prepare("SELECT * FROM jury_assignments WHERE competition_id = ? AND jury_id = ?");
        $stmt->execute([$competition_id, $jury_id]);
        
        if ($stmt->rowCount() > 0) {
            $_SESSION['error'] = "Juri sudah ditugaskan ke lomba ini";
        } else {
            $stmt = $pdo->prepare("INSERT INTO jury_assignments (competition_id, jury_id) VALUES (?, ?)");
            $stmt->execute([$competition_id, $jury_id]);
            $_SESSION['success'] = "Juri berhasil ditugaskan";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Gagal menugaskan juri: " . $e->getMessage();
    }
    
    header("Location: assign_jury.php");
    exit();
}

// Ambil daftar lomba
$competitions = $pdo->query("SELECT * FROM competitions WHERE status = 'open'")->fetchAll();

// Ambil daftar juri
$juries = $pdo->query("SELECT * FROM users WHERE role = 'jury'")->fetchAll();

// Paginasi untuk daftar penugasan
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10; // Jumlah penugasan per halaman
$offset = ($page - 1) * $records_per_page;

// Ambil total record untuk penugasan
$total_assignments_stmt = $pdo->query("SELECT COUNT(*) 
                                        FROM jury_assignments ja
                                        JOIN competitions c ON ja.competition_id = c.id
                                        JOIN users u ON ja.jury_id = u.id");
$total_assignments = $total_assignments_stmt->fetchColumn();
$total_pages_assignments = ceil($total_assignments / $records_per_page);

// Ambil daftar penugasan yang sudah ada dengan paginasi
$stmt_assignments = $pdo->prepare("
    SELECT ja.*, c.name as competition_name, u.full_name as jury_name 
    FROM jury_assignments ja
    JOIN competitions c ON ja.competition_id = c.id
    JOIN users u ON ja.jury_id = u.id
    ORDER BY c.name, u.full_name LIMIT :limit OFFSET :offset
");
$stmt_assignments->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
$stmt_assignments->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt_assignments->execute();
$assignments = $stmt_assignments->fetchAll();

$full_name = htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Admin'); // Ensure $full_name is defined for admin_navbar.php
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penugasan Juri</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
    </style>
</head>
<body>
    <?php include __DIR__.'/../includes/admin_navbar.php'; ?>
    
    <div class="container py-4">
        <h2><i class="bi bi-people-fill"></i> Penugasan Juri ke Lomba</h2>
        
        <!-- Tombol Kembali ke Dashboard -->
        <div class="mb-3">
            <a href="dashboard_admin.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Kembali ke Dashboard
            </a>
        </div>

        <!-- Notifikasi -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <!-- Form Penugasan -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>Tugaskan Juri</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Pilih Lomba</label>
                            <select name="competition_id" class="form-select" required>
                                <option value="">-- Pilih Lomba --</option>
                                <?php foreach ($competitions as $comp): ?>
                                    <option value="<?= $comp['id'] ?>"><?= htmlspecialchars($comp['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Pilih Juri</label>
                            <select name="jury_id" class="form-select" required>
                                <option value="">-- Pilih Juri --</option>
                                <?php foreach ($juries as $jury): ?>
                                    <option value="<?= $jury['id'] ?>"><?= htmlspecialchars($jury['full_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Simpan Penugasan
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Daftar Penugasan -->
        <div class="card">
            <div class="card-header">
                <h5>Daftar Penugasan Juri</h5>
            </div>
            <div class="card-body">
                <?php if (empty($assignments)): ?>
                    <div class="alert alert-info">Belum ada penugasan juri</div>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Lomba</th>
                                <th>Juri</th>
                                <th>Ditugaskan Pada</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($assignments as $assign): ?>
                                <tr>
                                    <td><?= htmlspecialchars($assign['competition_name']) ?></td>
                                    <td><?= htmlspecialchars($assign['jury_name']) ?></td>
                                    <td><?= date('d M Y H:i', strtotime($assign['assigned_at'])) ?></td>
                                    <td>
                                        <a href="delete_assignment.php?id=<?= $assign['id'] ?>" 
                                           class="btn btn-danger btn-sm"
                                           onclick="return confirm('Yakin ingin menghapus penugasan ini?')">
                                            <i class="bi bi-trash"></i> Hapus
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <!-- Navigasi Paginasi -->
                    <nav aria-label="Assignment Pagination" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page - 1 ?>">Previous</a>
                            </li>
                            <?php
                            $num_links_to_show = 5; // Total number of links to display (e.g., 1 2 [3] 4 5)
                            $start_page = max(1, $page - floor($num_links_to_show / 2));
                            $end_page = min($total_pages_assignments, $start_page + $num_links_to_show - 1);

                            // Adjust start/end if we hit the total_pages boundary
                            if ($end_page - $start_page + 1 < $num_links_to_show) {
                                $start_page = max(1, $end_page - $num_links_to_show + 1);
                            }
                            
                            $filter_params_suffix = ''; // No additional filters for assign_jury.php

                            // Always show first page if not already in range
                            if ($start_page > 1) {
                                echo '<li class="page-item"><a class="page-link" href="?page=1'. $filter_params_suffix .'">1</a></li>';
                                if ($start_page > 2) { // Show ellipsis if there's a gap after page 1
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                            }

                            for ($i = $start_page; $i <= $end_page; $i++) {
                                echo '<li class="page-item '. ($i == $page ? 'active' : '') .'"><a class="page-link" href="?page='. $i . $filter_params_suffix .'">'. $i .'</a></li>';
                            }

                            // Always show last page if not already in range
                            if ($end_page < $total_pages_assignments) {
                                if ($end_page < $total_pages_assignments - 1) { // Show ellipsis if there's a gap before last page
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                                echo '<li class="page-item"><a class="page-link" href="?page='. $total_pages_assignments . $filter_params_suffix .'">'. $total_pages_assignments .'</a></li>';
                            }
                            ?>
                            <li class="page-item <?= $page >= $total_pages_assignments ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page + 1 ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
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