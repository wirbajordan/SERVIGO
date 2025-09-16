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

    // Provider fields
    $business_name = $user_type === 'provider' ? trim($_POST['business_name']) : null;
    $business_description = $user_type === 'provider' ? trim($_POST['business_description']) : null;
    $experience_years = $user_type === 'provider' ? intval($_POST['experience_years']) : null;

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

    if (!$error) {
        // Update users table
        $stmt = $db->prepare("UPDATE users SET first_name=?, last_name=?, email=?, phone=?, address=?, city=?, region=?, profile_image=? WHERE id=?");
        $stmt->execute([$first_name, $last_name, $email, $phone, $address, $city, $region, $profile_image, $user_id]);

        // Update provider table if provider
        if ($user_type === 'provider') {
            $stmt = $db->prepare("UPDATE service_providers SET business_name=?, business_description=?, experience_years=? WHERE user_id=?");
            $stmt->execute([$business_name, $business_description, $experience_years, $user_id]);
        }
        // Refresh session data for immediate UI update
        $_SESSION['first_name'] = $first_name;
        $_SESSION['last_name'] = $last_name;
        $_SESSION['profile_image'] = $profile_image;
        $success = 'Profile updated successfully!';
        // Refresh data
        header("Location: edit_profile.php?success=1");
        exit();
    }
}
if (isset($_GET['success'])) {
    $success = 'Profile updated successfully!';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Profile | ServiGo</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="card mx-auto" style="max-width: 600px;">
        <div class="card-body">
            <h3 class="mb-4 text-center">Edit Profile</h3>
            <?php if ($success): ?>
                <div class="alert alert-success"> <?php echo $success; ?> </div>
            <?php elseif ($error): ?>
                <div class="alert alert-danger"> <?php echo $error; ?> </div>
            <?php endif; ?>
            <form method="post" enctype="multipart/form-data">
                <div class="mb-3 text-center">
                    <img src="<?php echo $user['profile_image'] ? htmlspecialchars($user['profile_image']) : 'assets/images/default-profile.png'; ?>" class="rounded-circle" style="width: 100px; height: 100px; object-fit: cover;" alt="Profile">
                </div>
                <div class="mb-3">
                    <label>Profile Image</label>
                    <input type="file" name="profile_image" class="form-control">
                </div>
                <div class="mb-3">
                    <label>First Name</label>
                    <input type="text" name="first_name" class="form-control" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                </div>
                <div class="mb-3">
                    <label>Last Name</label>
                    <input type="text" name="last_name" class="form-control" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                </div>
                <div class="mb-3">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                <div class="mb-3">
                    <label>Phone</label>
                    <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                </div>
                <div class="mb-3">
                    <label>Address</label>
                    <input type="text" name="address" class="form-control" value="<?php echo htmlspecialchars($user['address']); ?>">
                </div>
                <div class="mb-3">
                    <label>City</label>
                    <input type="text" name="city" class="form-control" value="<?php echo htmlspecialchars($user['city']); ?>" required>
                </div>
                <div class="mb-3">
                    <label>Region</label>
                    <input type="text" name="region" class="form-control" value="<?php echo htmlspecialchars($user['region']); ?>" required>
                </div>
                <?php if ($user_type === 'provider'): ?>
                <hr>
                <h5 class="mb-3">Provider Information</h5>
                <div class="mb-3">
                    <label>Business Name</label>
                    <input type="text" name="business_name" class="form-control" value="<?php echo htmlspecialchars($provider['business_name'] ?? ''); ?>">
                </div>
                <div class="mb-3">
                    <label>Business Description</label>
                    <textarea name="business_description" class="form-control" rows="3"><?php echo htmlspecialchars($provider['business_description'] ?? ''); ?></textarea>
                </div>
                <div class="mb-3">
                    <label>Years of Experience</label>
                    <input type="number" name="experience_years" class="form-control" value="<?php echo htmlspecialchars($provider['experience_years'] ?? ''); ?>">
                </div>
                <?php endif; ?>
                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <a href="provider_dashboard.php" class="btn btn-secondary ms-2">Back to Dashboard</a>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html> 