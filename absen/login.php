<?php
session_start();
require 'config/db.php'; // Pastikan path ke db.php benar

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $passwordInput = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if (password_verify($passwordInput, $user['password'])) {
            $_SESSION['user'] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'full_name' => $user['full_name'], // akan dipakai di dashboard
                'role' => $user['role']
            ];
            $redirect = $user['role'] == 'admin' ? 'admin/dashboard.php' : 'user/dashboard.php';
            header("Location: $redirect");
            exit;
        } else {
            $error = "Password salah!";
        }
    } else {
        $error = "Username tidak ditemukan!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <title>Login Absensi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(to right, #6a11cb, #2575fc);
            height: 100vh;
            display: flex; /* Menggunakan flexbox untuk centering vertikal dan horizontal */
            justify-content: center;
            align-items: center;
        }
        .login-box {
            background: #fff;
            padding: 2rem; /* Padding yang lebih baik */
            border-radius: 1rem;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            /* Mengatur lebar responsif */
            width: 90%; /* Lebar 90% pada layar sangat kecil */
            max-width: 400px; /* Batasan lebar maksimum untuk desktop */
        }
        /* Penyesuaian untuk ukuran layar kecil */
        @media (max-width: 576px) {
            .login-box {
                padding: 1.5rem; /* Kurangi padding pada layar sangat kecil */
            }
        }
        .form-label {
            font-weight: 500; /* Sedikit lebih tebal dari default Bootstrap */
        }
        .form-control {
            border-radius: 0.5rem; /* Pembulatan input */
        }
        .btn-primary {
            border-radius: 0.5rem; /* Pembulatan tombol */
            font-weight: 600;
        }
        .text-center i {
            margin-right: 8px; /* Spasi antara ikon dan teks judul */
        }
    </style>
</head>
<body>

<div class="login-box"> <h3 class="text-center mb-4"><i class="fas fa-fingerprint"></i> Login Absensi</h3>
    <?php if (isset($error)) echo "<div class='alert alert-danger text-center'>$error</div>"; ?>
    <form method="POST">
        <div class="mb-3">
            <label for="username" class="form-label">Username</label>
            <input type="text" class="form-control" id="username" name="username" required autofocus>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>
        <button type="submit" class="btn btn-primary w-100 mt-3">Login</button>
    </form>
</div>

</body>
</html>