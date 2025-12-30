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

// Proses generate jika form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_results'])) {
    try {
        // Hapus hasil championship lama untuk menghindari duplikasi
        $pdo->exec("DELETE FROM championships");

        // Query untuk hitung jumlah skor per peserta per kompetisi
        $query = "
            SELECT 
                s.competition_id,
                c.name AS competition_name,
                s.participant_id,
                p.full_name AS participant_name,
                p.school AS school,
                SUM(s.score) AS total_score
            FROM scores s
            JOIN competitions c ON s.competition_id = c.id
            JOIN participants p ON s.participant_id = p.id
            GROUP BY s.competition_id, s.participant_id
            ORDER BY s.competition_id, total_score DESC
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
                $previous_score = $result['total_score'];
            } else {
                // Jika skor sama dengan sebelumnya, gunakan peringkat sama; jika lebih rendah, naikkan peringkat
                if ($result['total_score'] < $previous_score) {
                    $position++;
                }
                $previous_score = $result['total_score'];
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
                $result['total_score'],
                $result['school']
            ]);
        }

        // Redirect dengan pesan sukses
        header('Location: manage_championship_results.php?feedback_type=success&feedback_message=' . urlencode('Hasil kejuaraan berdasarkan jumlah skor berhasil digenerate'));
        exit();

    } catch (PDOException $e) {
        // Tangani error
        header('Location: manage_championship_results.php?feedback_type=danger&feedback_message=' . urlencode('Error generating results: ' . $e->getMessage()));
        exit();
    }
} else {
    // Jika diakses langsung tanpa POST, redirect ke manage_championship_results
    header('Location: manage_championship_results.php');
    exit();
}
