<?php
$page_title = 'Manage Competitions';
require_once __DIR__.'/../includes/admin_header.php';

// Proses tambah lomba
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_competition'])) {
    $name = htmlspecialchars(trim($_POST['name'] ?? ''), ENT_QUOTES, 'UTF-8');
    $description = htmlspecialchars(trim($_POST['description'] ?? ''), ENT_QUOTES, 'UTF-8');
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $status = trim($_POST['status'] ?? '');

    $allowed_statuses = ['open', 'closed'];
    if (!in_array($status, $allowed_statuses)) {
        $message = '<div class="alert alert-danger">Error: Status yang dipilih tidak valid.</div>';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO competitions (name, description, start_date, end_date, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $description, $start_date, $end_date, $status]);
            $message = '<div class="alert alert-success">Lomba berhasil ditambahkan!</div>';
        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}

// Proses hapus lomba
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $competition_id = (int)$_GET['delete'];
    
    try {
        // Hapus semua pendaftaran yang terkait dengan lomba ini
        $stmt = $pdo->prepare("DELETE FROM participants WHERE competition_id = ?");
        $stmt->execute([$competition_id]);

        // Hapus lomba
        $stmt = $pdo->prepare("DELETE FROM competitions WHERE id = ?");
        $stmt->execute([$competition_id]);
        
        if ($stmt->rowCount() > 0) {
            $message = '<div class="alert alert-success">Lomba berhasil dihapus beserta pendaftarannya.</div>';
        } else {
            $message = '<div class="alert alert-danger">Data lomba tidak ditemukan.</div>';
        }
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

// Proses update lomba
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_competition'])) {
    $competition_id = (int)$_POST['competition_id'];
    $name = htmlspecialchars(trim($_POST['name'] ?? ''), ENT_QUOTES, 'UTF-8');
    $description = htmlspecialchars(trim($_POST['description'] ?? ''), ENT_QUOTES, 'UTF-8');
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $status = trim($_POST['status'] ?? '');

    $allowed_statuses = ['open', 'closed'];
    if (!in_array($status, $allowed_statuses)) {
        $message = '<div class="alert alert-danger">Error: Status yang dipilih tidak valid.</div>';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE competitions SET name = ?, description = ?, start_date = ?, end_date = ?, status = ? WHERE id = ?");
            $stmt->execute([$name, $description, $start_date, $end_date, $status, $competition_id]);
            $message = '<div class="alert alert-success">Lomba berhasil diperbarui!</div>';
        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}

// Ambil data lomba dengan paginasi
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Ambil total record
$total_stmt = $pdo->query("SELECT COUNT(*) FROM competitions");
$total_records = $total_stmt->fetchColumn();
$total_pages = ceil($total_records / $records_per_page);

// Ambil data lomba untuk halaman ini
$stmt = $pdo->prepare("SELECT * FROM competitions ORDER BY name LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$competitions = $stmt->fetchAll();
?>
<?php include __DIR__ . '/../includes/admin_sidebar.php'; ?>
<div class="content-wrapper">
    <?php include __DIR__ . '/../includes/admin_content_header.php'; ?>
    <div class="container-fluid">
        <h2><i class="bi bi-trophy"></i> Manage Competitions</h2>
        
        <!-- Tombol Kembali ke Dashboard -->
        <a href="dashboard_admin.php" class="btn btn-secondary mb-3">
            <i class="bi bi-arrow-left"></i> Kembali ke Dashboard
        </a>

        <!-- Notifikasi -->
        <?php if (!empty($message)) echo $message; ?>

        <!-- Form Tambah Lomba -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-plus-circle"></i> Tambah Lomba
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Nama Lomba:</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deskripsi:</label>
                        <textarea name="description" class="form-control" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tanggal Mulai:</label>
                        <input type="date" name="start_date" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tanggal Selesai:</label>
                        <input type="date" name="end_date" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status:</label>
                        <select name="status" class="form-select" required>
                            <option value="open">Open</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>
                    <button type="submit" name="add_competition" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Tambah Lomba
                    </button>
                </form>
            </div>
        </div>

        <!-- Daftar Lomba -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-list-ul"></i> Daftar Lomba
            </div>
            <div class="card-body">
                <?php if (empty($competitions)): ?>
                    <p class="text-muted">Tidak ada lomba yang tersedia.</p>
                <?php else: ?>
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nama Lomba</th>
                                <th>Deskripsi</th>
                                <th>Tanggal Mulai</th>
                                <th>Tanggal Selesai</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($competitions as $comp): ?>
                                <tr>
                                    <td><?= htmlspecialchars($comp['name']) ?></td>
                                    <td><?= htmlspecialchars($comp['description']) ?></td>
                                    <td><?= date('d M Y', strtotime($comp['start_date'])) ?></td>
                                    <td><?= date('d M Y', strtotime($comp['end_date'])) ?></td>
                                    <td><?= htmlspecialchars($comp['status']) ?></td>
                                    <td>
                                        <!-- Tombol Edit -->
                                        <button class="btn btn-warning btn-sm" data-bs-toggle="modal" 
                                                data-bs-target="#editModal" 
                                                data-id="<?= $comp['id'] ?>"
                                                data-name="<?= htmlspecialchars($comp['name']) ?>"
                                                data-description="<?= htmlspecialchars($comp['description']) ?>"
                                                data-start_date="<?= $comp['start_date'] ?>"
                                                data-end_date="<?= $comp['end_date'] ?>"
                                                data-status="<?= htmlspecialchars($comp['status']) ?>">
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>
                                        <!-- Tombol Hapus -->
                                        <a href="manage_competitions.php?delete=<?= $comp['id'] ?>" 
                                           class="btn btn-danger btn-sm"
                                           onclick="return confirm('Yakin ingin menghapus lomba ini?')">
                                            <i class="bi bi-trash"></i> Hapus
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
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
                        
                        $filter_params_suffix = ''; // No additional filters for manage_competitions.php

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
                        ?>
                        <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page + 1 ?>">Next</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <!-- Modal Edit -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editModalLabel">Edit Lomba</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="competition_id" id="editCompetitionId">
                        <div class="mb-3">
                            <label class="form-label">Nama Lomba:</label>
                            <input type="text" name="name" id="editName" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Deskripsi:</label>
                            <textarea name="description" id="editDescription" class="form-control" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tanggal Mulai:</label>
                            <input type="date" name="start_date" id="editStartDate" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tanggal Selesai:</label>
                            <input type="date" name="end_date" id="editEndDate" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status:</label>
                            <select name="status" id="editStatus" class="form-select" required>
                                <option value="open">Open</option>
                                <option value="closed">Closed</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="update_competition" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<?php include __DIR__.'/../includes/admin_footer.php'; ?>
<script>
    // Inisialisasi modal edit
    var editModal = document.getElementById('editModal');
    editModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget; // Tombol yang diklik
        var id = button.getAttribute('data-id'); // Ambil data-id
        var name = button.getAttribute('data-name'); // Ambil nama lomba
        var description = button.getAttribute('data-description'); // Ambil deskripsi
        var start_date = button.getAttribute('data-start_date'); // Ambil tanggal mulai
        var end_date = button.getAttribute('data-end_date'); // Ambil tanggal selesai
        var status = button.getAttribute('data-status'); // Ambil status
        
        // Isi data ke modal
        document.getElementById('editCompetitionId').value = id;
        document.getElementById('editName').value = name;
        document.getElementById('editDescription').value = description;
        document.getElementById('editStartDate').value = start_date;
        document.getElementById('editEndDate').value = end_date;
        document.getElementById('editStatus').value = status;
    });
</script>