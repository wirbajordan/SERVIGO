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

// Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['toggle_user_status'])) {
        $user_id = intval($_POST['user_id']);
        $current_status = intval($_POST['current_status']);
        $new_status = $current_status ? 0 : 1;
        $stmt = $db->prepare("UPDATE users SET is_active = ? WHERE id = ?");
        $stmt->execute([$new_status, $user_id]);
        $success = 'User status updated.';
    } elseif (isset($_POST['delete_user'])) {
        $user_id = intval($_POST['user_id']);
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $success = 'User deleted.';
    }
}

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;
$total = $db->query('SELECT COUNT(*) FROM users')->fetchColumn();
$stmt = $db->prepare('SELECT * FROM users ORDER BY created_at DESC LIMIT ? OFFSET ?');
$stmt->bindValue(1, $per_page, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total_pages = ceil($total / $per_page);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Users | Admin | ServiGo</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include '_header.php'; ?>
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>All Users</h2>
        <a href="dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
    </div>
    <?php if ($success): ?>
        <div class="alert alert-success"> <?php echo $success; ?> </div>
    <?php elseif ($error): ?>
        <div class="alert alert-danger"> <?php echo $error; ?> </div>
    <?php endif; ?>
    <div class="table-responsive">
        <table class="table table-striped table-bordered">
            <thead><tr><th>Name</th><th>Email</th><th>Type</th><th>Phone</th><th>City</th><th>Region</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><?php echo htmlspecialchars($u['first_name'] . ' ' . $u['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                    <td><?php echo ucfirst($u['user_type']); ?></td>
                    <td><?php echo htmlspecialchars($u['phone']); ?></td>
                    <td><?php echo htmlspecialchars($u['city']); ?></td>
                    <td><?php echo htmlspecialchars($u['region']); ?></td>
                    <td><?php echo $u['is_active'] ? 'Active' : 'Inactive'; ?></td>
                    <td><?php echo htmlspecialchars($u['created_at']); ?></td>
                    <td>
                        <form method="post" class="d-inline">
                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                            <input type="hidden" name="current_status" value="<?php echo $u['is_active']; ?>">
                            <button type="submit" name="toggle_user_status" class="btn btn-sm btn-<?php echo $u['is_active'] ? 'warning' : 'success'; ?>"><?php echo $u['is_active'] ? 'Deactivate' : 'Activate'; ?></button>
                        </form>
                        <form method="post" class="d-inline" onsubmit="return confirm('Delete this user?');">
                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                            <button type="submit" name="delete_user" class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($users)): ?>
                <tr><td colspan="9" class="text-center">No users found.</td></tr>
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
