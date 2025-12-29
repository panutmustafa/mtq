<?php
require_once __DIR__ . '/../includes/auth.php'; // Pastikan file ini menangani session_start()
require_once __DIR__ . '/../config/database.php'; // Pastikan ini ada dan koneksi PDO sudah dibuat ($pdo)

// Pengecekan role spesifik
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php?error=unauthorized');
    exit();
}

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
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Hasil Kejuaraan | Sistem Penilaian Lomba</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f0f2f5;
            color: #333;
        }
        .navbar {
            background-color: #2c3e50;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .navbar-brand {
            font-weight: 700;
            color: #ecf0f1 !important;
        }
        .navbar .nav-link {
            color: #bdc3c7 !important;
            transition: color 0.3s ease;
        }
        .navbar .nav-link:hover {
            color: #ecf0f1 !important;
        }
        .btn-outline-light {
            border-color: #ecf0f1;
            color: #ecf0f1;
            transition: all 0.3s ease;
        }
        .btn-outline-light:hover {
            background-color: #ecf0f1;
            color: #2c3e50;
        }

        .container {
            padding-top: 30px;
            padding-bottom: 30px;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
            overflow: hidden;
        }

        .card-header {
            background-color: #3498db;
            color: white;
            font-weight: 600;
            padding: 1.25rem 1.5rem;
            font-size: 1.15rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .card-header.bg-primary { background-color: #007bff; } /* Specific for input form */
        .card-header.bg-info { background-color: #17a2b8; } /* Specific for table list */

        .card-body {
            padding: 1.5rem;
        }

        .alert-custom {
            border-radius: 8px;
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1rem;
            margin-bottom: 1.5rem;
        }
        /* Bootstrap default alert colors are fine, but ensure icons match */
        .alert-custom.alert-info i { color: #31708f; }
        .alert-custom.alert-success i { color: #3c763d; }
        .alert-custom.alert-danger i { color: #a94442; }
        .alert-custom.alert-warning i { color: #8a6d3b; }


        .table thead th {
            background-color: #e9ecef;
            color: #495057;
            font-weight: 600;
            vertical-align: middle;
            border-bottom: 2px solid #dee2e6;
            text-align: center;
        }
        .table tbody tr:hover {
            background-color: #f5f5f5;
        }
        .table td, .table th {
            vertical-align: middle;
            padding: 0.75rem;
        }
        .table .btn {
            border-radius: 6px;
            font-size: 0.85rem;
            padding: 0.4rem 0.8rem;
        }
        .table .btn i {
            margin-right: 5px;
        }

        /* Responsive Table */
        @media (max-width: 767.98px) {
            .table-responsive {
                border: 1px solid #dee2e6;
                border-radius: 0.75rem;
                overflow-x: auto;
            }
            .table thead {
                display: none;
            }
            .table tbody tr {
                display: block;
                margin-bottom: 1rem;
                border: 1px solid #ddd;
                border-radius: 0.75rem;
                background-color: #fff;
                box-shadow: 0 2px 8px rgba(0,0,0,.05);
                padding: 0.75rem;
            }
            .table tbody td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 0.5rem 0.25rem;
                border: none;
                border-bottom: 1px solid #eee;
            }
            .table tbody td:last-child {
                border-bottom: none;
            }
            .table tbody td::before {
                content: attr(data-label);
                font-weight: bold;
                margin-right: 0.5rem;
                color: #555;
                flex-shrink: 0;
                text-align: left;
                width: 40%;
            }
            /* Specific labels for Championship table */
            .table tbody td:nth-child(1)::before { content: "Nama Lomba"; }
            .table tbody td:nth-child(2)::before { content: "Peserta"; }
            .table tbody td:nth-child(3)::before { content: "Posisi"; }
            .table tbody td:nth-child(4)::before { content: "Skor"; }
            .table tbody td:nth-child(5)::before { content: "Sekolah"; }
            .table tbody td:nth-child(6)::before { content: "Aksi"; }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark px-3">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard_admin.php"><i class="fas fa-cogs me-2"></i> Admin Dashboard</a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard_admin.php"><i class="fas fa-tachometer-alt me-1"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_competitions.php"><i class="fas fa-trophy me-1"></i> Lomba</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_users.php"><i class="fas fa-users-cog me-1"></i> Pengguna</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="manage_championship_results.php"><i class="fas fa-medal me-1"></i> Hasil Kejuaraan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php"><i class="fas fa-chart-line me-1"></i> Laporan</a>
                    </li>
                    <li class="nav-item">
                        <span class="navbar-text me-lg-3 text-white py-2 py-lg-0">
                            <i class="fas fa-user-circle me-2"></i> Halo, <?= htmlspecialchars($_SESSION['full_name'] ?? 'Admin') ?>
                        </span>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="btn btn-outline-light rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#logoutConfirmModal"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0 text-secondary"><i class="fas fa-medal me-3"></i>Kelola Hasil Kejuaraan</h2>
            <div>
                <a href="dashboard_admin.php" class="btn btn-secondary me-2">
                    <i class="fas fa-arrow-circle-left"></i> Kembali ke Dashboard
                </a>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>