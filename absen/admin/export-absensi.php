<?php
// Set high limits for large exports
ini_set('memory_limit', '512M'); // Increase memory limit
ini_set('max_execution_time', '300'); // Increase max execution time (5 minutes)
error_reporting(E_ALL);
ini_set('display_errors', 1);

require '../config/db.php';
require '../vendor/autoload.php'; // autoload PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory; // Added for more robust writing

// Ambil filter dari URL
$user_id = isset($_GET['user_id']) ? filter_var($_GET['user_id'], FILTER_VALIDATE_INT) : null;
$tanggal = isset($_GET['tanggal']) ? filter_var($_GET['tanggal'], FILTER_SANITIZE_STRING) : null;

// Query absensi dengan prepared statement untuk mencegah SQL Injection
$sql = "SELECT a.*, u.username, u.full_name, u.asal_sekolah 
        FROM absensi a 
        JOIN users u ON a.user_id = u.id 
        WHERE 1=1";

$params = [];
$types = '';

if ($user_id !== null) {
    $sql .= " AND a.user_id = ?";
    $params[] = $user_id;
    $types .= 'i'; // 'i' for integer
}
if ($tanggal !== null && $tanggal !== false) { // false if filter_var fails
    $sql .= " AND DATE(a.tanggal) = ?";
    $params[] = $tanggal;
    $types .= 's'; // 's' for string
}

$sql .= " ORDER BY a.tanggal DESC, a.waktu DESC";

try {
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    if (!empty($params)) {
        // Using call_user_func_array for bind_param to handle variable number of arguments
        $ref_params = [];
        foreach($params as $key => $value) {
            $ref_params[$key] = &$params[$key];
        }
        call_user_func_array([$stmt, 'bind_param'], array_merge([$types], $ref_params));
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        die("Tidak ada data absensi yang cocok dengan filter untuk diekspor.");
    }

} catch (Exception $e) {
    error_log("Database error fetching absensi data: " . $e->getMessage());
    die("Gagal mengambil data absensi: " . htmlspecialchars($e->getMessage()));
}


// Siapkan spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Data Absensi');

// Header kolom
$header = ['No', 'Nama Lengkap', 'Username', 'Asal Sekolah', 'Tanggal', 'Waktu', 'Keterangan', 'Latitude', 'Longitude'];
$sheet->fromArray($header, null, 'A1');

// Isi data
$no = 1;
$rowIndex = 2;
while ($row = $result->fetch_assoc()) {
    $sheet->fromArray([
        $no++,
        $row['full_name'],
        $row['username'],
        $row['asal_sekolah'],
        $row['tanggal'],
        date('H:i:s', strtotime($row['waktu'])),
        $row['keterangan'],
        $row['latitude'],
        $row['longitude']
    ], null, 'A' . $rowIndex++);
}

// Atur header download
ob_clean(); // Clean any accidental output before sending headers
$filename = 'data_absensi_' . date('Ymd_His') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');

// Simpan ke output
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;