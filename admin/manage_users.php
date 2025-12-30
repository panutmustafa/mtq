<?php
$page_title = 'Manage Users';
require_once __DIR__.'/../includes/admin_header.php';

// PROSES UTAMA
$message = '';

// 1. PROSES TAMBAH USER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $full_name = trim($_POST['full_name']);
    $role = trim($_POST['role']); // Pastikan menggunakan trim()

    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $password, $full_name, $role]);
        $message = '<div class="alert alert-success">User  berhasil ditambahkan!</div>';
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

// 3. PROSES UPDATE USER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $user_id = (int)$_POST['user_id'];
    $username = trim($_POST['username']);
    $full_name = trim($_POST['full_name']);
    $role = trim($_POST['role']); // Pastikan menggunakan trim()

    try {
        $stmt = $pdo->prepare("UPDATE users SET username = ?, full_name = ?, role = ? WHERE id = ?");
        $stmt->execute([$username, $full_name, $role, $user_id]);
        $message = '<div class="alert alert-success">User  berhasil diperbarui!</div>';
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

// 2. PROSES HAPUS USER
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $user_id = (int)$_GET['delete'];
    
    try {
        // Hapus entri terkait di tabel scores
        $stmt = $pdo->prepare("DELETE FROM scores WHERE participant_id IN (SELECT id FROM participants WHERE user_id = ?)");
        $stmt->execute([$user_id]);

        // Hapus entri terkait di tabel participants
        $stmt = $pdo->prepare("DELETE FROM participants WHERE user_id = ?");
        $stmt->execute([$user_id]);

        // Hapus user
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        
        if ($stmt->rowCount() > 0) {
            $message = '<div class="alert alert-success">User  berhasil dihapus!</div>';
        } else {
            $message = '<div class="alert alert-danger">User  tidak ditemukan</div>';
        }
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

// AMBIL DATA USER DENGAN PAGINASI
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Ambil total record untuk paginasi
$total_stmt = $pdo->query("SELECT COUNT(*) FROM users");
$total_records = $total_stmt->fetchColumn();
$total_pages = ceil($total_records / $records_per_page);

// Ambil data pengguna untuk halaman saat ini
$stmt = $pdo->prepare("SELECT * FROM users ORDER BY role, full_name LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$users = $stmt->fetchAll();
?>
<?php include __DIR__ . '/../includes/admin_sidebar.php'; ?>
<div class="content-wrapper">
    <?php include __DIR__ . '/../includes/admin_content_header.php'; ?>
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-people-fill"></i> Manage Users</h2>
            <a href="dashboard_admin.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Kembali ke Dashboard
            </a>
        </div>

        <!-- Notifikasi -->
        <?php if (!empty($message)) echo $message; ?>

        <!-- Form Tambah User -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-plus-circle"></i> Tambah User Baru
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" name="full_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Role</label>
                            <select name="role" class="form-select" required>
                                <option value="admin">Admin</option>
                                <option value="jury">Jury</option>
                                <option value="user">User </option>
                            </select>
                        </div>
                        <div class="col-12">
                            <button type="submit" name="add_user" class="btn btn-primary">
                                <i class="bi bi-save"></i> Simpan User
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Daftar User -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-list-ul"></i> Daftar User
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Nama Lengkap</th>
                                <th>Role</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?= htmlspecialchars($user['username']) ?></td>
                                    <td><?= htmlspecialchars($user['full_name']) ?></td>
                                    <td>
                                        <span class="role-badge role-<?= $user['role'] ?>">
                                            <?= ucfirst($user['role']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <!-- Tombol Edit -->
                                        <button class="btn btn-warning btn-sm" data-bs-toggle="modal" 
                                                data-bs-target="#editModal" 
                                                data-id="<?= $user['id'] ?>"
                                                data-username="<?= htmlspecialchars($user['username']) ?>"
                                                data-full_name="<?= htmlspecialchars($user['full_name']) ?>"
                                                data-role="<?= htmlspecialchars($user['role']) ?>">
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>
                                        <!-- Tombol Hapus -->
                                        <a href="manage_users.php?delete=<?= $user['id'] ?>" 
                                           class="btn btn-danger btn-sm"
                                           onclick="return confirm('Yakin ingin menghapus user ini?')">
                                            <i class="bi bi-trash"></i> Hapus
                                        </a>
                                    </td>
                                </tr>
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
                        
                        $filter_params_suffix = ''; // No additional filters for manage_users.php

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
                        <h5 class="modal-title" id="editModalLabel">Edit User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="user_id" id="editUser Id">
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" id="editUsername" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" name="full_name" id="editFullName" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select name="role" id="editRole" class="form-select" required>
                                <option value="admin">admin</option>
                                <option value="jury">jury</option>
                                <option value="user">user</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="update_user" class="btn btn-primary">Simpan Perubahan</button>
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
        var button = event.relatedTarget;
        var id = button.getAttribute('data-id');
        var username = button.getAttribute('data-username');
        var full_name = button.getAttribute('data-full_name');
        var role = button.getAttribute('data-role');

        // Isi data ke modal
        document.getElementById('editUser Id').value = id;
        document.getElementById('editUsername').value = username;
        document.getElementById('editFullName').value = full_name;
        document.getElementById('editRole').value = role;
    });
</script>