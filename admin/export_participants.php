<?php
session_start();
// Set high limits for large exports
ini_set('memory_limit', '512M'); // Increase memory limit
ini_set('max_execution_time', '300'); // Increase max execution time (5 minutes)
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../vendor/autoload.php'; // autoload PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Pengecekan role spesifik
if ($_SESSION['role'] !== 'admin') {
    header('Location: ../login.php?error=unauthorized');
    exit();
}

// Ambil filter dari URL
$filter_competition_id = isset($_GET['competition_id']) ? (int)$_GET['competition_id'] : 0;

// Query untuk mengambil data peserta
$sql = " 
    SELECT 
        p.full_name, 
        p.nisn, 
        p.birth_place, 
        p.birth_date, 
        p.class, 
        p.school, 
        c.name AS competition_name, 
        p.category 
    FROM participants p
    JOIN competitions c ON p.competition_id = c.id
";

$params = [];
if ($filter_competition_id > 0) {
    $sql .= " WHERE p.competition_id = :filter_comp_id";
    $params[':filter_comp_id'] = $filter_competition_id;
}
$sql .= " ORDER BY c.name ASC, p.full_name ASC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error fetching participant data for export: " . $e->getMessage());
    die("Gagal mengambil data peserta untuk diekspor: " . htmlspecialchars($e->getMessage()));
}

if (empty($participants)) {
    die("Tidak ada data peserta yang cocok dengan filter untuk diekspor.");
}

// Buat objek Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Data Peserta Lomba');

// Header kolom
$header = ['Nama Lomba', 'Nama Lengkap', 'NISN', 'Tempat Lahir', 'Tanggal Lahir', 'Kelas', 'Sekolah', 'Kategori'];
$sheet->fromArray($header, null, 'A1');

// Isi data
$row = 2;
foreach ($participants as $participant) {
    $sheet->setCellValue('A'.$row, $participant['competition_name']);
    $sheet->setCellValue('B'.$row, $participant['full_name']);
    $sheet->setCellValue('C'.$row, $participant['nisn']);
    $sheet->setCellValue('D'.$row, $participant['birth_place']);
    $sheet->setCellValue('E'.$row, $participant['birth_date']);
    $sheet->setCellValue('F'.$row, $participant['class']);
    $sheet->setCellValue('G'.$row, $participant['school']);
    $sheet->setCellValue('H'.$row, $participant['category']);
    $row++;
}

// Atur header download
ob_clean(); // Clean any accidental output before sending headers
$filename = 'data_peserta_lomba_' . date('Ymd_His') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');

// Simpan ke output
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;