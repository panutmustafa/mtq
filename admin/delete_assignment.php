<?php
session_start();
require_once __DIR__.'/../includes/auth.php';
if ($_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

require_once __DIR__.'/../config/database.php';

$assignment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($assignment_id > 0) {
    try {
        $stmt = $pdo->prepare("DELETE FROM jury_assignments WHERE id = ?");
        $stmt->execute([$assignment_id]);
        $_SESSION['success'] = "Penugasan berhasil dihapus";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Gagal menghapus penugasan: " . $e->getMessage();
    }
}

header("Location: assign_jury.php");
exit();
