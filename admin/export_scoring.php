<?php
session_start();
require_once __DIR__.'/../includes/auth.php';
requireRole('admin');

require_once __DIR__.'/../config/database.php';

// Query untuk mengambil data scoring
$scoring_query = "
    SELECT 
        c.name as competition_name,
        u.full_name as jury_name,
        p.full_name as participant_name,
        s.score,
        s.notes,
        s.created_at
    FROM scores s
    JOIN participants p ON s.participant_id = p.id
    JOIN competitions c ON s.competition_id = c.id
    JOIN users u ON s.jury_id = u.id
    ORDER BY c.name, u.full_name, p.full_name
";

$scoring_stmt = $pdo->prepare($scoring_query);
$scoring_stmt->execute();
$scores = $scoring_stmt->fetchAll();

// Set header untuk file Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="scoring_report.xls"');

// Tampilkan data dalam format tabel
echo "<table border='1'>";
echo "<tr>
        <th>Nama Lomba</th>
        <th>Nama Juri</th>
        <th>Nama Peserta</th>
        <th>Skor</th>
        <th>Catatan</th>
        <th>Tanggal Scoring</th>
      </tr>";

foreach ($scores as $score) {
    echo "<tr>
            <td>" . htmlspecialchars($score['competition_name']) . "</td>
            <td>" . htmlspecialchars($score['jury_name']) . "</td>
            <td>" . htmlspecialchars($score['participant_name']) . "</td>
            <td>" . htmlspecialchars($score['score']) . "</td>
            <td>" . nl2br(htmlspecialchars($score['notes'])) . "</td>
            <td>" . date('d M Y H:i', strtotime($score['created_at'])) . "</td>
          </tr>";
}

echo "</table>";
exit();
