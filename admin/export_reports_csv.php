<?php
session_start();
require_once __DIR__.'/../includes/auth.php';
if ($_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

require_once __DIR__.'/../config/database.php';

// Ambil filter dari GET
$competition_filter = isset($_GET['competition']) ? (int)$_GET['competition'] : 0;
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$name_filter = isset($_GET['name']) ? trim($_GET['name']) : '';

// Query untuk mengambil semua data pendaftaran tanpa paginasi
$query = "SELECT 
            c.name as competition_name,
            u.username,
            u.full_name,
            p.registration_date,
            c.status as competition_status,
            p.category
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
    $query .= " WHERE " . implode(" AND ", $where_clause);
}

$query .= " ORDER BY p.registration_date DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set header untuk file CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="registration_report_' . date('Y-m-d') . '.csv"');

// Buat file CSV
$output = fopen('php://output', 'w');

// Header CSV
fputcsv($output, ['Nama Lomba', 'Penanggungjawab', 'Username', 'Tanggal Daftar', 'Kategori', 'Status Lomba']);

// Isi data
foreach ($registrations as $reg) {
    fputcsv($output, [
        $reg['competition_name'],
        $reg['full_name'],
        $reg['username'],
        date('d M Y H:i', strtotime($reg['registration_date'])),
        $reg['category'] ?? '-',
        ucfirst($reg['competition_status'])
    ]);
}

fclose($output);
exit();
?>