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
$user_type = $_SESSION['user_type'];

// Handle new message submission (existing conversation or new)
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

// Get conversations (users you've messaged with) with last message preview
$conversations = [];
$stmt = $db->prepare("
    SELECT DISTINCT 
        CASE 
            WHEN m.sender_id = ? THEN m.receiver_id 
            ELSE m.sender_id 
        END as other_user_id,
        u.first_name, u.last_name, u.profile_image, u.user_type,
        MAX(m.created_at) as last_message_time,
        (
            SELECT message FROM messages m2 
            WHERE (m2.sender_id = ? AND m2.receiver_id = u.id) OR (m2.sender_id = u.id AND m2.receiver_id = ?) 
            ORDER BY m2.created_at DESC LIMIT 1
        ) as last_message,
        COUNT(CASE WHEN m.is_read = 0 AND m.receiver_id = ? THEN 1 END) as unread_count
    FROM messages m
    JOIN users u ON (m.sender_id = u.id OR m.receiver_id = u.id)
    WHERE (m.sender_id = ? OR m.receiver_id = ?) AND u.id != ?
    GROUP BY other_user_id, u.first_name, u.last_name, u.profile_image, u.user_type
    ORDER BY 6 DESC
");
$stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id]);
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

// Get all users for new message (exclude self). Include email and provider business_name for richer search labels
if ($user_type === 'provider') {
    // Providers can only message customers
    $stmt = $db->prepare("
        SELECT u.id, u.first_name, u.last_name, u.user_type, u.city, u.email, NULL as business_name
        FROM users u
        WHERE u.id != ? AND u.is_active = 1 AND u.user_type = 'customer'
        ORDER BY u.first_name, u.last_name
    ");
    $stmt->execute([$user_id]);
    $all_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Customers and others can message anyone
    $stmt = $db->prepare("
        SELECT u.id, u.first_name, u.last_name, u.user_type, u.city, u.email, sp.business_name
        FROM users u
        LEFT JOIN service_providers sp ON sp.user_id = u.id
        WHERE u.id != ? AND u.is_active = 1
        ORDER BY u.first_name, u.last_name
    ");
    $stmt->execute([$user_id]);
    $all_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

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
        .conversation-search { margin: 0.5rem 1rem 0.5rem 1rem; }
        .last-message-preview { font-size: 0.9em; color: #888; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .empty-state { text-align: center; color: #888; padding: 3rem 1rem; }
        .customer-highlight { background: #e6f7ff !important; }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Conversations</h5>
                    <button class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#newMessageModal"><i class="fas fa-plus"></i> New Message</button>
                </div>
                <div class="conversation-search">
                    <input type="text" id="conversationSearch" class="form-control" placeholder="Search conversations...">
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush" id="conversationList">
                        <?php if (empty($conversations)): ?>
                            <div class="empty-state">
                                <i class="fas fa-comments fa-3x mb-3"></i>
                                <p>No conversations yet.<br>Start a new message!</p>
                            </div>
                        <?php else: ?>
                        <?php foreach ($conversations as $conv): ?>
                        <?php
                        // Highlight customer conversations for providers
                        $is_customer = isset($conv['user_type']) ? $conv['user_type'] === 'customer' : false;
                        ?>
                        <a href="?user_id=<?php echo $conv['other_user_id']; ?>" 
                           class="list-group-item list-group-item-action conversation-item <?php echo (isset($_GET['user_id']) && $_GET['user_id'] == $conv['other_user_id']) ? 'active' : ''; ?><?php echo ($user_type === 'provider' && $is_customer) ? ' customer-highlight' : ''; ?>">
                            <div class="d-flex align-items-center">
                                <img src="<?php echo $conv['profile_image'] ? htmlspecialchars($conv['profile_image']) : 'assets/images/default-profile.png'; ?>" 
                                     class="profile-img me-2" alt="Profile">
                                <div class="flex-grow-1">
                                    <h6 class="mb-0">
                                        <?php echo htmlspecialchars($conv['first_name'] . ' ' . $conv['last_name']); ?>
                                        <?php if ($user_type === 'provider' && (isset($conv['user_type']) ? $conv['user_type'] === 'customer' : false)): ?>
                                            <span class="badge bg-info text-dark ms-1">Customer</span>
                                        <?php endif; ?>
                                    </h6>
                                    <div class="last-message-preview"><?php echo htmlspecialchars($conv['last_message']); ?></div>
                                    <small class="text-muted"><?php echo date('M j, g:i a', strtotime($conv['last_message_time'])); ?></small>
                                </div>
                                <?php if ($conv['unread_count'] > 0): ?>
                                <span class="unread-badge"><?php echo $conv['unread_count']; ?></span>
                                <?php endif; ?>
                            </div>
                        </a>
                        <?php endforeach; ?>
                        <?php endif; ?>
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
    <!-- New Message Modal -->
    <div class="modal fade" id="newMessageModal" tabindex="-1" role="dialog" aria-labelledby="newMessageModalLabel" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <form method="post">
            <div class="modal-header">
              <h5 class="modal-title" id="newMessageModalLabel">Start New Conversation</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <div class="form-group">
                <label for="receiver_search">Search User</label>
                <input type="text" id="receiver_search" class="form-control" placeholder="Search by name, email, business, city...">
              </div>
              <div class="form-group mt-2">
                <label for="receiver_id">Select User</label>
                <select name="receiver_id" id="receiver_id" class="form-control" required>
                  <option value="">-- Select User --</option>
                  <?php foreach ($all_users as $u): ?>
                    <?php
                      $label = trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''));
                      $typeCity = '(' . ucfirst($u['user_type']) . ', ' . ($u['city'] ?? '') . ')';
                      $email = $u['email'] ?? '';
                      $biz = $u['business_name'] ?? '';
                      $fullLabel = $label . (strlen($email)? ' - ' . $email : '') . (strlen($biz)? ' - ' . $biz : '') . ' ' . $typeCity;
                    ?>
                    <option value="<?php echo $u['id']; ?>" data-search="<?php echo htmlspecialchars(strtolower($fullLabel)); ?>"><?php echo htmlspecialchars($fullLabel); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group mt-3">
                <label for="new_message">Message</label>
                <textarea name="message" id="new_message" class="form-control" rows="3" required></textarea>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
              <button type="submit" name="send_message" class="btn btn-primary">Send</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <div class="text-center mt-4">
        <a href="dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Bootstrap 4 modal compatibility
$(function () {
    $('[data-toggle="modal"]').on('click', function(e) {
        var target = $(this).data('target');
        $(target).modal('show');
    });
});

// Conversation search filter
$(function () {
    $('#conversationSearch').on('input', function() {
        var val = $(this).val().toLowerCase();
        $('#conversationList .conversation-item').each(function() {
            var name = $(this).find('h6').text().toLowerCase();
            var preview = $(this).find('.last-message-preview').text().toLowerCase();
            $(this).toggle(name.indexOf(val) !== -1 || preview.indexOf(val) !== -1);
        });
    });
});

// New message recipient filter
$(function () {
    $('#receiver_search').on('input', function() {
        var val = $(this).val().toLowerCase();
        $('#receiver_id option').each(function() {
            if (!this.value) { return; }
            var text = $(this).attr('data-search');
            $(this).toggle(text.indexOf(val) !== -1);
        });
    });
});
</script>
</body>
</html> 