<?php
$page_title = 'Laporan Pendaftaran dan Scoring';
require_once __DIR__.'/../includes/admin_header.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Inisialisasi filter
$competition_filter = isset($_GET['competition']) ? (int)$_GET['competition'] : 0;
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$name_filter = isset($_GET['name']) ? trim($_GET['name']) : ''; // Filter nama lomba
$page_reg = isset($_GET['page_reg']) ? (int)$_GET['page_reg'] : 1;
$page_score = isset($_GET['page_score']) ? (int)$_GET['page_score'] : 1;
$records_per_page = 10;

// Query untuk menghitung total pendaftaran
$count_query = "SELECT COUNT(*) as total 
                FROM participants p
                JOIN competitions c ON p.competition_id = c.id
                JOIN users u ON p.user_id = u.id";
$where_clause = [];
$params = [];

if ($competition_filter > 0) {
    $where_clause[] = "p.competition_id = ?";
    $params[] = $competition_filter;
}

if ($status_filter !== 'all') {
    $where_clause[] = "c.status = ?";
    $params[] = $status_filter;
}

if (!empty($name_filter)) {
    $where_clause[] = "c.name LIKE ?";
    $params[] = "%$name_filter%";
}

if (!empty($where_clause)) {
    $count_query .= " WHERE " . implode(" AND ", $where_clause);
}

$count_stmt = $pdo->prepare($count_query);
$count_stmt->execute($params);
$total_reg = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages_reg = ceil($total_reg / $records_per_page);
$page_reg = max(1, min($page_reg, $total_pages_reg));
$offset_reg = ($page_reg - 1) * $records_per_page;

// Query untuk mengambil data laporan pendaftaran
$query = "SELECT 
            p.id,
            c.name as competition_name,
            u.username,
            u.full_name,
            p.registration_date,
            c.status as competition_status,
            p.category
          FROM participants p
          JOIN competitions c ON p.competition_id = c.id
          JOIN users u ON p.user_id = u.id";

if (!empty($where_clause)) {
    $query .= " WHERE " . implode(" AND ", $where_clause);
}

// Validate pagination parameters
$records_per_page = (int)$records_per_page;
$offset_reg = (int)$offset_reg;

// Append LIMIT and OFFSET directly as validated integers
$query .= " ORDER BY p.registration_date DESC LIMIT $records_per_page OFFSET $offset_reg";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Query untuk menghitung total scoring
$scoring_count_query = "SELECT COUNT(*) as total 
                       FROM scores s
                       JOIN participants p ON s.participant_id = p.id
                       JOIN competitions c ON s.competition_id = c.id
                       JOIN users u ON s.jury_id = u.id";
$scoring_where_clause = [];
$scoring_params = [];

if ($competition_filter > 0) {
    $scoring_where_clause[] = "s.competition_id = ?";
    $scoring_params[] = $competition_filter;
}

if (!empty($name_filter)) {
    $scoring_where_clause[] = "c.name LIKE ?";
    $scoring_params[] = "%$name_filter%";
}

if (!empty($scoring_where_clause)) {
    $scoring_count_query .= " WHERE " . implode(" AND ", $scoring_where_clause);
}

$count_stmt = $pdo->prepare($scoring_count_query);
$count_stmt->execute($scoring_params);
$total_scores = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages_score = ceil($total_scores / $records_per_page);
$page_score = max(1, min($page_score, $total_pages_score));
$offset_score = ($page_score - 1) * $records_per_page;

// Query untuk mengambil data scoring
$scoring_query = "
    SELECT 
        p.id as participant_id,
        p.full_name as participant_name,
        c.name as competition_name,
        s.score as jury_score,
        u.full_name as jury_name,
        s.notes as jury_notes,
        s.created_at as score_date
    FROM scores s
    JOIN participants p ON s.participant_id = p.id
    JOIN competitions c ON s.competition_id = c.id
    JOIN users u ON s.jury_id = u.id";

if (!empty($scoring_where_clause)) {
    $scoring_query .= " WHERE " . implode(" AND ", $scoring_where_clause);
}

// Validate pagination parameters for scoring
$offset_score = (int)$offset_score;

// Append LIMIT and OFFSET directly as validated integers
$scoring_query .= " ORDER BY c.name, p.full_name, u.full_name LIMIT $records_per_page OFFSET $offset_score";

$scoring_stmt = $pdo->prepare($scoring_query);
$scoring_stmt->execute($scoring_params);
$scores = $scoring_stmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil daftar lomba untuk filter
$competitions = $pdo->query("SELECT id, name FROM competitions ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>
<?php include __DIR__ . '/../includes/admin_sidebar.php'; ?>
<div class="content-wrapper">
    <?php include __DIR__ . '/../includes/admin_content_header.php'; ?>
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-bar-chart-line"></i> Laporan Pendaftaran dan Scoring</h2>
            <a href="dashboard_admin.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Kembali ke Dashboard
            </a>
        </div>

        <div class="filter-container">
            <form method="GET">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Filter Lomba</label>
                        <select name="competition" class="form-select">
                            <option value="0">Semua Lomba</option>
                            <?php foreach ($competitions as $comp): ?>
                                <option value="<?= $comp['id'] ?>" <?= $competition_filter == $comp['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($comp['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Nama Lomba</label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($name_filter) ?>" placeholder="Cari nama lomba...">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status Lomba</label>
                        <select name="status" class="form-select">
                            <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>Semua Status</option>
                            <option value="open" <?= $status_filter === 'open' ? 'selected' : '' ?>>Open</option>
                            <option value="closed" <?= $status_filter === 'closed' ? 'selected' : '' ?>>Closed</option>
                            <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>Completed</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-funnel"></i> Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <h5 class="card-title">Total Pendaftaran</h5>
                        <p class="card-text display-6"><?= count($registrations) ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <h5 class="card-title">Lomba Terdaftar</h5>
                        <p class="card-text display-6"><?= count(array_unique(array_column($registrations, 'competition_name'))) ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-info">
                    <div class="card-body">
                        <h5 class="card-title">Peserta Unik</h5>
                        <p class="card-text display-6"><?= count(array_unique(array_column($registrations, 'username'))) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-table"></i> Data Pendaftaran</span>
                    <a href="export_reports_csv.php?<?= http_build_query($_GET) ?>" class="btn btn-sm btn-success">
                        <i class="bi bi-download"></i> Export CSV
                    </a>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($registrations)): ?>
                    <div class="alert alert-info">Tidak ada data pendaftaran yang ditemukan.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Nama Lomba</th>
                                    <th>Penanggungjawab</th>
                                    <th>Username</th>
                                    <th>Tanggal Daftar</th>
                                    <th>Kategori</th>
                                    <th>Status Lomba</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($registrations as $index => $reg): ?>
                                <tr>
                                    <td><?= ($page_reg - 1) * $records_per_page + $index + 1 ?></td>
                                    <td><?= htmlspecialchars($reg['competition_name']) ?></td>
                                    <td><?= htmlspecialchars($reg['full_name']) ?></td>
                                    <td><?= htmlspecialchars($reg['username']) ?></td>
                                    <td><?= date('d M Y H:i', strtotime($reg['registration_date'])) ?></td>
                                    <td><?= htmlspecialchars($reg['category'] ?? '-') ?></td>
                                    <td>
                                        <span class="status-badge status-<?= $reg['competition_status'] ?>">
                                            <?= ucfirst($reg['competition_status']) ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <nav aria-label="Registration Pagination">
                        <ul class="pagination">
                            <li class="page-item <?= $page_reg <= 1 ? 'disabled' : '' ?>">
                                <?php
                                $current_filter_params_reg_prev = '';
                                if ($competition_filter > 0) {
                                    $current_filter_params_reg_prev .= '&competition=' . $competition_filter;
                                }
                                if ($status_filter !== 'all') {
                                    $current_filter_params_reg_prev .= '&status=' . $status_filter;
                                }
                                if (!empty($name_filter)) {
                                    $current_filter_params_reg_prev .= '&name=' . urlencode($name_filter);
                                }
                                $current_filter_params_reg_prev .= '&page_score=' . $page_score;
                                ?>
                                <a class="page-link" href="?page_reg=<?= $page_reg - 1 ?><?= $current_filter_params_reg_prev ?>">Previous</a>
                            </li>
                            <?php
                            $num_links_to_show = 5;
                            $current_filter_params_reg = '';
                            if ($competition_filter > 0) {
                                $current_filter_params_reg .= '&competition=' . $competition_filter;
                            }
                            if ($status_filter !== 'all') {
                                $current_filter_params_reg .= '&status=' . $status_filter;
                            }
                            if (!empty($name_filter)) {
                                $current_filter_params_reg .= '&name=' . urlencode($name_filter);
                            }
                            $current_filter_params_reg .= '&page_score=' . $page_score; // Preserve scoring page

                            $start_page = max(1, $page_reg - floor($num_links_to_show / 2));
                            $end_page = min($total_pages_reg, $start_page + $num_links_to_show - 1);

                            if ($end_page - $start_page + 1 < $num_links_to_show) {
                                $start_page = max(1, $end_page - $num_links_to_show + 1);
                            }
                            
                            // Show first page if not already in range, and ellipsis if needed
                            if ($start_page > 1) {
                                echo '<li class="page-item"><a class="page-link" href="?page_reg=1'. $current_filter_params_reg .'">1</a></li>';
                                if ($start_page > 2) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                            }

                            for ($i = $start_page; $i <= $end_page; $i++) {
                                echo '<li class="page-item '. ($i == $page_reg ? 'active' : '') .'"><a class="page-link" href="?page_reg='. $i . $current_filter_params_reg .'">'. $i .'</a></li>';
                            }

                            // Show last page if not already in range, and ellipsis if needed
                            if ($end_page < $total_pages_reg) {
                                if ($end_page < $total_pages_reg - 1) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                                echo '<li class="page-item"><a class="page-link" href="?page_reg='. $total_pages_reg . $current_filter_params_reg .'">'. $total_pages_reg .'</a></li>';
                            }
                            ?>
                            <li class="page-item <?= $page_reg >= $total_pages_reg ? 'disabled' : '' ?>">
                                <?php
                                $current_filter_params_reg_next = '';
                                if ($competition_filter > 0) {
                                    $current_filter_params_reg_next .= '&competition=' . $competition_filter;
                                }
                                if ($status_filter !== 'all') {
                                    $current_filter_params_reg_next .= '&status=' . $status_filter;
                                }
                                if (!empty($name_filter)) {
                                    $current_filter_params_reg_next .= '&name=' . urlencode($name_filter);
                                }
                                $current_filter_params_reg_next .= '&page_score=' . $page_score;
                                ?>
                                <a class="page-link" href="?page_reg=<?= $page_reg + 1 ?><?= $current_filter_params_reg_next ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-table"></i> Data Scoring</span>
                    <a href="export_scoring_csv.php?competition=<?= $competition_filter ?>&name=<?= urlencode($name_filter) ?>" class="btn btn-sm btn-success">
                        <i class="bi bi-download"></i> Export CSV
                    </a>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($scores)): ?>
                    <div class="alert alert-info">Tidak ada data scoring yang ditemukan. Silakan pilih lomba dari filter di atas atau pastikan juri sudah melakukan penilaian.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Nama Lomba</th>
                                    <th>Nama Peserta</th>
                                    <th>Nilai Juri</th>
                                    <th>Catatan Juri</th>
                                    <th>Nama Juri</th>
                                    <th>Tanggal Scoring</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($scores as $index => $score): ?>
                                <tr>
                                    <td><?= ($page_score - 1) * $records_per_page + $index + 1 ?></td>
                                    <td><?= htmlspecialchars($score['competition_name']) ?></td>
                                    <td><?= htmlspecialchars($score['participant_name']) ?></td>
                                    <td><?= htmlspecialchars($score['jury_score']) ?></td>
                                    <td><?= htmlspecialchars($score['jury_notes'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($score['jury_name']) ?></td>
                                    <td><?= date('d M Y H:i', strtotime($score['score_date'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <nav aria-label="Scoring Pagination">
                        <ul class="pagination">
                            <li class="page-item <?= $page_score <= 1 ? 'disabled' : '' ?>">
                                <?php
                                $current_filter_params_score_prev = '';
                                if ($competition_filter > 0) {
                                    $current_filter_params_score_prev .= '&competition=' . $competition_filter;
                                }
                                if ($status_filter !== 'all') { // Status filter applies to both reports
                                    $current_filter_params_score_prev .= '&status=' . $status_filter;
                                }
                                if (!empty($name_filter)) {
                                    $current_filter_params_score_prev .= '&name=' . urlencode($name_filter);
                                }
                                $current_filter_params_score_prev .= '&page_reg=' . $page_reg; // Preserve registration page
                                ?>
                                <a class="page-link" href="?page_score=<?= $page_score - 1 ?><?= $current_filter_params_score_prev ?>">Previous</a>
                            </li>
                            <?php
                            $num_links_to_show = 5;
                            $current_filter_params_score = '';
                            if ($competition_filter > 0) {
                                $current_filter_params_score .= '&competition=' . $competition_filter;
                            }
                            if ($status_filter !== 'all') { // Status filter applies to both reports
                                $current_filter_params_score .= '&status=' . $status_filter;
                            }
                            if (!empty($name_filter)) {
                                $current_filter_params_score .= '&name=' . urlencode($name_filter);
                            }
                            $current_filter_params_score .= '&page_reg=' . $page_reg; // Preserve registration page

                            $start_page = max(1, $page_score - floor($num_links_to_show / 2));
                            $end_page = min($total_pages_score, $start_page + $num_links_to_show - 1);

                            if ($end_page - $start_page + 1 < $num_links_to_show) {
                                $start_page = max(1, $end_page - $num_links_to_show + 1);
                            }
                            
                            // Show first page if not already in range, and ellipsis if needed
                            if ($start_page > 1) {
                                echo '<li class="page-item"><a class="page-link" href="?page_score=1'. $current_filter_params_score .'">1</a></li>';
                                if ($start_page > 2) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                            }

                            for ($i = $start_page; $i <= $end_page; $i++) {
                                echo '<li class="page-item '. ($i == $page_score ? 'active' : '') .'"><a class="page-link" href="?page_score='. $i . $current_filter_params_score .'">'. $i .'</a></li>';
                            }

                            // Show last page if not already in range, and ellipsis if needed
                            if ($end_page < $total_pages_score) {
                                if ($end_page < $total_pages_score - 1) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                                echo '<li class="page-item"><a class="page-link" href="?page_score='. $total_pages_score . $current_filter_params_score .'">'. $total_pages_score .'</a></li>';
                            }
                            ?>
                            <li class="page-item <?= $page_score >= $total_pages_score ? 'disabled' : '' ?>">
                                <?php
                                $current_filter_params_score_next = '';
                                if ($competition_filter > 0) {
                                    $current_filter_params_score_next .= '&competition=' . $competition_filter;
                                }
                                if ($status_filter !== 'all') { // Status filter applies to both reports
                                    $current_filter_params_score_next .= '&status=' . $status_filter;
                                }
                                if (!empty($name_filter)) {
                                    $current_filter_params_score_next .= '&name=' . urlencode($name_filter);
                                }
                                $current_filter_params_score_next .= '&page_reg=' . $page_reg; // Preserve registration page
                                ?>
                                <a class="page-link" href="?page_score=<?= $page_score + 1 ?><?= $current_filter_params_score_next ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php include __DIR__.'/../includes/admin_footer.php'; ?>