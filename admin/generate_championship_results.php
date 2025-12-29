<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
if ($_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

require_once __DIR__ . '/../config/database.php';

// Atur zona waktu ke WIB
date_default_timezone_set('Asia/Jakarta');

// Proses generate jika form submitted (misalnya, dari tombol "Generate Hasil" di reports.php)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_results'])) {
    try {
        // Hapus hasil championship lama untuk menghindari duplikasi
        $pdo->exec("DELETE FROM championships");

        // Query untuk hitung rata-rata skor per peserta per kompetisi
        $query = "
            SELECT 
                s.competition_id,
                c.name AS competition_name,
                s.participant_id,
                p.full_name AS participant_name,
                p.school AS school,
                AVG(s.score) AS average_score
            FROM scores s
            JOIN competitions c ON s.competition_id = c.id
            JOIN participants p ON s.participant_id = p.id
            GROUP BY s.competition_id, s.participant_id
            ORDER BY s.competition_id, average_score DESC
        ";

        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Kelompokkan per kompetisi dan assign peringkat
        $current_competition = null;
        $position = 1;
        $previous_score = null;

        foreach ($results as $result) {
            if ($result['competition_id'] !== $current_competition) {
                // Reset peringkat untuk kompetisi baru
                $current_competition = $result['competition_id'];
                $position = 1;
                $previous_score = $result['average_score'];
            } else {
                // Jika skor sama dengan sebelumnya, gunakan peringkat sama; jika lebih rendah, naikkan peringkat
                if ($result['average_score'] < $previous_score) {
                    $position++;
                }
                $previous_score = $result['average_score'];
            }

            // Insert ke championships
            $insert_query = "
                INSERT INTO championships (competition_id, competition_name, participant_id, participant_name, position, score, school)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ";
            $insert_stmt = $pdo->prepare($insert_query);
            $insert_stmt->execute([
                $result['competition_id'],
                $result['competition_name'],
                $result['participant_id'],
                $result['participant_name'],
                $position,
                $result['average_score'],
                $result['school']
            ]);
        }

        // Redirect dengan pesan sukses
        header('Location: reports.php?message=Hasil kejuaraan berhasil digenerate');
        exit();

    } catch (PDOException $e) {
        // Tangani error
        header('Location: reports.php?error=Error generating results: ' . urlencode($e->getMessage()));
        exit();
    }
} else {
    // Jika diakses langsung tanpa POST, redirect ke reports
    header('Location: reports.php');
    exit();
}