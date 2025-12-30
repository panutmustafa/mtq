<?php
ob_start(); // Start output buffering
$page_title = 'Daftar Pendaftaran Saya';
require_once __DIR__ . '/../includes/user_header.php';

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
            $_SESSION['feedback_message'] = "Pendaftaran dan data terkait berhasil dihapus.";
            $_SESSION['feedback_type'] = "success";
        } else {
            $_SESSION['feedback_message'] = "Data tidak ditemukan atau Anda tidak memiliki izin untuk menghapus pendaftaran ini.";
            $_SESSION['feedback_type'] = "danger";
        }
        header("Location: registration_list.php"); // Redirect to self after delete
        exit();
    } catch (PDOException $e) {
        $_SESSION['feedback_message'] = "Error: " . htmlspecialchars($e->getMessage());
        $_SESSION['feedback_type'] = "danger";
        header("Location: registration_list.php"); // Redirect to self after error
        exit();
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
            $_SESSION['feedback_message'] = "Data pendaftaran berhasil diperbarui!";
            $_SESSION['feedback_type'] = "success";
        } else {
            $_SESSION['feedback_message'] = "Gagal memperbarui data. Silakan coba lagi.";
            $_SESSION['feedback_type'] = "danger";
        }
        header("Location: registration_list.php"); // Redirect to self after update
        exit();
    } catch (PDOException $e) {
        $_SESSION['feedback_message'] = "Error: " . htmlspecialchars($e->getMessage());
        $_SESSION['feedback_type'] = "danger";
        header("Location: registration_list.php"); // Redirect to self after error
        exit();
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

// Retrieve feedback messages from session
if (isset($_SESSION['feedback_message']) && isset($_SESSION['feedback_type'])) {
    $feedback_message = '<div class="alert alert-' . htmlspecialchars($_SESSION['feedback_type']) . ' alert-dismissible fade show" role="alert"><i class="fas fa-' . ($_SESSION['feedback_type'] == 'success' ? 'check-circle' : 'x-circle') . ' me-2"></i>' . htmlspecialchars($_SESSION['feedback_message']) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    unset($_SESSION['feedback_message']);
    unset($_SESSION['feedback_type']);
}
?>
<?php include __DIR__ . '/../includes/user_sidebar.php'; ?>
<div class="content-wrapper">
    <?php include __DIR__ . '/../includes/user_content_navbar.php'; ?>
    <div class="container-fluid">
        <?php if (!empty($message)) echo $message; ?>

        <h2 class="mb-4"><i class="fas fa-list-alt me-2"></i> Data Pendaftaran Saya</h2>
        <div class="row">
            <!-- Lomba yang Diikuti -->
            <div class="col-lg-12 mb-4">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-list-alt"></i> Lomba yang Diikuti
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
<?php include __DIR__ . '/../includes/user_footer.php'; ?>
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
</script>
<?php ob_end_flush(); ?>
