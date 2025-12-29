<?php
// Mencegah caching halaman sensitif
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Tanggal di masa lalu

session_start();
require '../config/db.php'; // Pastikan path ini benar

// Pastikan hanya admin yang bisa mengakses halaman ini
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

date_default_timezone_set('Asia/Jakarta');

// Hapus data absensi
if (isset($_GET['hapus_absen'])) {
    $id = $_GET['hapus_absen'];
    $stmt = $conn->prepare("DELETE FROM absensi WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $_SESSION['alert_type'] = 'success';
        $_SESSION['alert_message'] = 'Data absensi berhasil dihapus!';
    } else {
        $_SESSION['alert_type'] = 'danger';
        $_SESSION['alert_message'] = 'Gagal menghapus data absensi. Terjadi kesalahan database.';
    }
    header("Location: absensi.php");
    exit;
}

// Ambil filter dari GET request
$filter_nama = trim($_GET['nama'] ?? '');
$filter_tgl_mulai = trim($_GET['tgl_mulai'] ?? '');
$filter_tgl_sampai = trim($_GET['tgl_sampai'] ?? '');

// Query dasar dengan JOIN
$sql = "SELECT a.id, a.tanggal, a.waktu, a.keterangan, a.latitude, a.longitude, u.full_name, u.asal_sekolah
        FROM absensi a
        JOIN users u ON a.user_id = u.id
        WHERE 1=1";

// Tambahkan filter jika ada
$params = [];
$types = "";

if ($filter_nama !== '') {
    $sql .= " AND u.full_name LIKE ?";
    $params[] = '%' . $filter_nama . '%';
    $types .= "s";
}
if ($filter_tgl_mulai !== '' && $filter_tgl_sampai !== '') {
    $sql .= " AND a.tanggal BETWEEN ? AND ?";
    $params[] = $filter_tgl_mulai;
    $params[] = $filter_tgl_sampai;
    $types .= "ss";
} elseif ($filter_tgl_mulai !== '') {
    $sql .= " AND a.tanggal >= ?";
    $params[] = $filter_tgl_mulai;
    $types .= "s";
} elseif ($filter_tgl_sampai !== '') {
    $sql .= " AND a.tanggal <= ?";
    $params[] = $filter_tgl_sampai;
    $types .= "s";
}

$sql .= " ORDER BY a.tanggal DESC, a.waktu DESC";

// Siapkan statement
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    // Memanggil bind_param secara dinamis
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result_absensi = $stmt->get_result();

// Siapkan URL export dengan query string yang sama
$export_url_excel = "export-absensi.php";
$export_url_pdf = "export-absensi-pdf.php";
$query_string_parts = [];

if (!empty($filter_nama)) {
    $query_string_parts[] = "nama=" . urlencode($filter_nama);
}
if (!empty($filter_tgl_mulai)) {
    $query_string_parts[] = "tgl_mulai=" . urlencode($filter_tgl_mulai);
}
if (!empty($filter_tgl_sampai)) {
    $query_string_parts[] = "tgl_sampai=" . urlencode($filter_tgl_sampai);
}

if ($query_string_parts) {
    $full_query_string = implode('&', $query_string_parts);
    $export_url_excel .= '?' . $full_query_string;
    $export_url_pdf .= '?' . $full_query_string;
}

// Ambil pesan alert dari session (jika ada)
$alert_type = '';
$alert_message = '';
if (isset($_SESSION['alert_type']) && isset($_SESSION['alert_message'])) {
    $alert_type = $_SESSION['alert_type'];
    $alert_message = $_SESSION['alert_message'];
    unset($_SESSION['alert_type']);
    unset($_SESSION['alert_message']);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Absensi | Sistem Absensi</title>
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
            .table tbody td:nth-child(1)::before { content: "No."; }
            .table tbody td:nth-child(2)::before { content: "Nama Lengkap"; }
            .table tbody td:nth-child(3)::before { content: "Asal Sekolah"; }
            .table tbody td:nth-child(4)::before { content: "Tanggal"; }
            .table tbody td:nth-child(5)::before { content: "Waktu"; }
            .table tbody td:nth-child(6)::before { content: "Keterangan"; }
            .table tbody td:nth-child(7)::before { content: "Lokasi"; }
            .table tbody td:nth-child(8)::before { content: "Aksi"; }
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
                    <a class="nav-link" href="users.php"><i class="fas fa-users me-1"></i> Kelola Pengguna</a>
                </li>
                 <li class="nav-item">
                    <a class="nav-link active" aria-current="page" href="absensi.php"><i class="fas fa-calendar-check me-1"></i> Data Absensi</a>
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
            <h2 class="mb-4 text-center text-primary"><i class="fas fa-chart-bar me-3"></i> Rekap Data Absensi</h2>
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
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-filter me-2"></i> Filter Data Absensi</h5>
            <div class="d-flex flex-wrap gap-2">
                <a href="<?= htmlspecialchars($export_url_excel) ?>" target='_blank' class="btn btn-success btn-sm"><i class="fas fa-file-excel me-1"></i> Export Excel</a>
                <a href="<?= htmlspecialchars($export_url_pdf) ?>" target='_blank' class="btn btn-danger btn-sm"><i class="fas fa-file-pdf me-1"></i> Export PDF</a>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="nama" class="form-label text-muted">Nama Lengkap</label>
                    <input type="text" id="nama" name="nama" class="form-control" placeholder="Cari berdasarkan nama..." value="<?= htmlspecialchars($filter_nama) ?>">
                </div>
                <div class="col-md-3">
                    <label for="tgl_mulai" class="form-label text-muted">Tanggal Mulai</label>
                    <input type="date" id="tgl_mulai" name="tgl_mulai" class="form-control" value="<?= htmlspecialchars($filter_tgl_mulai) ?>">
                </div>
                <div class="col-md-3">
                    <label for="tgl_sampai" class="form-label text-muted">Tanggal Sampai</label>
                    <input type="date" id="tgl_sampai" name="tgl_sampai" class="form-control" value="<?= htmlspecialchars($filter_tgl_sampai) ?>">
                </div>
                <div class="col-md-2 d-grid gap-2">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search me-1"></i> Terapkan</button>
                    <a href="absensi.php" class="btn btn-outline-secondary"><i class="fas fa-redo me-1"></i> Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="fas fa-table me-2"></i> Data Absensi Lengkap</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="text-center">No.</th>
                            <th scope="col">Nama Lengkap</th>
                            <th scope="col">Asal Sekolah</th>
                            <th scope="col">Tanggal</th>
                            <th scope="col">Waktu</th>
                            <th scope="col" class="text-center">Keterangan</th>
                            <th scope="col" class="text-center">Lokasi</th>
                            <th scope="col" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; if ($result_absensi->num_rows > 0): ?>
                            <?php while ($row = $result_absensi->fetch_assoc()): ?>
                                <tr>
                                    <td data-label="No." class="text-center"><?= $no++ ?></td>
                                    <td data-label="Nama Lengkap"><?= htmlspecialchars($row['full_name']) ?></td>
                                    <td data-label="Asal Sekolah"><?= htmlspecialchars($row['asal_sekolah']) ?></td>
                                    <td data-label="Tanggal"><?= date('d M Y', strtotime($row['tanggal'])) ?></td>
                                    <td data-label="Waktu"><?= date('H:i', strtotime($row['waktu'])) ?></td>
                                    <td data-label="Keterangan" class="text-center">
                                        <?php
                                        $status_class = match($row['keterangan']) {
                                            'Hadir' => 'bg-success',
                                            'Izin' => 'bg-info',
                                            'Sakit' => 'bg-warning text-dark',
                                            'Alpha' => 'bg-danger',
                                            default => 'bg-secondary'
                                        };
                                        ?>
                                        <span class="badge <?= $status_class ?>"><?= htmlspecialchars($row['keterangan']) ?></span>
                                    </td>
                                    <td data-label="Lokasi" class="text-center">
                                        <?php if (!empty($row['latitude']) && !empty($row['longitude'])): ?>
                                            <a href="https://maps.google.com/maps?q=<?= htmlspecialchars($row['latitude']) ?>,<?= htmlspecialchars($row['longitude']) ?>" target="_blank" class="btn btn-outline-primary btn-action" title="Lihat Lokasi">
                                                <i class="fas fa-map-marker-alt"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Aksi" class="text-center">
                                        <a href="?hapus_absen=<?= $row['id'] ?>" onclick="return confirm('Apakah Anda yakin ingin menghapus data absensi ini? Tindakan ini tidak bisa dibatalkan.')" class="btn btn-danger btn-action" title="Hapus Data">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="fas fa-info-circle me-2"></i> Tidak ada data absensi yang ditemukan sesuai filter.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>