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

// Fetch reviews for this provider
$stmt = $db->prepare("
    SELECT r.*, u.first_name, u.last_name, sr.title as request_title
    FROM reviews r
    JOIN users u ON r.customer_id = u.id
    JOIN service_requests sr ON r.request_id = sr.id
    WHERE r.provider_id = ?
    ORDER BY r.created_at DESC
");
$stmt->execute([$provider_id]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate average rating
$average_rating = calculate_average_rating($provider_id);
$total_reviews = count($reviews);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Ratings & Reviews | Provider</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container py-5">
    <h2 class="mb-4 text-center">Ratings & Reviews</h2>
    <div class="mb-3 text-end">
        <a href="provider_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>
    <div class="card mb-4">
        <div class="card-body text-center">
            <h4>Average Rating</h4>
            <h2 class="text-warning">★ <?php echo $average_rating; ?> / 5</h2>
            <p>Total Reviews: <strong><?php echo $total_reviews; ?></strong></p>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead class="thead-light">
                <tr>
                    <th>Customer</th>
                    <th>Service Request</th>
                    <th>Rating</th>
                    <th>Review</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($reviews as $rev): ?>
                <tr>
                    <td><?php echo htmlspecialchars($rev['first_name'] . ' ' . $rev['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($rev['request_title']); ?></td>
                    <td><span class="badge bg-warning text-dark">★ <?php echo $rev['rating']; ?></span></td>
                    <td><?php echo htmlspecialchars($rev['review']); ?></td>
                    <td><?php echo format_datetime($rev['created_at']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html> 