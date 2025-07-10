<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'provider') {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$db = getDB();

// Fetch provider data
$provider = get_provider_data($user_id);
$pending_requests = get_provider_requests($provider['id'], 'pending');
$completed_requests = get_provider_requests($provider['id'], 'completed');
$earnings = $db->prepare("SELECT SUM(amount) FROM payments WHERE request_id IN (SELECT id FROM service_requests WHERE provider_id = ?) AND status = 'completed'");
$earnings->execute([$provider['id']]);
$total_earnings = $earnings->fetchColumn() ?: 0;
$average_rating = calculate_average_rating($provider['id']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Provider Dashboard | ServiGo</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="card mb-4">
        <div class="card-body text-center">
            <h2 class="mb-3">Welcome, <?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?>!</h2>
            <p class="mb-1">Business: <strong><?php echo htmlspecialchars($provider['business_name'] ?? 'N/A'); ?></strong></p>
            <p class="mb-1">Average Rating: <strong><?php echo $average_rating; ?> / 5</strong></p>
        </div>
    </div>
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5>Pending Requests</h5>
                    <h2><?php echo count($pending_requests); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5>Completed Jobs</h5>
                    <h2><?php echo count($completed_requests); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5>Total Earnings</h5>
                    <h2>FCFA <?php echo number_format($total_earnings, 0, ',', ' '); ?></h2>
                </div>
            </div>
        </div>
    </div>
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <a href="edit_profile.php" class="btn btn-outline-primary w-100">Profile Management</a>
        </div>
        <div class="col-md-3 mb-3">
            <a href="provider_services.php" class="btn btn-outline-primary w-100">Manage Services</a>
        </div>
        <div class="col-md-3 mb-3">
            <a href="provider_requests.php" class="btn btn-outline-primary w-100">Service Requests</a>
        </div>
        <div class="col-md-3 mb-3">
            <a href="provider_earnings.php" class="btn btn-outline-primary w-100">Earnings & Payouts</a>
        </div>
        <div class="col-md-3 mb-3">
            <a href="provider_reviews.php" class="btn btn-outline-primary w-100">Ratings & Reviews</a>
        </div>
        <div class="col-md-3 mb-3">
            <a href="messages.php" class="btn btn-outline-primary w-100">In-App Messaging</a>
        </div>
        <div class="col-md-3 mb-3">
            <a href="notifications.php" class="btn btn-outline-primary w-100">Notifications</a>
        </div>
        <div class="col-md-3 mb-3">
            <a href="provider_calendar.php" class="btn btn-outline-primary w-100">Calendar</a>
        </div>
        <div class="col-md-3 mb-3">
            <a href="provider_analytics.php" class="btn btn-outline-primary w-100">Analytics & Insights</a>
        </div>
        <div class="col-md-3 mb-3">
            <a href="provider_promotions.php" class="btn btn-outline-primary w-100">Promotions & Discounts</a>
        </div>
    </div>
    <div class="text-center mt-4">
        <a href="dashboard.php" class="btn btn-outline-secondary">Back to User Dashboard</a>
    </div>
</div>
</body>
</html> 