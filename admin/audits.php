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

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 25;
$offset = ($page - 1) * $per_page;
$total = $db->query('SELECT COUNT(*) FROM audit_logs')->fetchColumn();
$stmt = $db->prepare('SELECT al.*, u.first_name, u.last_name FROM audit_logs al JOIN users u ON al.admin_id = u.id ORDER BY al.created_at DESC LIMIT ? OFFSET ?');
$stmt->bindValue(1, $per_page, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$audit_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total_pages = ceil($total / $per_page);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Audit Logs | Admin | ServiGo</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include '_header.php'; ?>
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Audit Logs</h2>
        <a href="dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
    </div>
    <div class="table-responsive">
        <table class="table table-striped table-bordered">
            <thead><tr><th>Admin</th><th>Action</th><th>Details</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach ($audit_logs as $log): ?>
                <tr>
                    <td><?php echo htmlspecialchars($log['first_name'] . ' ' . $log['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($log['action']); ?></td>
                    <td><?php echo htmlspecialchars($log['details']); ?></td>
                    <td><?php echo date('M j, Y g:i a', strtotime($log['created_at'])); ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($audit_logs)): ?>
                <tr><td colspan="4" class="text-center">No audit logs found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <nav>
        <ul class="pagination justify-content-center">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
