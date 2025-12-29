<?php
// Mencegah caching halaman sensitif
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Tanggal di masa lalu

session_start();
require '../config/db.php'; // Pastikan path ini benar (relatif terhadap file ini)

// Pastikan hanya admin yang bisa mengakses halaman ini
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

date_default_timezone_set('Asia/Jakarta');

// Fungsi untuk menyiapkan pesan alert
function set_alert_message($type, $message) {
    $_SESSION['alert_type'] = $type;
    $_SESSION['alert_message'] = $message;
}

// Tambah user
if (isset($_POST['tambah_user'])) {
    $username = trim($_POST['username'] ?? '');
    $password_plain = $_POST['password'] ?? 'user123'; // Default password jika kosong
    $password = password_hash($password_plain, PASSWORD_DEFAULT);
    $role = trim($_POST['role'] ?? 'user');
    $full_name = trim($_POST['full_name'] ?? '');
    $asal_sekolah = trim($_POST['asal_sekolah'] ?? '');

    if (empty($username) || empty($full_name) || empty($asal_sekolah)) {
        set_alert_message("warning", "Username, Nama Lengkap, dan Asal Sekolah harus diisi.");
    } else {
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $check_stmt->bind_param("s", $username);
        $check_stmt->execute();
        $check_stmt->store_result();
        if ($check_stmt->num_rows > 0) {
            set_alert_message("danger", "Gagal menambahkan user. Username sudah ada.");
        } else {
            $stmt = $conn->prepare("INSERT INTO users (username, full_name, asal_sekolah, password, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $username, $full_name, $asal_sekolah, $password, $role);
            if ($stmt->execute()) {
                set_alert_message("success", "User berhasil ditambahkan.");
            } else {
                set_alert_message("danger", "Gagal menambahkan user. Terjadi kesalahan database.");
            }
        }
        $check_stmt->close();
    }
    header("Location: users.php"); // Redirect agar alert muncul setelah refresh
    exit;
}

// Update user
if (isset($_POST['update_user'])) {
    $id = $_POST['id_user'];
    $username = trim($_POST['username'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $role = trim($_POST['role'] ?? 'user');
    $asal_sekolah = trim($_POST['asal_sekolah'] ?? '');

    if (empty($username) || empty($full_name) || empty($asal_sekolah)) {
        set_alert_message("warning", "Username, Nama Lengkap, dan Asal Sekolah harus diisi.");
    } else {
        // Cek apakah username sudah ada untuk user lain
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $check_stmt->bind_param("si", $username, $id);
        $check_stmt->execute();
        $check_stmt->store_result();
        if ($check_stmt->num_rows > 0) {
            set_alert_message("danger", "Gagal mengupdate user. Username sudah digunakan oleh user lain.");
        } else {
            $stmt = $conn->prepare("UPDATE users SET username=?, full_name=?, asal_sekolah=?, role=? WHERE id=?");
            $stmt->bind_param("ssssi", $username, $full_name, $asal_sekolah, $role, $id);
            if ($stmt->execute()) {
                set_alert_message("success", "User berhasil diupdate.");
            } else {
                set_alert_message("danger", "Gagal mengupdate user. Terjadi kesalahan database.");
            }
        }
        $check_stmt->close();
    }
    header("Location: users.php"); // Redirect agar alert muncul setelah refresh
    exit;
}

// Reset password
if (isset($_GET['reset_user'])) {
    $id = $_GET['reset_user'];
    $newpass_plain = 'user123'; // Default password
    $newpass = password_hash($newpass_plain, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
    $stmt->bind_param("si", $newpass, $id);
    if ($stmt->execute()) {
        set_alert_message("success", "Password berhasil direset ke: <strong>{$newpass_plain}</strong>.");
    } else {
        set_alert_message("danger", "Gagal mereset password.");
    }
    header("Location: users.php"); // Redirect agar alert muncul setelah refresh
    exit;
}

// Hapus user
if (isset($_GET['hapus_user'])) {
    $id = $_GET['hapus_user'];
    // Penting: Jika ada foreign key constraint di tabel lain (misal absensi),
    // Anda perlu menangani penghapusan data terkait terlebih dahulu atau
    // memastikan foreign key di database diatur ke CASCADE ON DELETE.
    // Contoh: DELETE FROM absensi WHERE user_id = ?;
    $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        set_alert_message("success", "User berhasil dihapus.");
    } else {
        set_alert_message("danger", "Gagal menghapus user. Pastikan tidak ada data absensi terkait.");
    }
    header("Location: users.php"); // Redirect agar alert muncul setelah refresh
    exit;
}

// Ambil data dari SESSION untuk alert (setelah redirect)
$alert_type = '';
$alert_message = '';
if (isset($_SESSION['alert_type']) && isset($_SESSION['alert_message'])) {
    $alert_type = $_SESSION['alert_type'];
    $alert_message = $_SESSION['alert_message'];
    unset($_SESSION['alert_type']);
    unset($_SESSION['alert_message']);
}

// Data untuk ditampilkan
$users_data = $conn->query("SELECT * FROM users ORDER BY id ASC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengguna | Sistem Absensi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }
        .navbar {
            background-color: #2c3e50; /* Dark Blue */
            box-shadow: 0 4px 8px rgba(0,0,0,.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .navbar-brand, .navbar-text {
            color: #ecf0f1 !important; /* Light Gray */
            font-weight: 600;
        }
        .navbar-nav .nav-link {
            color: #bdc3c7 !important; /* Muted Gray */
            transition: color 0.3s ease;
        }
        .navbar-nav .nav-link:hover {
            color: #ecf0f1 !important; /* Light Gray on hover */
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
            border-radius: 0.75rem;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
            border: none;
            overflow: hidden;
        }
        .card-header {
            border-top-left-radius: 0.75rem;
            border-top-right-radius: 0.75rem;
            padding: 1.25rem 1.75rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            font-size: 1.15rem;
            gap: 10px;
        }
        .card-header i {
            margin-right: 0.5rem;
        }
        .card-body {
            padding: 1.75rem;
        }
        .form-label {
            font-weight: 500;
            color: #555;
            margin-bottom: 0.5rem;
        }
        .form-control, .form-select {
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
        }
        .btn {
            border-radius: 0.5rem;
            padding: 0.75rem 1.25rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn i {
            margin-right: 0.5rem;
        }
        .btn-action { /* Untuk tombol aksi di tabel */
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            padding: 0;
            border-radius: 50%; /* Membuat tombol lingkaran */
            font-size: 0.9em;
        }
        .btn-action i {
            margin: 0; /* Hapus margin di ikon dalam tombol lingkaran */
            font-size: 1.1em;
        }

        /* Alerts */
        .alert-custom {
            border-radius: 0.75rem;
            padding: 1.25rem 1.75rem;
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 1rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }
        .alert-custom i {
            font-size: 1.4rem;
        }
        .alert-custom .btn-close {
            font-size: 0.9rem;
        }

        /* Tables */
        .table thead th {
            background-color: #e9ecef; /* Light gray for table header */
            color: #495057; /* Dark gray text */
            font-weight: 600;
            vertical-align: middle;
            border-bottom: 2px solid #dee2e6;
            text-align: center;
        }
        .table tbody td {
            vertical-align: middle;
            padding: 1rem;
            text-align: center;
        }
        /* Align specific columns to left for better readability */
        .table tbody td:first-child,
        .table tbody td:nth-child(2),
        .table tbody td:nth-child(3)
        {
            text-align: left;
        }
        .table tbody tr:hover {
            background-color: #f5f5f5;
        }
        .badge {
            font-size: 0.85em;
            padding: 0.4em 0.8em;
            border-radius: 0.5rem;
        }

        /* Responsive Table */
        @media (max-width: 767.98px) {
            .table-responsive {
                border: 1px solid #dee2e6;
                border-radius: 0.75rem;
                overflow-x: auto; /* Memungkinkan scrolling horizontal */
            }
            .table thead {
                display: none; /* Sembunyikan header tabel di mobile */
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
                text-align: right; /* Konten di kanan */
            }
            .table tbody td:last-child {
                border-bottom: none;
            }
            .table tbody td::before {
                content: attr(data-label); /* Tampilkan label dari data-label */
                font-weight: bold;
                margin-right: 0.5rem;
                color: #555;
                flex-shrink: 0;
                text-align: left; /* Label di kiri */
                width: 45%; /* Sesuaikan lebar label */
            }
            /* Specific labels for each column */
            .table.users-table tbody td:nth-child(1)::before { content: "ID"; }
            .table.users-table tbody td:nth-child(2)::before { content: "Nama Lengkap"; }
            .table.users-table tbody td:nth-child(3)::before { content: "Username"; }
            .table.users-table tbody td:nth-child(4)::before { content: "Role"; }
            .table.users-table tbody td:nth-child(5)::before { content: "Asal Sekolah"; }
            .table.users-table tbody td:nth-child(6)::before { content: "Aksi"; }

            .card-header {
                flex-direction: column;
                align-items: flex-start !important;
            }
            .card-header .d-flex {
                width: 100%;
                justify-content: flex-start !important;
                margin-top: 0.5rem;
            }
            .card-header .d-flex .btn {
                width: 100%;
                margin-bottom: 0.5rem;
            }
        }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark px-3">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php"><i class="fas fa-user-shield me-2"></i> Admin Panel</a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt me-1"></i> Dashboard</a>
                </li>
                 <li class="nav-item">
                    <a class="nav-link active" aria-current="page" href="users.php"><i class="fas fa-users me-1"></i> Kelola Pengguna</a>
                </li>
                 <li class="nav-item">
                    <a class="nav-link" href="absensi.php"><i class="fas fa-calendar-check me-1"></i> Data Absensi</a>
                </li>
                <li class="nav-item">
                    <span class="navbar-text me-lg-3 text-white py-2 py-lg-0">
                        <i class="fas fa-user-circle me-2"></i> Halo, <?= htmlspecialchars($_SESSION['user']['full_name'] ?? $_SESSION['user']['username']) ?>
                    </span>
                </li>
                <li class="nav-item">
                    <a href="../logout.php" class="btn btn-outline-light rounded-pill px-3"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4 text-center text-success"><i class="fas fa-users me-3"></i> Kelola Pengguna</h2>
            <hr class="mb-5">
        </div>

        <?php if ($alert_message): ?>
            <div class="col-12">
                <div class="alert alert-<?= htmlspecialchars($alert_type) ?> alert-dismissible fade show shadow-sm alert-custom" role="alert">
                    <i class="fas fa-<?= $alert_type == 'success' ? 'check-circle' : ($alert_type == 'warning' ? 'exclamation-triangle' : ($alert_type == 'danger' ? 'times-circle' : 'info-circle')) ?> me-2"></i>
                    <div><?= $alert_message ?></div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="card shadow-sm mb-5">
        <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
            <span>Daftar Pengguna Sistem</span>
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#tambahUserModal">
                    <i class="fas fa-plus-circle me-1"></i> Tambah User Baru
                </button>
                <a href="upload-users.php" target='_blank' class="btn btn-info btn-sm text-white">
                      <i class="fas fa-file-excel me-1"></i> Upload user
                  </a>
                <a href="export-users.php" target='_blank' class="btn btn-info btn-sm text-white">
                    <i class="fas fa-file-excel me-1"></i> Export Data
                </a>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0 users-table">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="text-center">ID</th>
                            <th scope="col">Nama Lengkap</th>
                            <th scope="col">Username</th>
                            <th scope="col" class="text-center">Role</th>
                            <th scope="col">Asal Sekolah</th>
                            <th scope="col" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($users_data->num_rows > 0): ?>
                            <?php while ($row = $users_data->fetch_assoc()): ?>
                                <tr>
                                    <td data-label="ID" class="text-center"><?= htmlspecialchars($row['id']) ?></td>
                                    <td data-label="Nama Lengkap"><?= htmlspecialchars($row['full_name']) ?></td>
                                    <td data-label="Username"><?= htmlspecialchars($row['username']) ?></td>
                                    <td data-label="Role" class="text-center">
                                        <span class="badge bg-<?= $row['role'] == 'admin' ? 'danger' : 'secondary' ?>">
                                            <?= ucfirst(htmlspecialchars($row['role'])) ?>
                                        </span>
                                    </td>
                                    <td data-label="Asal Sekolah"><?= htmlspecialchars($row['asal_sekolah']) ?></td>
                                    <td data-label="Aksi" class="text-center">
                                        <button type="button" class="btn btn-warning btn-action me-1" title="Edit User"
                                                data-bs-toggle="modal" data-bs-target="#editUserModal"
                                                data-id="<?= $row['id'] ?>"
                                                data-username="<?= htmlspecialchars($row['username']) ?>"
                                                data-fullname="<?= htmlspecialchars($row['full_name']) ?>"
                                                data-role="<?= htmlspecialchars($row['role']) ?>"
                                                data-asalsekolah="<?= htmlspecialchars($row['asal_sekolah']) ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="?reset_user=<?= $row['id'] ?>" onclick="return confirm('Apakah Anda yakin ingin mereset password user ini? Password akan direset ke \'user123\'.')" class="btn btn-info btn-action me-1 text-white" title="Reset Password">
                                            <i class="fas fa-key"></i>
                                        </a>
                                        <a href="?hapus_user=<?= $row['id'] ?>" onclick="return confirm('Apakah Anda yakin ingin menghapus user ini? Semua data terkait user ini (termasuk absensi) mungkin akan terhapus.')" class="btn btn-danger btn-action" title="Hapus User">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <i class="fas fa-info-circle me-2"></i> Belum ada data pengguna. Klik "Tambah User Baru" untuk memulai.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div> <div class="modal fade" id="tambahUserModal" tabindex="-1" aria-labelledby="tambahUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="tambahUserModalLabel"><i class="fas fa-user-plus me-2"></i> Tambah User Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="tambah_username" class="form-label">Username</label>
                        <input type="text" name="username" id="tambah_username" class="form-control" placeholder="Masukkan username" required>
                    </div>
                    <div class="mb-3">
                        <label for="tambah_full_name" class="form-label">Nama Lengkap</label>
                        <input type="text" name="full_name" id="tambah_full_name" class="form-control" placeholder="Masukkan nama lengkap" required>
                    </div>
                    <div class="mb-3">
                        <label for="tambah_asal_sekolah" class="form-label">Asal Sekolah</label>
                        <input type="text" name="asal_sekolah" id="tambah_asal_sekolah" class="form-control" placeholder="Masukkan asal sekolah" required>
                    </div>
                    <div class="mb-3">
                        <label for="tambah_password" class="form-label">Password (Default: user123)</label>
                        <input type="password" name="password" id="tambah_password" class="form-control" placeholder="Kosongkan untuk default 'user123'">
                    </div>
                    <div class="mb-3">
                        <label for="tambah_role" class="form-label">Role</label>
                        <select name="role" id="tambah_role" class="form-select" required>
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fas fa-times-circle me-2"></i> Batal</button>
                    <button type="submit" name="tambah_user" class="btn btn-success"><i class="fas fa-plus-circle me-2"></i> Tambah User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="editUserModalLabel"><i class="fas fa-user-edit me-2"></i> Edit Data Pengguna</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id_user" id="edit_id_user">
                    <div class="mb-3">
                        <label for="edit_username" class="form-label">Username</label>
                        <input type="text" name="username" id="edit_username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_full_name" class="form-label">Nama Lengkap</label>
                        <input type="text" name="full_name" id="edit_full_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_asal_sekolah" class="form-label">Asal Sekolah</label>
                        <input type="text" name="asal_sekolah" id="edit_asal_sekolah" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_role" class="form-label">Role</label>
                        <select name="role" id="edit_role" class="form-select" required>
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fas fa-times-circle me-2"></i> Batal</button>
                    <button type="submit" name="update_user" class="btn btn-primary"><i class="fas fa-save me-2"></i> Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var editUserModal = document.getElementById('editUserModal');
        editUserModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget; // Button that triggered the modal
            var id = button.getAttribute('data-id');
            var username = button.getAttribute('data-username');
            var fullName = button.getAttribute('data-fullname');
            var role = button.getAttribute('data-role');
            var asalSekolah = button.getAttribute('data-asalsekolah');

            // Update the modal's content.
            var modalId = editUserModal.querySelector('#edit_id_user');
            var modalUsername = editUserModal.querySelector('#edit_username');
            var modalFullName = editUserModal.querySelector('#edit_full_name');
            var modalRole = editUserModal.querySelector('#edit_role');
            var modalAsalSekolah = editUserModal.querySelector('#edit_asal_sekolah');

            modalId.value = id;
            modalUsername.value = username;
            modalFullName.value = fullName;
            modalRole.value = role;
            modalAsalSekolah.value = asalSekolah;
        });
    });
</script>

</body>
</html>