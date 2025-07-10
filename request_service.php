<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$db = getDB();
$user_id = $_SESSION['user_id'];
$success = $error = '';

// Fetch service categories
$categories = get_service_categories();

// Fetch providers
$providers = [];
$stmt = $db->prepare("
    SELECT sp.id, sp.business_name, u.first_name, u.last_name, u.city, sp.rating
    FROM service_providers sp
    JOIN users u ON sp.user_id = u.id
    WHERE u.is_active = 1 AND sp.is_available = 1
    ORDER BY sp.rating DESC, sp.business_name
");
$stmt->execute();
$providers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $provider_id = intval($_POST['provider_id']);
    $category_id = intval($_POST['category_id']);
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $location = trim($_POST['location']);
    $budget = floatval($_POST['budget']);
    $urgency = $_POST['urgency'];
    $scheduled_date = $_POST['scheduled_date'];
    $scheduled_time = $_POST['scheduled_time'];

    if ($provider_id && $category_id && $title && $description) {
        $stmt = $db->prepare("
            INSERT INTO service_requests (customer_id, provider_id, category_id, title, description, location, budget, urgency, scheduled_date, scheduled_time, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
        ");
        if ($stmt->execute([$user_id, $provider_id, $category_id, $title, $description, $location, $budget, $urgency, $scheduled_date, $scheduled_time])) {
            $success = 'Service request submitted successfully! The provider will be notified.';
        } else {
            $error = 'Failed to submit service request. Please try again.';
        }
    } else {
        $error = 'Please fill in all required fields.';
    }
}

$primary = '#007bff';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Service | ServiGo</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body { background: #f8f9fa; }
        .request-form { background: #fff; border-radius: 10px; box-shadow: 0 2px 12px rgba(0,0,0,0.07); padding: 2rem; }
        .btn-primary { background: <?php echo $primary; ?>; border: none; }
        .form-section { border: 1px solid #e3e3e3; border-radius: 8px; padding: 1.5rem; margin-bottom: 1.5rem; }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="request-form">
                <h2 class="mb-4 text-center" style="color: <?php echo $primary; ?>;">Request a Service</h2>
                <?php if ($success): ?>
                    <div class="alert alert-success"> <?php echo $success; ?> </div>
                <?php elseif ($error): ?>
                    <div class="alert alert-danger"> <?php echo $error; ?> </div>
                <?php endif; ?>
                <form method="post">
                    <div class="form-section">
                        <h5 class="mb-3">Service Details</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label>Service Provider *</label>
                                <select name="provider_id" class="form-control" required>
                                    <option value="">Select a provider</option>
                                    <?php foreach ($providers as $prov): ?>
                                        <option value="<?php echo $prov['id']; ?>">
                                            <?php echo htmlspecialchars($prov['first_name'] . ' ' . $prov['last_name']); ?>
                                            <?php if ($prov['business_name']): ?>
                                                (<?php echo htmlspecialchars($prov['business_name']); ?>)
                                            <?php endif; ?>
                                            - <?php echo htmlspecialchars($prov['city']); ?>
                                            - Rating: <?php echo htmlspecialchars($prov['rating']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Service Category *</label>
                                <select name="category_id" class="form-control" required>
                                    <option value="">Select a service</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label>Request Title *</label>
                                <input type="text" name="title" class="form-control" placeholder="Brief title for your request" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Urgency Level</label>
                                <select name="urgency" class="form-control">
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-section">
                        <h5 class="mb-3">Request Details</h5>
                        <div class="mb-3">
                            <label>Description *</label>
                            <textarea name="description" class="form-control" rows="4" placeholder="Describe your service needs in detail..." required></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label>Location</label>
                                <input type="text" name="location" class="form-control" placeholder="Service location">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Budget (FCFA)</label>
                                <input type="number" name="budget" class="form-control" placeholder="Your budget">
                            </div>
                        </div>
                    </div>
                    <div class="form-section">
                        <h5 class="mb-3">Schedule (Optional)</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label>Preferred Date</label>
                                <input type="date" name="scheduled_date" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Preferred Time</label>
                                <input type="time" name="scheduled_time" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="text-center mt-4">
                        <button type="submit" class="btn btn-primary px-5">Submit Request</button>
                        <a href="dashboard.php" class="btn btn-outline-secondary ms-2">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 