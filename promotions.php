<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
    header('Location: login.php');
    exit();
}

$db = getDB();

// Fetch all active promotions (not expired)
$stmt = $db->prepare("
    SELECT pp.*, u.first_name as provider_first, u.last_name as provider_last, sp.business_name
    FROM provider_promotions pp
    JOIN service_providers sp ON pp.provider_id = sp.id
    JOIN users u ON sp.user_id = u.id
    WHERE pp.valid_until IS NULL OR pp.valid_until >= CURDATE()
    ORDER BY pp.valid_until ASC, pp.created_at DESC
");
$stmt->execute();
$promotions = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Promotions | Customer</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container py-5">
    <h2 class="mb-4 text-center">Available Promotions</h2>
    <div class="mb-3 text-end">
        <a href="customer_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>
    <div class="card">
        <div class="card-body">
            <?php if (empty($promotions)): ?>
                <div class="text-center text-muted">No promotions available at this time.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="thead-light">
                            <tr>
                                <th>Provider</th>
                                <th>Business</th>
                                <th>Title</th>
                                <th>Description</th>
                                <th>Discount (%)</th>
                                <th>Valid Until</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($promotions as $promo): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($promo['provider_first'] . ' ' . $promo['provider_last']); ?></td>
                                <td><?php echo htmlspecialchars($promo['business_name']); ?></td>
                                <td><?php echo htmlspecialchars($promo['title']); ?></td>
                                <td><?php echo htmlspecialchars($promo['description']); ?></td>
                                <td><?php echo $promo['discount_percent']; ?></td>
                                <td><?php echo $promo['valid_until'] ? htmlspecialchars($promo['valid_until']) : '<span class="text-muted">No expiry</span>'; ?></td>
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