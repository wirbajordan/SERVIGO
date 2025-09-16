<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Simple admin check (replace with your own logic)
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$db = getDB();
$success = $error = '';

// Approve document
if (isset($_GET['approve']) && is_numeric($_GET['approve'])) {
    $doc_id = intval($_GET['approve']);
    // Get provider_id and user_id from document
    $stmt = $db->prepare('SELECT provider_id FROM provider_documents WHERE id = ?');
    $stmt->execute([$doc_id]);
    $doc = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($doc) {
        $provider_id = $doc['provider_id'];
        $db->prepare('UPDATE provider_documents SET is_verified=1, status="approved", rejection_reason=NULL WHERE id=?')->execute([$doc_id]);
        // Get user_id
        $stmt = $db->prepare('SELECT user_id FROM service_providers WHERE id=?');
        $stmt->execute([$provider_id]);
        $user_id = $stmt->fetchColumn();
        // Check if all documents for this provider are now approved
        $stmt = $db->prepare('SELECT COUNT(*) FROM provider_documents WHERE provider_id=? AND status!="approved"');
        $stmt->execute([$provider_id]);
        if ($stmt->fetchColumn() == 0) {
            // Set user as verified
            $db->prepare('UPDATE users SET is_verified=1 WHERE id=?')->execute([$user_id]);
        }
        // Notify provider
        $db->prepare('INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)')->execute([
            $user_id,
            'Document Approved',
            'Your document has been approved by the admin.',
            'system'
        ]);
        $success = 'Document approved.';
    }
}
// Reject document (with reason)
if (isset($_POST['reject_doc_id']) && is_numeric($_POST['reject_doc_id'])) {
    $doc_id = intval($_POST['reject_doc_id']);
    $reason = trim($_POST['rejection_reason']);
    // Get provider_id and user_id
    $stmt = $db->prepare('SELECT provider_id FROM provider_documents WHERE id = ?');
    $stmt->execute([$doc_id]);
    $doc = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($doc) {
        $provider_id = $doc['provider_id'];
        $stmt = $db->prepare('SELECT user_id FROM service_providers WHERE id=?');
        $stmt->execute([$provider_id]);
        $user_id = $stmt->fetchColumn();
        $db->prepare('UPDATE provider_documents SET is_verified=0, status="rejected", rejection_reason=? WHERE id=?')->execute([$reason, $doc_id]);
        // Notify provider
        $db->prepare('INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)')->execute([
            $user_id,
            'Document Rejected',
            'Your document was rejected by the admin. Reason: ' . $reason,
            'system'
        ]);
        $success = 'Document rejected.';
    }
}
// Fetch all pending documents
$stmt = $db->query('SELECT d.*, p.business_name, u.first_name, u.last_name, u.email FROM provider_documents d JOIN service_providers p ON d.provider_id = p.id JOIN users u ON p.user_id = u.id WHERE d.status = "pending" ORDER BY d.uploaded_at ASC');
$pending_docs = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Fetch all rejected documents
$stmt = $db->query('SELECT d.*, p.business_name, u.first_name, u.last_name, u.email FROM provider_documents d JOIN service_providers p ON d.provider_id = p.id JOIN users u ON p.user_id = u.id WHERE d.status = "rejected" ORDER BY d.uploaded_at DESC');
$rejected_docs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Provider Verification | Admin | ServiGo</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include '_header.php'; ?>
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Provider Document Verification</h2>
        <a href="dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
    </div>
    <?php if ($success): ?>
        <div class="alert alert-success"> <?php echo $success; ?> </div>
    <?php elseif ($error): ?>
        <div class="alert alert-danger"> <?php echo $error; ?> </div>
    <?php endif; ?>
    <div class="table-responsive">
        <table class="table table-bordered align-middle">
            <thead>
                <tr>
                    <th>Provider</th>
                    <th>Email</th>
                    <th>Business</th>
                    <th>Document Type</th>
                    <th>Document Name</th>
                    <th>File</th>
                    <th>Uploaded At</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pending_docs as $doc): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($doc['first_name'] . ' ' . $doc['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($doc['email']); ?></td>
                        <td><?php echo htmlspecialchars($doc['business_name']); ?></td>
                        <td><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $doc['document_type']))); ?></td>
                        <td><?php echo htmlspecialchars($doc['document_name']); ?></td>
                        <td><a href="../<?php echo $doc['file_path']; ?>" target="_blank">View</a></td>
                        <td><?php echo htmlspecialchars($doc['uploaded_at']); ?></td>
                        <td>
                            <a href="?approve=<?php echo $doc['id']; ?>" class="btn btn-success btn-sm mb-1"><i class="fas fa-check"></i> Approve</a>
                            <!-- Reject with reason form -->
                            <button class="btn btn-danger btn-sm mb-1" data-bs-toggle="modal" data-bs-target="#rejectModal<?php echo $doc['id']; ?>"><i class="fas fa-times"></i> Reject</button>
                            <div class="modal fade" id="rejectModal<?php echo $doc['id']; ?>" tabindex="-1" aria-labelledby="rejectModalLabel<?php echo $doc['id']; ?>" aria-hidden="true">
                              <div class="modal-dialog">
                                <div class="modal-content">
                                  <form method="post">
                                    <div class="modal-header">
                                      <h5 class="modal-title" id="rejectModalLabel<?php echo $doc['id']; ?>">Reject Document</h5>
                                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                      <input type="hidden" name="reject_doc_id" value="<?php echo $doc['id']; ?>">
                                      <div class="mb-3">
                                        <label for="rejection_reason<?php echo $doc['id']; ?>" class="form-label">Reason for rejection</label>
                                        <textarea name="rejection_reason" id="rejection_reason<?php echo $doc['id']; ?>" class="form-control" required></textarea>
                                      </div>
                                    </div>
                                    <div class="modal-footer">
                                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                      <button type="submit" class="btn btn-danger">Reject</button>
                                    </div>
                                  </form>
                                </div>
                              </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($pending_docs)): ?>
                    <tr><td colspan="8" class="text-center">No pending documents.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <h5 class="mt-5">Rejected Documents</h5>
    <div class="table-responsive">
        <table class="table table-bordered align-middle">
            <thead>
                <tr>
                    <th>Provider</th>
                    <th>Email</th>
                    <th>Business</th>
                    <th>Document Type</th>
                    <th>Document Name</th>
                    <th>File</th>
                    <th>Uploaded At</th>
                    <th>Rejection Reason</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rejected_docs as $doc): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($doc['first_name'] . ' ' . $doc['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($doc['email']); ?></td>
                        <td><?php echo htmlspecialchars($doc['business_name']); ?></td>
                        <td><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $doc['document_type']))); ?></td>
                        <td><?php echo htmlspecialchars($doc['document_name']); ?></td>
                        <td><a href="../<?php echo $doc['file_path']; ?>" target="_blank">View</a></td>
                        <td><?php echo htmlspecialchars($doc['uploaded_at']); ?></td>
                        <td><?php echo htmlspecialchars($doc['rejection_reason']); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($rejected_docs)): ?>
                    <tr><td colspan="8" class="text-center">No rejected documents.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
