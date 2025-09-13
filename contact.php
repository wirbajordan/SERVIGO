<?php
session_start();
require_once 'config/database.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    
    try {
        $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, subject, message, created_at) 
                             VALUES (:name, :email, :subject, :message, NOW())");
        
        $result = $stmt->execute([
            'name' => $name,
            'email' => $email,
            'subject' => $subject,
            'message' => $message
        ]);
        
        if ($result) {
            $success = 'Thank you, ' . htmlspecialchars($name) . '! Your message has been received.';
        } else {
            $error = 'Sorry, there was an error sending your message. Please try again.';
        }
    } catch (PDOException $e) {
        $error = 'Sorry, there was an error sending your message. Please try again.';
        error_log("Contact form error: " . $e->getMessage());
    }
}

?>
 <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us | ServiGo</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .contact-hero { background: #007bff; color: #fff; padding: 4rem 0 2rem 0; }
        .contact-section { background: #fff; border-radius: 10px; box-shadow: 0 2px 12px rgba(0,0,0,0.07); padding: 2rem; margin-bottom: 2rem; }
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
                        <li class="nav-item"><a href="about.php" class="nav-link">About</a></li>
                        <li class="nav-item"><a href="contact.php" class="nav-link active">Contact</a></li>
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
    <section class="contact-hero text-center">
        <div class="container">
            <h1 class="display-4 fw-bold">Contact Us</h1>
            <p class="lead">We'd love to hear from you. Reach out with your questions, feedback, or partnership ideas!</p>
        </div>
    </section>
    <!-- Contact Content -->
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-7">
                <div class="contact-section">
                    <h2 class="mb-4"><i class="fas fa-envelope me-2"></i>Send a Message</h2>
                    <?php if ($success): ?>
                        <div class="alert alert-success"> <?php echo $success; ?> </div>
                    <?php endif; ?>
                    <form method="post">
                        <div class="mb-3">
                            <label for="name" class="form-label">Your Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email address</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="subject" class="form-label">Subject</label>
                            <input type="text" class="form-control" id="subject" name="subject" required>
                        </div>
                        <div class="mb-3">
                            <label for="message" class="form-label">Message</label>
                            <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary px-4">Send Message</button>
                    </form>
                </div>
            </div>
            <div class="col-md-5">
                <div class="contact-section h-100">
                    <h2 class="mb-4"><i class="fas fa-info-circle me-2"></i>Contact Info</h2>
                    <p><i class="fas fa-map-marker-alt me-2"></i>Yaoundé, Cameroon</p>
                    <p><i class="fas fa-envelope me-2"></i><a href="mailto:info@servigo.com">info@servigo.com</a></p>
                    <p><i class="fas fa-phone me-2"></i>+237 674 419 495</p>
                    <hr>
                    <h5>Follow Us</h5>
                    <a href="#" class="me-2"><i class="fab fa-facebook fa-lg"></i></a>
                    <a href="#" class="me-2"><i class="fab fa-twitter fa-lg"></i></a>
                    <a href="#" class="me-2"><i class="fab fa-linkedin fa-lg"></i></a>
                </div>
            </div>
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