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

// Ensure payout_requests table exists (idempotent)
$db->exec("CREATE TABLE IF NOT EXISTS payout_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    provider_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    method VARCHAR(50) DEFAULT 'mobile_money',
    status ENUM('pending','approved','rejected','paid') DEFAULT 'pending',
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,
    FOREIGN KEY (provider_id) REFERENCES service_providers(id) ON DELETE CASCADE
)");

// Get all completed payments for this provider
$stmt = $db->prepare("
    SELECT p.*, sr.title as request_title, sr.created_at as request_date
    FROM payments p
    JOIN service_requests sr ON p.request_id = sr.id
    WHERE sr.provider_id = ? AND p.status = 'completed'
    ORDER BY p.created_at DESC
");
$stmt->execute([$provider_id]);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$total_earned = 0.0;
foreach ($payments as $pay) {
    $total_earned += (float)$pay['amount'];
}

// Sum approved/paid payouts
$stmt = $db->prepare("SELECT COALESCE(SUM(amount),0) FROM payout_requests WHERE provider_id = ? AND status IN ('approved','paid')");
$stmt->execute([$provider_id]);
$total_paid_out = (float)$stmt->fetchColumn();

$available_balance = max(0.0, $total_earned - $total_paid_out);

$success = $error = '';

// Handle payout request submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_payout'])) {
    $amount = (float)($_POST['amount'] ?? 0);
    $method = trim($_POST['method'] ?? 'mobile_money');
    $notes  = trim($_POST['notes'] ?? '');

    if ($amount <= 0) {
        $error = 'Please enter a valid payout amount.';
    } elseif ($amount > $available_balance) {
        $error = 'Requested amount exceeds available balance.';
    } else {
        $stmt = $db->prepare("INSERT INTO payout_requests (provider_id, amount, method, notes) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$provider_id, $amount, $method, $notes])) {
            // Notify admin (first admin user)
            $adminStmt = $db->query("SELECT id FROM users WHERE user_type = 'admin' ORDER BY id LIMIT 1");
            $adminId = (int)($adminStmt->fetchColumn() ?: 0);
            if ($adminId) {
                create_notification($adminId, 'New payout request', 'A provider requested a payout of FCFA ' . number_format($amount, 0, ',', ' '), 'system');
            }
            $success = 'Payout request submitted successfully.';
            // Recalculate available balance after request (optional: keep same until approval)
        } else {
            $error = 'Failed to submit payout request. Please try again.';
        }
    }
}

// Fetch payout history
$stmt = $db->prepare("SELECT * FROM payout_requests WHERE provider_id = ? ORDER BY created_at DESC");
$stmt->execute([$provider_id]);
$payouts = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Earnings & Payouts | Provider</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container py-5">
    <h2 class="mb-4 text-center">Earnings & Payouts</h2>
    <div class="mb-3 text-end">
        <a href="provider_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

    <div class="row">
        <div class="col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-1">Total Earned</h6>
                    <h3 class="text-success">FCFA <?= number_format($total_earned, 0, ',', ' ') ?></h3>
                    <hr>
                    <h6 class="text-muted mb-1">Paid Out</h6>
                    <h4 class="text-primary">FCFA <?= number_format($total_paid_out, 0, ',', ' ') ?></h4>
                    <hr>
                    <h6 class="text-muted mb-1">Available Balance</h6>
                    <h3>FCFA <?= number_format($available_balance, 0, ',', ' ') ?></h3>
                </div>
            </div>
        </div>
        <div class="col-lg-8 mb-4">
            <div class="card h-100">
                <div class="card-header">Request Payout</div>
                <div class="card-body">
                    <form method="post">
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label>Amount (FCFA)</label>
                                <input type="number" name="amount" class="form-control" min="1000" step="100" max="<?= (int)$available_balance ?>" placeholder="Enter amount" required>
                            </div>
                            <div class="form-group col-md-4">
                                <label>Method</label>
                                <select name="method" class="form-control">
                                    <option value="mobile_money">Mobile Money</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="cash">Cash</option>
                                </select>
                            </div>
                            <div class="form-group col-md-12 mt-2">
                                <label>Notes (optional)</label>
                                <textarea name="notes" class="form-control" rows="2" placeholder="Payment details (e.g., MoMo number, bank info)"></textarea>
                            </div>
                        </div>
                        <button type="submit" name="request_payout" class="btn btn-primary mt-3" <?= $available_balance <= 0 ? 'disabled' : '' ?>>Submit Request</button>
                        <?php if ($available_balance <= 0): ?><small class="text-muted ms-2">No available balance</small><?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">Payment History</div>
        <div class="table-responsive">
            <table class="table table-bordered table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Payment ID</th>
                        <th>Service Request</th>
                        <th>Amount</th>
                        <th>Payment Method</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($payments as $pay): ?>
                    <tr>
                        <td><?= $pay['id'] ?></td>
                        <td><?= htmlspecialchars($pay['request_title']) ?></td>
                        <td>FCFA <?= number_format($pay['amount'], 0, ',', ' ') ?></td>
                        <td><?= ucfirst(str_replace('_', ' ', $pay['payment_method'])) ?></td>
                        <td><span class="badge badge-success">Completed</span></td>
                        <td><?= format_datetime($pay['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">Payout Requests</div>
        <div class="table-responsive">
            <table class="table table-bordered table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Status</th>
                        <th>Requested At</th>
                        <th>Processed At</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($payouts)): ?>
                    <tr><td colspan="7" class="text-center text-muted">No payout requests yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($payouts as $p): ?>
                        <tr>
                            <td><?= $p['id'] ?></td>
                            <td>FCFA <?= number_format($p['amount'], 0, ',', ' ') ?></td>
                            <td><?= htmlspecialchars(ucfirst(str_replace('_',' ', $p['method']))) ?></td>
                            <td><span class="badge badge-<?php
                                switch($p['status']){
                                    case 'pending': echo 'warning'; break;
                                    case 'approved': echo 'primary'; break;
                                    case 'paid': echo 'success'; break;
                                    case 'rejected': echo 'danger'; break;
                                    default: echo 'secondary';
                                }
                            ?>"><?= ucfirst($p['status']) ?></span></td>
                            <td><?= format_datetime($p['created_at']) ?></td>
                            <td><?= $p['processed_at'] ? format_datetime($p['processed_at']) : '-' ?></td>
                            <td><?= htmlspecialchars($p['notes'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html> 