<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user = get_logged_in_user();

// Get unread admin notifications count
$unread_admin_notifications = 0;
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') {
    $db = getDB();
    $unread_admin_notifications = $db->query("SELECT COUNT(*) FROM notifications WHERE for_admin = 1 AND is_read = 0")->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ServiGo</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
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
                        <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'provider'): ?>
                        <li class="nav-item"><a href="provider_services.php" class="nav-link">My Services</a></li>
                        <?php endif; ?>
                        <li class="nav-item"><a href="dashboard.php" class="nav-link active">Dashboard</a></li>
                        <li class="nav-item"><a href="edit_profile.php" class="nav-link">Edit Profile</a></li>
                        <li class="nav-item"><a href="request_service.php" class="nav-link">Request Service</a></li>
                        <li class="nav-item"><a href="messages.php" class="nav-link">Messages</a></li>
                        <li class="nav-item"><a href="notifications.php" class="nav-link">Notifications</a></li>
                        <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin'): ?>
                        <li class="nav-item">
                            <a href="admin/notifications.php" class="nav-link">
                                Admin Notifications
                                <?php if ($unread_admin_notifications > 0): ?>
                                    <span class="badge bg-danger ms-1"><?php echo $unread_admin_notifications; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item"><a href="admin/dashboard.php" class="nav-link">Admin Dashboard</a></li>
                        <?php endif; ?>
                        <li class="nav-item"><a href="logout.php" class="nav-link">Logout</a></li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
    <section class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <div class="card card-servigo shadow">
                        <div class="card-body p-5 text-center">
                            <h2 class="text-primary-servigo fw-bold mb-3">Welcome, <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>!</h2>
                            <p class="mb-2">Your user type: <span class="badge bg-success-servigo text-uppercase ms-1"><?php echo ucfirst($user['user_type']); ?></span></p>
                            <p class="mb-2">Email: <span class="fw-semibold text-neutral-servigo"><?php echo htmlspecialchars($user['email']); ?></span></p>
                            <p class="mb-2">Phone: <span class="fw-semibold text-neutral-servigo"><?php echo htmlspecialchars($user['phone']); ?></span></p>
                            <p class="mb-2">City: <span class="fw-semibold text-neutral-servigo"><?php echo htmlspecialchars($user['city']); ?></span></p>
                            <p class="mb-4">Region: <span class="fw-semibold text-neutral-servigo"><?php echo htmlspecialchars($user['region']); ?></span></p>
                            <a href="logout.php" class="btn btn-secondary">Logout</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <footer class="footer bg-neutral-dark text-white mt-5">
        <div class="container py-4">
            <div class="row">
                <div class="col-md-6">
                    <h3 class="fw-bold"><i class="fas fa-tools me-2"></i>ServiGo</h3>
                    <p>Connecting Cameroon with trusted local service providers.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">&copy; 2024 ServiGo. All rights reserved. | Made for Cameroon</p>
                </div>
            </div>
        </div>
    </footer>
    <script src="assets/js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 