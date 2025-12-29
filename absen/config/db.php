<?php
$host = "localhost";
$user = "presensi";
$pass = "moslem78";
$dbname = "presensi";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>
