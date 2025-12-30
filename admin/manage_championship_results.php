<?php
$page_title = 'Manage Championship Results';
require_once __DIR__ . '/../includes/admin_header.php';

// Inisialisasi pesan feedback
$feedback_message = '';
$feedback_type = ''; // success, danger, warning, info

    // Definisikan pemetaan dari ID integer ke string deskriptif untuk tampilan.
    // Ini akan digunakan untuk validasi input dan konversi untuk tampilan.
    $positionDisplayMap = [
        1 => 'Juara 1',
        2 => 'Juara 2',
        3 => 'Juara 3',
        4 => 'Juara Harapan 1',
        5 => 'Juara Harapan 2',
        6 => 'Harapan 3', // Menggunakan 'Harapan 3' untuk konsistensi dengan tampilan
    ];

    // Proses input hasil kejuaraan manual
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_championship_result'])) {
        $competition_name = trim($_POST['competition_name'] ?? '');
        $participant_name = trim($_POST['participant_name'] ?? '');
        $position_input_id = (int)($_POST['position'] ?? 0); // Ambil ID integer dari form
        $score = $_POST['score'] !== '' ? (float)$_POST['score'] : null; // Ubah menjadi null jika kosong
        $school = trim($_POST['school'] ?? '');

        // Validasi sederhana: Pastikan posisi adalah integer valid antara 1 dan 6
        if (empty($competition_name) || empty($participant_name) || empty($school) || !array_key_exists($position_input_id, $positionDisplayMap)) {
            $feedback_message = "Nama Lomba, Nama Peserta, Sekolah, dan Posisi harus diisi dengan benar.";
            $feedback_type = "danger";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO championships
                    (competition_name, participant_name, position, score, school)
                    VALUES (?, ?, ?, ?, ?)");
                // Gunakan $position_input_id (integer) secara langsung
                $stmt->execute([$competition_name, $participant_name, $position_input_id, $score, $school]);            
            $feedback_message = "Hasil kejuaraan berhasil disimpan!";
            $feedback_type = "success";
            // Redirect setelah POST untuk mencegah resubmission form
            header("Location: manage_championship_results.php?feedback_type={$feedback_type}&feedback_message=".urlencode($feedback_message));
            exit();
        } catch (PDOException $e) {
            $feedback_message = "Terjadi kesalahan saat menyimpan: " . htmlspecialchars($e->getMessage());
            $feedback_type = "danger";
        }
    }
}

// Ambil parameter filter
$search_query = trim($_GET['search'] ?? '');
$filter_position = isset($_GET['filter_position']) && is_numeric($_GET['filter_position']) ? (int)$_GET['filter_position'] : null;

// Paginasi
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10; // Jumlah hasil kejuaraan per halaman
$offset = ($page - 1) * $records_per_page;

// Bangun klausa WHERE berdasarkan filter
$where_clauses = [];
$params = [];

if (!empty($search_query)) {
    $where_clauses[] = "(competition_name LIKE :search_comp OR participant_name LIKE :search_part OR school LIKE :search_school)";
    $params[':search_comp'] = '%' . $search_query . '%';
    $params[':search_part'] = '%' . $search_query . '%';
    $params[':search_school'] = '%' . $search_query . '%';
}

if ($filter_position !== null && array_key_exists($filter_position, $positionDisplayMap)) {
    $where_clauses[] = "position = :filter_position";
    $params[':filter_position'] = $filter_position;
}

$where_sql = count($where_clauses) > 0 ? " WHERE " . implode(" AND ", $where_clauses) : "";

// Ambil total record (dengan filter)
try {
    $total_stmt = $pdo->prepare("SELECT COUNT(*) FROM championships" . $where_sql);
    foreach ($params as $param_name => $param_value) {
        $total_stmt->bindValue($param_name, $param_value, is_int($param_value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $total_stmt->execute();
    $total_records = $total_stmt->fetchColumn();
    $total_pages = ceil($total_records / $records_per_page);
} catch (PDOException $e) {
    $feedback_message = "Gagal menghitung total data kejuaraan: " . htmlspecialchars($e->getMessage());
    $feedback_type = "danger";
    $total_records = 0;
    $total_pages = 1;
}

// Ambil data hasil kejuaraan untuk ditampilkan (dengan filter dan paginasi)
try {
    $sql = "SELECT * FROM championships" . $where_sql . " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);

    foreach ($params as $param_name => $param_value) {
        $stmt->bindValue($param_name, $param_value, is_int($param_value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    
    $stmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $feedback_message = "Gagal mengambil data kejuaraan: " . htmlspecialchars($e->getMessage());
    $feedback_type = "danger";
    $results = []; // Pastikan $results kosong jika ada error
}

// Tangani pesan feedback dari redirect (jika ada, termasuk dari proses upload Excel)
if (isset($_GET['feedback_message']) && isset($_GET['feedback_type'])) {
    $feedback_message = htmlspecialchars($_GET['feedback_message']);
    $feedback_type = htmlspecialchars($_GET['feedback_type']);
} elseif (isset($_SESSION['upload_success_message'])) { // Pesan dari upload Excel
    $feedback_message = $_SESSION['upload_success_message'];
    $feedback_type = 'success';
    unset($_SESSION['upload_success_message']);
} elseif (isset($_SESSION['upload_error_message'])) { // Pesan error dari upload Excel
    $feedback_message = $_SESSION['upload_error_message'];
    $feedback_type = 'danger';
    unset($_SESSION['upload_error_message']);
}

// Persiapkan parameter filter untuk paginasi
$filter_params = $_GET;
unset($filter_params['page']); // Hapus 'page' agar tidak double
$filter_params_suffix = !empty($filter_params) ? '&' . http_build_query($filter_params) : '';

?>
<?php include __DIR__ . '/../includes/admin_sidebar.php'; ?>
<div class="content-wrapper">
    <?php include __DIR__ . '/../includes/admin_content_header.php'; ?>
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0 text-secondary"><i class="fas fa-medal me-3"></i>Kelola Hasil Kejuaraan</h2>
            <div>
                <a href="dashboard_admin.php" class="btn btn-secondary me-2">
                    <i class="fas fa-arrow-circle-left"></i> Kembali ke Dashboard
                </a>
                <form method="POST" action="generate_championship_results.php" class="d-inline-block">
                    <button type="submit" name="generate_results" class="btn btn-primary me-2">
                        <i class="fas fa-trophy me-1"></i> Generate Hasil (Rata-rata)
                    </button>
                </form>
                <form method="POST" action="generate_championship_results_sum.php" class="d-inline-block">
                    <button type="submit" name="generate_results" class="btn btn-success me-2">
                        <i class="fas fa-plus-square me-1"></i> Generate Hasil (Jumlah Skor)
                    </button>
                </form>
                <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#uploadExcelModal">
                    <i class="fas fa-upload me-1"></i> Upload Hasil Excel
                </button>
            </div>
        </div>

        <?php if ($feedback_message): ?>
            <div class="alert alert-<?= htmlspecialchars($feedback_type) ?> alert-dismissible fade show alert-custom shadow-sm" role="alert">
                <i class="fas fa-<?= $feedback_type == 'success' ? 'check-circle' : ($feedback_type == 'danger' ? 'times-circle' : ($feedback_type == 'warning' ? 'exclamation-triangle' : 'info-circle')) ?> fa-lg"></i>
                <div><?= $feedback_message ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm mb-5">
            <div class="card-header bg-primary text-white">
                <i class="fas fa-plus-circle me-2"></i> Input Hasil Kejuaraan Baru (Manual)
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="competition_name" class="form-label">Nama Lomba / Kejuaraan</label>
                            <input type="text" id="competition_name" name="competition_name" class="form-control" placeholder="Ex: Lomba Catur Nasional" required>
                        </div>
                        <div class="col-md-6">
                            <label for="participant_name" class="form-label">Nama Peserta</label>
                            <input type="text" id="participant_name" name="participant_name" class="form-control" placeholder="Ex: Budi Santoso" required>
                        </div>
                        <div class="col-md-3">
                            <label for="position" class="form-label">Posisi</label>
                            <select id="position" name="position" class="form-select" required>
                                <option value="">Pilih Posisi</option>
                                <option value="1">Juara 1</option>
                                <option value="2">Juara 2</option>
                                <option value="3">Juara 3</option>
                                <option value="4">Harapan 1</option>
                                <option value="5">Harapan 2</option>
                                <option value="6">Lainnya</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="score" class="form-label">Skor / Nilai (Opsional)</label>
                            <input type="number" id="score" name="score" step="0.01" class="form-control" placeholder="Ex: 95.75">
                        </div>
                        <div class="col-md-6">
                            <label for="school" class="form-label">Asal Sekolah / Instansi</label>
                            <input type="text" id="school" name="school" class="form-control" placeholder="Ex: SMA Negeri 1 Yogyakarta" required>
                        </div>
                        <div class="col-12 text-end">
                            <button type="submit" name="add_championship_result" class="btn btn-primary btn-lg px-4">
                                <i class="fas fa-save me-2"></i> Simpan Hasil
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <form method="GET" class="d-flex flex-grow-1 me-2">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="Cari berdasarkan lomba, peserta, atau sekolah..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                    <select name="filter_position" class="form-select">
                        <option value="">Semua Posisi</option>
                        <?php foreach ($positionDisplayMap as $id => $text): ?>
                            <option value="<?= $id ?>" <?= (isset($_GET['filter_position']) && (int)$_GET['filter_position'] === $id) ? 'selected' : '' ?>>
                                <?= $text ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-outline-secondary" type="submit"><i class="fas fa-search me-1"></i> Cari</button>
                    <a href="manage_championship_results.php" class="btn btn-outline-danger"><i class="fas fa-times me-1"></i> Reset</a>
                </div>
            </form>
            <div class="dropdown">
                <button class="btn btn-secondary dropdown-toggle" type="button" id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-download me-2"></i> Export Data
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="exportDropdown">
                    <li><a class="dropdown-item" href="export_excel.php?<?= http_build_query($_GET) ?>"><i class="far fa-file-excel me-2 text-success"></i> Excel (.xls)</a></li>
                    <li><a class="dropdown-item" href="export_pdf.php?<?= http_build_query($_GET) ?>"><i class="far fa-file-pdf me-2 text-danger"></i> PDF (.pdf)</a></li>
                    <li><a class="dropdown-item" href="export_csv.php?<?= http_build_query($_GET) ?>"><i class="fas fa-file-csv me-2 text-primary"></i> CSV (.csv)</a></li>
                </ul>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-info text-white">
                <i class="fas fa-list-alt me-2"></i> Daftar Hasil Kejuaraan
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Nama Lomba</th>
                                <th>Nama Peserta</th>
                                <th class="text-center">Posisi</th>
                                <th class="text-center">Skor</th>
                                <th>Asal Sekolah</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($results)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <i class="fas fa-box-open fa-2x text-muted mb-2"></i><br>
                                        Tidak ada hasil kejuaraan yang tersedia.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($results as $res): ?>
                                    <tr>
                                        <td data-label="Nama Lomba"><?= htmlspecialchars($res['competition_name']) ?></td>
                                        <td data-label="Nama Peserta"><?= htmlspecialchars($res['participant_name']) ?></td>
                                        <td data-label="Posisi" class="text-center">
                                            <?php
                                                $position_text = $positionDisplayMap[(int)$res['position']] ?? 'Lainnya'; // Gunakan map baru dan pastikan cast ke int
                                                $badge_class = '';
                                                // Gunakan nilai integer dari $res['position'] untuk menentukan badge class
                                                switch ((int)$res['position']) { // Cast to int for safety
                                                    case 1: $badge_class = 'bg-warning text-dark'; break;
                                                    case 2: $badge_class = 'bg-secondary'; break;
                                                    case 3: $badge_class = 'bg-info'; break;
                                                    case 4:
                                                    case 5:
                                                    case 6: $badge_class = 'bg-light text-dark border border-secondary'; break;
                                                    default: $badge_class = 'bg-dark'; break; // Untuk nilai yang tidak ada di ENUM atau NULL
                                                }
                                            ?>
                                            <span class="badge <?= $badge_class ?>"><?= $position_text ?></span>
                                        </td>
                                        <td data-label="Skor" class="text-center"><?= htmlspecialchars($res['score'] ?? '-') ?></td>
                                        <td data-label="Asal Sekolah"><?= htmlspecialchars($res['school']) ?></td>
                                        <td data-label="Aksi" class="text-center">
                                            <div class="d-flex justify-content-center flex-wrap gap-2">
                                                <a href="edit_championship.php?id=<?= $res['id'] ?>" class="btn btn-sm btn-warning" title="Edit Hasil">
                                                    <i class="fas fa-edit"></i> <span class="d-none d-lg-inline">Edit</span>
                                                </a>
                                                <a href="delete_championship.php?id=<?= $res['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus hasil ini?')" title="Hapus Hasil">
                                                    <i class="fas fa-trash-alt"></i> <span class="d-none d-lg-inline">Hapus</span>
                                                </a>
                                            </div>
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
                                                        <a class="page-link" href="?page=<?= $page - 1 ?>">Previous</a>
                                                    </li>
                                                                            <?php
                                                                            $num_links_to_show = 5; // Total number of links to display (e.g., 1 2 [3] 4 5)
                                                                            $start_page = max(1, $page - floor($num_links_to_show / 2));
                                                                            $end_page = min($total_pages, $start_page + $num_links_to_show - 1);
                                                    
                                                                            // Adjust start/end if we hit the total_pages boundary
                                                                            if ($end_page - $start_page + 1 < $num_links_to_show) {
                                                                                $start_page = max(1, $end_page - $num_links_to_show + 1);
                                                                            }
                                                                            
                                                                            $filter_params = $_GET;
unset($filter_params['page']); // Hapus 'page' agar tidak double
$filter_params_suffix = !empty($filter_params) ? '&' . http_build_query($filter_params) : '';
                                                    
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
                                                                            if ($end_page < $total_pages) {
                                                                                if ($end_page < $total_pages - 1) { // Show ellipsis if there's a gap before last page
                                                                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                                                                }
                                                                                echo '<li class="page-item"><a class="page-link" href="?page='. $total_pages . $filter_params_suffix .'">'. $total_pages .'</a></li>';
                                                                            }
                                                                            ?>                                                    <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                                        <a class="page-link" href="?page=<?= $page + 1 ?>">Next</a>
                                                    </li>
                                                </ul>
                                            </nav>
                                        </div>        </div>

    </div>
    
    <div class="modal fade" id="uploadExcelModal" tabindex="-1" aria-labelledby="uploadExcelModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="import_championship_results.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-header bg-info text-white">
                        <h5 class="modal-title" id="uploadExcelModalLabel"><i class="fas fa-file-excel me-2"></i> Upload File Excel Hasil Kejuaraan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Pilih file Excel (.xlsx atau .xls) yang berisi data hasil kejuaraan. Pastikan format kolom sesuai dengan template berikut.</p>
                        <p class="fw-bold">Format Kolom yang Diharapkan (Tanpa Header):</p>
                        <ol>
                            <li>Kolom A: **Nama Kompetisi** (misal: "Lomba Catur Nasional")</li>
                            <li>Kolom B: **Nama Peserta** (misal: "Budi Santoso")</li>
                            <li>Kolom C: **Posisi** (angka, misal: 1 untuk Juara 1, 2 untuk Juara 2, dst. atau 6 untuk Lainnya)</li>
                            <li>Kolom D: **Skor** (angka, misal: 95.75, biarkan kosong jika tidak ada)</li>
                            <li>Kolom E: **Asal Sekolah** (misal: "SMA Negeri 1 Yogyakarta")</li>
                        </ol>
                        <div class="alert alert-warning small" role="alert">
                            <i class="fas fa-exclamation-triangle me-1"></i> Penting: Pastikan "Nama Kompetisi", "Nama Peserta", dan "Asal Sekolah" di Excel sama persis dengan data di database jika ingin melakukan update atau link yang benar.
                        </div>
                        <div class="mb-3">
                            <label for="excelFile" class="form-label">Pilih File Excel:</label>
                            <input class="form-control" type="file" id="excelFile" name="excelFile" accept=".xlsx, .xls" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fas fa-times-circle me-1"></i> Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-cloud-upload-alt me-1"></i> Upload</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<?php include __DIR__.'/../includes/admin_footer.php'; ?>