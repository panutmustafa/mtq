<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in to show appropriate navigation
$isLoggedIn = isset($_SESSION['user_id']);
$userRole = $isLoggedIn ? $_SESSION['role'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Competition Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --accent-color: #e74c3c;
        }
        
        .hero-section {
            position: relative;
            overflow: hidden;
            height: 100vh;
            display: flex;
            align-items: center;
            color: white;
        }
        
        .hero-video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: 0;
        }
        
        .hero-content {
            position: relative;
            z-index: 1;
            width: 100%;
            text-align: center;
        }
        
        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 0;
        }
        
        .feature-card {
            transition: all 0.3s ease;
            border: none;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            height: 100%;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .hero-section {
                height: 70vh;
            }
            .hero-content h1 {
                font-size: 2.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-trophy-fill"></i> CompetitionHub
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Features</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#competitions">Competitions</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#about">About</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <?php if ($isLoggedIn): ?>
                        <a href="<?= $userRole ?>/dashboard_<?= $userRole ?>.php" class="btn btn-outline-light me-2">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                        <a href="logout.php" class="btn btn-danger">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-primary">
                            <i class="bi bi-box-arrow-in-right"></i> Login
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section with Video Background -->
    <section class="hero-section">
        <video class="hero-video" autoplay muted loop>
            <source src="https://cdn.pixabay.com/video/2022/10/08/134046-760489590_tiny.mp4" type="video/mp4">
            Your browser does not support the video tag.
        </video>
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h1 class="display-4 fw-bold mb-4">Musabaqah Tilawatil Qur'an</h1>
            <h3 class="lead mb-5">Pelajar Umum Sekolah Dasar Kapanewon Berbah</h3>
            <div class="d-flex justify-content-center gap-3">
                <a href="#competitions" class="btn btn-primary btn-lg px-4">
                    <i class="bi bi-search"></i> Browse Competitions
                </a>
                <?php if (!$isLoggedIn): ?>
                    <a href="register.php" class="btn btn-outline-light btn-lg px-4">
                        <i class="bi bi-person-plus"></i> Register
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold">Why Choose Our Platform</h2>
                <p class="text-muted">Discover the powerful features that make competition management seamless</p>
            </div>
            
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card card p-4">
                        <div class="text-center">
                            <div class="feature-icon">
                                <i class="bi bi-people-fill"></i>
                            </div>
                            <h3>User Management</h3>
                            <p>Easily manage participants, judges, and administrators with our intuitive role-based system.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="feature-card card p-4">
                        <div class="text-center">
                            <div class="feature-icon">
                                <i class="bi bi-trophy-fill"></i>
                            </div>
                            <h3>Competition Control</h3>
                            <p>Create and manage competitions with customizable settings and automated workflows.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="feature-card card p-4">
                        <div class="text-center">
                            <div class="feature-icon">
                                <i class="bi bi-bar-chart-line-fill"></i>
                            </div>
                            <h3>Real-time Analytics</h3>
                            <p>Get insights with comprehensive reports and data visualization tools.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Current Competitions -->
    <section id="competitions" class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold">Current Competitions</h2>
                <p class="text-muted">Join these exciting competitions today</p>
            </div>
            
            <div class="row g-4">
                <?php
                // Fetch active competitions
                $stmt = $pdo->query("SELECT * FROM competitions WHERE status = 'open' ORDER BY start_date DESC LIMIT 3");
                $competitions = $stmt->fetchAll();
                
                if (empty($competitions)) {
                    echo '<div class="col-12 text-center"><p>No active competitions at the moment. Check back soon!</p></div>';
                } else {
                    foreach ($competitions as $comp) {
                        echo '
                        <div class="col-md-4">
                            <div class="card h-100">
                                <img src="https://placehold.co/600x400" class="card-img-top" alt="'.htmlspecialchars($comp['name']).'">
                                <div class="card-body">
                                    <h5 class="card-title">'.htmlspecialchars($comp['name']).'</h5>
                                    <p class="card-text">'.nl2br(htmlspecialchars(substr($comp['description'], 0, 150))).'...</p>
                                    <p class="text-muted small">
                                        <i class="bi bi-calendar-event"></i> '.date('M d, Y', strtotime($comp['start_date'])).' - '.date('M d, Y', strtotime($comp['end_date'])).'
                                    </p>
                                </div>
                                <div class="card-footer bg-white">
                                    <a href="'.($isLoggedIn ? 'user/competitions.php' : 'login.php').'" class="btn btn-primary w-100">
                                        '.($isLoggedIn ? 'View Details' : 'Login to Join').'
                                    </a>
                                </div>
                            </div>
                        </div>';
                    }
                }
                ?>
            </div>
            
            <div class="text-center mt-4">
                <a href="competitions.php" class="btn btn-outline-primary">View All Competitions</a>
            </div>
        </div>
    </section>

    <!-- Testimonials -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold">What Our Users Say</h2>
                <p class="text-muted">Hear from participants and organizers</p>
            </div>
            
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="testimonial-card card p-4 h-100">
                        <div class="d-flex mb-3">
                            <img src="https://placehold.co/100?text=A" class="rounded-circle me-3" style="width: 60px; height: 60px; object-fit: cover;">
                            <div>
                                <h5 class="mb-0">John Doe</h5>
                                <p class="text-muted mb-0">Competition Participant</p>
                            </div>
                        </div>
                        <p class="card-text">"The platform made it so easy to register and participate in competitions. The interface is intuitive and the process is seamless!"</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="testimonial-card card p-4 h-100">
                        <div class="d-flex mb-3">
                            <img src="https://placehold.co/100?text=B" class="rounded-circle me-3" style="width: 60px; height: 60px; object-fit: cover;">
                            <div>
                                <h5 class="mb-0">Jane Smith</h5>
                                <p class="text-muted mb-0">Event Organizer</p>
                            </div>
                        </div>
                        <p class="card-text">"Managing our competition has never been easier. The dashboard provides all the tools we need to handle participants, judges, and results efficiently."</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="testimonial-card card p-4 h-100">
                        <div class="d-flex mb-3">
                            <img src="https://placehold.co/100?text=C" class="rounded-circle me-3" style="width: 60px; height: 60px; object-fit: cover;">
                            <div>
                                <h5 class="mb-0">Michael Johnson</h5>
                                <p class="text-muted mb-0">Judge</p>
                            </div>
                        </div>
                        <p class="card-text">"The scoring interface is well-designed and saves us so much time. We can focus on evaluating the participants rather than paperwork."</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <h2 class="display-5 fw-bold mb-4">About CompetitionHub</h2>
                    <p>CompetitionHub is a comprehensive platform designed to streamline the entire competition management process. Our mission is to provide organizers with powerful tools to create, manage, and analyze competitions while offering participants a seamless experience from registration to results.</p>
                    <p>Founded in 2023, we've helped hundreds of organizations run successful competitions across various domains, from academic contests to creative challenges.</p>
                    <a href="about.php" class="btn btn-primary">Learn More</a>
                </div>
                <div class="col-lg-6">
                    <img src="https://placehold.co/600x400" alt="Team working on competition management" class="img-fluid rounded">
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section py-5 text-center">
        <div class="container">
            <h2 class="display-5 fw-bold mb-4">Ready to Get Started?</h2>
            <p class="lead mb-5">Join thousands of organizers and participants using our platform</p>
            <div class="d-flex justify-content-center gap-3">
                <?php if ($isLoggedIn): ?>
                    <a href="<?= $userRole ?>/dashboard_<?= $userRole ?>.php" class="btn btn-light btn-lg px-4">
                        <i class="bi bi-speedometer2"></i> Go to Dashboard
                    </a>
                <?php else: ?>
                    <a href="register.php" class="btn btn-light btn-lg px-4">
                        <i class="bi bi-person-plus"></i> Create Account
                    </a>
                    <a href="login.php" class="btn btn-outline-light btn-lg px-4">
                        <i class="bi bi-box-arrow-in-right"></i> Login
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4 mb-md-0">
                    <h5>CompetitionHub</h5>
                    <p class="text-muted">The complete solution for competition management and participation.</p>
                </div>
                <div class="col-md-2 mb-4 mb-md-0">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-muted">Home</a></li>
                        <li><a href="competitions.php" class="text-muted">Competitions</a></li>
                        <li><a href="about.php" class="text-muted">About</a></li>
                        <li><a href="contact.php" class="text-muted">Contact</a></li>
                    </ul>
                </div>
                <div class="col-md-4 mb-4 mb-md-0">
                    <h5>Contact Us</h5>
                    <ul class="list-unstyled text-muted">
                        <li><i class="bi bi-envelope"></i> info@competitionhub.com</li>
                        <li><i class="bi bi-telephone"></i> +1 (555) 123-4567</li>
                    </ul>
                </div>
                <div class="col-md-2">
                    <h5>Follow Us</h5>
                    <div class="d-flex gap-3">
                        <a href="#" class="text-muted"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="text-muted"><i class="bi bi-twitter"></i></a>
                        <a href="#" class="text-muted"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="text-muted"><i class="bi bi-linkedin"></i></a>
                    </div>
                </div>
            </div>
            <hr class="my-4">
            <div class="text-center text-muted">
                <small>© 2023 CompetitionHub. All rights reserved.</small>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
