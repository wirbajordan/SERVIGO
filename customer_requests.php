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

// Handle cancel request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_request_id'])) {
    $request_id = intval($_POST['cancel_request_id']);
    $stmt = $db->prepare("UPDATE service_requests SET status = 'cancelled' WHERE id = ? AND customer_id = ? AND status = 'pending'");
    $stmt->execute([$request_id, $user_id]);
    header('Location: customer_requests.php');
    exit();
}

// Fetch all requests for this customer
$stmt = $db->prepare("
    SELECT sr.*, sc.name as service_name, u.first_name as provider_first, u.last_name as provider_last
    FROM service_requests sr
    JOIN service_categories sc ON sr.category_id = sc.id
    LEFT JOIN service_providers sp ON sr.provider_id = sp.id
    LEFT JOIN users u ON sp.user_id = u.id
    WHERE sr.customer_id = ?
    ORDER BY sr.created_at DESC
");
$stmt->execute([$user_id]);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Requests | Customer</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container py-5">
    <h2 class="mb-4 text-center">My Service Requests</h2>
    <div class="mb-3 text-end">
        <a href="customer_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead class="thead-light">
                <tr>
                    <th>Request ID</th>
                    <th>Service</th>
                    <th>Provider</th>
                    <th>Status</th>
                    <th>Requested At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($requests as $req): ?>
                <tr>
                    <td><?php echo $req['id']; ?></td>
                    <td><?php echo htmlspecialchars($req['service_name']); ?></td>
                    <td><?php echo $req['provider_first'] ? htmlspecialchars($req['provider_first'] . ' ' . $req['provider_last']) : '<span class="text-muted">Not assigned</span>'; ?></td>
                    <td><span class="badge bg-<?php
                        switch($req['status']) {
                            case 'pending': echo 'warning'; break;
                            case 'accepted': echo 'primary'; break;
                            case 'completed': echo 'success'; break;
                            case 'declined': echo 'danger'; break;
                            case 'cancelled': echo 'secondary'; break;
                            default: echo 'secondary';
                        }
                    ?>"><?php echo ucfirst($req['status']); ?></span></td>
                    <td><?php echo format_datetime($req['created_at']); ?></td>
                    <td>
                        <?php if ($req['status'] === 'pending'): ?>
                            <form method="post" style="display:inline-block">
                                <input type="hidden" name="cancel_request_id" value="<?php echo $req['id']; ?>">
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Cancel this request?')">Cancel</button>
                            </form>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html> 