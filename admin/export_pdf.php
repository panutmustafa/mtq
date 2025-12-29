<?php
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../libs/dompdf/autoload.inc.php';

use Dompdf\Dompdf;

// Ambil data
$results = $pdo->query("SELECT * FROM championships")->fetchAll();

if (empty($results)) {
    die("Tidak ada data untuk diekspor.");
}

// Buat HTML untuk PDF
$html = '<h1 style="text-align:center">Laporan Hasil Kejuaraan</h1>';
$html .= '<table border="1" cellpadding="5" style="width:100%;border-collapse:collapse">';
$html .= '<thead><tr>
            <th>Nama Lomba</th>
            <th>Nama Peserta</th>
            <th>Posisi</th>
            <th>Skor</th>
            <th>Asal Sekolah</th>
          </tr></thead><tbody>';

foreach ($results as $data) {
    $html .= '<tr>';
    $html .= '<td>'.htmlspecialchars($data['competition_name']).'</td>';
    $html .= '<td>'.htmlspecialchars($data['participant_name']).'</td>';
    $html .= '<td>'.htmlspecialchars($data['position']).'</td>';
    $html .= '<td>'.htmlspecialchars($data['score']).'</td>';
    $html .= '<td>'.htmlspecialchars($data['school']).'</td>';
    $html .= '</tr>';
}

$html .= '</tbody></table>';

// Buat PDF
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

// Output PDF
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="hasil_kejuaraan.pdf"');
echo $dompdf->output();
exit;
?>
