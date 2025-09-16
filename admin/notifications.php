<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$db = getDB();
$success = $error = '';

// Mark as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    $notification_id = intval($_POST['notification_id']);
    $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND for_admin = 1");
    if ($stmt->execute([$notification_id])) {
        $success = 'Notification marked as read.';
    } else {
        $error = 'Failed to mark notification as read.';
    }
}

// Get admin notifications
$stmt = $db->prepare("SELECT * FROM notifications WHERE for_admin = 1 ORDER BY created_at DESC LIMIT 50");
$stmt->execute();
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get unread count
$stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE for_admin = 1 AND is_read = 0");
$stmt->execute();
$unread_count = $stmt->fetchColumn();

$primary = '#007bff';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Notifications | ServiGo</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .notification-item { border-left: 4px solid transparent; }
        .notification-item.unread { border-left-color: <?php echo $primary; ?>; background-color: #f0f8ff; }
    </style>
</head>
<body>
<?php include '_header.php'; ?>
<div class="container py-5">
    <h2 class="mb-4" style="color: <?php echo $primary; ?>;">Admin Notifications <span class="badge bg-primary ms-2"><?php echo $unread_count; ?> unread</span></h2>
    <?php if ($success): ?>
        <div class="alert alert-success"> <?php echo $success; ?> </div>
    <?php elseif ($error): ?>
        <div class="alert alert-danger"> <?php echo $error; ?> </div>
    <?php endif; ?>
    <div class="card">
        <div class="card-body">
            <?php if (count($notifications) > 0): ?>
                <?php foreach ($notifications as $notif): ?>
                <div class="notification-item p-3 mb-2 <?php echo $notif['is_read'] ? '' : 'unread'; ?>">
                    <h6><?php echo htmlspecialchars($notif['title']); ?></h6>
                    <p class="mb-1"><?php echo htmlspecialchars($notif['message']); ?></p>
                    <small class="text-muted"><?php echo date('M j, Y g:i a', strtotime($notif['created_at'])); ?></small>
                    <?php if (!$notif['is_read']): ?>
                    <form method="post" class="mt-2">
                        <input type="hidden" name="notification_id" value="<?php echo $notif['id']; ?>">
                        <button type="submit" name="mark_read" class="btn btn-sm btn-primary">Mark as Read</button>
                    </form>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-4">
                    <h5 class="text-muted">No notifications</h5>
                    <p class="text-muted">You're all caught up!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="text-center mt-4">
        <a href="dashboard.php" class="btn btn-outline-primary">Back to Admin Dashboard</a>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 