<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us | ServiGo</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .about-hero { background: #007bff; color: #fff; padding: 4rem 0 2rem 0; }
        .about-section { background: #fff; border-radius: 10px; box-shadow: 0 2px 12px rgba(0,0,0,0.07); padding: 2rem; margin-bottom: 2rem; }
        .about-icon { font-size: 2.5rem; color: #007bff; }
        .footer { background: #343a40; color: #fff; }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header navbar-servigo shadow-sm mb-4">
        <nav class="navbar navbar-expand-lg navbar-light bg-white">
            <div class="container">
                <a class="navbar-brand text-primary-servigo fw-bold" href="index.php"><i class="fas fa-tools me-2"></i>ServiGo</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="mainNavbar">
                    <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                        <li class="nav-item"><a href="index.php" class="nav-link">Home</a></li>
                        <li class="nav-item"><a href="services.php" class="nav-link">Services</a></li>
                        <li class="nav-item"><a href="providers.php" class="nav-link">Service Providers</a></li>
                        <li class="nav-item"><a href="about.php" class="nav-link active">About</a></li>
                        <li class="nav-item"><a href="contact.php" class="nav-link">Contact</a></li>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <li class="nav-item"><a href="dashboard.php" class="nav-link">Dashboard</a></li>
                        <?php else: ?>
                            <li class="nav-item"><a href="login.php" class="nav-link">Login</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
    <!-- Hero Section -->
    <section class="about-hero text-center">
        <div class="container">
            <h1 class="display-4 fw-bold">About ServiGo</h1>
            <p class="lead">Connecting Cameroon with trusted local service providers.</p>
        </div>
    </section>
    <!-- About Content -->
    <div class="container my-5">
        <div class="about-section mb-4">
            <h2 class="mb-3"><i class="fas fa-bullseye about-icon me-2"></i>Our Mission</h2>
            <p>ServiGo aims to empower communities in Cameroon by making it easy to find, book, and trust local service providers. We bridge the gap between skilled professionals and customers, ensuring quality, reliability, and convenience for all.</p>
        </div>
        <div class="about-section mb-4">
            <h2 class="mb-3"><i class="fas fa-eye about-icon me-2"></i>Our Vision</h2>
            <p>To be Cameroon’s most trusted platform for local services, fostering economic growth and community development through technology and transparency.</p>
        </div>
        <div class="about-section mb-4">
            <h2 class="mb-3"><i class="fas fa-star about-icon me-2"></i>Key Features</h2>
            <ul class="list-group list-group-flush">
                <li class="list-group-item">✔️ Browse and book a wide range of local services</li>
                <li class="list-group-item">✔️ Verified and rated service providers</li>
                <li class="list-group-item">✔️ Secure messaging and notifications</li>
                <li class="list-group-item">✔️ Easy service request and booking process</li>
                <li class="list-group-item">✔️ Admin dashboard for platform management</li>
                <li class="list-group-item">✔️ Professional, mobile-friendly design</li>
            </ul>
        </div>
        <div class="about-section mb-4">
            <h2 class="mb-3"><i class="fas fa-users about-icon me-2"></i>Our Team</h2>
            <p>ServiGo is built by a passionate team of developers, designers, and local business experts dedicated to improving access to quality services in Cameroon.</p>
        </div>
        <div class="about-section">
            <h2 class="mb-3"><i class="fas fa-envelope about-icon me-2"></i>Contact Us</h2>
            <p>Have questions or feedback? Reach out to us at <a href="mailto:info@servigo.com">info@servigo.com</a> or use our <a href="contact.php">contact form</a>.</p>
        </div>
    </div>
    <!-- Footer -->
    <footer class="footer text-white mt-5">
        <div class="container py-4">
            <div class="row">
                <div class="col-md-6">
                    <h3 class="fw-bold"><i class="fas fa-tools me-2"></i>ServiGo</h3>
                    <p>Connecting Cameroon with trusted local service providers.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">&copy; 2025 ServiGo. All rights reserved. | Made for Cameroon</p>
                </div>
            </div>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 