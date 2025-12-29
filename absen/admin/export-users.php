<?php
require '../config/db.php';
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Ambil data pengguna dari database (termasuk password)
$sql = "SELECT id, full_name, username, password, asal_sekolah, role FROM users ORDER BY id ASC";
$result = $conn->query($sql);

// Buat spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Data Pengguna');

// Header kolom
$sheet->setCellValue('A1', 'No');
$sheet->setCellValue('B1', 'Nama Lengkap');
$sheet->setCellValue('C1', 'Username');
$sheet->setCellValue('D1', 'Password (Hash)');
$sheet->setCellValue('E1', 'Asal Sekolah');
$sheet->setCellValue('F1', 'Role');

// Isi data
$no = 1;
$rowIndex = 2;
while ($row = $result->fetch_assoc()) {
    $sheet->setCellValue('A' . $rowIndex, $no++);
    $sheet->setCellValue('B' . $rowIndex, $row['full_name']);
    $sheet->setCellValue('C' . $rowIndex, $row['username']);
    $sheet->setCellValue('D' . $rowIndex, $row['password']); // dalam format hash
    $sheet->setCellValue('E' . $rowIndex, $row['asal_sekolah']);
    $sheet->setCellValue('F' . $rowIndex, ucfirst($row['role']));
    $rowIndex++;
}

// Output file Excel
$filename = 'data_pengguna_' . date('Ymd_His') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
