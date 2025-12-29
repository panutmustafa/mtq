<?php
session_start();
require_once __DIR__.'/../includes/auth.php';
if ($_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

require_once __DIR__.'/../config/database.php';

$id = (int)$_GET['id'];
$stmt = $pdo->prepare("DELETE FROM championships WHERE id = ?");
$stmt->execute([$id]);

$_SESSION['success'] = "Data kejuaraan berhasil dihapus!";
header("Location: manage_championship_results.php");
exit();
?>
