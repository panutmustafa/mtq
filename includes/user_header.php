<?php
// Pastikan session dan auth berjalan
session_start();
require_once __DIR__ . '/../includes/auth.php'; // Pastikan path ini benar
if ($_SESSION['role'] !== 'user') {
    header('Location: ../login.php');
    exit();
}

// Koneksi database
require_once __DIR__ . '/../config/database.php'; // Pastikan path ini benar

// Atur zona waktu ke WIB
date_default_timezone_set('Asia/Jakarta');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'User Dashboard' ?> | Sistem Penilaian Lomba</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f6f5;
            color: #2f3349;
            margin: 0;
            padding-top: 0; /* Remove body padding, use content-wrapper margin-top */
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
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            overflow: hidden;
            transition: transform 0.2s ease;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .card-header {
            background-color: #2f3349;
            color: #ffffff;
            font-weight: 600;
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .card-body {
            padding: 1.5rem;
        }
        .alert {
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .list-group-item {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            margin-bottom: 10px;
            padding: 15px;
            transition: all 0.2s ease;
        }
        .list-group-item:hover {
            background-color: #f5f6f5;
            transform: translateY(-2px);
        }
        .table {
            border-radius: 8px;
            overflow: hidden;
        }
        .table th, .table td {
            padding: 1rem;
            vertical-align: middle;
        }
        .table thead {
            background-color: #f5f6f5;
        }
        .btn {
            border-radius: 8px;
            font-weight: 500;
        }
        .form-control, .form-select {
            border-radius: 8px;
            box-shadow: none;
            border: 1px solid #e5e7eb;
        }
        .modal-content {
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .modal-header {
            background-color: #2f3349;
            color: #ffffff;
        }
    </style>
</head>
<body>
