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

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'])) {
    $request_id = intval($_POST['request_id']);
    if (isset($_POST['accept'])) {
        $stmt = $db->prepare("UPDATE service_requests SET status = 'accepted' WHERE id = ? AND provider_id = ?");
        $stmt->execute([$request_id, $provider_id]);
    } elseif (isset($_POST['decline'])) {
        $stmt = $db->prepare("UPDATE service_requests SET status = 'declined' WHERE id = ? AND provider_id = ?");
        $stmt->execute([$request_id, $provider_id]);
    } elseif (isset($_POST['complete'])) {
        $stmt = $db->prepare("UPDATE service_requests SET status = 'completed' WHERE id = ? AND provider_id = ?");
        $stmt->execute([$request_id, $provider_id]);
    }
    header('Location: provider_requests.php');
    exit();
}

// Fetch all requests for this provider
$stmt = $db->prepare("
    SELECT sr.*, u.first_name, u.last_name, u.phone, u.city, sc.name as service_name
    FROM service_requests sr
    JOIN users u ON sr.customer_id = u.id
    JOIN service_categories sc ON sr.category_id = sc.id
    WHERE sr.provider_id = ?
    ORDER BY sr.created_at DESC
");
$stmt->execute([$provider_id]);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Service Requests | Provider</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container py-5">
    <h2 class="mb-4 text-center">My Service Requests</h2>
    <div class="mb-3 text-end">
        <a href="provider_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead class="thead-light">
                <tr>
                    <th>Request ID</th>
                    <th>Service</th>
                    <th>Customer</th>
                    <th>Contact</th>
                    <th>Location</th>
                    <th>Budget</th>
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
                    <td><?php echo htmlspecialchars($req['first_name'] . ' ' . $req['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($req['phone']); ?></td>
                    <td><?php echo htmlspecialchars($req['city']); ?></td>
                    <td>FCFA <?php echo number_format($req['budget'], 0, ',', ' '); ?></td>
                    <td><span class="badge bg-<?php
                        switch($req['status']) {
                            case 'pending': echo 'warning'; break;
                            case 'accepted': echo 'primary'; break;
                            case 'completed': echo 'success'; break;
                            case 'declined': echo 'danger'; break;
                            default: echo 'secondary';
                        }
                    ?>"><?php echo ucfirst($req['status']); ?></span></td>
                    <td><?php echo format_datetime($req['created_at']); ?></td>
                    <td>
                        <?php if ($req['status'] === 'pending'): ?>
                            <form method="post" style="display:inline-block">
                                <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                                <button type="submit" name="accept" class="btn btn-success btn-sm">Accept</button>
                                <button type="submit" name="decline" class="btn btn-danger btn-sm">Decline</button>
                            </form>
                        <?php elseif ($req['status'] === 'accepted'): ?>
                            <form method="post" style="display:inline-block">
                                <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                                <button type="submit" name="complete" class="btn btn-primary btn-sm">Mark as Completed</button>
                            </form>
                        <?php else: ?>
                            <span class="text-muted">No actions</span>
                        <?php endif; ?>
                        <a href="messages.php?user_id=<?php echo $req['customer_id']; ?>" class="btn btn-outline-secondary btn-sm ms-1">Message Customer</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html> 