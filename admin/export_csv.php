<?php
require_once __DIR__.'/../config/database.php'; // Pastikan ini ada

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="hasil.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['Nama Peserta', 'Posisi', 'Skor', 'Sekolah']);

// Ambil data dari database
$results = $pdo->query("SELECT participant_name, position, score, school FROM championships");
while ($row = $results->fetch(PDO::FETCH_ASSOC)) {
    // Ubah posisi menjadi string
    $row['position'] = $row['position'] == 1 ? 'Juara 1' : ($row['position'] == 2 ? 'Juara 2' : ($row['position'] == 3 ? 'Juara 3' : 'Harapan ' . ($row['position'] - 3)));
    fputcsv($output, $row);
}
fclose($output);
exit; // Pastikan untuk menghentikan eksekusi setelah output
?>
