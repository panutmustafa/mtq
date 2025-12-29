<?php
// --- FILE: admin/mark_notifications_read.php ---
require_once __DIR__ . '/../includes/auth.php'; // Pastikan ini memuat session dan cek login
require_once __DIR__ . '/../config/database.php'; // Pastikan ini ada dan koneksi PDO sudah dibuat ($pdo)

// Periksa apakah pengguna adalah admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php?error=unauthorized');
    exit();
}

try {
    // Tandai semua notifikasi yang belum dibaca menjadi sudah dibaca
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE is_read = FALSE");
    $stmt->execute();

    // Redirect kembali ke dashboard admin dengan pesan sukses
    header('Location: dashboard_admin.php?feedback_type=success&feedback_message=' . urlencode('Semua notifikasi berhasil ditandai sudah dibaca.'));
    exit();

} catch (PDOException $e) {
    // Redirect kembali dengan pesan error jika gagal
    header('Location: dashboard_admin.php?feedback_type=danger&feedback_message=' . urlencode('Gagal menandai notifikasi: ' . $e->getMessage()));
    exit();
}
?>