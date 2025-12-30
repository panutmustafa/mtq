<?php $current_page = basename($_SERVER['PHP_SELF']); ?>
    <!-- Sidebar -->
    <div class="sidebar d-none d-md-block">
        <div class="sidebar-header text-center py-3">
            <img src="../assets/images/logo.png" alt="Logo" class="img-fluid" style="max-height: 100px;">
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= ($current_page === 'dashboard_user.php') ? 'active' : '' ?>" href="dashboard_user.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            </li>
          	<li class="nav-item">
                <a class="nav-link <?= ($current_page === 'register_competition.php') ? 'active' : '' ?>" href="register_competition.php"><i class="fas fa-pencil-alt"></i> Registrasi</a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?= ($current_page === 'registration_list.php') ? 'active' : '' ?>" href="registration_list.php"><i class="fas fa-list-alt"></i> Data Pendaftaran</a>
            </li>
		<li class="nav-item">
                <a class="nav-link <?= ($current_page === 'hasil_kejuaraan.php') ? 'active' : '' ?>" href="hasil_kejuaraan.php"><i class="fas fa-trophy"></i> Hasil kejuaraan</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#logoutConfirmModal"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </li>
        </ul>
    </div>
