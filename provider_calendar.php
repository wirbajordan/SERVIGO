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

// Fetch upcoming jobs (pending or accepted, scheduled for today or later)
$stmt = $db->prepare("
    SELECT sr.*, u.first_name, u.last_name, sc.name as service_name
    FROM service_requests sr
    JOIN users u ON sr.customer_id = u.id
    JOIN service_categories sc ON sr.category_id = sc.id
    WHERE sr.provider_id = ?
      AND (sr.status = 'pending' OR sr.status = 'accepted')
      AND sr.scheduled_date >= CURDATE()
    ORDER BY sr.scheduled_date, sr.scheduled_time
");
$stmt->execute([$provider_id]);
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Provider Calendar | ServiGo</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container py-5">
    <h2 class="mb-4 text-center">My Calendar</h2>
    <div class="mb-3 text-end">
        <a href="provider_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>
    <div class="card">
        <div class="card-body">
            <?php if (empty($jobs)): ?>
                <div class="text-center text-muted">No upcoming jobs scheduled.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="thead-light">
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Service</th>
                                <th>Customer</th>
                                <th>Status</th>
                                <th>Location</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($jobs as $job): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($job['scheduled_date']); ?></td>
                                <td><?php echo htmlspecialchars($job['scheduled_time']); ?></td>
                                <td><?php echo htmlspecialchars($job['service_name']); ?></td>
                                <td><?php echo htmlspecialchars($job['first_name'] . ' ' . $job['last_name']); ?></td>
                                <td><span class="badge bg-<?php echo $job['status'] === 'accepted' ? 'primary' : 'warning'; ?>"><?php echo ucfirst($job['status']); ?></span></td>
                                <td><?php echo htmlspecialchars($job['location']); ?></td>
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