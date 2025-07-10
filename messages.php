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

// Handle new message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $receiver_id = intval($_POST['receiver_id']);
    $message_text = trim($_POST['message']);
    $request_id = isset($_POST['request_id']) ? intval($_POST['request_id']) : null;
    
    if ($receiver_id && $message_text) {
        $stmt = $db->prepare("INSERT INTO messages (sender_id, receiver_id, request_id, message) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$user_id, $receiver_id, $request_id, $message_text])) {
            $success = 'Message sent successfully!';
        } else {
            $error = 'Failed to send message. Please try again.';
        }
    } else {
        $error = 'Please fill in all required fields.';
    }
}

// Get conversations (users you've messaged with)
$conversations = [];
$stmt = $db->prepare("
    SELECT DISTINCT 
        CASE 
            WHEN m.sender_id = ? THEN m.receiver_id 
            ELSE m.sender_id 
        END as other_user_id,
        u.first_name, u.last_name, u.profile_image,
        MAX(m.created_at) as last_message_time,
        COUNT(CASE WHEN m.is_read = 0 AND m.receiver_id = ? THEN 1 END) as unread_count
    FROM messages m
    JOIN users u ON (m.sender_id = u.id OR m.receiver_id = u.id)
    WHERE (m.sender_id = ? OR m.receiver_id = ?) AND u.id != ?
    GROUP BY other_user_id, u.first_name, u.last_name, u.profile_image
    ORDER BY last_message_time DESC
");
$stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id]);
$conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get messages with a specific user
$selected_user = null;
$messages = [];
if (isset($_GET['user_id'])) {
    $other_user_id = intval($_GET['user_id']);
    $stmt = $db->prepare("
        SELECT u.first_name, u.last_name, u.profile_image 
        FROM users u WHERE u.id = ?
    ");
    $stmt->execute([$other_user_id]);
    $selected_user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($selected_user) {
        // Mark messages as read
        $stmt = $db->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0");
        $stmt->execute([$other_user_id, $user_id]);
        
        // Get messages
        $stmt = $db->prepare("
            SELECT m.*, u.first_name, u.last_name, u.profile_image
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            WHERE (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?)
            ORDER BY m.created_at ASC
        ");
        $stmt->execute([$user_id, $other_user_id, $other_user_id, $user_id]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Get all users for new message
$all_users = [];
$stmt = $db->prepare("
    SELECT id, first_name, last_name, user_type, city 
    FROM users 
    WHERE id != ? AND is_active = 1 
    ORDER BY first_name, last_name
");
$stmt->execute([$user_id]);
$all_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$primary = '#007bff';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages | ServiGo</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body { background: #f8f9fa; }
        .message-container { height: 400px; overflow-y: auto; border: 1px solid #e3e3e3; border-radius: 8px; padding: 1rem; }
        .message { margin-bottom: 1rem; }
        .message.sent { text-align: right; }
        .message.received { text-align: left; }
        .message-bubble { display: inline-block; max-width: 70%; padding: 0.5rem 1rem; border-radius: 15px; }
        .message.sent .message-bubble { background: <?php echo $primary; ?>; color: white; }
        .message.received .message-bubble { background: #e9ecef; color: #333; }
        .conversation-item { cursor: pointer; transition: background-color 0.2s; }
        .conversation-item:hover { background-color: #f8f9fa; }
        .conversation-item.active { background-color: <?php echo $primary; ?>; color: white; }
        .unread-badge { background: #dc3545; color: white; border-radius: 50%; padding: 0.25rem 0.5rem; font-size: 0.75rem; }
        .profile-img { width: 40px; height: 40px; object-fit: cover; border-radius: 50%; }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Conversations</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php foreach ($conversations as $conv): ?>
                        <a href="?user_id=<?php echo $conv['other_user_id']; ?>" 
                           class="list-group-item list-group-item-action conversation-item <?php echo (isset($_GET['user_id']) && $_GET['user_id'] == $conv['other_user_id']) ? 'active' : ''; ?>">
                            <div class="d-flex align-items-center">
                                <img src="<?php echo $conv['profile_image'] ? htmlspecialchars($conv['profile_image']) : 'assets/images/default-profile.png'; ?>" 
                                     class="profile-img me-2" alt="Profile">
                                <div class="flex-grow-1">
                                    <h6 class="mb-0"><?php echo htmlspecialchars($conv['first_name'] . ' ' . $conv['last_name']); ?></h6>
                                    <small class="text-muted"><?php echo date('M j, g:i a', strtotime($conv['last_message_time'])); ?></small>
                                </div>
                                <?php if ($conv['unread_count'] > 0): ?>
                                <span class="unread-badge"><?php echo $conv['unread_count']; ?></span>
                                <?php endif; ?>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <?php if ($selected_user): ?>
                    <h5 class="mb-0">
                        <img src="<?php echo $selected_user['profile_image'] ? htmlspecialchars($selected_user['profile_image']) : 'assets/images/default-profile.png'; ?>" 
                             class="profile-img me-2" alt="Profile">
                        <?php echo htmlspecialchars($selected_user['first_name'] . ' ' . $selected_user['last_name']); ?>
                    </h5>
                    <?php else: ?>
                    <h5 class="mb-0">Select a conversation</h5>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if ($selected_user): ?>
                    <div class="message-container mb-3">
                        <?php foreach ($messages as $msg): ?>
                        <div class="message <?php echo $msg['sender_id'] == $user_id ? 'sent' : 'received'; ?>">
                            <div class="message-bubble">
                                <?php echo htmlspecialchars($msg['message']); ?>
                                <div class="small text-muted mt-1">
                                    <?php echo date('g:i a', strtotime($msg['created_at'])); ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <form method="post">
                        <input type="hidden" name="receiver_id" value="<?php echo $_GET['user_id']; ?>">
                        <div class="input-group">
                            <input type="text" name="message" class="form-control" placeholder="Type your message..." required>
                            <button type="submit" name="send_message" class="btn btn-primary">Send</button>
                        </div>
                    </form>
                    <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-comments fa-3x mb-3"></i>
                        <p>Select a conversation to start messaging</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="text-center mt-4">
        <a href="dashboard.php" class="btn btn-outline-primary">Back to Dashboard</a>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 