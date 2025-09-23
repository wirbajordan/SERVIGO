<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Redirect admins and providers to their respective dashboards
// Admins can bypass this redirect with ?main=1 to view the generic dashboard
$bypassMain = isset($_GET['main']) && $_GET['main'] == '1';
if (isset($_SESSION['user_type'])) {
    if ($_SESSION['user_type'] === 'admin' && !$bypassMain) {
        header('Location: admin/dashboard.php');
        exit();
    } elseif ($_SESSION['user_type'] === 'provider') {
        header('Location: provider_dashboard.php');
        exit();
    }
}

$db = getDB();
$user_id = $_SESSION['user_id'];

// Fetch customer profile
$stmtUser = $db->prepare('SELECT first_name, last_name, city, address FROM users WHERE id = ?');
$stmtUser->execute([$user_id]);
$customer = $stmtUser->fetch() ?: ['first_name' => '', 'last_name' => '', 'city' => '', 'address' => ''];
$customer_city = $customer['city'] ?? '';
$customer_quarter = '';
if (!empty($customer['address'])) {
    $parts = explode(',', $customer['address']);
    $customer_quarter = trim($parts[0]);
}

// Stats
$pending_requests = get_user_requests($user_id, 'pending');
$in_progress_requests = get_user_requests($user_id, 'in_progress');
$completed_requests = get_user_requests($user_id, 'completed');
$unread_notifications = get_unread_notifications_count($user_id);

// Recent bookings (last 5)
$stmt = $db->prepare("\n    SELECT sr.*, sc.name AS category_name, u.first_name, u.last_name\n    FROM service_requests sr\n    JOIN service_categories sc ON sr.category_id = sc.id\n    JOIN users u ON sr.provider_id = u.id\n    WHERE sr.customer_id = ?\n    ORDER BY sr.created_at DESC\n    LIMIT 5\n");
$stmt->execute([$user_id]);
$recent_requests = $stmt->fetchAll();

// Suggested nearby providers (top 6)
$sqlProviders = "\n    SELECT sp.id, sp.business_name, sp.rating, u.first_name, u.last_name, u.city, u.address\n    FROM service_providers sp\n    JOIN users u ON sp.user_id = u.id\n    WHERE u.is_active = 1 AND sp.is_available = 1\n";
$params = [];
if ($customer_city !== '') {
    $sqlProviders .= " AND u.city = ?";
    $params[] = $customer_city;
}
$sqlProviders .= " ORDER BY sp.rating DESC, sp.business_name LIMIT 6";
$stmt = $db->prepare($sqlProviders);
$stmt->execute($params);
$suggested_providers = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer Dashboard | ServiGo</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include 'includes/site_header.php'; ?>
<div class="container py-5">
    <h2 class="mb-2 text-center">Welcome, <?php echo htmlspecialchars(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '')); ?></h2>
    <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'customer'): ?>
        <div class="text-center mb-4">
            <a href="customer_dashboard.php" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-arrow-left me-1"></i> Go to Customer Dashboard (with menu)
            </a>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin' && $bypassMain): ?>
        <div class="text-center mb-4">
            <a href="admin/dashboard.php" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to Admin Dashboard
            </a>
        </div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card text-center h-100">
                <div class="card-body">
                    <h6>Pending Bookings</h6>
                    <h2><?php echo count($pending_requests); ?></h2>
                    <a href="customer_requests.php" class="small">View all</a>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-center h-100">
                <div class="card-body">
                    <h6>In Progress</h6>
                    <h2><?php echo count($in_progress_requests); ?></h2>
                    <a href="customer_requests.php?status=in_progress" class="small">Track</a>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-center h-100">
                <div class="card-body">
                    <h6>Completed</h6>
                    <h2><?php echo count($completed_requests); ?></h2>
                    <a href="customer_requests.php?status=completed" class="small">History</a>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-center h-100">
                <div class="card-body">
                    <h6>Notifications</h6>
                    <h2><?php echo (int)$unread_notifications; ?></h2>
                    <a href="notifications.php" class="small">Open inbox</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <a href="request_service.php" class="text-decoration-none">
                <div class="card h-100 p-4 text-center">
                    <i class="fas fa-calendar-check fa-2x mb-3 text-primary"></i>
                    <div class="fw-semibold">Book a Service</div>
                    <div class="text-muted small">Request and schedule a provider</div>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="messages.php" class="text-decoration-none">
                <div class="card h-100 p-4 text-center">
                    <i class="fas fa-comments fa-2x mb-3 text-primary"></i>
                    <div class="fw-semibold">Messages</div>
                    <div class="text-muted small">Chat with providers</div>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="edit_profile.php" class="text-decoration-none">
                <div class="card h-100 p-4 text-center">
                    <i class="fas fa-user fa-2x mb-3 text-primary"></i>
                    <div class="fw-semibold">Edit Profile</div>
                    <div class="text-muted small">Update your details</div>
                </div>
            </a>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Recent Bookings</span>
            <a href="customer_requests.php" class="btn btn-sm btn-outline-secondary">See all</a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Service</th>
                        <th>Provider</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent_requests)): ?>
                        <tr><td colspan="5" class="text-center text-muted">No bookings yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recent_requests as $r): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($r['title']); ?></td>
                                <td><?php echo htmlspecialchars($r['category_name']); ?></td>
                                <td><?php echo htmlspecialchars($r['first_name'] . ' ' . $r['last_name']); ?></td>
                                <td><span class="badge <?php
                                    switch($r['status']){
                                        case 'pending': echo 'bg-warning'; break;
                                        case 'accepted': echo 'bg-primary'; break;
                                        case 'in_progress': echo 'bg-info text-dark'; break;
                                        case 'completed': echo 'bg-success'; break;
                                        case 'cancelled': echo 'bg-secondary'; break;
                                        case 'declined': echo 'bg-danger'; break;
                                        default: echo 'bg-light text-dark';
                                    }
                                ?>"><?php echo ucfirst($r['status']); ?></span></td>
                                <td><?php echo format_datetime($r['created_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Suggested Providers Near You<?php echo $customer_city ? ' — ' . htmlspecialchars($customer_city) : ''; ?></span>
            <a href="providers.php" class="btn btn-sm btn-outline-secondary">Browse all</a>
        </div>
        <div class="card-body">
            <div class="row">
                <?php if (empty($suggested_providers)): ?>
                    <div class="col-12 text-center text-muted">No providers to suggest yet.</div>
                <?php else: ?>
                    <?php foreach ($suggested_providers as $sp): ?>
                        <div class="col-md-4 mb-3">
                            <div class="border rounded p-3 h-100">
                                <div class="fw-semibold mb-1"><?php echo htmlspecialchars(($sp['first_name'] ?? '') . ' ' . ($sp['last_name'] ?? '')); ?></div>
                                <div class="text-muted small mb-1"><?php echo htmlspecialchars($sp['business_name'] ?? ''); ?></div>
                                <div class="small mb-1"><?php echo htmlspecialchars($sp['address'] ?: $sp['city']); ?></div>
                                <div class="small mb-2">Rating: <?php echo number_format((float)$sp['rating'], 1); ?></div>
                                <a class="btn btn-sm btn-outline-primary" href="request_service.php">Book</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 