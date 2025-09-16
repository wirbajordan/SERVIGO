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
    <?php include 'includes/site_header.php'; ?>

    <section class="py-4">
        <div class="container">
            <div class="row">
                <div class="col-lg-3 mb-4">
                    <div class="card h-100">
                        <div class="card-header fw-semibold">My Menu</div>
                        <div class="list-group list-group-flush">
                            <a href="dashboard.php" class="list-group-item list-group-item-action d-flex align-items-center"><i class="fas fa-home me-2"></i>Dashboard</a>
                            <a href="edit_profile.php" class="list-group-item list-group-item-action d-flex align-items-center"><i class="fas fa-user-edit me-2"></i>Edit Profile</a>
                            <a href="request_service.php" class="list-group-item list-group-item-action d-flex align-items-center"><i class="fas fa-calendar-check me-2"></i>Request Service</a>
                            <a href="messages.php" class="list-group-item list-group-item-action d-flex align-items-center"><i class="fas fa-comments me-2"></i>Messages</a>
                            <a href="notifications.php" class="list-group-item list-group-item-action d-flex align-items-center"><i class="fas fa-bell me-2"></i>Notifications</a>
                            <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'provider'): ?>
                            <div class="border-top"></div>
                            <a href="provider_dashboard.php" class="list-group-item list-group-item-action d-flex align-items-center"><i class="fas fa-briefcase me-2"></i>Provider Dashboard</a>
                            <a href="provider_services.php" class="list-group-item list-group-item-action d-flex align-items-center"><i class="fas fa-tools me-2"></i>My Services</a>
                            <?php endif; ?>
                            <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin'): ?>
                            <div class="border-top"></div>
                            <a href="admin/dashboard.php" class="list-group-item list-group-item-action d-flex align-items-center"><i class="fas fa-gauge-high me-2"></i>Admin Dashboard</a>
                            <a href="admin/notifications.php" class="list-group-item list-group-item-action d-flex align-items-center">
                                <i class="fas fa-bell me-2"></i>Admin Notifications
                                <?php if ($unread_admin_notifications > 0): ?>
                                    <span class="badge bg-danger ms-auto"><?php echo $unread_admin_notifications; ?></span>
                                <?php endif; ?>
                            </a>
                            <?php endif; ?>
                            <div class="border-top"></div>
                            <a href="logout.php" class="list-group-item list-group-item-action d-flex align-items-center text-danger"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-9">
                    <div class="card card-servigo shadow mb-4">
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
                    <div class="text-center my-4">
                        <a href="pay.php" class="btn btn-success btn-lg">Make a Payment</a>
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