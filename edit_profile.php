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
$user_type = $_SESSION['user_type'];

// Fetch user data
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch provider data if provider
$provider = null;
if ($user_type === 'provider') {
    $stmt = $db->prepare("SELECT * FROM service_providers WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $provider = $stmt->fetch(PDO::FETCH_ASSOC);
}

$success = $error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $region = trim($_POST['region']);
    $profile_image = $user['profile_image'];

    // Handle profile image upload
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $img_name = uniqid('profile_') . '_' . basename($_FILES['profile_image']['name']);
        $img_path = 'assets/images/' . $img_name;
        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $img_path)) {
            $profile_image = $img_path;
        } else {
            $error = 'Failed to upload profile image.';
        }
    }

    // Update users table
    if (!$error) {
        $stmt = $db->prepare("UPDATE users SET first_name=?, last_name=?, email=?, phone=?, address=?, city=?, region=?, profile_image=? WHERE id=?");
        $ok = $stmt->execute([$first_name, $last_name, $email, $phone, $address, $city, $region, $profile_image, $user_id]);
        if ($ok) {
            // Update provider-specific fields
            if ($user_type === 'provider') {
                $business_name = trim($_POST['business_name']);
                $business_description = trim($_POST['business_description']);
                $experience_years = intval($_POST['experience_years']);
                $hourly_rate = floatval($_POST['hourly_rate']);
                $daily_rate = floatval($_POST['daily_rate']);
                $stmt2 = $db->prepare("UPDATE service_providers SET business_name=?, business_description=?, experience_years=?, hourly_rate=?, daily_rate=? WHERE user_id=?");
                $stmt2->execute([$business_name, $business_description, $experience_years, $hourly_rate, $daily_rate, $user_id]);
            }
            $success = 'Profile updated successfully!';
            // Refresh user data
            $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user_type === 'provider') {
                $stmt = $db->prepare("SELECT * FROM service_providers WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $provider = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        } else {
            $error = 'Failed to update profile.';
        }
    }
}

$primary = '#007bff';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile | ServiGo</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body { background: #f8f9fa; }
        .profile-img { width: 120px; height: 120px; object-fit: cover; border-radius: 50%; border: 3px solid <?php echo $primary; ?>; }
        .form-section { background: #fff; border-radius: 10px; box-shadow: 0 2px 12px rgba(0,0,0,0.07); padding: 2rem; }
        .btn-primary { background: <?php echo $primary; ?>; border: none; }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="form-section">
                <h2 class="mb-4 text-center" style="color: <?php echo $primary; ?>;">Edit Profile</h2>
                <?php if ($success): ?>
                    <div class="alert alert-success"> <?php echo $success; ?> </div>
                <?php elseif ($error): ?>
                    <div class="alert alert-danger"> <?php echo $error; ?> </div>
                <?php endif; ?>
                <form method="post" enctype="multipart/form-data">
                    <div class="text-center mb-4">
                        <img src="<?php echo $user['profile_image'] ? htmlspecialchars($user['profile_image']) : 'assets/images/default-profile.png'; ?>" class="profile-img" alt="Profile Image">
                        <div class="mt-2">
                            <input type="file" name="profile_image" accept="image/*" class="form-control-file">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>First Name</label>
                            <input type="text" name="first_name" class="form-control" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Last Name</label>
                            <input type="text" name="last_name" class="form-control" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Phone</label>
                            <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label>Address</label>
                        <input type="text" name="address" class="form-control" value="<?php echo htmlspecialchars($user['address']); ?>">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>City</label>
                            <input type="text" name="city" class="form-control" value="<?php echo htmlspecialchars($user['city']); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Region</label>
                            <input type="text" name="region" class="form-control" value="<?php echo htmlspecialchars($user['region']); ?>">
                        </div>
                    </div>
                    <?php if ($user_type === 'provider' && $provider): ?>
                    <hr>
                    <h5 class="mb-3">Provider Details</h5>
                    <div class="mb-3">
                        <label>Business Name</label>
                        <input type="text" name="business_name" class="form-control" value="<?php echo htmlspecialchars($provider['business_name']); ?>">
                    </div>
                    <div class="mb-3">
                        <label>Business Description</label>
                        <textarea name="business_description" class="form-control" rows="3"><?php echo htmlspecialchars($provider['business_description']); ?></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label>Experience (years)</label>
                            <input type="number" name="experience_years" class="form-control" value="<?php echo htmlspecialchars($provider['experience_years']); ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Hourly Rate (FCFA)</label>
                            <input type="number" step="0.01" name="hourly_rate" class="form-control" value="<?php echo htmlspecialchars($provider['hourly_rate']); ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Daily Rate (FCFA)</label>
                            <input type="number" step="0.01" name="daily_rate" class="form-control" value="<?php echo htmlspecialchars($provider['daily_rate']); ?>">
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="text-center mt-4">
                        <button type="submit" class="btn btn-primary px-5">Save Changes</button>
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