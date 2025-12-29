<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Redirect berdasarkan role
switch ($_SESSION['role']) {
    case 'admin':
        header('Location: admin/dashboard_admin.php');
        break;
    case 'jury':
        header('Location: jury/dashboard_jury.php');
        break;
    case 'user':
        header('Location: user/dashboard_user.php');
        break;
    default:
        header('Location: login.php');
        exit();
}
exit();
?>
