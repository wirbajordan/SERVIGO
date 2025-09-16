<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'provider') {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$db = getDB();

// Fetch provider data
$provider = get_provider_data($user_id);
if (!$provider) {
    $provider = [ 'id' => 0, 'business_name' => 'N/A' ];
}
$providerIdForQueries = (int)($provider['id'] ?? 0);

$pending_requests = $providerIdForQueries > 0 ? get_provider_requests($providerIdForQueries, 'pending') : [];
$completed_requests = $providerIdForQueries > 0 ? get_provider_requests($providerIdForQueries, 'completed') : [];
$earnings = $db->prepare("SELECT SUM(amount) FROM payments WHERE request_id IN (SELECT id FROM service_requests WHERE provider_id = ?) AND status = 'completed'");
$earnings->execute([$providerIdForQueries]);
$total_earnings = $earnings->fetchColumn() ?: 0;
$average_rating = $providerIdForQueries > 0 ? calculate_average_rating($providerIdForQueries) : 0;

// Fetch user verification status
$stmtVerified = $db->prepare('SELECT is_verified FROM users WHERE id = ?');
$stmtVerified->execute([$user_id]);
$userRow = $stmtVerified->fetch(PDO::FETCH_ASSOC) ?: [];
$is_verified_user = (bool)($userRow['is_verified'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Provider Dashboard | ServiGo</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include 'includes/site_header.php'; ?>
<div class="container py-4">
    <div class="row">
        <div class="col-lg-3 mb-4">
            <div class="card h-100">
                <div class="card-header fw-semibold">Provider Menu</div>
                <div class="list-group list-group-flush">
                    <a href="provider_dashboard.php" class="list-group-item list-group-item-action d-flex align-items-center"><i class="fas fa-gauge-high me-2"></i>Overview</a>
                    <a href="provider_services.php" class="list-group-item list-group-item-action d-flex align-items-center"><i class="fas fa-tools me-2"></i>My Services</a>
                    <a href="provider_requests.php" class="list-group-item list-group-item-action d-flex align-items-center"><i class="fas fa-clipboard-list me-2"></i>Requests</a>
                    <a href="provider_document_upload.php" class="list-group-item list-group-item-action d-flex align-items-center"><i class="fas fa-id-card me-2"></i>Verification</a>
                    <a href="messages.php" class="list-group-item list-group-item-action d-flex align-items-center"><i class="fas fa-comments me-2"></i>Messages</a>
                    <a href="notifications.php" class="list-group-item list-group-item-action d-flex align-items-center"><i class="fas fa-bell me-2"></i>Notifications</a>
                    <div class="border-top"></div>
                    <a href="dashboard.php" class="list-group-item list-group-item-action d-flex align-items-center"><i class="fas fa-home me-2"></i>Main Dashboard</a>
                    <a href="logout.php" class="list-group-item list-group-item-action d-flex align-items-center text-danger"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
                </div>
            </div>
        </div>
        <div class="col-lg-9">
            <?php if (!$is_verified_user): ?>
            <div class="alert alert-warning d-flex align-items-center mb-4" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <div>
                    <strong>Your account is not yet verified.</strong> Please <a href="provider_document_upload.php" class="alert-link">upload your verification documents</a> to complete the verification process.
                </div>
            </div>
            <?php endif; ?>
            <div class="card mb-4">
                <div class="card-body text-center">
                    <h2 class="mb-3">Welcome, <?php echo htmlspecialchars(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '')); ?>!</h2>
                    <p class="mb-1">Business: <strong><?php echo htmlspecialchars($provider['business_name'] ?? 'N/A'); ?></strong></p>
                    <p class="mb-1">Average Rating: <strong><?php echo $average_rating; ?> / 5</strong></p>
                </div>
            </div>
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5>Pending Requests</h5>
                            <h2><?php echo count($pending_requests); ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5>Completed Jobs</h5>
                            <h2><?php echo count($completed_requests); ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5>Total Earnings</h5>
                            <h2>FCFA <?php echo number_format($total_earnings, 0, ',', ' '); ?></h2>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row g-3">
                <div class="col-md-4">
                    <a href="provider_requests.php" class="text-decoration-none">
                        <div class="card h-100 card-servigo">
                            <div class="card-body d-flex align-items-center">
                                <i class="fas fa-inbox text-primary-servigo me-3"></i>
                                <div>
                                    <div class="fw-semibold">View Requests</div>
                                    <div class="text-muted small">Respond to new jobs</div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="provider_services.php" class="text-decoration-none">
                        <div class="card h-100 card-servigo">
                            <div class="card-body d-flex align-items-center">
                                <i class="fas fa-screwdriver-wrench text-primary-servigo me-3"></i>
                                <div>
                                    <div class="fw-semibold">Manage Services</div>
                                    <div class="text-muted small">Edit offerings & pricing</div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="provider_document_upload.php" class="text-decoration-none">
                        <div class="card h-100 card-servigo">
                            <div class="card-body d-flex align-items-center">
                                <i class="fas fa-id-card text-primary-servigo me-3"></i>
                                <div>
                                    <div class="fw-semibold">Verification</div>
                                    <div class="text-muted small">Upload/track documents</div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="messages.php" class="text-decoration-none">
                        <div class="card h-100 card-servigo">
                            <div class="card-body d-flex align-items-center">
                                <i class="fas fa-comments text-primary-servigo me-3"></i>
                                <div>
                                    <div class="fw-semibold">Messages</div>
                                    <div class="text-muted small">Chat with customers</div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="notifications.php" class="text-decoration-none">
                        <div class="card h-100 card-servigo">
                            <div class="card-body d-flex align-items-center">
                                <i class="fas fa-bell text-primary-servigo me-3"></i>
                                <div>
                                    <div class="fw-semibold">Notifications</div>
                                    <div class="text-muted small">See latest updates</div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="provider_earnings.php" class="text-decoration-none">
                        <div class="card h-100 card-servigo">
                            <div class="card-body d-flex align-items-center">
                                <i class="fas fa-wallet text-primary-servigo me-3"></i>
                                <div>
                                    <div class="fw-semibold">Earnings & Payouts</div>
                                    <div class="text-muted small">Track income</div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 