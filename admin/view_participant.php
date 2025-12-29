<?php
require_once __DIR__.'/../includes/auth.php';
if ($_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

require_once __DIR__.'/../config/database.php';

// Validasi competition_id
$competition_id = filter_input(INPUT_GET, 'competition_id', FILTER_VALIDATE_INT);
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

// Dapatkan data peserta
$participants = $pdo->prepare("SELECT u.id, u.full_name, u.username, u.email, p.registration_date 
                             FROM participants p 
                             JOIN users u ON p.user_id = u.id 
                             WHERE p.competition_id = ?
                             ORDER BY p.registration_date DESC");
$participants->execute([$competition_id]);
$total_participants = $participants->rowCount();

$full_name = htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Admin'); // Ensure $full_name is defined for admin_navbar.php
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Peserta - <?= htmlspecialchars($competition['name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <?php include __DIR__.'/../includes/admin_navbar.php'; ?>
    
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2>Daftar Peserta</h2>
                <h4 class="text-muted"><?= htmlspecialchars($competition['name']) ?></h4>
                <p><span class="badge bg-primary">Total: <?= $total_participants ?> peserta</span></p>
            </div>
            <div>
                <a href="manage_competitions.php" class="btn btn-outline-secondary">Kembali</a>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <?php if ($total_participants <= 0): ?>
                    <div class="alert alert-info">Belum ada peserta yang mendaftar</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th></th>
                                    <th>Nama</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Tanggal Daftar</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($participants as $index => $row): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td>
                                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($row['full_name']) ?>&background=random" 
                                             class="avatar" 
                                             alt="Avatar">
                                    </td>
                                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                                    <td><?= htmlspecialchars($row['username']) ?></td>
                                    <td><?= htmlspecialchars($row['email']) ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($row['registration_date'])) ?></td>
                                    <td>
                                        <a href="view_participant.php?user_id=<?= $row['id'] ?>&competition_id=<?= $competition_id ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            Detail
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