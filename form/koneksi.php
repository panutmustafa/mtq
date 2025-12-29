<?php
date_default_timezone_set('Asia/Jakarta');
// =========================================================================
// KONFIGURASI DATABASE
// =========================================================================
// Harap ganti dengan kredensial database Anda
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'formulir');
define('DB_PASSWORD', 'moslem78'); // Ganti dengan password Anda
define('DB_NAME', 'formulir'); // Pastikan nama database sudah dibuat

// Membuat koneksi menggunakan MySQLi Object Oriented
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Memeriksa koneksi
if ($conn->connect_error) {
    // Lebih aman jika di production, jangan tampilkan detail error koneksi database
    // die("Koneksi gagal: " . $conn->connect_error);
    die("Koneksi ke database gagal. Mohon coba lagi nanti.");
}
// Koneksi berhasil
?>