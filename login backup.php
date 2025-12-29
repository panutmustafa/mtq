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
    <title>Login - Sistem Manajemen Lomba</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow">
                    <div class="card-body p-4">
                        <h2 class="text-center mb-4">Portal MTQ</h2>
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>
                        <form method="post">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" required autofocus>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Login</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
