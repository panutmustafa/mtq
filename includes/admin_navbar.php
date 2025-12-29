<?php $current_page = basename($_SERVER['PHP_SELF']); ?>
<nav class="navbar navbar-expand-lg navbar-dark px-3">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard_admin.php"><i class="fas fa-cogs me-2"></i> Admin Dashboard</a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page === 'dashboard_admin.php') ? 'active' : '' ?>" href="dashboard_admin.php"><i class="fas fa-tachometer-alt me-1"></i> Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page === 'manage_competitions.php') ? 'active' : '' ?>" href="manage_competitions.php"><i class="fas fa-trophy me-1"></i> Lomba</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page === 'manage_users.php') ? 'active' : '' ?>" href="manage_users.php"><i class="fas fa-users-cog me-1"></i> Pengguna</a>
                </li>
              	<li class="nav-item">
                    <a class="nav-link <?= ($current_page === 'manage_participants.php') ? 'active' : '' ?>" href="manage_participants.php">Kelola Peserta</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page === 'manage_championship_results.php') ? 'active' : '' ?>" href="manage_championship_results.php"><i class="fas fa-medal me-1"></i> Hasil Kejuaraan</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page === 'reports.php') ? 'active' : '' ?>" href="reports.php"><i class="fas fa-chart-line me-1"></i> Laporan</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page === 'assign_jury.php') ? 'active' : '' ?>" href="assign_jury.php"><i class="fas fa-gavel me-1"></i> Tugaskan Juri</a>
                </li>
              	<li class="nav-item">
                    <a class="nav-link <?= ($current_page === 'manage_announcements.php') ? 'active' : '' ?>" href="manage_announcements.php"><i class="fas fa-bullhorn me-1"></i> Pengumuman</a>
                </li>
                <li class="nav-item">
                    <span class="navbar-text me-lg-3 text-white py-2 py-lg-0">
                        <i class="fas fa-user-circle me-2"></i> Halo, <?= $full_name ?>
                    </span>
                </li>
                <li class="nav-item">
                    <a href="#" class="btn btn-outline-light rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#logoutConfirmModal"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
