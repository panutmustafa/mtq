<?php
$page_title = 'Kelola Pengumuman';
require_once __DIR__ . '/../includes/admin_header.php';

// Proses tambah pengumuman
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_announcement'])) {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $created_by = $_SESSION['user_id'];

    try {
        $stmt = $pdo->prepare("INSERT INTO announcements (title, content, created_by) VALUES (?, ?, ?)");
        $stmt->execute([$title, $content, $created_by]);
        $message = '<div class="alert alert-success">Pengumuman berhasil ditambahkan!</div>';
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

// Proses edit pengumuman
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_announcement'])) {
    $id = (int)$_POST['id'];
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);

    try {
        $stmt = $pdo->prepare("UPDATE announcements SET title = ?, content = ? WHERE id = ?");
        $stmt->execute([$title, $content, $id]);
        $message = '<div class="alert alert-success">Pengumuman berhasil diperbarui!</div>';
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

// Proses hapus pengumuman
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM announcements WHERE id = ?");
        $stmt->execute([$id]);
        $message = '<div class="alert alert-success">Pengumuman berhasil dihapus!</div>';
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

// Paginasi
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10; // Jumlah pengumuman per halaman
$offset = ($page - 1) * $records_per_page;

// Ambil total record
$total_query = "SELECT COUNT(*) FROM announcements";
$total_stmt = $pdo->query($total_query);
$total_records = $total_stmt->fetchColumn();
$total_pages = ceil($total_records / $records_per_page);

// Ambil pengumuman untuk halaman ini
$stmt = $pdo->prepare("SELECT a.*, u.full_name AS created_by_name FROM announcements a JOIN users u ON a.created_by = u.id ORDER BY a.created_at DESC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$announcements = $stmt->fetchAll();
?>
<?php include __DIR__ . '/../includes/admin_sidebar.php'; ?>
<div class="content-wrapper">
    <?php include __DIR__ . '/../includes/admin_content_header.php'; ?>
    <div class="container-fluid">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <i class="fas fa-bullhorn me-2"></i> Kelola Pengumuman
            </div>
            
            <div class="card-body">
                <?php if (!empty($message)) echo $message; ?>
                
                <!-- Form Tambah Pengumuman -->
                <h5 class="mb-3"><i class="fas fa-plus-circle me-2"></i> Tambah Pengumuman</h5>
                <form method="POST" class="mb-4">
                    <div class="mb-3">
                        <label for="title" class="form-label">Judul Pengumuman:</label>
                        <input type="text" name="title" id="title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="content" class="form-label">Isi Pengumuman:</label>
                        <textarea name="content" id="content" class="form-control" rows="5" required></textarea>
                    </div>
                    <button type="submit" name="add_announcement" class="btn btn-primary"><i class="fas fa-save me-1"></i> Tambah</button>
                  <a href="dashboard_admin.php" class="btn btn-secondary">
                  <i class="bi bi-arrow-left"></i> Kembali ke Dashboard
              </a>
                </form>

                <!-- Daftar Pengumuman -->
                <h5 class="mb-3"><i class="fas fa-list me-2"></i> Daftar Pengumuman</h5>
                <?php if (empty($announcements)): ?>
                    <div class="alert alert-info">Belum ada pengumuman.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Judul</th>
                                    <th>Isi</th>
                                    <th>Dibuat Oleh</th>
                                    <th>Tanggal</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; foreach ($announcements as $ann): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= htmlspecialchars($ann['title']) ?></td>
                                        <td><?= htmlspecialchars(substr($ann['content'], 0, 100)) . (strlen($ann['content']) > 100 ? '...' : '') ?></td>
                                        <td><?= htmlspecialchars($ann['created_by_name']) ?></td>
                                        <td><?= date('d M Y H:i', strtotime($ann['created_at'])) ?></td>
                                        <td>
                                            <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?= $ann['id'] ?>"><i class="fas fa-edit"></i> Edit</button>
                                            <a href="?delete=<?= $ann['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus pengumuman ini?')"><i class="fas fa-trash"></i> Hapus</a>
                                        </td>
                                    </tr>
                                    <!-- Modal Edit -->
                                    <div class="modal fade" id="editModal<?= $ann['id'] ?>" tabindex="-1" aria-labelledby="editModalLabel<?= $ann['id'] ?>" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST">
                                                    <div class="modal-header bg-warning">
                                                        <h5 class="modal-title" id="editModalLabel<?= $ann['id'] ?>"><i class="fas fa-edit me-2"></i> Edit Pengumuman</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <input type="hidden" name="id" value="<?= $ann['id'] ?>">
                                                        <div class="mb-3">
                                                            <label for="title<?= $ann['id'] ?>" class="form-label">Judul Pengumuman:</label>
                                                            <input type="text" name="title" id="title<?= $ann['id'] ?>" class="form-control" value="<?= htmlspecialchars($ann['title']) ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="content<?= $ann['id'] ?>" class="form-label">Isi Pengumuman:</label>
                                                            <textarea name="content" id="content<?= $ann['id'] ?>" class="form-control" rows="5" required><?= htmlspecialchars($ann['content']) ?></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fas fa-times"></i> Batal</button>
                                                        <button type="submit" name="edit_announcement" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
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
                                                    
                                                    $filter_params_suffix = ''; // No additional filters for manage_announcements.php
                            
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
                                                    ?>                            <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page + 1 ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php include __DIR__.'/../includes/admin_footer.php'; ?>