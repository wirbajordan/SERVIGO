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
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body { background: #f8f9fa; }
        .card-stat { border-left: 5px solid <?php echo $primary; ?>; }
        .table thead { background: <?php echo $primary; ?>; color: #fff; }
    </style>
</head>
<body>
<div class="container py-5">
    <h2 class="mb-4 text-center" style="color: <?php echo $primary; ?>;">Admin Dashboard</h2>
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
                <div class="card-header"><strong>Recent Users</strong></div>
                <div class="card-body p-0">
                    <table class="table mb-0">
                        <thead><tr><th>Name</th><th>Email</th><th>Type</th><th>Status</th><th>Actions</th></tr></thead>
                        <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($u['first_name'] . ' ' . $u['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($u['email']); ?></td>
                                <td><?php echo ucfirst($u['user_type']); ?></td>
                                <td><?php echo $u['is_active'] ? 'Active' : 'Inactive'; ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-info" data-toggle="modal" data-target="#viewUserModal<?php echo $u['id']; ?>">View</button>
                                    <!-- View User Modal -->
                                    <div class="modal fade" id="viewUserModal<?php echo $u['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="viewUserModalLabel<?php echo $u['id']; ?>" aria-hidden="true">
                                      <div class="modal-dialog" role="document">
                                        <div class="modal-content">
                                          <div class="modal-header">
                                            <h5 class="modal-title" id="viewUserModalLabel<?php echo $u['id']; ?>">User Details</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                              <span aria-hidden="true">&times;</span>
                                            </button>
                                          </div>
                                          <div class="modal-body">
                                            <ul class="list-group mb-3">
                                              <li class="list-group-item"><strong>Name:</strong> <?php echo htmlspecialchars($u['first_name'] . ' ' . $u['last_name']); ?></li>
                                              <li class="list-group-item"><strong>Email:</strong> <?php echo htmlspecialchars($u['email']); ?></li>
                                              <li class="list-group-item"><strong>Type:</strong> <?php echo ucfirst($u['user_type']); ?></li>
                                              <li class="list-group-item"><strong>Phone:</strong> <?php echo htmlspecialchars($u['phone']); ?></li>
                                              <li class="list-group-item"><strong>City:</strong> <?php echo htmlspecialchars($u['city']); ?></li>
                                              <li class="list-group-item"><strong>Region:</strong> <?php echo htmlspecialchars($u['region']); ?></li>
                                              <li class="list-group-item"><strong>Status:</strong> <?php echo $u['is_active'] ? 'Active' : 'Inactive'; ?></li>
                                              <li class="list-group-item"><strong>Created:</strong> <?php echo htmlspecialchars($u['created_at']); ?></li>
                                            </ul>
                                            <hr>
                                            <h6>Reset Password</h6>
                                            <form method="post">
                                              <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                              <div class="form-group">
                                                <input type="password" name="new_password" class="form-control" placeholder="New password (min 6 chars)" required>
                                              </div>
                                              <button type="submit" name="reset_password" class="btn btn-primary">Reset Password</button>
                                            </form>
                                          </div>
                                        </div>
                                      </div>
                                    </div>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                        <input type="hidden" name="current_status" value="<?php echo $u['is_active']; ?>">
                                        <button type="submit" name="toggle_user_status" class="btn btn-sm btn-<?php echo $u['is_active'] ? 'warning' : 'success'; ?>">
                                            <?php echo $u['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                        </button>
                                    </form>
                                    <form method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                        <button type="submit" name="delete_user" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header"><strong>Recent Services</strong></div>
                <div class="card-body p-0">
                    <table class="table mb-0">
                        <thead><tr><th>Name</th><th>Description</th><th>Status</th><th>Actions</th></tr></thead>
                        <tbody>
                        <?php foreach ($services as $s): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($s['name']); ?></td>
                                <td><?php echo htmlspecialchars($s['description']); ?></td>
                                <td><?php echo $s['is_active'] ? 'Active' : 'Inactive'; ?></td>
                                <td>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="service_id" value="<?php echo $s['id']; ?>">
                                        <input type="hidden" name="current_status" value="<?php echo $s['is_active']; ?>">
                                        <button type="submit" name="toggle_service_status" class="btn btn-sm btn-<?php echo $s['is_active'] ? 'warning' : 'success'; ?>">
                                            <?php echo $s['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                        </button>
                                    </form>
                                    <!-- Edit button triggers modal -->
                                    <button type="button" class="btn btn-sm btn-info" data-toggle="modal" data-target="#editServiceModal<?php echo $s['id']; ?>">Edit</button>
                                    <form method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this service?');">
                                        <input type="hidden" name="service_id" value="<?php echo $s['id']; ?>">
                                        <button type="submit" name="delete_service" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                    <!-- Edit Modal -->
                                    <div class="modal fade" id="editServiceModal<?php echo $s['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="editServiceModalLabel<?php echo $s['id']; ?>" aria-hidden="true">
                                      <div class="modal-dialog" role="document">
                                        <div class="modal-content">
                                          <form method="post">
                                            <div class="modal-header">
                                              <h5 class="modal-title" id="editServiceModalLabel<?php echo $s['id']; ?>">Edit Service</h5>
                                              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                              </button>
                                            </div>
                                            <div class="modal-body">
                                              <input type="hidden" name="service_id" value="<?php echo $s['id']; ?>">
                                              <div class="form-group">
                                                <label>Name</label>
                                                <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($s['name']); ?>" required>
                                              </div>
                                              <div class="form-group">
                                                <label>Description</label>
                                                <textarea name="description" class="form-control" required><?php echo htmlspecialchars($s['description']); ?></textarea>
                                              </div>
                                            </div>
                                            <div class="modal-footer">
                                              <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                              <button type="submit" name="edit_service" class="btn btn-primary">Save Changes</button>
                                            </div>
                                          </form>
                                        </div>
                                      </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header"><strong>Recent Service Requests</strong></div>
                <div class="card-body p-0">
                    <table class="table mb-0">
                        <thead><tr><th>Customer</th><th>Title</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
                        <tbody>
                        <?php foreach ($requests as $r): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($r['first_name'] . ' ' . $r['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($r['title']); ?></td>
                                <td><?php echo ucfirst($r['status']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($r['created_at'])); ?></td>
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
                                    <form method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this request?');">
                                        <input type="hidden" name="request_id" value="<?php echo $r['id']; ?>">
                                        <button type="submit" name="delete_request" class="btn btn-sm btn-danger">Delete</button>
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
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-secondary text-white"><strong>Recent Audit Logs</strong></div>
                <div class="card-body p-0">
                    <table class="table mb-0">
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
                        </tbody>
                    </table>
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