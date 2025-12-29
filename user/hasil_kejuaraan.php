<?php
session_start();
require_once __DIR__.'/../includes/auth.php';
if ($_SESSION['role'] !== 'user') {
    header('Location: ../login.php');
    exit();
}

require_once __DIR__.'/../config/database.php';

// Definisikan pemetaan dari ID integer ke string deskriptif untuk tampilan.
$positionDisplayMap = [
    1 => 'Juara 1',
    2 => 'Juara 2',
    3 => 'Juara 3',
    4 => 'Juara Harapan 1',
    5 => 'Juara Harapan 2',
    6 => 'Harapan 3',
];

// Atur zona waktu ke WIB
date_default_timezone_set('Asia/Jakarta');

// Ambil parameter filter
$search_query = trim($_GET['search'] ?? '');
$filter_position = isset($_GET['filter_position']) && is_numeric($_GET['filter_position']) ? (int)$_GET['filter_position'] : null;

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

// Inisialisasi paginasi untuk hasil kejuaraan
$page_champ = isset($_GET['page_champ']) ? (int)$_GET['page_champ'] : 1;
$records_per_page = 10;

// Query untuk menghitung total hasil kejuaraan (dengan filter)
$count_query = "SELECT COUNT(*) as total FROM championships" . $where_sql;
$count_stmt = $pdo->prepare($count_query);
foreach ($params as $param_name => $param_value) {
    $count_stmt->bindValue($param_name, $param_value, is_int($param_value) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$count_stmt->execute();
$total_champ = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages_champ = ceil($total_champ / $records_per_page);
$page_champ = max(1, min($page_champ, $total_pages_champ));
$offset_champ = ($page_champ - 1) * $records_per_page;

// Query untuk mengambil data kejuaraan (dengan filter dan paginasi)
$query = "
    SELECT
        competition_id,
        competition_name,
        participant_name,
        position,
        score,
        school,
        created_at
    FROM championships" . $where_sql . "
    ORDER BY competition_id, position ASC
    LIMIT :limit OFFSET :offset
";

$stmt = $pdo->prepare($query);
foreach ($params as $param_name => $param_value) {
    $stmt->bindValue($param_name, $param_value, is_int($param_value) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset_champ, PDO::PARAM_INT);
$stmt->execute();
$championships = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buat parameter suffix untuk paginasi agar filter tetap berlaku
$filter_params = [];
if (!empty($search_query)) {
    $filter_params['search'] = $search_query;
}
if ($filter_position !== null) {
    $filter_params['filter_position'] = $filter_position;
}
$filter_params_suffix = http_build_query($filter_params);
if (!empty($filter_params_suffix)) {
    $filter_params_suffix = '&' . $filter_params_suffix;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Kejuaraan | Sistem Penilaian Lomba</title>
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
        /* Specific styles from original hasil_kejuaraan.php */
        .status-badge {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
            border-radius: 1rem;
        }
        .position-gold { background-color: #ffd700; color: black; }
        .position-silver { background-color: #c0c0c0; color: black; }
        .position-bronze { background-color: #cd7f32; color: white; }
        .position-harapan { background-color: #add8e6; color: black; }
        .position-other { background-color: #6c757d; color: white; }
        .card-header {
            background-color: #f8f9fa; /* Override default header color for this card */
        }
        .pagination {
            justify-content: center;
            margin-top: 1rem;
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
                <a class="nav-link active" href="hasil_kejuaraan.php"><i class="fas fa-trophy"></i> Hasil kejuaraan</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="registration_list.php"><i class="fas fa-list-alt"></i> Daftar Pendaftaran</a>
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
                        <a class="nav-link active" href="hasil_kejuaraan.php"><i class="fas fa-trophy"></i> Hasil Kejuaraan</a>
                    </li>
                    <li class="nav-item">
                        <a href="registration_list.php" class="nav-link"><i class="fas fa-list-alt"></i> Daftar Pendaftaran</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Content -->
    <div class="content-wrapper">
        <div class="container-fluid py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-trophy"></i> Hasil Kejuaraan</h2>
            </div>

            <div class="mb-3">
                <form method="GET" class="d-flex">
                    <input type="text" name="search" class="form-control me-2" placeholder="Cari lomba, peserta, atau sekolah..." value="<?= htmlspecialchars($search_query) ?>">
                    <select name="filter_position" class="form-select me-2" style="width: 200px;">
                        <option value="">Semua Posisi</option>
                        <?php foreach ($positionDisplayMap as $id => $text): ?>
                            <option value="<?= $id ?>" <?= ($filter_position !== null && $filter_position === $id) ? 'selected' : '' ?>>
                                <?= $text ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-primary me-2" type="submit"><i class="fas fa-search"></i> Cari</button>
                    <a href="hasil_kejuaraan.php" class="btn btn-danger"><i class="fas fa-x-circle"></i> Reset</a>
                </form>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5><i class="fas fa-table"></i> Hasil Kejuaraan</h5>
                </div>

                <div class="card-body">
                    <?php if (empty($championships)): ?>
                        <div class="alert alert-info">Tidak ada data kejuaraan yang ditemukan. Silakan hubungi admin untuk generate hasil.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Nama Lomba</th>
                                        <th>Nama Peserta</th>
                                        <th>Peringkat</th>
                                        <th>Skor</th>
                                        <th>Sekolah</th>
                                        <th>Tanggal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($championships as $index => $champ): ?>
                                    <tr>
                                        <td><?= ($page_champ - 1) * $records_per_page + $index + 1 ?></td>
                                        <td><?= htmlspecialchars($champ['competition_name']) ?></td>
                                        <td><?= htmlspecialchars($champ['participant_name']) ?></td>
                                        <td>
                                            <span class="status-badge position-<?php
                                                if ($champ['position'] == 1) echo 'gold';
                                                elseif ($champ['position'] == 2) echo 'silver';
                                                elseif ($champ['position'] == 3) echo 'bronze';
                                                elseif ($champ['position'] >= 4 && $champ['position'] <= 6) echo 'harapan';
                                                else echo 'other';
                                            ?>">
                                                <?= $positionDisplayMap[(int)$champ['position']] ?? 'Lainnya' ?>
                                            </span>
                                        </td>
                                        <td><?= number_format($champ['score'], 2) ?></td>
                                        <td><?= htmlspecialchars($champ['school'] ?? '-') ?></td>
                                        <td><?= date('d M Y H:i', strtotime($champ['created_at'])) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <nav aria-label="Championship Pagination">
                            <ul class="pagination">
                                <li class="page-item <?= $page_champ <= 1 ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?page_champ=<?= $page_champ - 1 ?><?= $filter_params_suffix ?>">Previous</a>
                                </li>
                                <?php for ($i = 1; $i <= $total_pages_champ; $i++): ?>
                                    <li class="page-item <?= $page_champ == $i ? 'active' : '' ?>">
                                        <a class="page-link" href="?page_champ=<?= $i ?><?= $filter_params_suffix ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?= $page_champ >= $total_pages_champ ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?page_champ=<?= $page_champ + 1 ?><?= $filter_params_suffix ?>">Next</a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
            <!-- Footer -->
            <footer class="mt-4 text-center text-muted">
                <p>Developed by <a href="https://panutmustafa.my.id">Panut, S.Pd.</a> | SDN Jomblang 2</p>
            </footer>
        </div>
    </div>

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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar on mobile
        document.querySelector('.navbar-toggler').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });
    </script>
</body>
</html>
