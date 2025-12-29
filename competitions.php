<?php
session_start();
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../config/database.php';

// Ambil data lomba yang tersedia
$competitions = $pdo->query("SELECT * FROM competitions WHERE status = 'open'")->fetchAll();

// Pesan untuk notifikasi
$message = '';

// Proses pendaftaran lomba
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_competition'])) {
    $competition_id = (int)$_POST['competition_id'];
    $user_id = (int)$_SESSION['user_id'];
    
    // Ambil data peserta dari form
    $full_name = trim($_POST['full_name']);
    $nisn = trim($_POST['nisn']);
    $birth_place = trim($_POST['birth_place']);
    $birth_date = $_POST['birth_date'];
    $class = trim($_POST['class']);
    $school = trim($_POST['school']);
    $category = trim($_POST['category']);

    try {
        // Cek pendaftaran ganda
        $stmt = $pdo->prepare("SELECT * FROM participants WHERE user_id = ? AND competition_id = ?");
        $stmt->execute([$user_id, $competition_id]);
        
        if ($stmt->rowCount() > 0) {
            $message = '<div class="alert alert-warning">Anda sudah terdaftar di lomba ini</div>';
        } else {
            // Simpan data ke tabel participants
            $insert = $pdo->prepare("INSERT INTO participants 
                (user_id, competition_id, full_name, nisn, birth_place, birth_date, class, school, category) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            if ($insert->execute([
                $user_id, 
                $competition_id,
                $full_name,
                $nisn,
                $birth_place,
                $birth_date,
                $class,
                $school,
                $category
            ])) {
                $message = '<div class="alert alert-success">Pendaftaran berhasil!</div>';
                header("Location: competitions.php");
                exit();
            }
        }
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Lomba</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        .competition-card {
            transition: all 0.3s ease;
        }
        .competition-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <?php include __DIR__.'/../includes/header.php'; ?>
    
    <div class="container py-4">
        <h2><i class="bi bi-trophy"></i> Daftar Lomba</h2>
        <?php if (!empty($message)) echo $message; ?>

        <div class="row row-cols-1 row-cols-md-3 g-4">
            <?php if (empty($competitions)): ?>
                <div class="alert alert-info">Tidak ada lomba yang tersedia saat ini.</div>
            <?php else: ?>
                <?php foreach ($competitions as $comp): ?>
                    <div class="col">
                        <div class="card competition-card h-100">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($comp['name']) ?></h5>
                                <p class="card-text text-muted small">
                                    <i class="bi bi-calendar-event"></i> 
                                    <?= date('d M Y', strtotime($comp['start_date'])) ?> - 
                                    <?= date('d M Y', strtotime($comp['end_date'])) ?>
                                </p>
                                <p class="card-text"><?= nl2br(htmlspecialchars($comp['description'])) ?></p>
                                <form method="POST">
                                    <input type="hidden" name="competition_id" value="<?= $comp['id'] ?>">
                                    <div class="mb-3">
                                        <label class="form-label">Nama Lengkap:</label>
                                        <input type="text" name="full_name" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">NISN:</label>
                                        <input type="text" name="nisn" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Tempat Lahir:</label>
                                        <input type="text" name="birth_place" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Tanggal Lahir:</label>
                                        <input type="date" name="birth_date" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Kelas:</label>
                                        <input type="text" name="class" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Asal Sekolah:</label>
                                        <input type="text" name="school" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Kategori Lomba:</label>
                                        <input type="text" name="category" class="form-control" required>
                                    </div>
                                    <button type="submit" name="register_competition" class="btn btn-primary">
                                        <i class="bi bi-send"></i> Daftar Sekarang
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php include __DIR__.'/../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
