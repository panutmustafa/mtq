<?php
// --- FILE: admin/get_notifications.php ---
require_once __DIR__ . '/../includes/auth.php'; // Pastikan ini memuat session dan cek login
require_once __DIR__ . '/../config/database.php'; // Pastikan ini ada dan koneksi PDO sudah dibuat ($pdo)

header('Content-Type: application/json'); // Penting untuk memberitahu browser bahwa ini adalah JSON

// Periksa apakah pengguna adalah admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['error' => 'Unauthorized access.']);
    exit();
}

$notifications = [];
try {
    // Ambil 5 notifikasi terbaru yang belum dibaca, atau sesuaikan jumlahnya
    // Order by id DESC atau created_at DESC untuk yang terbaru
    $stmt = $pdo->prepare("SELECT message, type, created_at FROM notifications WHERE is_read = FALSE ORDER BY created_at DESC LIMIT 5");
    $stmt->execute();
    $raw_notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($raw_notifications as $notif) {
        // Format waktu agar lebih mudah dibaca di frontend
        $notif['created_at_formatted'] = date('d M Y H:i', strtotime($notif['created_at']));
        $notifications[] = $notif;
    }

    echo json_encode(['notifications' => $notifications]);

} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    // Dalam produksi, hindari menampilkan pesan error database secara langsung
    // error_log("Database error in get_notifications.php: " . $e->getMessage());
}
?>