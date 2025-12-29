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

// Inisialisasi pesan
$message = '';

// Proses pendaftaran lomba (tetap sama)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_competition'])) {
    $competition_id = (int)$_POST['competition_id'];
    $user_id = (int)$_SESSION['user_id'];

    // Ambil data peserta dari form
    $full_name = trim($_POST['full_name']);
    $nisn = trim($_POST['nisn']);
    $birth_place = trim($_POST['birth_place']);
    $birth_date = $_POST['birth_date'];
    $class = trim($_POST['class']);
    $school = trim($_POST['school']);
    $category = trim($_POST['category']);

    try {
        // Cek pendaftaran ganda
        $stmt = $pdo->prepare("SELECT * FROM participants WHERE user_id = ? AND competition_id = ?");
        $stmt->execute([$user_id, $competition_id]);

        if ($stmt->rowCount() > 0) {
            $message = '<div class="alert alert-warning alert-dismissible fade show" role="alert">Anda sudah terdaftar di lomba ini.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
        } else {
            // Simpan SEMUA data ke tabel participants
            $insert = $pdo->prepare("INSERT INTO participants
                (user_id, competition_id, full_name, nisn, birth_place, birth_date, class, school, category)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

            if ($insert->execute([
                $user_id,
                $competition_id,
                $full_name,
                $nisn,
                $birth_place,
                $birth_date,
                $class,
                $school,
                $category
            ])) {
                $message = '<div class="alert alert-success alert-dismissible fade show" role="alert">Pendaftaran berhasil!<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                // Redirect untuk mencegah resubmission form
                header("Location: dashboard_user.php");
                exit();
            } else {
                $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">Gagal mendaftar lomba. Silakan coba lagi.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
            }
        }
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">Error: ' . htmlspecialchars($e->getMessage()) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    }
}

// Proses hapus pendaftaran (tetap sama)
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
            $message = '<div class="alert alert-success alert-dismissible fade show" role="alert">Pendaftaran dan data terkait berhasil dihapus.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
        } else {
            $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">Data tidak ditemukan atau Anda tidak memiliki izin untuk menghapus pendaftaran ini.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
        }
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">Error: ' . htmlspecialchars($e->getMessage()) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    }
}

// Proses update pendaftaran (DIUBAH)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_registration'])) {
    $registration_id = (int)$_POST['registration_id'];
    $user_id = (int)$_SESSION['user_id']; // Pastikan user_id didefinisikan

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
            $message = '<div class="alert alert-success alert-dismissible fade show" role="alert">Data pendaftaran berhasil diperbarui!<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
            // Redirect untuk mencegah resubmission form
            header("Location: dashboard_user.php");
            exit();
        } else {
             $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">Gagal memperbarui data. Silakan coba lagi.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
        }
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">Error: ' . htmlspecialchars($e->getMessage()) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    }
}

// Ambil data lomba yang tersedia (status 'open')
$competitions = $pdo->query("SELECT * FROM competitions WHERE status = 'open'")->fetchAll();

// Ambil lomba yang sudah diikuti user (DIUBAH UNTUK MENGAMBIL SEMUA KOLOM PARTICIPANTS)
$registered = [];
if (!empty($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT p.*, c.name AS competition_name FROM participants p
                           JOIN competitions c ON p.competition_id = c.id
                           WHERE p.user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $registered = $stmt->fetchAll(PDO::FETCH_ASSOC); // Fetch as associative array
}

// Ambil data hasil kejuaraan
$results = $pdo->query("SELECT * FROM championships ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Sekolah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
  	<link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark px-3">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard_user.php"><i class="bi bi-speedometer2 me-2"></i> Dashboard Sekolah</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">
                    <li class="nav-item">
                        <span class="navbar-text me-lg-3 text-white py-2 py-lg-0">
                            <i class="bi bi-person-circle me-2"></i> Halo, <?= htmlspecialchars($_SESSION['full_name']) ?>
                        </span>
                    </li>
                    <li class="nav-item">
                        <a href="../logout.php" class="btn btn-outline-light rounded-pill px-3"><i class="bi bi-box-arrow-right me-1"></i> Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <div class="container py-4">
        <div class="row mb-4">
            <div class="col-12">
                <h2 class="text-primary"><i class="bi bi-person-circle me-3"></i> Dashboard Sekolah</h2>
                <p class="text-muted">Selamat datang, <b><?= htmlspecialchars($_SESSION['full_name']) ?></b></p>
                <hr>
            </div>
        </div>

        <?php if (!empty($message)) echo $message; ?>

        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card border-primary">
                    <div class="card-header bg-primary text-white">
                        <i class="bi bi-pencil-square me-2"></i> Form Pendaftaran Lomba
                    </div>
                    <div class="card-body">
                        <?php if (empty($competitions)): ?>
                            <div class="alert alert-info" role="alert">Tidak ada lomba yang tersedia saat ini.</div>
                        <?php else: ?>
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="competition_id" class="form-label">Pilih Lomba:</label>
                                    <select name="competition_id" id="competition_id" class="form-select" required>
                                        <option value="">-- Pilih Lomba --</option>
                                        <?php foreach ($competitions as $comp): ?>
                                            <option value="<?= htmlspecialchars($comp['id']) ?>">
                                                <?= htmlspecialchars($comp['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <h6 class="mt-4 mb-3 text-muted">Data Siswa:</h6>
                                <div class="mb-3">
                                    <label for="full_name" class="form-label">Nama Lengkap:</label>
                                    <input type="text" name="full_name" id="full_name" class="form-control" required value="<?= htmlspecialchars($_SESSION['full_name'] ?? '') ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="nisn" class="form-label">NISN:</label>
                                    <input type="text" name="nisn" id="nisn" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label for="birth_place" class="form-label">Tempat Lahir:</label>
                                    <input type="text" name="birth_place" id="birth_place" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label for="birth_date" class="form-label">Tanggal Lahir:</label>
                                    <input type="date" name="birth_date" id="birth_date" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label for="class" class="form-label">Kelas:</label>
                                    <input type="text" name="class" id="class" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label for="school" class="form-label">Asal Sekolah:</label>
                                    <input type="text" name="school" id="school" class="form-control" required value="<?= htmlspecialchars($_SESSION['school'] ?? '') ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="category" class="form-label">Kategori Lomba (contoh: Solo, Grup A, Tingkat Dasar):</label>
                                    <input type="text" name="category" id="category" class="form-control" required>
                                </div>

                                <button type="submit" name="register_competition" class="btn btn-primary mt-3">
                                    <i class="bi bi-send me-2"></i> Daftar Sekarang
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card border-success">
                    <div class="card-header bg-success text-white">
                        <i class="bi bi-trophy me-2"></i> Lomba yang Diikuti
                    </div>
                    <div class="card-body">
                        <?php if (empty($registered)): ?>
                            <div class="alert alert-info" role="alert">Murid anda belum terdaftar di lomba apapun.</div>
                        <?php else: ?>
                            <ul class="list-group">
                                <?php foreach ($registered as $reg): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center flex-wrap">
                                        <div>
                                            <strong><?= htmlspecialchars($reg['competition_name']) ?></strong><br>
                                            <small class="text-muted">Nama: <?= htmlspecialchars($reg['full_name']) ?></small><br>
                                            <small class="text-muted">NISN: <?= htmlspecialchars($reg['nisn']) ?></small><br>
                                            <small class="text-muted">Lahir: <?= htmlspecialchars($reg['birth_place']) ?>, <?= date('d M Y', strtotime($reg['birth_date'])) ?></small><br>
                                            <small class="text-muted">Kelas: <?= htmlspecialchars($reg['class']) ?></small><br>
                                            <small class="text-muted">Sekolah: <?= htmlspecialchars($reg['school']) ?></small><br>
                                            <small class="text-muted">Kategori: <span class="badge bg-secondary"><?= htmlspecialchars($reg['category']) ?></span></small>
                                        </div>
                                        <div class="d-flex flex-wrap gap-2 mt-2 mt-md-0">
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
                                                <i class="bi bi-pencil me-1"></i> Edit
                                            </button>
                                            <a href="dashboard_user.php?delete=<?= htmlspecialchars($reg['id']) ?>"
                                               class="btn btn-danger btn-sm"
                                               onclick="return confirm('Yakin ingin menghapus pendaftaran ini? Tindakan ini akan menghapus semua data terkait peserta ini dari lomba.')">
                                                <i class="bi bi-trash me-1"></i> Hapus
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


        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <i class="bi bi-award me-2"></i> Hasil Kejuaraan
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col" class="text-center">#</th>
                                <th scope="col">Nama Lomba</th>
                                <th scope="col">Nama Peserta</th>
                                <th scope="col" class="text-center">Posisi</th>
                                <th scope="col" class="text-center">Skor</th>
                                <th scope="col">Asal Sekolah</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($results)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">Tidak ada hasil kejuaraan yang tersedia.</td>
                                </tr>
                            <?php else: ?>
                                <?php $no = 1; foreach ($results as $res): ?>
                                    <tr>
                                        <td class="text-center"><?= $no++ ?></td>
                                        <td><?= htmlspecialchars($res['competition_name']) ?></td>
                                        <td><?= htmlspecialchars($res['participant_name']) ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary"><?= htmlspecialchars($res['position']) ?></span>
                                        </td>
                                        <td class="text-center"><?= htmlspecialchars($res['score']) ?></td>
                                        <td><?= htmlspecialchars($res['school']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg"> <div class="modal-content">
                <form method="POST">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title" id="editModalLabel"><i class="bi bi-pencil-square me-2"></i> Edit Data Pendaftaran Lomba</h5>
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
                            <input type="text" name="edit_category" id="edit_category" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="bi bi-x-circle me-1"></i> Batal</button>
                        <button type="submit" name="update_registration" class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan Perubahan</button>
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
            var button = event.relatedTarget; // Tombol yang diklik

            // Ambil semua data dari data-attributes
            var id = button.getAttribute('data-id');
            var fullName = button.getAttribute('data-full_name');
            var nisn = button.getAttribute('data-nisn');
            var birthPlace = button.getAttribute('data-birth_place');
            var birthDate = button.getAttribute('data-birth_date'); // Format YYYY-MM-DD
            var className = button.getAttribute('data-class'); // "class" adalah reserved keyword, jadi pakai className
            var school = button.getAttribute('data-school');
            var category = button.getAttribute('data-category');

            // Isi data ke field modal
            document.getElementById('editRegistrationId').value = id;
            document.getElementById('edit_full_name').value = fullName;
            document.getElementById('edit_nisn').value = nisn;
            document.getElementById('edit_birth_place').value = birthPlace;
            document.getElementById('edit_birth_date').value = birthDate;
            document.getElementById('edit_class').value = className;
            document.getElementById('edit_school').value = school;
            document.getElementById('edit_category').value = category;
        });
    </script>
</body>
</html>