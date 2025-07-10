<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = sanitize_input($_POST['first_name']);
    $last_name = sanitize_input($_POST['last_name']);
    $email = sanitize_input($_POST['email']);
    $username = sanitize_input($_POST['username']);
    $phone = sanitize_input($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $user_type = $_POST['user_type'];
    $city = sanitize_input($_POST['city']);
    $region = sanitize_input($_POST['region']);

    // Provider-specific fields
    $business_name = isset($_POST['business_name']) ? sanitize_input($_POST['business_name']) : null;
    $business_description = isset($_POST['business_description']) ? sanitize_input($_POST['business_description']) : null;
    $experience_years = isset($_POST['experience_years']) ? intval($_POST['experience_years']) : null;

    // Validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($username) || empty($phone) || empty($password) || empty($confirm_password) || empty($user_type) || empty($city) || empty($region)) {
        $error = 'Please fill in all required fields.';
    } elseif (!is_valid_email($email)) {
        $error = 'Invalid email address.';
    } elseif (!is_valid_phone($phone)) {
        $error = 'Invalid phone number.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        try {
            $db = getDB();
            // Check for existing email or username
            $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ? OR username = ?");
            $stmt->execute([$email, $username]);
            if ($stmt->fetchColumn() > 0) {
                $error = 'Email or username already exists.';
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO users (username, email, password, first_name, last_name, phone, city, region, user_type, is_verified, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $username, $email, $hashed_password, $first_name, $last_name, format_phone($phone), $city, $region, $user_type, 1, 1
                ]);
                $user_id = $db->lastInsertId();

                // If provider, insert into service_providers
                if ($user_type === 'provider') {
                    $stmt2 = $db->prepare("INSERT INTO service_providers (user_id, business_name, business_description, experience_years, is_available, created_at, updated_at) VALUES (?, ?, ?, ?, 1, NOW(), NOW())");
                    $stmt2->execute([
                        $user_id, $business_name, $business_description, $experience_years
                    ]);
                }

                // Notify admin of new registration
                $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type, for_admin) VALUES (?, ?, ?, ?, 1)");
                $stmt->execute([1, 'New User Registration', 'A new user has registered: ' . $first_name . ' ' . $last_name . ' (' . $email . ')', 'system']);

                $success = 'Registration successful! You can now <a href=\'login.php\'>login</a>.';
            }
        } catch (PDOException $e) {
            $error = 'Registration failed. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - ServiGo</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script>
    function toggleProviderFields() {
        var userType = document.getElementById('user_type').value;
        var providerFields = document.getElementById('provider-fields');
        providerFields.style.display = (userType === 'provider') ? 'block' : 'none';
    }
    </script>
</head>
<body class="bg-light">
    <!-- Header -->
    <header class="header navbar-servigo shadow-sm mb-4">
        <nav class="navbar navbar-expand-lg navbar-light bg-white">
            <div class="container">
                <a class="navbar-brand text-primary-servigo fw-bold" href="index.php"><i class="fas fa-tools me-2"></i>ServiGo</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="mainNavbar">
                    <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                        <li class="nav-item"><a href="index.php" class="nav-link">Home</a></li>
                        <li class="nav-item"><a href="services.php" class="nav-link">Services</a></li>
                        <li class="nav-item"><a href="providers.php" class="nav-link">Service Providers</a></li>
                        <li class="nav-item"><a href="about.php" class="nav-link">About</a></li>
                        <li class="nav-item"><a href="contact.php" class="nav-link">Contact</a></li>
                        <li class="nav-item"><a href="login.php" class="nav-link">Login</a></li>
                        <li class="nav-item"><a href="register.php" class="nav-link active">Register</a></li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <!-- Registration Section -->
    <section class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <div class="card card-servigo shadow">
                        <div class="card-body p-5">
                            <div class="text-center mb-4">
                                <h2 class="text-primary-servigo fw-bold"><i class="fas fa-user-plus me-2"></i>Register</h2>
                                <p class="text-muted">Create your account to get started.</p>
                            </div>
                            <?php if($error): ?>
                                <div class="alert alert-danger d-flex align-items-center" role="alert">
                                    <i class="fas fa-exclamation-circle me-2"></i>
                                    <?php echo $error; ?>
                                </div>
                            <?php endif; ?>
                            <?php if($success): ?>
                                <div class="alert alert-success d-flex align-items-center" role="alert">
                                    <i class="fas fa-check-circle me-2"></i>
                                    <?php echo $success; ?>
                                </div>
                            <?php endif; ?>
                            <form method="POST" class="needs-validation" autocomplete="off" novalidate>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="first_name" class="form-label fw-semibold">First Name</label>
                                        <input type="text" id="first_name" name="first_name" class="form-control" required value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="last_name" class="form-label fw-semibold">Last Name</label>
                                        <input type="text" id="last_name" name="last_name" class="form-control" required value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="username" class="form-label fw-semibold">Username</label>
                                        <input type="text" id="username" name="username" class="form-control" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="email" class="form-label fw-semibold">Email Address</label>
                                        <input type="email" id="email" name="email" class="form-control" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="phone" class="form-label fw-semibold">Phone Number</label>
                                        <input type="text" id="phone" name="phone" class="form-control" required value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="city" class="form-label fw-semibold">City</label>
                                        <input type="text" id="city" name="city" class="form-control" required value="<?php echo isset($_POST['city']) ? htmlspecialchars($_POST['city']) : ''; ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="region" class="form-label fw-semibold">Region</label>
                                        <input type="text" id="region" name="region" class="form-control" required value="<?php echo isset($_POST['region']) ? htmlspecialchars($_POST['region']) : ''; ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="user_type" class="form-label fw-semibold">Register as</label>
                                        <select id="user_type" name="user_type" class="form-select" required onchange="toggleProviderFields()">
                                            <option value="customer" <?php if(isset($_POST['user_type']) && $_POST['user_type']==='customer') echo 'selected'; ?>>Customer</option>
                                            <option value="provider" <?php if(isset($_POST['user_type']) && $_POST['user_type']==='provider') echo 'selected'; ?>>Service Provider</option>
                                        </select>
                                    </div>
                                </div>
                                <div id="provider-fields" style="display:<?php echo (isset($_POST['user_type']) && $_POST['user_type']==='provider') ? 'block' : 'none'; ?>;" class="mt-3">
                                    <div class="mb-3">
                                        <label for="business_name" class="form-label fw-semibold">Business Name</label>
                                        <input type="text" id="business_name" name="business_name" class="form-control" value="<?php echo isset($_POST['business_name']) ? htmlspecialchars($_POST['business_name']) : ''; ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label for="business_description" class="form-label fw-semibold">Business Description</label>
                                        <textarea id="business_description" name="business_description" class="form-control"><?php echo isset($_POST['business_description']) ? htmlspecialchars($_POST['business_description']) : ''; ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label for="experience_years" class="form-label fw-semibold">Years of Experience</label>
                                        <input type="number" id="experience_years" name="experience_years" class="form-control" min="0" value="<?php echo isset($_POST['experience_years']) ? htmlspecialchars($_POST['experience_years']) : ''; ?>">
                                    </div>
                                </div>
                                <div class="row g-3 mt-2">
                                    <div class="col-md-6">
                                        <label for="password" class="form-label fw-semibold">Password</label>
                                        <input type="password" id="password" name="password" class="form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="confirm_password" class="form-label fw-semibold">Confirm Password</label>
                                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                                    </div>
                                </div>
                                <div class="d-grid mt-4">
                                    <button type="submit" class="btn btn-servigo btn-lg">
                                        <i class="fas fa-user-plus me-2"></i> Register
                                    </button>
                                </div>
                            </form>
                            <div class="text-center mt-4">
                                <p>Already have an account? <a href="login.php" class="text-primary-servigo text-decoration-none fw-semibold">Login here</a></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Footer -->
    <footer class="footer bg-neutral-dark text-white mt-5">
        <div class="container py-4">
            <div class="row">
                <div class="col-md-6">
                    <h3 class="fw-bold"><i class="fas fa-tools me-2"></i>ServiGo</h3>
                    <p>Connecting Cameroon with trusted local service providers.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">&copy; 2024 ServiGo. All rights reserved. | Made for Cameroon</p>
                </div>
            </div>
        </div>
    </footer>
    <script src="assets/js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>toggleProviderFields();</script>
</body>
</html> 