<?php $current_page = basename($_SERVER['PHP_SELF']); ?>
<div class="sidebar d-none d-md-block">
    <div class="sidebar-header text-center py-3">
        <img src="../assets/images/logo.png" alt="Logo" class="img-fluid" style="max-height: 100px;">
    </div>
    <ul class="nav flex-column">
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
            <a class="nav-link <?= ($current_page === 'manage_participants.php') ? 'active' : '' ?>" href="manage_participants.php"><i class="fas fa-users me-1"></i> Kelola Peserta</a>
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
            <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#logoutConfirmModal"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
        </li>
    </ul>
</div>
