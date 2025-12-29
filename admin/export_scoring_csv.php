<?php
session_start();
require_once __DIR__.'/../includes/auth.php';
if ($_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

require_once __DIR__.'/../config/database.php';

// Nonaktifkan display_errors untuk mencegah pesan error masuk ke CSV
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Atur zona waktu ke WIB
date_default_timezone_set('Asia/Jakarta');

// Ambil filter dari GET
$competition_filter = isset($_GET['competition']) ? (int)$_GET['competition'] : 0;
$name_filter = isset($_GET['name']) ? trim($_GET['name']) : '';

// Query untuk mengambil semua data scoring tanpa paginasi
$scoring_query = "
    SELECT 
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
    $scoring_query .= " WHERE " . implode(" AND ", $scoring_where_clause);
}

$scoring_query .= " ORDER BY c.name, p.full_name, u.full_name";

try {
    $scoring_stmt = $pdo->prepare($scoring_query);
    $scoring_stmt->execute($scoring_params);
    $scores = $scoring_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Periksa apakah ada data
    if (empty($scores)) {
        header('Content-Type: text/html; charset=utf-8');
        die('Tidak ada data scoring yang ditemukan untuk filter yang diberikan.');
    }

    // Set header untuk file CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="scoring_report_' . date('Y-m-d') . '.csv"');
    // Tambahkan BOM untuk UTF-8 agar Excel membaca karakter khusus dengan benar
    echo "\xEF\xBB\xBF";

    // Buat file CSV
    $output = fopen('php://output', 'w');

    // Header CSV dengan parameter escape eksplisit
    fputcsv($output, ['Nama Peserta', 'Nama Lomba', 'Nilai Juri', 'Nama Juri', 'Catatan Juri', 'Tanggal Scoring'], ',', '"', '\\');

    // Isi data
    foreach ($scores as $score) {
        fputcsv($output, [
            $score['participant_name'],
            $score['competition_name'],
            $score['jury_score'],
            $score['jury_name'],
            $score['jury_notes'] ?? '-',
            date('d M Y H:i', strtotime($score['score_date']))
        ], ',', '"', '\\');
    }

    fclose($output);
    exit();

} catch (PDOException $e) {
    // Tangani kesalahan database
    header('Content-Type: text/html; charset=utf-8');
    die('Error: Gagal mengambil data dari database. ' . htmlspecialchars($e->getMessage()));
} catch (Exception $e) {
    // Tangani kesalahan lainnya
    header('Content-Type: text/html; charset=utf-8');
    die('Error: ' . htmlspecialchars($e->getMessage()));
}
?>