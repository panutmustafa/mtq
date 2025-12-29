<?php
require_once '../tcpdf/tcpdf.php';
require_once '../config/db.php';

// Ambil data absensi
$sql = "SELECT a.*, u.username, u.full_name, u.asal_sekolah FROM absensi a 
        JOIN users u ON a.user_id = u.id
        ORDER BY a.tanggal DESC, a.waktu DESC";
$result = $conn->query($sql);

// Ambil filter dari URL
$filter_nama = $_GET['nama'] ?? '';
$filter_tgl_mulai = $_GET['tgl_mulai'] ?? '';
$filter_tgl_sampai = $_GET['tgl_sampai'] ?? '';

// Query dasar
$sql = "SELECT a.*, u.username, u.full_name, u.asal_sekolah FROM absensi a 
        JOIN users u ON a.user_id = u.id WHERE 1=1";

// Tambahkan filter ke query
if ($filter_nama !== '') {
    $sql .= " AND u.full_name LIKE '%" . $conn->real_escape_string($filter_nama) . "%'";
}
if ($filter_tgl_mulai !== '' && $filter_tgl_sampai !== '') {
    $sql .= " AND a.tanggal BETWEEN '" . $conn->real_escape_string($filter_tgl_mulai) . "' AND '" . $conn->real_escape_string($filter_tgl_sampai) . "'";
}

$sql .= " ORDER BY a.tanggal DESC, a.waktu DESC";
$result = $conn->query($sql);

// Buat PDF
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('Absensi Online');
$pdf->SetAuthor('Admin');
$pdf->SetTitle('Laporan Absensi');
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(TRUE, 10);
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 10); // Ubah angka 10 sesuai kebutuhan


$html = '<h2 style="text-align:center;">Laporan Data Absensi</h2>';
$html .= '<table border="1" cellpadding="4">
<tr style="background-color:#f2f2f2;">
    <th width="5%">No</th>
    <th width="30%">Nama Lengkap</th>
    <th width="25%">Asal Sekolah</th>
    <th width="15%">Tanggal</th>
    <th width="15%">Waktu</th>
    <th width="10%">Ket</th>
</tr>';

$no = 1;
while ($row = $result->fetch_assoc()) {
    $html .= '<tr>
        <td>' . $no++ . '</td>
        <td>' . htmlspecialchars($row['full_name']) . '</td>
        <td>' . htmlspecialchars($row['asal_sekolah']) . '</td>
        <td>' . $row['tanggal'] . '</td>
        <td>' . $row['waktu'] . '</td>
        <td>' . $row['keterangan'] . '</td>
    </tr>';
}

$html .= '</table>';

$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output('laporan_absensi.pdf', 'I');
