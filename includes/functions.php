<?php
// Utility functions for ServiGo application

// Sanitize input data
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Hash password
function hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Verify password
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

// Check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Check if user is admin
function is_admin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

// Check if user is provider
function is_provider() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'provider';
}

// Get current user data
function get_logged_in_user() {
    if (!is_logged_in()) {
        return null;
    }
    
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

// Redirect function
function redirect($url) {
    header("Location: $url");
    exit();
}

// Generate random string
function generate_random_string($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $random_string = '';
    for ($i = 0; $i < $length; $i++) {
        $random_string .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $random_string;
}

// Format currency
function format_currency($amount) {
    return 'FCFA ' . number_format($amount, 0, ',', ' ');
}

// Format date
function format_date($date) {
    return date('d M Y', strtotime($date));
}

// Format datetime
function format_datetime($datetime) {
    return date('d M Y H:i', strtotime($datetime));
}

// Get service categories
function get_service_categories() {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM service_categories WHERE is_active = 1 ORDER BY name");
    $stmt->execute();
    return $stmt->fetchAll();
}

// Get service providers by category
function get_providers_by_category($category_id) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT sp.*, u.first_name, u.last_name, u.city, u.region, ps.price
        FROM service_providers sp
        JOIN users u ON sp.user_id = u.id
        JOIN provider_services ps ON sp.id = ps.provider_id
        WHERE ps.category_id = ? AND ps.is_active = 1 AND sp.is_available = 1
        ORDER BY sp.rating DESC
    ");
    $stmt->execute([$category_id]);
    return $stmt->fetchAll();
}

// Get user notifications
function get_user_notifications($user_id, $limit = 10) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT * FROM notifications 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT ?
    ");
    // Bind as integers to satisfy MySQL LIMIT requirements
    $stmt->bindValue(1, (int)$user_id, PDO::PARAM_INT);
    $stmt->bindValue(2, (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

// Create notification
function create_notification($user_id, $title, $message, $type = 'system') {
    $db = getDB();
    $stmt = $db->prepare("
        INSERT INTO notifications (user_id, title, message, type) 
        VALUES (?, ?, ?, ?)
    ");
    return $stmt->execute([$user_id, $title, $message, $type]);
}

// Mark notification as read
function mark_notification_read($notification_id) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
    return $stmt->execute([$notification_id]);
}

// Get unread notifications count
function get_unread_notifications_count($user_id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);
    return $stmt->fetchColumn();
}

// Validate email
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Validate phone number (Cameroon format)
function is_valid_phone($phone) {
    // Remove spaces and special characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Check if it's a valid Cameroon phone number
    // Cameroon numbers start with +237 or 237
    if (strlen($phone) == 9 && substr($phone, 0, 1) == '6') {
        return true;
    }
    if (strlen($phone) == 12 && substr($phone, 0, 3) == '237') {
        return true;
    }
    if (strlen($phone) == 13 && substr($phone, 0, 4) == '+237') {
        return true;
    }
    
    return false;
}

// Format phone number
function format_phone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    if (strlen($phone) == 9 && substr($phone, 0, 1) == '6') {
        return '+237' . $phone;
    }
    if (strlen($phone) == 12 && substr($phone, 0, 3) == '237') {
        return '+' . $phone;
    }
    if (strlen($phone) == 13 && substr($phone, 0, 4) == '+237') {
        return $phone;
    }
    
    return $phone;
}

// Get user's service requests
function get_user_requests($user_id, $status = null) {
    $db = getDB();
    
    if ($status) {
        $stmt = $db->prepare("
            SELECT sr.*, sc.name as category_name, u.first_name, u.last_name
            FROM service_requests sr
            JOIN service_categories sc ON sr.category_id = sc.id
            JOIN users u ON sr.provider_id = u.id
            WHERE sr.customer_id = ? AND sr.status = ?
            ORDER BY sr.created_at DESC
        ");
        $stmt->execute([$user_id, $status]);
    } else {
        $stmt = $db->prepare("
            SELECT sr.*, sc.name as category_name, u.first_name, u.last_name
            FROM service_requests sr
            JOIN service_categories sc ON sr.category_id = sc.id
            JOIN users u ON sr.provider_id = u.id
            WHERE sr.customer_id = ?
            ORDER BY sr.created_at DESC
        ");
        $stmt->execute([$user_id]);
    }
    
    return $stmt->fetchAll();
}

// Get provider's service requests
function get_provider_requests($provider_id, $status = null) {
    $db = getDB();
    
    if ($status) {
        $stmt = $db->prepare("
            SELECT sr.*, sc.name as category_name, u.first_name, u.last_name
            FROM service_requests sr
            JOIN service_categories sc ON sr.category_id = sc.id
            JOIN users u ON sr.customer_id = u.id
            WHERE sr.provider_id = ? AND sr.status = ?
            ORDER BY sr.created_at DESC
        ");
        $stmt->execute([$provider_id, $status]);
    } else {
        $stmt = $db->prepare("
            SELECT sr.*, sc.name as category_name, u.first_name, u.last_name
            FROM service_requests sr
            JOIN service_categories sc ON sr.category_id = sc.id
            JOIN users u ON sr.customer_id = u.id
            WHERE sr.provider_id = ?
            ORDER BY sr.created_at DESC
        ");
        $stmt->execute([$provider_id]);
    }
    
    return $stmt->fetchAll();
}

// Check if user is provider
function is_user_provider($user_id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) FROM service_providers WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetchColumn() > 0;
}

// Get provider data
function get_provider_data($user_id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM service_providers WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

// Calculate average rating
function calculate_average_rating($provider_id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT AVG(rating) FROM reviews WHERE provider_id = ?");
    $stmt->execute([$provider_id]);
    return round($stmt->fetchColumn(), 2);
}

// Update provider rating
function update_provider_rating($provider_id) {
    $db = getDB();
    $avg_rating = calculate_average_rating($provider_id);
    $total_reviews = get_provider_reviews_count($provider_id);
    
    $stmt = $db->prepare("UPDATE service_providers SET rating = ?, total_reviews = ? WHERE id = ?");
    return $stmt->execute([$avg_rating, $total_reviews, $provider_id]);
}

// Get provider reviews count
function get_provider_reviews_count($provider_id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) FROM reviews WHERE provider_id = ?");
    $stmt->execute([$provider_id]);
    return $stmt->fetchColumn();
}

// Get provider reviews
function get_provider_reviews($provider_id, $limit = 10) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT r.*, u.first_name, u.last_name
        FROM reviews r
        JOIN users u ON r.customer_id = u.id
        WHERE r.provider_id = ?
        ORDER BY r.created_at DESC
        LIMIT ?
    ");
    // Bind as integers to satisfy MySQL LIMIT requirements
    $stmt->bindValue(1, (int)$provider_id, PDO::PARAM_INT);
    $stmt->bindValue(2, (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

// Check if user can review provider
function can_review_provider($customer_id, $provider_id) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM service_requests 
        WHERE customer_id = ? AND provider_id = ? AND status = 'completed'
    ");
    $stmt->execute([$customer_id, $provider_id]);
    return $stmt->fetchColumn() > 0;
}

// Check if user has already reviewed
function has_reviewed_provider($customer_id, $provider_id) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM reviews 
        WHERE customer_id = ? AND provider_id = ?
    ");
    $stmt->execute([$customer_id, $provider_id]);
    return $stmt->fetchColumn() > 0;
}
?> 