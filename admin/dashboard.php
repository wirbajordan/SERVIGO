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

function log_audit($db, $admin_id, $action, $details = null) {
    $stmt = $db->prepare("INSERT INTO audit_logs (admin_id, action, details) VALUES (?, ?, ?)");
    $stmt->execute([$admin_id, $action, $details]);
}

$admin_id = $_SESSION['user_id'];

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['toggle_user_status'])) {
        $user_id = intval($_POST['user_id']);
        $current_status = intval($_POST['current_status']);
        $new_status = $current_status ? 0 : 1;
        $stmt = $db->prepare("UPDATE users SET is_active = ? WHERE id = ?");
        if ($stmt->execute([$new_status, $user_id])) {
            $success = 'User status updated.';
            log_audit($db, $admin_id, 'Toggle User Status', 'User ID: ' . $user_id . ', New Status: ' . $new_status);
        } else {
            $error = 'Failed to update user status.';
        }
    } elseif (isset($_POST['delete_user'])) {
        $user_id = intval($_POST['user_id']);
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        if ($stmt->execute([$user_id])) {
            $success = 'User deleted.';
            log_audit($db, $admin_id, 'Delete User', 'User ID: ' . $user_id);
        } else {
            $error = 'Failed to delete user.';
        }
    }
}

// Handle service actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['toggle_service_status'])) {
        $service_id = intval($_POST['service_id']);
        $current_status = intval($_POST['current_status']);
        $new_status = $current_status ? 0 : 1;
        $stmt = $db->prepare("UPDATE service_categories SET is_active = ? WHERE id = ?");
        if ($stmt->execute([$new_status, $service_id])) {
            $success = 'Service status updated.';
            log_audit($db, $admin_id, 'Toggle Service Status', 'Service ID: ' . $service_id . ', New Status: ' . $new_status);
        } else {
            $error = 'Failed to update service status.';
        }
    } elseif (isset($_POST['delete_service'])) {
        $service_id = intval($_POST['service_id']);
        $stmt = $db->prepare("DELETE FROM service_categories WHERE id = ?");
        if ($stmt->execute([$service_id])) {
            $success = 'Service deleted.';
            log_audit($db, $admin_id, 'Delete Service', 'Service ID: ' . $service_id);
        } else {
            $error = 'Failed to delete service.';
        }
    } elseif (isset($_POST['edit_service'])) {
        $service_id = intval($_POST['service_id']);
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $stmt = $db->prepare("UPDATE service_categories SET name = ?, description = ? WHERE id = ?");
        if ($stmt->execute([$name, $description, $service_id])) {
            $success = 'Service updated.';
            log_audit($db, $admin_id, 'Edit Service', 'Service ID: ' . $service_id);
        } else {
            $error = 'Failed to update service.';
        }
    }
}

// Handle request actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['change_request_status'])) {
        $request_id = intval($_POST['request_id']);
        $new_status = $_POST['new_status'];
        $stmt = $db->prepare("UPDATE service_requests SET status = ? WHERE id = ?");
        if ($stmt->execute([$new_status, $request_id])) {
            $success = 'Request status updated.';
            log_audit($db, $admin_id, 'Change Request Status', 'Request ID: ' . $request_id . ', New Status: ' . $new_status);
        } else {
            $error = 'Failed to update request status.';
        }
    } elseif (isset($_POST['delete_request'])) {
        $request_id = intval($_POST['request_id']);
        $stmt = $db->prepare("DELETE FROM service_requests WHERE id = ?");
        if ($stmt->execute([$request_id])) {
            $success = 'Request deleted.';
            log_audit($db, $admin_id, 'Delete Request', 'Request ID: ' . $request_id);
        } else {
            $error = 'Failed to delete request.';
        }
    }
}

// Handle pending service approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve_service'])) {
        $service_id = intval($_POST['service_id']);
        $stmt = $db->prepare("UPDATE service_categories SET is_active = 1 WHERE id = ?");
        if ($stmt->execute([$service_id])) {
            $success = 'Service approved.';
            log_audit($db, $admin_id, 'Approve Service', 'Service ID: ' . $service_id);
        } else {
            $error = 'Failed to approve service.';
        }
    } elseif (isset($_POST['reject_service'])) {
        $service_id = intval($_POST['service_id']);
        $stmt = $db->prepare("DELETE FROM service_categories WHERE id = ?");
        if ($stmt->execute([$service_id])) {
            $success = 'Service rejected and deleted.';
            log_audit($db, $admin_id, 'Reject Service', 'Service ID: ' . $service_id);
        } else {
            $error = 'Failed to reject service.';
        }
    }
}

// Handle user detail view and password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['reset_password'])) {
        $user_id = intval($_POST['user_id']);
        $new_password = $_POST['new_password'];
        if (strlen($new_password) < 6) {
            $error = 'Password must be at least 6 characters.';
        } else {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($stmt->execute([$hashed, $user_id])) {
                $success = 'Password reset successfully.';
                log_audit($db, $admin_id, 'Reset User Password', 'User ID: ' . $user_id);
            } else {
                $error = 'Failed to reset password.';
            }
        }
    }
}

// Overview stats
$total_users = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_providers = $db->query("SELECT COUNT(*) FROM users WHERE user_type = 'provider'")->fetchColumn();
$total_customers = $db->query("SELECT COUNT(*) FROM users WHERE user_type = 'customer'")->fetchColumn();
$total_services = $db->query("SELECT COUNT(*) FROM service_categories WHERE is_active = 1")->fetchColumn();
$total_requests = $db->query("SELECT COUNT(*) FROM service_requests")->fetchColumn();

// List users
$users = $db->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
// List services
$services = $db->query("SELECT * FROM service_categories ORDER BY name LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
// List requests
$requests = $db->query("SELECT sr.*, u.first_name, u.last_name FROM service_requests sr JOIN users u ON sr.customer_id = u.id ORDER BY sr.created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
// List pending services
$pending_services = $db->query("SELECT * FROM service_categories WHERE is_active = 0 ORDER BY created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

// --- Analytics Data ---
// User growth (monthly registrations for last 12 months)
$user_growth = $db->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count FROM users GROUP BY month ORDER BY month DESC LIMIT 12")->fetchAll(PDO::FETCH_ASSOC);
$user_growth = array_reverse($user_growth);
// Service usage (number of providers per service)
$service_usage = $db->query("SELECT name, (SELECT COUNT(*) FROM provider_services WHERE category_id = sc.id) as count FROM service_categories sc ORDER BY name LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
// Request trends (monthly requests for last 12 months)
$request_trends = $db->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count FROM service_requests GROUP BY month ORDER BY month DESC LIMIT 12")->fetchAll(PDO::FETCH_ASSOC);
$request_trends = array_reverse($request_trends);

$primary = '#007bff';

// Export CSV functionality
if (isset($_GET['export'])) {
    $type = $_GET['export'];
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $type . '_export_' . date('Ymd_His') . '.csv"');
    $output = fopen('php://output', 'w');
    if ($type === 'users') {
        fputcsv($output, ['ID', 'First Name', 'Last Name', 'Email', 'Type', 'Phone', 'City', 'Region', 'Status', 'Created']);
        $rows = $db->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            fputcsv($output, [
                $row['id'], $row['first_name'], $row['last_name'], $row['email'], $row['user_type'],
                $row['phone'], $row['city'], $row['region'], $row['is_active'] ? 'Active' : 'Inactive', $row['created_at']
            ]);
        }
    } elseif ($type === 'services') {
        fputcsv($output, ['ID', 'Name', 'Description', 'Status', 'Created']);
        $rows = $db->query("SELECT * FROM service_categories ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            fputcsv($output, [
                $row['id'], $row['name'], $row['description'], $row['is_active'] ? 'Active' : 'Inactive', $row['created_at']
            ]);
        }
    } elseif ($type === 'requests') {
        fputcsv($output, ['ID', 'Customer ID', 'Provider ID', 'Category ID', 'Title', 'Status', 'Created']);
        $rows = $db->query("SELECT * FROM service_requests ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            fputcsv($output, [
                $row['id'], $row['customer_id'], $row['provider_id'], $row['category_id'], $row['title'], $row['status'], $row['created_at']
            ]);
        }
    }
    fclose($output);
    log_audit($db, $admin_id, 'Export ' . ucfirst($type) . ' CSV');
    exit();
}

// Fetch recent audit logs
$audit_logs = $db->query("SELECT al.*, u.first_name, u.last_name FROM audit_logs al JOIN users u ON al.admin_id = u.id ORDER BY al.created_at DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);

// Get unread admin notifications count
$unread_admin_notifications = $db->query("SELECT COUNT(*) FROM notifications WHERE for_admin = 1 AND is_read = 0")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | ServiGo</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .card-stat { border-left: 5px solid <?php echo $primary; ?>; }
        .table thead { background: <?php echo $primary; ?>; color: #fff; }
    </style>
</head>
<body>
<?php include '_header.php'; ?>
<div class="container py-4">
    <h2 class="mb-3 text-center" style="color: <?php echo $primary; ?>;">Admin Dashboard</h2>
    <div class="d-flex flex-wrap justify-content-center gap-2 mb-4">
        <a href="users.php" class="btn btn-sm btn-outline-primary me-2 mb-2"><i class="fas fa-users me-1"></i> Users</a>
        <a href="requests.php" class="btn btn-sm btn-outline-primary me-2 mb-2"><i class="fas fa-clipboard-list me-1"></i> Requests</a>
        <a href="provider_verification.php" class="btn btn-sm btn-outline-warning me-2 mb-2"><i class="fas fa-id-badge me-1"></i> Provider Verification</a>
        <a href="notifications.php" class="btn btn-sm btn-outline-secondary me-2 mb-2"><i class="fas fa-bell me-1"></i> Notifications</a>
        <a href="audits.php" class="btn btn-sm btn-outline-dark me-2 mb-2"><i class="fas fa-clipboard-check me-1"></i> Audit Logs</a>
        <a href="../dashboard.php?main=1" class="btn btn-sm btn-outline-success me-2 mb-2"><i class="fas fa-gauge-high me-1"></i> Main Dashboard</a>
        <a href="../index.php" class="btn btn-sm btn-outline-success me-2 mb-2"><i class="fas fa-house me-1"></i> Welcome Page</a>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"> <?php echo $success; ?> </div>
    <?php elseif ($error): ?>
        <div class="alert alert-danger"> <?php echo $error; ?> </div>
    <?php endif; ?>
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card card-stat p-3 mb-2">
                <h6>Total Users</h6>
                <h3><?php echo $total_users; ?></h3>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card card-stat p-3 mb-2">
                <h6>Providers</h6>
                <h3><?php echo $total_providers; ?></h3>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card card-stat p-3 mb-2">
                <h6>Customers</h6>
                <h3><?php echo $total_customers; ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-stat p-3 mb-2">
                <h6>Active Services</h6>
                <h3><?php echo $total_services; ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-stat p-3 mb-2">
                <h6>Service Requests</h6>
                <h3><?php echo $total_requests; ?></h3>
            </div>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-md-4 mb-2">
            <a href="?export=users" class="btn btn-outline-primary w-100">Export Users CSV</a>
        </div>
        <div class="col-md-4 mb-2">
            <a href="?export=services" class="btn btn-outline-primary w-100">Export Services CSV</a>
        </div>
        <div class="col-md-4 mb-2">
            <a href="?export=requests" class="btn btn-outline-primary w-100">Export Requests CSV</a>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-md-4 mb-2">
            <a href="provider_verification.php" class="btn btn-warning w-100"><i class="fas fa-id-badge me-2"></i>Provider Verification</a>
        </div>
    </div>
    <?php if (count($pending_services) > 0): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-warning"><strong>Pending Services (Approval Required)</strong></div>
                <div class="card-body p-0">
                    <table class="table mb-0">
                        <thead><tr><th>Name</th><th>Description</th><th>Actions</th></tr></thead>
                        <tbody>
                        <?php foreach ($pending_services as $ps): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($ps['name']); ?></td>
                                <td><?php echo htmlspecialchars($ps['description']); ?></td>
                                <td>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="service_id" value="<?php echo $ps['id']; ?>">
                                        <button type="submit" name="approve_service" class="btn btn-sm btn-success">Approve</button>
                                    </form>
                                    <form method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to reject and delete this service?');">
                                        <input type="hidden" name="service_id" value="<?php echo $ps['id']; ?>">
                                        <button type="submit" name="reject_service" class="btn btn-sm btn-danger">Reject</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <div class="row mb-5">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header"><strong>Analytics</strong></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-4">
                            <canvas id="userGrowthChart"></canvas>
                        </div>
                        <div class="col-md-4 mb-4">
                            <canvas id="serviceUsageChart"></canvas>
                        </div>
                        <div class="col-md-4 mb-4">
                            <canvas id="requestTrendsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>Recent Users</strong>
                    <a href="users.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <?php foreach (array_slice($users, 0, 5) as $u): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><?php echo htmlspecialchars($u['first_name'] . ' ' . $u['last_name']); ?> (<?php echo ucfirst($u['user_type']); ?>)</span>
                                <span class="badge bg-<?php echo $u['is_active'] ? 'success' : 'secondary'; ?>"><?php echo $u['is_active'] ? 'Active' : 'Inactive'; ?></span>
                            </li>
                        <?php endforeach; ?>
                        <?php if (empty($users)): ?>
                            <li class="list-group-item text-muted">No recent users.</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>Recent Service Requests</strong>
                    <a href="requests.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <?php foreach (array_slice($requests, 0, 5) as $r): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><?php echo htmlspecialchars($r['first_name'] . ' ' . $r['last_name']); ?> - <?php echo htmlspecialchars($r['title']); ?></span>
                                <span class="badge bg-info text-dark"><?php echo ucfirst($r['status']); ?></span>
                            </li>
                        <?php endforeach; ?>
                        <?php if (empty($requests)): ?>
                            <li class="list-group-item text-muted">No recent requests.</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center bg-secondary text-white">
                    <strong>Recent Audit Logs</strong>
                    <a href="audits.php" class="btn btn-sm btn-light">View All</a>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php foreach (array_slice($audit_logs, 0, 5) as $log): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><?php echo htmlspecialchars($log['first_name'] . ' ' . $log['last_name']); ?> - <?php echo htmlspecialchars($log['action']); ?></span>
                                <small class="text-muted"><?php echo date('M j, Y g:i a', strtotime($log['created_at'])); ?></small>
                            </li>
                        <?php endforeach; ?>
                        <?php if (empty($audit_logs)): ?>
                            <li class="list-group-item text-muted">No recent audit logs.</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <div class="text-center mt-4">
        <a href="../dashboard.php" class="btn btn-outline-primary">Back to User Dashboard</a>
    </div>
</div>
<!-- Add Bootstrap JS for modal support -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// User Growth Data
const userGrowthLabels = <?php echo json_encode(array_column($user_growth, 'month')); ?>;
const userGrowthData = <?php echo json_encode(array_column($user_growth, 'count')); ?>;
// Service Usage Data
const serviceUsageLabels = <?php echo json_encode(array_column($service_usage, 'name')); ?>;
const serviceUsageData = <?php echo json_encode(array_column($service_usage, 'count')); ?>;
// Request Trends Data
const requestTrendsLabels = <?php echo json_encode(array_column($request_trends, 'month')); ?>;
const requestTrendsData = <?php echo json_encode(array_column($request_trends, 'count')); ?>;

// User Growth Chart
new Chart(document.getElementById('userGrowthChart'), {
    type: 'line',
    data: {
        labels: userGrowthLabels,
        datasets: [{
            label: 'User Registrations',
            data: userGrowthData,
            borderColor: '#007bff',
            backgroundColor: 'rgba(0,123,255,0.1)',
            fill: true
        }]
    },
    options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
});
// Service Usage Chart
new Chart(document.getElementById('serviceUsageChart'), {
    type: 'bar',
    data: {
        labels: serviceUsageLabels,
        datasets: [{
            label: 'Providers per Service',
            data: serviceUsageData,
            backgroundColor: '#007bff'
        }]
    },
    options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
});
// Request Trends Chart
new Chart(document.getElementById('requestTrendsChart'), {
    type: 'line',
    data: {
        labels: requestTrendsLabels,
        datasets: [{
            label: 'Service Requests',
            data: requestTrendsData,
            borderColor: '#28a745',
            backgroundColor: 'rgba(40,167,69,0.1)',
            fill: true
        }]
    },
    options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
});
</script>
</body>
</html> 