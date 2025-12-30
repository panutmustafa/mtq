<?php
require_once __DIR__ . '/auth.php'; // Pastikan file ini menangani session_start()
require_once __DIR__ . '/../config/database.php'; // Pastikan ini ada dan koneksi PDO sudah dibuat ($pdo)

// Pengecekan role spesifik
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php?error=unauthorized');
    exit();
}
$full_name = htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Admin');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Admin Dashboard' ?> | Sistem Penilaian Lomba</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f6f5;
            color: #2f3349;
            margin: 0;
            /* padding-top will be handled by content-wrapper or specific elements */
        }
        .navbar {
            background-color: #ffffff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 0.5rem 1rem;
            position: sticky; /* Make navbar sticky */
            top: 0;
            z-index: 1000;
        }
        .sidebar {
            width: 250px;
            background-color: #ffffff;
            height: 100vh;
            position: fixed;
            top: 0; /* Align sidebar to the very top */
            left: 0;
            box-shadow: 2px 0 5px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
            /* Removed padding-top here as it affects logo position */
        }
        .sidebar .sidebar-header {
             padding-top: 15px; /* Adjust padding for the logo/header within the sidebar */
             padding-bottom: 15px;
        }
        .sidebar .nav { /* Added this specific selector for nav links */
            padding-top: 15px; /* Add padding to the navigation list inside the sidebar below the logo */
        }
        .sidebar .nav-link {
            color: #6c7288;
            font-weight: 500;
            padding: 10px 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background-color: #f5f6f5;
            color: #2f3349;
        }
        .content-wrapper {
            margin-left: 250px;
            padding: 20px;
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .content-wrapper {
                margin-left: 0;
            }
            .navbar-toggler {
                display: block !important;
            }
        }
    </style>
</head>
<body>