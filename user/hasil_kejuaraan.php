<?php
$page_title = 'Hasil Kejuaraan';
require_once __DIR__ . '/../includes/user_header.php';

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
<?php include __DIR__ . '/../includes/user_sidebar.php'; ?>
<div class="content-wrapper">
    <?php include __DIR__ . '/../includes/user_content_navbar.php'; ?>
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
<?php include __DIR__ . '/../includes/user_footer.php'; ?>
