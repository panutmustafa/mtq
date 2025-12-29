<?php
// File: /var/www/yourdomain.com/login.php
session_start();
require_once __DIR__.'/config/database.php';

// Redirect jika sudah login
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'] ?? '';
    $target = match($role) {
        'admin' => 'admin/dashboard_admin.php',
        'jury' => 'jury/dashboard_jury.php',
        'user' => 'user/dashboard_user.php',
        default => 'index.php'
    };
    
    if (!headers_sent()) {
        header("Location: $target");
        exit();
    }
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = "Harap isi username dan password";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION = [
                    'user_id' => $user['id'],
                    'username' => $user['username'],
                    'role' => $user['role'],
                    'full_name' => $user['full_name'],
                    'last_login' => time()
                ];

                $target = match($user['role']) {
                    'admin' => 'admin/dashboard_admin.php',
                    'jury' => 'jury/dashboard_jury.php',
                    'user' => 'user/dashboard_user.php',
                    default => 'index.php'
                };

                header("Location: $target");
                exit();
            } else {
                $error = "Username atau password salah";
            }
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            $error = "Sistem error. Silakan coba lagi nanti.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Portal MTQ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="assets/css/style.css">
        <style>
            body {
                font-family: 'Poppins', sans-serif;
            }
        </style>
        </head>
        <body class="bg-light">    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-5 col-xl-4">
                <div class="card login-card">
                    <div class="card-body">
                        <div class="login-header">
                            <img src="assets/images/logo.png" alt="Logo Portal MTQ" class="mb-3" style="max-width: 120px; height: auto;"> <h2>Selamat Datang</h2>
                            <p>Silakan login untuk melanjutkan ke Portal MTQ</p>
                        </div>
                        <?php if ($error): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>
                        <form method="post">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" id="username" name="username" placeholder="Masukkan username Anda" required autofocus autocomplete="username">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" placeholder="Masukkan password Anda" required autocomplete="current-password">
                                </div>
                            </div>
                            <div class="forgot-password-link">
                                <a href="#">Lupa Password?</a> </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-sign-in-alt me-2"></i>Login
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>