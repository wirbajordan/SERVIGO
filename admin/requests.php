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
    if (isset($_POST['change_request_status'])) {
        $request_id = intval($_POST['request_id']);
        $new_status = $_POST['new_status'];
        $stmt = $db->prepare("UPDATE service_requests SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $request_id]);
        $success = 'Request status updated.';
    } elseif (isset($_POST['delete_request'])) {
        $request_id = intval($_POST['request_id']);
        $stmt = $db->prepare("DELETE FROM service_requests WHERE id = ?");
        $stmt->execute([$request_id]);
        $success = 'Request deleted.';
    }
}

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;
$total = $db->query('SELECT COUNT(*) FROM service_requests')->fetchColumn();
$stmt = $db->prepare('SELECT sr.*, u.first_name, u.last_name FROM service_requests sr JOIN users u ON sr.customer_id = u.id ORDER BY sr.created_at DESC LIMIT ? OFFSET ?');
$stmt->bindValue(1, $per_page, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total_pages = ceil($total / $per_page);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Service Requests | Admin | ServiGo</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include '_header.php'; ?>
<div class="container py-5">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php">Admin Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Service Requests</li>
        </ol>
    </nav>
    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="dashboard.php" class="btn btn-sm btn-outline-primary"><i class="fas fa-gauge-high me-1"></i> Dashboard</a>
        <a href="users.php" class="btn btn-sm btn-outline-primary"><i class="fas fa-users me-1"></i> Users</a>
        <a href="requests.php" class="btn btn-sm btn-primary"><i class="fas fa-clipboard-list me-1"></i> Requests</a>
        <a href="provider_verification.php" class="btn btn-sm btn-outline-warning"><i class="fas fa-id-badge me-1"></i> Provider Verification</a>
        <a href="notifications.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-bell me-1"></i> Notifications</a>
        <a href="audits.php" class="btn btn-sm btn-outline-dark"><i class="fas fa-clipboard-check me-1"></i> Audit Logs</a>
    </div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>All Service Requests</h2>
        <a href="dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
    </div>
    <?php if ($success): ?>
        <div class="alert alert-success"> <?php echo $success; ?> </div>
    <?php elseif ($error): ?>
        <div class="alert alert-danger"> <?php echo $error; ?> </div>
    <?php endif; ?>
    <div class="table-responsive">
        <table class="table table-striped table-bordered">
            <thead><tr><th>Customer</th><th>Title</th><th>Status</th><th>Budget</th><th>Urgency</th><th>Created</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($requests as $r): ?>
                <tr>
                    <td><?php echo htmlspecialchars($r['first_name'] . ' ' . $r['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($r['title']); ?></td>
                    <td><span class="badge bg-info text-dark"><?php echo ucfirst($r['status']); ?></span></td>
                    <td><?php echo $r['budget'] !== null ? number_format($r['budget'], 0, ',', ' ') : '-'; ?></td>
                    <td><?php echo ucfirst($r['urgency']); ?></td>
                    <td><?php echo htmlspecialchars($r['created_at']); ?></td>
                    <td>
                        <form method="post" class="d-inline">
                            <input type="hidden" name="request_id" value="<?php echo $r['id']; ?>">
                            <select name="new_status" class="form-select form-select-sm d-inline w-auto">
                                <?php foreach (["pending","accepted","in_progress","completed","cancelled"] as $status): ?>
                                    <option value="<?php echo $status; ?>" <?php if ($r['status'] === $status) echo 'selected'; ?>><?php echo ucfirst($status); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" name="change_request_status" class="btn btn-sm btn-primary">Update</button>
                        </form>
                        <form method="post" class="d-inline" onsubmit="return confirm('Delete this request?');">
                            <input type="hidden" name="request_id" value="<?php echo $r['id']; ?>">
                            <button type="submit" name="delete_request" class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($requests)): ?>
                <tr><td colspan="7" class="text-center">No requests found.</td></tr>
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
