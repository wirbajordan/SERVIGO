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
$provider = get_provider_data($user_id);
$provider_id = $provider['id'];

// Get all completed payments for this provider
$stmt = $db->prepare("
    SELECT p.*, sr.title as request_title, sr.created_at as request_date
    FROM payments p
    JOIN service_requests sr ON p.request_id = sr.id
    WHERE sr.provider_id = ? AND p.status = 'completed'
    ORDER BY p.created_at DESC
");
$stmt->execute([$provider_id]);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate total earnings
$total_earnings = 0;
foreach ($payments as $pay) {
    $total_earnings += $pay['amount'];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Earnings & Payouts | Provider</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container py-5">
    <h2 class="mb-4 text-center">Earnings & Payouts</h2>
    <div class="mb-3 text-end">
        <a href="provider_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>
    <div class="card mb-4">
        <div class="card-body text-center">
            <h4>Total Earnings</h4>
            <h2 class="text-success">FCFA <?php echo number_format($total_earnings, 0, ',', ' '); ?></h2>
            <button class="btn btn-primary mt-3" disabled>Request Payout (Coming Soon)</button>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead class="thead-light">
                <tr>
                    <th>Payment ID</th>
                    <th>Service Request</th>
                    <th>Amount</th>
                    <th>Payment Method</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($payments as $pay): ?>
                <tr>
                    <td><?php echo $pay['id']; ?></td>
                    <td><?php echo htmlspecialchars($pay['request_title']); ?></td>
                    <td>FCFA <?php echo number_format($pay['amount'], 0, ',', ' '); ?></td>
                    <td><?php echo ucfirst(str_replace('_', ' ', $pay['payment_method'])); ?></td>
                    <td><span class="badge bg-success">Completed</span></td>
                    <td><?php echo format_datetime($pay['created_at']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html> 