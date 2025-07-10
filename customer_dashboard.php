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
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container py-5">
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
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <a href="edit_profile.php" class="btn btn-outline-primary w-100">Edit Profile</a>
        </div>
        <div class="col-md-3 mb-3">
            <a href="customer_requests.php" class="btn btn-outline-primary w-100">My Requests</a>
        </div>
        <div class="col-md-3 mb-3">
            <a href="pay.php" class="btn btn-outline-primary w-100">Make a Payment</a>
        </div>
        <div class="col-md-3 mb-3">
            <a href="messages.php" class="btn btn-outline-primary w-100">Messages</a>
        </div>
        <div class="col-md-3 mb-3">
            <a href="notifications.php" class="btn btn-outline-primary w-100">Notifications</a>
        </div>
        <div class="col-md-3 mb-3">
            <a href="customer_reviews.php" class="btn btn-outline-primary w-100">Reviews Given</a>
        </div>
        <div class="col-md-3 mb-3">
            <a href="promotions.php" class="btn btn-outline-primary w-100">Promotions</a>
        </div>
    </div>
    <div class="text-center mt-4">
        <a href="dashboard.php" class="btn btn-outline-secondary">Back to Main Dashboard</a>
    </div>
</div>
</body>
</html> 