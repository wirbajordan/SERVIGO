<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$db = getDB();

// Fetch user data
$user = get_logged_in_user();

// Quick stats
// Total requests
$stmt = $db->prepare("SELECT COUNT(*) FROM service_requests WHERE customer_id = ?");
$stmt->execute([$user_id]);
$total_requests = $stmt->fetchColumn();
// Completed requests
$stmt = $db->prepare("SELECT COUNT(*) FROM service_requests WHERE customer_id = ? AND status = 'completed'");
$stmt->execute([$user_id]);
$completed_requests = $stmt->fetchColumn();
// Pending requests
$stmt = $db->prepare("SELECT COUNT(*) FROM service_requests WHERE customer_id = ? AND status = 'pending'");
$stmt->execute([$user_id]);
$pending_requests = $stmt->fetchColumn();
// Total spent
$stmt = $db->prepare("
    SELECT SUM(amount) FROM payments WHERE request_id IN (SELECT id FROM service_requests WHERE customer_id = ?) AND status = 'completed'
");
$stmt->execute([$user_id]);
$total_spent = $stmt->fetchColumn() ?: 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer Dashboard | ServiGo</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include 'includes/site_header.php'; ?>
<div class="container py-4">
    <div class="row">
        <div class="col-lg-3 mb-4">
            <div class="card h-100">
                <div class="card-header fw-semibold">Customer Menu</div>
                <div class="list-group list-group-flush">
                    <a href="customer_dashboard.php" class="list-group-item list-group-item-action d-flex align-items-center"><i class="fas fa-gauge-high me-2"></i>Overview</a>
                    <a href="customer_requests.php" class="list-group-item list-group-item-action d-flex align-items-center"><i class="fas fa-clipboard-list me-2"></i>My Requests</a>
                    <a href="pay.php" class="list-group-item list-group-item-action d-flex align-items-center"><i class="fas fa-money-bill me-2"></i>Payments</a>
                    <a href="messages.php" class="list-group-item list-group-item-action d-flex align-items-center"><i class="fas fa-comments me-2"></i>Messages</a>
                    <a href="notifications.php" class="list-group-item list-group-item-action d-flex align-items-center"><i class="fas fa-bell me-2"></i>Notifications</a>
                    <a href="edit_profile.php" class="list-group-item list-group-item-action d-flex align-items-center"><i class="fas fa-user-edit me-2"></i>Edit Profile</a>
                    <div class="border-top"></div>
                    <a href="dashboard.php" class="list-group-item list-group-item-action d-flex align-items-center"><i class="fas fa-home me-2"></i>Main Dashboard</a>
                    <a href="logout.php" class="list-group-item list-group-item-action d-flex align-items-center text-danger"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
                </div>
            </div>
        </div>
        <div class="col-lg-9">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <h2 class="mb-3">Welcome, <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>!</h2>
                    <p class="mb-1">Email: <strong><?php echo htmlspecialchars($user['email']); ?></strong></p>
                    <p class="mb-1">Phone: <strong><?php echo htmlspecialchars($user['phone']); ?></strong></p>
                    <p class="mb-1">City: <strong><?php echo htmlspecialchars($user['city']); ?></strong></p>
                </div>
            </div>
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h6>Total Requests</h6>
                            <h3><?php echo $total_requests; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h6>Completed</h6>
                            <h3><?php echo $completed_requests; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h6>Pending</h6>
                            <h3><?php echo $pending_requests; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h6>Total Spent</h6>
                            <h3>FCFA <?php echo number_format($total_spent, 0, ',', ' '); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row g-3">
                <div class="col-md-4">
                    <a href="request_service.php" class="text-decoration-none">
                        <div class="card h-100 card-servigo">
                            <div class="card-body d-flex align-items-center">
                                <i class="fas fa-calendar-check text-primary-servigo me-3"></i>
                                <div>
                                    <div class="fw-semibold">Book a Service</div>
                                    <div class="text-muted small">Book a provider</div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="customer_requests.php" class="text-decoration-none">
                        <div class="card h-100 card-servigo">
                            <div class="card-body d-flex align-items-center">
                                <i class="fas fa-clipboard-list text-primary-servigo me-3"></i>
                                <div>
                                    <div class="fw-semibold">Manage Requests</div>
                                    <div class="text-muted small">Track status</div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="messages.php" class="text-decoration-none">
                        <div class="card h-100 card-servigo">
                            <div class="card-body d-flex align-items-center">
                                <i class="fas fa-comments text-primary-servigo me-3"></i>
                                <div>
                                    <div class="fw-semibold">Messages</div>
                                    <div class="text-muted small">Chat with providers</div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="notifications.php" class="text-decoration-none">
                        <div class="card h-100 card-servigo">
                            <div class="card-body d-flex align-items-center">
                                <i class="fas fa-bell text-primary-servigo me-3"></i>
                                <div>
                                    <div class="fw-semibold">Notifications</div>
                                    <div class="text-muted small">See updates</div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="pay.php" class="text-decoration-none">
                        <div class="card h-100 card-servigo">
                            <div class="card-body d-flex align-items-center">
                                <i class="fas fa-money-bill text-primary-servigo me-3"></i>
                                <div>
                                    <div class="fw-semibold">Payments</div>
                                    <div class="text-muted small">Pay securely</div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="customer_reviews.php" class="text-decoration-none">
                        <div class="card h-100 card-servigo">
                            <div class="card-body d-flex align-items-center">
                                <i class="fas fa-star text-primary-servigo me-3"></i>
                                <div>
                                    <div class="fw-semibold">Your Reviews</div>
                                    <div class="text-muted small">View feedback</div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 