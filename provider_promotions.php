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

// Create table if not exists (for demo/dev)
$db->exec("CREATE TABLE IF NOT EXISTS provider_promotions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    provider_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    discount_percent DECIMAL(5,2),
    valid_until DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (provider_id) REFERENCES service_providers(id) ON DELETE CASCADE
)");

// Handle add promotion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_promo'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $discount_percent = floatval($_POST['discount_percent']);
    $valid_until = $_POST['valid_until'];
    if ($title && $discount_percent > 0) {
        $stmt = $db->prepare("INSERT INTO provider_promotions (provider_id, title, description, discount_percent, valid_until) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$provider_id, $title, $description, $discount_percent, $valid_until]);
    }
    header('Location: provider_promotions.php');
    exit();
}
// Handle remove promotion
if (isset($_GET['remove'])) {
    $promo_id = intval($_GET['remove']);
    $stmt = $db->prepare("DELETE FROM provider_promotions WHERE id = ? AND provider_id = ?");
    $stmt->execute([$promo_id, $provider_id]);
    header('Location: provider_promotions.php');
    exit();
}
// Fetch promotions
$stmt = $db->prepare("SELECT * FROM provider_promotions WHERE provider_id = ? ORDER BY created_at DESC");
$stmt->execute([$provider_id]);
$promotions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Promotions & Discounts | Provider</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container py-5">
    <h2 class="mb-4 text-center">Promotions & Discounts</h2>
    <div class="mb-3 text-end">
        <a href="provider_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="mb-3">Add New Promotion</h5>
            <form method="post" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="title" class="form-control" placeholder="Title" required>
                </div>
                <div class="col-md-4">
                    <input type="number" name="discount_percent" class="form-control" placeholder="Discount (%)" min="1" max="100" step="0.01" required>
                </div>
                <div class="col-md-4">
                    <input type="date" name="valid_until" class="form-control" placeholder="Valid Until">
                </div>
                <div class="col-12">
                    <textarea name="description" class="form-control" placeholder="Description" rows="2"></textarea>
                </div>
                <div class="col-12 text-end">
                    <button type="submit" name="add_promo" class="btn btn-primary">Add Promotion</button>
                </div>
            </form>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <h5 class="mb-3">My Promotions</h5>
            <?php if (empty($promotions)): ?>
                <div class="text-center text-muted">No promotions found.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="thead-light">
                            <tr>
                                <th>Title</th>
                                <th>Description</th>
                                <th>Discount (%)</th>
                                <th>Valid Until</th>
                                <th>Created</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($promotions as $promo): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($promo['title']); ?></td>
                                <td><?php echo htmlspecialchars($promo['description']); ?></td>
                                <td><?php echo $promo['discount_percent']; ?></td>
                                <td><?php echo htmlspecialchars($promo['valid_until']); ?></td>
                                <td><?php echo format_datetime($promo['created_at']); ?></td>
                                <td><a href="provider_promotions.php?remove=<?php echo $promo['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Remove this promotion?')">Remove</a></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html> 