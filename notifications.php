<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];
$db = getDB();

// Fetch notifications for the user
$stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Notifications | ServiGo</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container py-5">
    <h2 class="mb-4 text-center">Notifications</h2>
    <div class="mb-3 text-end">
        <?php if ($user_type === 'provider'): ?>
            <a href="provider_dashboard.php" class="btn btn-secondary">Back to Provider Dashboard</a>
        <?php else: ?>
            <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        <?php endif; ?>
    </div>
    <div class="card">
        <div class="card-body p-0">
            <ul class="list-group list-group-flush">
                <?php if (empty($notifications)): ?>
                    <li class="list-group-item text-center text-muted">No notifications found.</li>
                <?php else: ?>
                    <?php foreach ($notifications as $note): ?>
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?php echo htmlspecialchars($note['title']); ?></strong>
                                    <div class="small text-muted"><?php echo htmlspecialchars($note['message']); ?></div>
                                </div>
                                <span class="badge bg-<?php echo $note['is_read'] ? 'secondary' : 'primary'; ?> ms-2"><?php echo $note['is_read'] ? 'Read' : 'New'; ?></span>
                            </div>
                            <div class="small text-muted mt-1 text-end"><?php echo format_datetime($note['created_at']); ?></div>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>
</body>
</html> 