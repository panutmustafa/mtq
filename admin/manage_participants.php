<?php
$page_title = 'Kelola Peserta';
require_once __DIR__.'/../includes/admin_header.php';

// Inisialisasi variabel untuk pesan feedback
$success_message = '';
$error_message = '';

// --- Logika Tambah/Edit/Hapus Peserta ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        // Ambil data umum dari POST
        $full_name = trim($_POST['full_name'] ?? '');
        $nisn = trim($_POST['nisn'] ?? '');
        $birth_place = trim($_POST['birth_place'] ?? '');
        $birth_date = trim($_POST['birth_date'] ?? '');
        $class = trim($_POST['class'] ?? '');
        $school = trim($_POST['school'] ?? '');
        $competition_id = (int)($_POST['competition_id'] ?? 0);

        // Validasi dasar
        if (empty($full_name) || empty($nisn) || empty($birth_place) || empty($birth_date) || empty($class) || empty($school) || $competition_id <= 0) {
            $error_message = "Semua kolom wajib diisi dan Kompetisi harus dipilih.";
        } else {
            try {
                if ($action === 'add') {
                    $stmt = $pdo->prepare("
                        INSERT INTO participants (competition_id, full_name, nisn, birth_place, birth_date, class, school)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$competition_id, $full_name, $nisn, $birth_place, $birth_date, $class, $school]);
                    $success_message = "Peserta **{$full_name}** berhasil ditambahkan.";
                } elseif ($action === 'edit') {
                    $participant_id = (int)($_POST['participant_id'] ?? 0);
                    if ($participant_id > 0) {
                        $stmt = $pdo->prepare("
                            UPDATE participants
                            SET competition_id = ?, full_name = ?, nisn = ?, birth_place = ?, birth_date = ?, class = ?, school = ?
                            WHERE id = ?
                        ");
                        $stmt->execute([$competition_id, $full_name, $nisn, $birth_place, $birth_date, $class, $school, $participant_id]);
                        $success_message = "Data peserta **{$full_name}** berhasil diperbarui.";
                    } else {
                        $error_message = "ID Peserta tidak valid untuk diperbarui.";
                    }
                } elseif ($action === 'delete') {
                    $participant_id = (int)($_POST['participant_id'] ?? 0);
                    if ($participant_id > 0) {
                        // Hapus juga skor terkait (jika ada) untuk menghindari error foreign key
                        $stmt = $pdo->prepare("DELETE FROM scores WHERE participant_id = ?");
                        $stmt->execute([$participant_id]);

                        $stmt = $pdo->prepare("DELETE FROM participants WHERE id = ?");
                        $stmt->execute([$participant_id]);
                        $success_message = "Peserta berhasil dihapus.";
                    } else {
                        $error_message = "ID Peserta tidak valid untuk dihapus.";
                    }
                }
            } catch (PDOException $e) {
                // Tangani error jika ada duplikasi NISN atau masalah lain
                if ($e->getCode() === '23000') { // Kode SQLSTATE untuk integrity constraint violation (misal: unique constraint)
                    $error_message = "Gagal menyimpan data: NISN '{$nisn}' kemungkinan sudah terdaftar atau ada duplikasi data.";
                } else {
                    $error_message = "Terjadi kesalahan database: " . $e->getMessage();
                }
                 error_log("Error managing participant: " . $e->getMessage()); // Log error
            }
        }
    }
}

// Paginasi
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// --- Ambil Data Peserta untuk Ditampilkan ---
// Ambil daftar kompetisi untuk dropdown filter dan form
$competitions_stmt = $pdo->query("SELECT id, name FROM competitions ORDER BY name");
$competitions = $competitions_stmt->fetchAll(PDO::FETCH_ASSOC);

// Filter peserta berdasarkan kompetisi yang dipilih (jika ada)
$filter_competition_id = isset($_GET['competition_id']) ? (int)$_GET['competition_id'] : 0;

// Ambil total record untuk paginasi (dengan filter)
$total_query = "SELECT COUNT(*)
                        FROM participants p
                        JOIN competitions c ON p.competition_id = c.id";
$total_params = [];
if ($filter_competition_id > 0) {
    $total_query .= " WHERE p.competition_id = :filter_comp_id";
    $total_params[':filter_comp_id'] = $filter_competition_id;
}
$total_stmt = $pdo->prepare($total_query);
$total_stmt->execute($total_params);
$total_records = $total_stmt->fetchColumn();
$total_pages = ceil($total_records / $records_per_page);


// Ambil data peserta untuk halaman ini (dengan filter dan paginasi)
$participants_query = "
    SELECT p.id, p.full_name, p.nisn, p.birth_place, p.birth_date, p.class, p.school, c.name as competition_name, c.id as competition_id_raw
    FROM participants p
    JOIN competitions c ON p.competition_id = c.id
";
$data_params = [];
if ($filter_competition_id > 0) {
    $participants_query .= " WHERE p.competition_id = :filter_comp_id";
    $data_params[':filter_comp_id'] = $filter_competition_id;
}
$participants_query .= " ORDER BY p.full_name ASC LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($participants_query);
foreach ($data_params as $key => $value) {
    $stmt->bindValue($key, $value, PDO::PARAM_INT);
}
$stmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$participants = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php include __DIR__ . '/../includes/admin_sidebar.php'; ?>
<div class="content-wrapper">
    <?php include __DIR__ . '/../includes/admin_content_header.php'; ?>
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-people-fill"></i> Kelola Data Peserta</h2>
            <div>
                <a href="dashboard_admin.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left-circle"></i> Kembali ke Dashboard
                </a>
                <a href="export_participants.php<?php echo ($filter_competition_id > 0 ? '?competition_id=' . $filter_competition_id : ''); ?>" class="btn btn-success me-2">
                      <i class="bi bi-file-earmark-excel"></i> Ekspor ke Excel
                  </a>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#participantModal" data-action="add">
                    <i class="bi bi-plus-circle"></i> Tambah Peserta
                </button>
            </div>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $success_message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $error_message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <div class="mb-3">
                    <label for="filterCompetition" class="form-label">Filter berdasarkan Kompetisi:</label>
                    <select class="form-select" id="filterCompetition" onchange="location = this.value;">
                        <option value="manage_participants.php">Semua Kompetisi</option>
                        <?php foreach ($competitions as $comp): ?>
                            <option value="manage_participants.php?competition_id=<?= $comp['id'] ?>"
                                <?= ($filter_competition_id == $comp['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($comp['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="table-responsive">
                    <table id="participantsTable" class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Kompetisi</th>
                                <th>Nama Lengkap</th>
                                <th>NISN</th>
                                <th>Tempat Lahir</th>
                                <th>Tanggal Lahir</th>
                                <th>Kelas</th>
                                <th>Sekolah</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($participants)): ?>
                                <tr>
                                    <td colspan="9" class="text-center">Belum ada peserta yang terdaftar.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($participants as $participant): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($participant['id']) ?></td>
                                        <td><?= htmlspecialchars($participant['competition_name']) ?></td>
                                        <td><?= htmlspecialchars($participant['full_name']) ?></td>
                                        <td><?= htmlspecialchars($participant['nisn']) ?></td>
                                        <td><?= htmlspecialchars($participant['birth_place']) ?></td>
                                        <td><?= htmlspecialchars($participant['birth_date']) ?></td>
                                        <td><?= htmlspecialchars($participant['class']) ?></td>
                                        <td><?= htmlspecialchars($participant['school']) ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-warning btn-action edit-btn"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#participantModal"
                                                    data-action="edit"
                                                    data-id="<?= $participant['id'] ?>"
                                                    data-competition_id="<?= $participant['competition_id_raw'] ?>"
                                                    data-full_name="<?= htmlspecialchars($participant['full_name']) ?>"
                                                    data-nisn="<?= htmlspecialchars($participant['nisn']) ?>"
                                                    data-birth_place="<?= htmlspecialchars($participant['birth_place']) ?>"
                                                    data-birth_date="<?= htmlspecialchars($participant['birth_date']) ?>"
                                                    data-class="<?= htmlspecialchars($participant['class']) ?>"
                                                    data-school="<?= htmlspecialchars($participant['school']) ?>">
                                                <i class="bi bi-pencil"></i> Edit
                                            </button>
                                            <button class="btn btn-sm btn-danger btn-action delete-btn"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#deleteConfirmModal"
                                                    data-id="<?= $participant['id'] ?>"
                                                    data-name="<?= htmlspecialchars($participant['full_name']) ?>">
                                                <i class="bi bi-trash"></i> Hapus
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                                            <?php endif; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <!-- Navigasi Paginasi -->
                                            <nav aria-label="Page navigation" class="mt-4">
                                                <ul class="pagination justify-content-center">
                                                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                                        <a class="page-link" href="?page=<?= $page - 1 ?><?= $filter_competition_id > 0 ? '&competition_id=' . $filter_competition_id : '' ?>">Previous</a>
                                                    </li>
                                                                            <?php
                                                                            $num_links_to_show = 5;
                                                                            $start_page = max(1, $page - floor($num_links_to_show / 2));
                                                                            $end_page = min($total_pages, $start_page + $num_links_to_show - 1);
                                                    
                                                                            if ($end_page - $start_page + 1 < $num_links_to_show) {
                                                                                $start_page = max(1, $end_page - $num_links_to_show + 1);
                                                                            }
                                                                            
                                                                            $filter_params_suffix = ($filter_competition_id > 0 ? '&competition_id=' . $filter_competition_id : '');
                                                                            
                                                                            if ($start_page > 1) {
                                                                                echo '<li class="page-item"><a class="page-link" href="?page=1'. $filter_params_suffix .'">1</a></li>';
                                                                                if ($start_page > 2) {
                                                                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                                                                }
                                                                            }
                                                    
                                                                            for ($i = $start_page; $i <= $end_page; $i++) {
                                                                                echo '<li class="page-item '. ($i == $page ? 'active' : '') .'"><a class="page-link" href="?page='. $i . $filter_params_suffix .'">'. $i .'</a></li>';
                                                                            }
                                                    
                                                                            if ($end_page < $total_pages) {
                                                                                if ($end_page < $total_pages - 1) {
                                                                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                                                                }
                                                                                echo '<li class="page-item"><a class="page-link" href="?page='. $total_pages . $filter_params_suffix .'">'. $total_pages .'</a></li>';
                                                                            }
                                                                            ?>                                                    <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                                        <a class="page-link" href="?page=<?= $page + 1 ?><?= $filter_competition_id > 0 ? '&competition_id=' . $filter_competition_id : '' ?>">Next</a>
                                                    </li>
                                                </ul>
                                            </nav>
                                        </div>        </div>
    </div>

    <div class="modal fade" id="participantModal" tabindex="-1" aria-labelledby="participantModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="participantForm" method="POST" action="manage_participants.php">
                    <div class="modal-header">
                        <h5 class="modal-title" id="participantModalLabel">Tambah Peserta Baru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" id="action">
                        <input type="hidden" name="participant_id" id="participant_id">
                        
                        <div class="mb-3">
                            <label for="competition_id" class="form-label">Kompetisi</label>
                            <select class="form-select" id="competition_id" name="competition_id" required>
                                <option value="">Pilih Kompetisi</option>
                                <?php foreach ($competitions as $comp): ?>
                                    <option value="<?= $comp['id'] ?>"><?= htmlspecialchars($comp['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="full_name" class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="nisn" class="form-label">NISN</label>
                            <input type="text" class="form-control" id="nisn" name="nisn" required>
                        </div>
                        <div class="mb-3">
                            <label for="birth_place" class="form-label">Tempat Lahir</label>
                            <input type="text" class="form-control" id="birth_place" name="birth_place" required>
                        </div>
                        <div class="mb-3">
                            <label for="birth_date" class="form-label">Tanggal Lahir</label>
                            <input type="date" class="form-control" id="birth_date" name="birth_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="class" class="form-label">Kelas</label>
                            <input type="text" class="form-control" id="class" name="class" required>
                        </div>
                        <div class="mb-3">
                            <label for="school" class="form-label">Sekolah</label>
                            <input type="text" class="form-control" id="school" name="school" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="deleteForm" method="POST" action="manage_participants.php">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteConfirmModalLabel">Konfirmasi Hapus Peserta</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="participant_id" id="delete_participant_id">
                        <p>Anda yakin ingin menghapus peserta <strong id="delete_participant_name"></strong>? Tindakan ini tidak dapat dibatalkan dan akan menghapus semua skor terkait peserta ini.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">Hapus</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<?php include __DIR__.'/../includes/admin_footer.php'; ?>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.0.8/js/dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/2.0.8/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            // Inisialisasi DataTable
            $('#participantsTable').DataTable({
                "language": {
                    "url": "https://cdn.datatables.net/plug-ins/1.10.25/i18n/Indonesian.json" // Bahasa Indonesia
                }
            });

            // Logic untuk Modal Tambah/Edit
            var participantModal = document.getElementById('participantModal');
            participantModal.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget; // Tombol yang memicu modal
                var action = button.getAttribute('data-action'); // Ambil aksi (add/edit)

                var modalTitle = participantModal.querySelector('.modal-title');
                var formAction = participantModal.querySelector('#action');
                var participantIdField = participantModal.querySelector('#participant_id');
                var competitionIdField = participantModal.querySelector('#competition_id');
                var fullNameField = participantModal.querySelector('#full_name');
                var nisnField = participantModal.querySelector('#nisn');
                var birthPlaceField = participantModal.querySelector('#birth_place');
                var birthDateField = participantModal.querySelector('#birth_date');
                var classField = participantModal.querySelector('#class');
                var schoolField = participantModal.querySelector('#school');

                formAction.value = action; // Set nilai action di hidden input

                if (action === 'add') {
                    modalTitle.textContent = 'Tambah Peserta Baru';
                    // Kosongkan semua field
                    participantIdField.value = '';
                    competitionIdField.value = ''; // Reset dropdown
                    fullNameField.value = '';
                    nisnField.value = '';
                    birthPlaceField.value = '';
                    birthDateField.value = '';
                    classField.value = '';
                    schoolField.value = '';
                } else if (action === 'edit') {
                    modalTitle.textContent = 'Edit Data Peserta';
                    // Ambil data dari tombol dan isi ke form
                    participantIdField.value = button.getAttribute('data-id');
                    competitionIdField.value = button.getAttribute('data-competition_id'); // Ambil dari competition_id_raw
                    fullNameField.value = button.getAttribute('data-full_name');
                    nisnField.value = button.getAttribute('data-nisn');
                    birthPlaceField.value = button.getAttribute('data-birth_place');
                    birthDateField.value = button.getAttribute('data-birth_date');
                    classField.value = button.getAttribute('data-class');
                    schoolField.value = button.getAttribute('data-school');
                }
            });

            // Logic untuk Modal Konfirmasi Hapus
            var deleteConfirmModal = document.getElementById('deleteConfirmModal');
            deleteConfirmModal.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget;
                var participantId = button.getAttribute('data-id');
                var participantName = button.getAttribute('data-name');

                var modalBodyParticipantId = deleteConfirmModal.querySelector('#delete_participant_id');
                var modalBodyParticipantName = deleteConfirmModal.querySelector('#delete_participant_name');

                modalBodyParticipantId.value = participantId;
                modalBodyParticipantName.textContent = participantName;
            });
        });
    </script>

