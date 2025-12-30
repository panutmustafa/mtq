<?php
$page_title = 'Detail Peserta';
require_once __DIR__.'/../includes/admin_header.php';

// Validasi competition_id
$competition_id = filter_input(INPUT_GET, 'competition_id', FILTER_VALIDATE_INT);
if ($competition_id <= 0) {
    header('Location: manage_competitions.php');
    exit();
}

// Dapatkan detail lomba
$stmt = $pdo->prepare("SELECT * FROM competitions WHERE id = ?");
$stmt->execute([$competition_id]);
$competition = $stmt->fetch();

if (!$competition) {
    header('Location: manage_competitions.php');
    exit();
}

// Dapatkan data peserta
$participants = $pdo->prepare("SELECT u.id, u.full_name, u.username, u.email, p.registration_date 
                             FROM participants p 
                             JOIN users u ON p.user_id = u.id 
                             WHERE p.competition_id = ?
                             ORDER BY p.registration_date DESC");
$participants->execute([$competition_id]);
$total_participants = $participants->rowCount();
?>
<?php include __DIR__ . '/../includes/admin_sidebar.php'; ?>
<div class="content-wrapper">
    <?php include __DIR__ . '/../includes/admin_content_header.php'; ?>
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2>Daftar Peserta</h2>
                <h4 class="text-muted"><?= htmlspecialchars($competition['name']) ?></h4>
                <p><span class="badge bg-primary">Total: <?= $total_participants ?> peserta</span></p>
            </div>
            <div>
                <a href="manage_competitions.php" class="btn btn-outline-secondary">Kembali</a>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <?php if ($total_participants <= 0): ?>
                    <div class="alert alert-info">Belum ada peserta yang mendaftar</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th></th>
                                    <th>Nama</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Tanggal Daftar</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($participants as $index => $row): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td>
                                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($row['full_name']) ?>&background=random" 
                                             class="avatar" 
                                             alt="Avatar">
                                    </td>
                                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                                    <td><?= htmlspecialchars($row['username']) ?></td>
                                    <td><?= htmlspecialchars($row['email']) ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($row['registration_date'])) ?></td>
                                    <td>
                                        <a href="view_participant.php?user_id=<?= $row['id'] ?>&competition_id=<?= $competition_id ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            Detail
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php include __DIR__.'/../includes/admin_footer.php'; ?>