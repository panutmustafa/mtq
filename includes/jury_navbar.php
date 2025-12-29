<?php $current_page = basename($_SERVER['PHP_SELF']); ?>
<nav class="navbar navbar-expand-lg navbar-dark px-3">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard_jury.php"><i class="fas fa-gavel me-2"></i> Jury Dashboard</a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page === 'dashboard_jury.php') ? 'active' : '' ?>" href="dashboard_jury.php"><i class="fas fa-tachometer-alt me-1"></i> Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page === 'assignments.php') ? 'active' : '' ?>" href="assignments.php"><i class="fas fa-clipboard-list me-1"></i> Penugasan</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page === 'scoring.php') ? 'active' : '' ?>" href="scoring.php"><i class="fas fa-edit me-1"></i> Penilaian</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page === 'view_scores.php') ? 'active' : '' ?>" href="view_scores.php"><i class="fas fa-chart-bar me-1"></i> Lihat Skor</a>
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
