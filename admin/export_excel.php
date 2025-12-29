<?php
// Set high limits for large exports
ini_set('memory_limit', '512M'); // Increase memory limit
ini_set('max_execution_time', '300'); // Increase max execution time (5 minutes)
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../libs/PHPExcel/Classes/PHPExcel.php';

// Definisikan pemetaan dari ID integer ke string deskriptif untuk tampilan.
$positionDisplayMap = [
    1 => 'Juara 1',
    2 => 'Juara 2',
    3 => 'Juara 3',
    4 => 'Juara Harapan 1',
    5 => 'Juara Harapan 2',
    6 => 'Harapan 3',
];

// Ambil parameter filter dari URL (jika ada)
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

// Ambil data dengan filter
try {
    $sql = "SELECT * FROM championships" . $where_sql . " ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $param_name => $param_value) {
        $stmt->bindValue($param_name, $param_value, is_int($param_value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->execute();
    $results = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Gagal mengambil data untuk diekspor: " . htmlspecialchars($e->getMessage()));
}

if (empty($results)) {
    die("Tidak ada data yang cocok dengan filter untuk diekspor.");
}

// Buat objek Excel
$objPHPExcel = new PHPExcel();
$sheet = $objPHPExcel->getActiveSheet();

// Set header
$sheet->setCellValue('A1', 'Nama Lomba');
$sheet->setCellValue('B1', 'Nama Peserta');
$sheet->setCellValue('C1', 'Posisi');
$sheet->setCellValue('D1', 'Skor');
$sheet->setCellValue('E1', 'Asal Sekolah');

// Isi data
$row = 2;
foreach ($results as $data) {
    $position_text = $positionDisplayMap[(int)$data['position']] ?? 'Lainnya'; // Map integer to string
    $sheet->setCellValue('A'.$row, $data['competition_name']);
    $sheet->setCellValue('B'.$row, $data['participant_name']);
    $sheet->setCellValue('C'.$row, $position_text); // Use mapped string for position
    $sheet->setCellValue('D'.$row, $data['score']);
    $sheet->setCellValue('E'.$row, $data['school']);
    $row++;
}

// Download file
ob_clean(); // Clean any accidental output before sending headers
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="hasil_kejuaraan.xls"');
header('Cache-Control: max-age=0');

$writer = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
$writer->save('php://output');
exit;
?>