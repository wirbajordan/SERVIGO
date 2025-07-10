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

// Fetch reviews given by this customer
$stmt = $db->prepare("
    SELECT r.*, u.first_name as provider_first, u.last_name as provider_last, sc.name as service_name, sr.title as request_title
    FROM reviews r
    JOIN service_requests sr ON r.request_id = sr.id
    JOIN service_categories sc ON sr.category_id = sc.id
    JOIN service_providers sp ON r.provider_id = sp.id
    JOIN users u ON sp.user_id = u.id
    WHERE r.customer_id = ?
    ORDER BY r.created_at DESC
");
$stmt->execute([$user_id]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reviews Given | Customer</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container py-5">
    <h2 class="mb-4 text-center">Reviews Given</h2>
    <div class="mb-3 text-end">
        <a href="customer_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>
    <div class="card">
        <div class="card-body">
            <?php if (empty($reviews)): ?>
                <div class="text-center text-muted">You have not left any reviews yet.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="thead-light">
                            <tr>
                                <th>Provider</th>
                                <th>Service</th>
                                <th>Request</th>
                                <th>Rating</th>
                                <th>Review</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($reviews as $rev): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($rev['provider_first'] . ' ' . $rev['provider_last']); ?></td>
                                <td><?php echo htmlspecialchars($rev['service_name']); ?></td>
                                <td><?php echo htmlspecialchars($rev['request_title']); ?></td>
                                <td><span class="badge bg-warning text-dark">★ <?php echo $rev['rating']; ?></span></td>
                                <td><?php echo htmlspecialchars($rev['review']); ?></td>
                                <td><?php echo format_datetime($rev['created_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html> 