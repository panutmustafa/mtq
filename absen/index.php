<?php
// index.php
session_start();

// Jika sudah login, arahkan langsung ke dashboard sesuai role
if (isset($_SESSION['user'])) {
    $role = $_SESSION['user']['role'];
    if ($role === 'admin') {
        header("Location: admin/dashboard.php");
        exit;
    } elseif ($role === 'user') {
        header("Location: user/dashboard.php");
        exit;
    }
}

// Jika belum login, redirect ke halaman login
header("Location: login.php");
exit;
