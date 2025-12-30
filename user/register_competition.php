<?php
$page_title = 'Form Pendaftaran Lomba';
require_once __DIR__ . '/../includes/user_header.php';

// Inisialisasi pesan
$message = '';

// Proses pendaftaran lomba
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
            $message = '<div class="alert alert-warning alert-dismissible fade show" role="alert"><i class="fas fa-exclamation-triangle me-2"></i>Anda sudah terdaftar di lomba ini.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
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
                $message = '<div class="alert alert-success alert-dismissible fade show" role="alert"><i class="fas fa-check-circle me-2"></i>Pendaftaran berhasil!<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                // Redirect untuk mencegah resubmission form
                header("Location: register_competition.php"); // Redirect to self
                exit();
            } else {
                $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert"><i class="fas fa-x-circle me-2"></i>Gagal mendaftar lomba. Silakan coba lagi.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
            }
        }
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert"><i class="fas fa-x-circle me-2"></i>Error: ' . htmlspecialchars($e->getMessage()) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    }
}

// Ambil data lomba yang tersedia (status 'open')
$competitions = $pdo->query("SELECT * FROM competitions WHERE status = 'open'")->fetchAll();

?>
<?php include __DIR__ . '/../includes/user_sidebar.php'; ?>
<div class="content-wrapper">
    <?php include __DIR__ . '/../includes/user_content_navbar.php'; ?>
    <div class="container-fluid">
        <?php if (!empty($message)) echo $message; ?>

        <h2 class="mb-4"><i class="fas fa-pencil-alt me-2"></i> Form Pendaftaran Lomba</h2>
        <div class="row">
            <!-- Form Pendaftaran -->
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-pencil-alt"></i> Silakan anda isi dengan data yang valid
                    </div>
                    <div class="card-body">
                        <?php if (empty($competitions)): ?>
                            <div class="alert alert-info" role="alert">
                                <i class="fas fa-info-circle me-2"></i>Tidak ada lomba yang tersedia saat ini.
                            </div>
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
                                    <label for="full_name" class="form-label">Nama Lengkap Siswa:</label>
                                    <input type="text" name="full_name" id="full_name" class="form-control" placeholder="Masukkan nama lengkap siswa" required value="<?= htmlspecialchars($_SESSION['full_name'] ?? '') ?>">
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
                                    <label for="category" class="form-label">Kategori Lomba:</label>
                                    <select name="category" id="category" class="form-select" required>
                                        <option value="">-- Pilih Kategori --</option>
                                        <option value="Solo">Solo</option>
                                        <option value="Group">Group</option>
                                    </select>
                                </div>
                                <button type="submit" name="register_competition" class="btn btn-primary w-100">
                                    <i class="fas fa-save"></i> Daftar Sekarang
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php include __DIR__ . '/../includes/user_footer.php'; ?>