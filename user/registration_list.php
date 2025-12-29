<?php
// Pastikan session dan auth berjalan
session_start();
require_once __DIR__ . '/../includes/auth.php'; // Pastikan path ini benar
if ($_SESSION['role'] !== 'user') {
    header('Location: ../login.php');
    exit();
}

// Koneksi database
require_once __DIR__ . '/../config/database.php'; // Pastikan path ini benar

// Atur zona waktu ke WIB
date_default_timezone_set('Asia/Jakarta');

// Inisialisasi pesan
$message = '';

// Proses hapus pendaftaran
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $registration_id = (int)$_GET['delete'];
    $user_id = (int)$_SESSION['user_id'];

    try {
        // Hapus entri terkait di tabel scores
        $deleteScores = $pdo->prepare("DELETE FROM scores WHERE participant_id = ?");
        $deleteScores->execute([$registration_id]);

        // Hapus peserta dari tabel participants
        $stmt = $pdo->prepare("DELETE FROM participants WHERE id = ? AND user_id = ?");
        $stmt->execute([$registration_id, $user_id]);

        if ($stmt->rowCount() > 0) {
            $message = '<div class="alert alert-success alert-dismissible fade show" role="alert"><i class="fas fa-check-circle me-2"></i>Pendaftaran dan data terkait berhasil dihapus.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
        } else {
            $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert"><i class="fas fa-x-circle me-2"></i>Data tidak ditemukan atau Anda tidak memiliki izin untuk menghapus pendaftaran ini.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
        }
        header("Location: registration_list.php"); // Redirect to self after delete
        exit();
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert"><i class="fas fa-x-circle me-2"></i>Error: ' . htmlspecialchars($e->getMessage()) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    }
}

// Proses update pendaftaran
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_registration'])) {
    $registration_id = (int)$_POST['registration_id'];
    $user_id = (int)$_SESSION['user_id'];

    // Ambil semua data dari form update
    $full_name = trim($_POST['edit_full_name']);
    $nisn = trim($_POST['edit_nisn']);
    $birth_place = trim($_POST['edit_birth_place']);
    $birth_date = $_POST['edit_birth_date'];
    $class = trim($_POST['edit_class']);
    $school = trim($_POST['edit_school']);
    $category = trim($_POST['edit_category']);

    try {
        $stmt = $pdo->prepare("UPDATE participants SET
            full_name = ?, nisn = ?, birth_place = ?, birth_date = ?, class = ?, school = ?, category = ?
            WHERE id = ? AND user_id = ?");

        if ($stmt->execute([
            $full_name,
            $nisn,
            $birth_place,
            $birth_date,
            $class,
            $school,
            $category,
            $registration_id,
            $user_id
        ])) {
            $message = '<div class="alert alert-success alert-dismissible fade show" role="alert"><i class="fas fa-check-circle me-2"></i>Data pendaftaran berhasil diperbarui!<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
            // Redirect untuk mencegah resubmission form
            header("Location: registration_list.php"); // Redirect to self after update
            exit();
        } else {
            $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert"><i class="fas fa-x-circle me-2"></i>Gagal memperbarui data. Silakan coba lagi.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
        }
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert"><i class="fas fa-x-circle me-2"></i>Error: ' . htmlspecialchars($e->getMessage()) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    }
}

// Ambil lomba yang sudah diikuti user
$registered = [];
if (!empty($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT p.*, c.name AS competition_name FROM participants p
                            JOIN competitions c ON p.competition_id = c.id
                            WHERE p.user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $registered = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Ambil data hasil kejuaraan (this part might not be needed for this specific page, but keeping it for now if it's generally used for user context)
// This was previously in dashboard_user.php but was not used there. Let's remove it for now.
// $results = $pdo->query("SELECT * FROM championships ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Pendaftaran Saya | Sistem Penilaian Lomba</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f6f5;
            color: #2f3349;
            margin: 0;
            padding: 0;
        }
        .navbar {
            background-color: #ffffff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 0.5rem 1rem;
        }
        .navbar-brand {
            font-weight: 600;
            color: #2f3349 !important;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .navbar .nav-link {
            color: #6c7288 !important;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        .navbar .nav-link:hover {
            color: #2f3349 !important;
        }
        .btn-outline-light {
            border-color: #e5e7eb;
            color: #2f3349;
            transition: all 0.3s ease;
        }
        .btn-outline-light:hover {
            background-color: #2f3349;
            color: #ffffff;
        }
        .sidebar {
            width: 250px;
            background-color: #ffffff;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding-top: 70px;
            box-shadow: 2px 0 5px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
        }
        .sidebar .nav-link {
            color: #6c7288;
            font-weight: 500;
            padding: 10px 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background-color: #f5f6f5;
            color: #2f3349;
        }
        .content-wrapper {
            margin-left: 250px;
            padding: 20px;
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .content-wrapper {
                margin-left: 0;
            }
            .navbar-toggler {
                display: block !important;
            }
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            overflow: hidden;
            transition: transform 0.2s ease;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .card-header {
            background-color: #2f3349;
            color: #ffffff;
            font-weight: 600;
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .card-body {
            padding: 1.5rem;
        }
        .alert {
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .list-group-item {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            margin-bottom: 10px;
            padding: 15px;
            transition: all 0.2s ease;
        }
        .list-group-item:hover {
            background-color: #f5f6f5;
            transform: translateY(-2px);
        }
        .table {
            border-radius: 8px;
            overflow: hidden;
        }
        .table th, .table td {
            padding: 1rem;
            vertical-align: middle;
        }
        .table thead {
            background-color: #f5f6f5;
        }
        .btn {
            border-radius: 8px;
            font-weight: 500;
        }
        .form-control, .form-select {
            border-radius: 8px;
            box-shadow: none;
            border: 1px solid #e5e7eb;
        }
        .modal-content {
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .modal-header {
            background-color: #2f3349;
            color: #ffffff;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar d-none d-md-block">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="dashboard_user.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            </li>
          	<li class="nav-item">
                <a class="nav-link" href="register_competition.php"><i class="fas fa-pencil-alt"></i> Daftar Lomba</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="hasil_kejuaraan.php"><i class="fas fa-trophy"></i> Hasil kejuaraan</a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="registration_list.php"><i class="fas fa-list-alt"></i> Daftar Pendaftaran</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </li>
        </ul>
    </div>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <a class="navbar-brand" href="dashboard_user.php"><i class="fas fa-school"></i> Dashboard Sekolah</a>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-lg-center">
                    <li class="nav-item">
                        <span class="navbar-text me-lg-3 py-2 py-lg-0">
                            <i class="fas fa-user-circle"></i> Halo, <?= htmlspecialchars($_SESSION['full_name']) ?>
                        </span>
                    </li>
                    <li class="nav-item">
                        <a href="../logout.php" class="btn btn-outline-light"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register_competition.php"><i class="fas fa-pencil-alt"></i> Daftar Lomba</a>
                    </li>
                    <li class="nav-item">
                        <a href="hasil_kejuaraan.php" class="nav-link"><i class="fas fa-trophy"></i> Hasil Kejuaraan</a>
                    </li>
                    <li class="nav-item">
                        <a href="registration_list.php" class="nav-link active"><i class="fas fa-list-alt"></i> Daftar Pendaftaran</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Content -->
    <div class="content-wrapper">
        <div class="container-fluid">
            <?php if (!empty($message)) echo $message; ?>

            <div class="row">
                <!-- Lomba yang Diikuti -->
                <div class="col-lg-12 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-trophy"></i> Lomba yang Diikuti
                        </div>
                        <div class="card-body">
                            <?php if (empty($registered)): ?>
                                <div class="alert alert-info" role="alert">
                                    <i class="fas fa-info-circle me-2"></i>Murid Anda belum terdaftar di lomba apapun.
                                </div>
                            <?php else: ?>
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($registered as $reg): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-start flex-wrap">
                                            <div>
                                                <strong><i class="fas fa-flag me-2"></i><?= htmlspecialchars($reg['competition_name']) ?></strong><br>
                                                <small class="text-muted"><i class="fas fa-user me-1"></i>Nama: <?= htmlspecialchars($reg['full_name']) ?></small><br>
                                                <small class="text-muted"><i class="fas fa-id-card me-1"></i>NISN: <?= htmlspecialchars($reg['nisn']) ?></small><br>
                                                <small class="text-muted"><i class="fas fa-map-marker-alt me-1"></i>Lahir: <?= htmlspecialchars($reg['birth_place']) ?>, <?= date('d M Y', strtotime($reg['birth_date'])) ?></small><br>
                                                <small class="text-muted"><i class="fas fa-book me-1"></i>Kelas: <?= htmlspecialchars($reg['class']) ?></small><br>
                                                <small class="text-muted"><i class="fas fa-school me-1"></i>Sekolah: <?= htmlspecialchars($reg['school']) ?></small><br>
                                                <small class="text-muted"><i class="fas fa-tags me-1"></i>Kategori: <span class="badge bg-primary rounded-pill"><?= htmlspecialchars($reg['category']) ?></span></small>
                                            </div>
                                            <div class="d-flex flex-wrap gap-2 mt-3 mt-md-0">
                                                <button class="btn btn-warning btn-sm" data-bs-toggle="modal"
                                                        data-bs-target="#editModal"
                                                        data-id="<?= htmlspecialchars($reg['id']) ?>"
                                                        data-full_name="<?= htmlspecialchars($reg['full_name']) ?>"
                                                        data-nisn="<?= htmlspecialchars($reg['nisn']) ?>"
                                                        data-birth_place="<?= htmlspecialchars($reg['birth_place']) ?>"
                                                        data-birth_date="<?= htmlspecialchars($reg['birth_date']) ?>"
                                                        data-class="<?= htmlspecialchars($reg['class']) ?>"
                                                        data-school="<?= htmlspecialchars($reg['school']) ?>"
                                                        data-category="<?= htmlspecialchars($reg['category']) ?>">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <a href="registration_list.php?delete=<?= htmlspecialchars($reg['id']) ?>"
                                                   class="btn btn-danger btn-sm"
                                                   onclick="return confirm('Yakin ingin menghapus pendaftaran ini? Tindakan ini akan menghapus semua data terkait peserta ini dari lomba.')">
                                                    <i class="fas fa-trash"></i> Hapus
                                                </a>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <footer class="mt-4 text-center text-muted">
                <p>Developed by <a href="https://panutmustafa.my.id">Panut, S.Pd.</a> | SDN Jomblang 2</p>
            </footer>
        </div>
    </div>

    <!-- Modal Edit -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editModalLabel"><i class="fas fa-edit me-2"></i> Edit Data Pendaftaran Lomba</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="registration_id" id="editRegistrationId">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_full_name" class="form-label">Nama Lengkap:</label>
                                <input type="text" name="edit_full_name" id="edit_full_name" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_nisn" class="form-label">NISN:</label>
                                <input type="text" name="edit_nisn" id="edit_nisn" class="form-control" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_birth_place" class="form-label">Tempat Lahir:</label>
                                <input type="text" name="edit_birth_place" id="edit_birth_place" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_birth_date" class="form-label">Tanggal Lahir:</label>
                                <input type="date" name="edit_birth_date" id="edit_birth_date" class="form-control" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_class" class="form-label">Kelas:</label>
                                <input type="text" name="edit_class" id="edit_class" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_school" class="form-label">Asal Sekolah:</label>
                                <input type="text" name="edit_school" id="edit_school" class="form-control" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_category" class="form-label">Kategori Lomba:</label>
                            <select name="edit_category" id="edit_category" class="form-select" required>
                                <option value="">-- Pilih Kategori --</option>
                                <option value="Solo">Solo</option>
                                <option value="Group">Group</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fas fa-times"></i> Batal</button>
                        <button type="submit" name="update_registration" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Inisialisasi modal edit
        var editModal = document.getElementById('editModal');
        editModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var id = button.getAttribute('data-id');
            var fullName = button.getAttribute('data-full_name');
            var nisn = button.getAttribute('data-nisn');
            var birthPlace = button.getAttribute('data-birth_place');
            var birthDate = button.getAttribute('data-birth_date');
            var className = button.getAttribute('data-class');
            var school = button.getAttribute('data-school');
            var category = button.getAttribute('data-category');

            document.getElementById('editRegistrationId').value = id;
            document.getElementById('edit_full_name').value = fullName;
            document.getElementById('edit_nisn').value = nisn;
            document.getElementById('edit_birth_place').value = birthPlace;
            document.getElementById('edit_birth_date').value = birthDate;
            document.getElementById('edit_class').value = className;
            document.getElementById('edit_school').value = school;
            document.getElementById('edit_category').value = category;
        });

        // Toggle sidebar on mobile
        document.querySelector('.navbar-toggler').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });
    </script>
</body>
</html>
