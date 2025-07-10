<?php
require_once 'includes/mtn_momo.php';
require_once 'config/database.php';

$referenceId = $_GET['ref'] ?? null;
if (!$referenceId) {
    die('Invalid reference');
}

$mtn = new MTNMomo();
$status = $mtn->getPaymentStatus($referenceId);

// Display status
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MTN MoMo Payment Status</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="card mx-auto" style="max-width: 400px;">
        <div class="card-body text-center">
            <h2 class="mb-4">Payment Status</h2>
            <h4 class="mb-3">
                <?php echo htmlspecialchars($status['status']); ?>
            </h4>
            <?php if (isset($status['reason'])): ?>
                <p class="text-danger"><?php echo htmlspecialchars($status['reason']); ?></p>
            <?php endif; ?>
            <a href="dashboard.php" class="btn btn-primary mt-3">Back to Dashboard</a>
        </div>
    </div>
</div>
</body>
</html>
<?php
// Update DB
$db = getDB();
if ($status['status'] === 'SUCCESSFUL') {
    $stmt = $db->prepare("UPDATE payments SET status = 'successful' WHERE reference_id = ?");
    $stmt->execute([$referenceId]);
} elseif ($status['status'] === 'FAILED') {
    $stmt = $db->prepare("UPDATE payments SET status = 'failed' WHERE reference_id = ?");
    $stmt->execute([$referenceId]);
}
?> 