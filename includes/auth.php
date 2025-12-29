<?php
// File: /var/www/yourdomain.com/includes/auth.php
session_start();

function isLoggedIn() {
    return !empty($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header('Location: ../login.php');
        exit();
    }
}

function requireRole($role) {
    requireLogin();
    if ($_SESSION['role'] !== $role) {
        http_response_code(403);
        include __DIR__.'/../403.html';
        exit();
    }
}

// Auto-logout setelah 30 menit inaktivitas
$inactive = 1800; // 30 menit dalam detik
if (isset($_SESSION['last_active']) && (time() - $_SESSION['last_active'] > $inactive)) {
    session_unset();
    session_destroy();
    header('Location: ../login.php?timeout=1');
    exit();
}
$_SESSION['last_active'] = time();
?>
