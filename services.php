<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

$db = getDB();
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Fetch service categories (with optional search)
if ($search) {
    $stmt = $db->prepare("SELECT * FROM service_categories WHERE is_active = 1 AND name LIKE ? ORDER BY name");
    $stmt->execute(['%' . $search . '%']);
} else {
    $stmt = $db->prepare("SELECT * FROM service_categories WHERE is_active = 1 ORDER BY name");
    $stmt->execute();
}
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$primary = '#007bff';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services | ServiGo</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body { background: #f8f9fa; }
        .service-card { border: 1px solid #e3e3e3; border-radius: 8px; transition: box-shadow 0.2s; }
        .service-card:hover { box-shadow: 0 2px 12px rgba(0,0,0,0.08); }
        .service-icon { font-size: 2.5rem; color: <?php echo $primary; ?>; }
        .search-bar { max-width: 400px; margin: 0 auto 2rem auto; }
    </style>
</head>
<body>
<div class="container py-5">
    <h2 class="mb-4 text-center" style="color: <?php echo $primary; ?>;">Available Services</h2>
    <form method="get" class="search-bar mb-4">
        <div class="input-group">
            <input type="text" name="search" class="form-control" placeholder="Search services..." value="<?php echo htmlspecialchars($search); ?>">
            <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
        </div>
    </form>
    <div class="row">
        <?php if (count($categories) > 0): ?>
            <?php foreach ($categories as $cat): ?>
            <div class="col-md-4 mb-4">
                <div class="service-card p-4 h-100 d-flex flex-column justify-content-between">
                    <div>
                        <div class="service-icon mb-3"><i class="<?php echo htmlspecialchars($cat['icon']); ?>"></i></div>
                        <h5><?php echo htmlspecialchars($cat['name']); ?></h5>
                        <p class="text-muted"><?php echo htmlspecialchars($cat['description']); ?></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center">
                <div class="alert alert-info">No services found.</div>
            </div>
        <?php endif; ?>
    </div>
    <div class="text-center mt-4">
        <a href="index.php" class="btn btn-outline-primary">Back to Home</a>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 