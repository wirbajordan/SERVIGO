<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

$db = getDB();

// Fetch filter options
$categories = get_service_categories();
$cities = $db->query("SELECT DISTINCT city FROM users WHERE user_type = 'provider' AND city IS NOT NULL AND city != '' ORDER BY city")->fetchAll(PDO::FETCH_COLUMN);

// Get filters from query
$filter_category = isset($_GET['category']) ? intval($_GET['category']) : 0;
$filter_city = isset($_GET['city']) ? trim($_GET['city']) : '';

// Build provider query
$query = "
    SELECT sp.*, u.first_name, u.last_name, u.city, u.region, u.profile_image, u.email, u.phone, sp.rating, sp.business_name
    FROM service_providers sp
    JOIN users u ON sp.user_id = u.id
    WHERE u.user_type = 'provider' AND u.is_active = 1
";
$params = [];
if ($filter_city) {
    $query .= " AND u.city = ? ";
    $params[] = $filter_city;
}
if ($filter_category) {
    $query .= " AND sp.id IN (SELECT provider_id FROM provider_services WHERE category_id = ? AND is_active = 1) ";
    $params[] = $filter_category;
}
$query .= " ORDER BY sp.rating DESC, sp.business_name, u.first_name";
$stmt = $db->prepare($query);
$stmt->execute($params);
$providers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Helper: get provider's services
function get_provider_services($db, $provider_id) {
    $stmt = $db->prepare("SELECT sc.name FROM provider_services ps JOIN service_categories sc ON ps.category_id = sc.id WHERE ps.provider_id = ? AND ps.is_active = 1");
    $stmt->execute([$provider_id]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

$primary = '#007bff';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Providers | ServiGo</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body { background: #f8f9fa; }
        .provider-card { border: 1px solid #e3e3e3; border-radius: 8px; transition: box-shadow 0.2s; }
        .provider-card:hover { box-shadow: 0 2px 12px rgba(0,0,0,0.08); }
        .profile-img { width: 60px; height: 60px; object-fit: cover; border-radius: 50%; border: 2px solid <?php echo $primary; ?>; }
        .badge-service { background: <?php echo $primary; ?>; color: #fff; margin-right: 0.25rem; }
        .filter-bar { background: #fff; border-radius: 8px; box-shadow: 0 2px 12px rgba(0,0,0,0.04); padding: 1rem 1.5rem; margin-bottom: 2rem; }
    </style>
</head>
<body>
<div class="container py-5">
    <h2 class="mb-4 text-center" style="color: <?php echo $primary; ?>;">Service Providers</h2>
    <form method="get" class="filter-bar mb-4">
        <div class="row align-items-end">
            <div class="col-md-5 mb-2">
                <label>Filter by Service</label>
                <select name="category" class="form-control">
                    <option value="0">All Services</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php if ($filter_category == $cat['id']) echo 'selected'; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-5 mb-2">
                <label>Filter by City</label>
                <select name="city" class="form-control">
                    <option value="">All Cities</option>
                    <?php foreach ($cities as $city): ?>
                        <option value="<?php echo htmlspecialchars($city); ?>" <?php if ($filter_city == $city) echo 'selected'; ?>><?php echo htmlspecialchars($city); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 mb-2 text-end">
                <button class="btn btn-primary w-100" type="submit"><i class="fas fa-filter"></i> Filter</button>
            </div>
        </div>
    </form>
    <div class="row">
        <?php if (count($providers) > 0): ?>
            <?php foreach ($providers as $prov): ?>
            <div class="col-md-4 mb-4">
                <div class="provider-card p-4 h-100 d-flex flex-column justify-content-between">
                    <div class="d-flex align-items-center mb-3">
                        <img src="<?php echo $prov['profile_image'] ? htmlspecialchars($prov['profile_image']) : 'assets/images/default-profile.png'; ?>" class="profile-img me-3" alt="Profile">
                        <div>
                            <h5 class="mb-0"><?php echo htmlspecialchars($prov['first_name'] . ' ' . $prov['last_name']); ?></h5>
                            <small class="text-muted"><?php echo htmlspecialchars($prov['business_name']); ?></small>
                        </div>
                    </div>
                    <div class="mb-2">
                        <span class="fw-bold">City:</span> <?php echo htmlspecialchars($prov['city']); ?>
                        <span class="fw-bold ms-3">Region:</span> <?php echo htmlspecialchars($prov['region']); ?>
                    </div>
                    <div class="mb-2">
                        <span class="fw-bold">Rating:</span> <span class="text-warning"><i class="fas fa-star"></i></span> <?php echo htmlspecialchars($prov['rating']); ?>
                    </div>
                    <div class="mb-2">
                        <span class="fw-bold">Services:</span><br>
                        <?php $prov_services = get_provider_services($db, $prov['id']);
                        foreach ($prov_services as $svc): ?>
                            <span class="badge badge-service"> <?php echo htmlspecialchars($svc); ?> </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center">
                <div class="alert alert-info">No providers found for the selected filters.</div>
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