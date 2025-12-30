<?php
session_start();
require_once 'config/database.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Initialize variables
$errors = [];
$username = $full_name = $role = '';

// Process registration form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate inputs
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];
    $full_name = trim($_POST['full_name']);
    $role = $_POST['role'];

    // Validation rules
    if (empty($username)) {
        $errors['username'] = 'Username is required';
    } elseif (strlen($username) < 4) {
        $errors['username'] = 'Username must be at least 4 characters';
    }

    if (empty($password)) {
        $errors['password'] = 'Password is required';
    } elseif (strlen($password) < 6) {
        $errors['password'] = 'Password must be at least 6 characters';
    }

    if ($password !== $password_confirm) {
        $errors['password_confirm'] = 'Passwords do not match';
    }

    if (empty($full_name)) {
        $errors['full_name'] = 'Full name is required';
    }

    if (empty($role) || !in_array($role, ['jury', 'user'])) {
        $errors['role'] = 'Invalid role selected';
    }

    // Check if username exists
    if (empty($errors['username'])) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->rowCount() > 0) {
            $errors['username'] = 'Username already taken';
        }
    }

    // If no errors, register user
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $hashed_password, $full_name, $role]);
            
            $_SESSION['registration_success'] = true;
            header('Location: login.php');
            exit();
        } catch (PDOException $e) {
            $errors['database'] = 'Registration failed: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Competition Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }
        .register-container {
            max-width: 500px;
            margin: 0 auto;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            margin-top: 5rem;
            margin-bottom: 5rem;
        }
        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .register-header h2 {
            color: #2c3e50;
        }
        .form-control:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 0.25rem rgba(52, 152, 219, 0.25);
        }
        .btn-register {
            background-color: #3498db;
            border: none;
            padding: 0.5rem 2rem;
        }
        .btn-register:hover {
            background-color: #2980b9;
        }
        .login-link {
            color: #3498db;
            text-decoration: none;
        }
        .login-link:hover {
            text-decoration: underline;
        }
        .error-message {
            color: #e74c3c;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="register-container">
            <div class="register-header">
                <h2><i class="bi bi-person-plus"></i> Create Your Account</h2>
                <p class="text-muted">Join our competition management platform</p>
            </div>

            <?php if (!empty($errors['database'])): ?>
                <div class="alert alert-danger"><?= $errors['database'] ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control <?= isset($errors['username']) ? 'is-invalid' : '' ?>" 
                           id="username" name="username" value="<?= htmlspecialchars($username) ?>" required>
                    <?php if (isset($errors['username'])): ?>
                        <div class="error-message"><?= $errors['username'] ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" 
                           id="password" name="password" required>
                    <?php if (isset($errors['password'])): ?>
                        <div class="error-message"><?= $errors['password'] ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="password_confirm" class="form-label">Confirm Password</label>
                    <input type="password" class="form-control <?= isset($errors['password_confirm']) ? 'is-invalid' : '' ?>" 
                           id="password_confirm" name="password_confirm" required>
                    <?php if (isset($errors['password_confirm'])): ?>
                        <div class="error-message"><?= $errors['password_confirm'] ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="full_name" class="form-label">Full Name</label>
                    <input type="text" class="form-control <?= isset($errors['full_name']) ? 'is-invalid' : '' ?>" 
                           id="full_name" name="full_name" value="<?= htmlspecialchars($full_name) ?>" required>
                    <?php if (isset($errors['full_name'])): ?>
                        <div class="error-message"><?= $errors['full_name'] ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="role" class="form-label">Register As</label>
                    <select class="form-select <?= isset($errors['role']) ? 'is-invalid' : '' ?>" 
                            id="role" name="role" required>
                        <option value="">Select your role</option>
                        <option value="user" <?= $role === 'user' ? 'selected' : '' ?>>Participant</option>
                                                    <option value="jury" <?= $role === 'jury' ? 'selected' : '' ?>>Jury Member</option>
                                                </select>                    <?php if (isset($errors['role'])): ?>
                        <div class="error-message"><?= $errors['role'] ?></div>
                    <?php endif; ?>
                    <small class="text-muted">Note: Admin accounts require additional verification</small>
                </div>

                <div class="d-grid mb-3">
                    <button type="submit" class="btn btn-primary btn-register">
                        <i class="bi bi-person-plus"></i> Register
                    </button>
                </div>

                <div class="text-center">
                    <p>Already have an account? <a href="login.php" class="login-link">Login here</a></p>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
