<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Redirect if not logged in or not a provider
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'provider') {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$db = getDB();

// Get provider_id from service_providers table
$provider_id = null;
$stmt = $db->prepare("SELECT id FROM service_providers WHERE user_id = ?");
$stmt->execute([$user_id]);
$provider_id = $stmt->fetchColumn();

if (!$provider_id) {
    echo '<div class="alert alert-danger">Provider profile not found.</div>';
    exit();
}

// Handle add/remove service
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['category_id'])) {
    $category_id = intval($_POST['category_id']);
    if (isset($_POST['add_service'])) {
        // Add service
        $stmt = $db->prepare("INSERT IGNORE INTO provider_services (provider_id, category_id, is_active) VALUES (?, ?, 1)");
        $stmt->execute([$provider_id, $category_id]);

        // Notify admin of new pending service
        $stmt = $db->prepare("SELECT name FROM service_categories WHERE id = ?");
        $stmt->execute([$category_id]);
        $service_name = $stmt->fetchColumn();
        $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type, for_admin) VALUES (?, ?, ?, ?, 1)");
        $stmt->execute([1, 'New Service Pending Approval', 'Provider ID ' . $provider_id . ' added service: ' . $service_name, 'system']);
    } elseif (isset($_POST['remove_service'])) {
        // Remove service
        $stmt = $db->prepare("DELETE FROM provider_services WHERE provider_id = ? AND category_id = ?");
        $stmt->execute([$provider_id, $category_id]);
    }
    // Refresh to avoid resubmission
    header('Location: provider_services.php');
    exit();
}

// Fetch all service categories
$categories = [];
$stmt = $db->prepare("SELECT id, name, description, icon FROM service_categories WHERE is_active = 1 ORDER BY name");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch provider's current services
$my_services = [];
$stmt = $db->prepare("SELECT category_id FROM provider_services WHERE provider_id = ? AND is_active = 1");
$stmt->execute([$provider_id]);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $my_services[] = $row['category_id'];
}

// Bootstrap primary color (customize as needed)
$primary = '#007bff';     
$secondary = '#343a40';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage My Services | ServiGo</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body { background: #f8f9fa; }
        .service-card { border: 1px solid #e3e3e3; border-radius: 8px; transition: box-shadow 0.2s; }
        .service-card:hover { box-shadow: 0 2px 12px rgba(0,0,0,0.08); }
        .service-icon { font-size: 2rem; color: <?php echo $primary; ?>; }
        .btn-primary { background: <?php echo $primary; ?>; border: none; }
        .btn-secondary { background: <?php echo $secondary; ?>; border: none; }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
<div class="container py-5">
    <h2 class="mb-4 text-center" style="color: <?php echo $primary; ?>;">Manage My Services</h2>
    <div class="row">
        <?php foreach ($categories as $cat): ?>
        <div class="col-md-4 mb-4">
            <div class="service-card p-3 h-100 d-flex flex-column justify-content-between">
                <div>
                    <div class="service-icon mb-2"><i class="<?php echo htmlspecialchars($cat['icon']); ?>"></i></div>
                    <h5><?php echo htmlspecialchars($cat['name']); ?></h5>
                    <p class="text-muted"><?php echo htmlspecialchars($cat['description']); ?></p>
                </div>
                <form method="post" class="mt-3">
                    <input type="hidden" name="category_id" value="<?php echo $cat['id']; ?>">
                    <?php if (in_array($cat['id'], $my_services)): ?>
                        <button type="submit" name="remove_service" class="btn btn-secondary btn-block">Remove</button>
                    <?php else: ?>
                        <button type="submit" name="add_service" class="btn btn-primary btn-block">Add</button>
                    <?php endif; ?>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <div class="text-center mt-4">
        <a href="dashboard.php" class="btn btn-outline-primary">Back to Dashboard</a>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 