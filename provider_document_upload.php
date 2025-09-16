<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'provider') {
    header('Location: login.php');
    exit();
}

$db = getDB();
$user_id = $_SESSION['user_id'];
// Get provider id
$stmt = $db->prepare('SELECT id FROM service_providers WHERE user_id = ?');
$stmt->execute([$user_id]);
$provider = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$provider) {
    die('Provider not found.');
}
$provider_id = $provider['id'];

$success = $error = '';

// Handle document upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['document'])) {
    $doc_type = $_POST['document_type'];
    $doc_name = trim($_POST['document_name']);
    $file = $_FILES['document'];
    if ($file['error'] === UPLOAD_ERR_OK && $doc_type && $doc_name) {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('doc_') . '.' . $ext;
        $target = 'assets/documents/' . $filename;
        if (!is_dir('assets/documents')) {
            mkdir('assets/documents', 0777, true);
        }
        if (move_uploaded_file($file['tmp_name'], $target)) {
            $stmt = $db->prepare('INSERT INTO provider_documents (provider_id, document_type, document_name, file_path, is_verified) VALUES (?, ?, ?, ?, 0)');
            $stmt->execute([$provider_id, $doc_type, $doc_name, $target]);
            $success = 'Document uploaded successfully!';
        } else {
            $error = 'Failed to upload document.';
        }
    } else {
        $error = 'Please select a document and fill all fields.';
    }
}
// Fetch uploaded documents
$stmt = $db->prepare('SELECT * FROM provider_documents WHERE provider_id = ? ORDER BY uploaded_at DESC');
$stmt->execute([$provider_id]);
$documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload Verification Documents | ServiGo</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">Upload Verification Documents</h3>
        <a href="provider_dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
    </div>
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card card-servigo shadow">
                <div class="card-body p-5">
                    <?php if ($success): ?>
                        <div class="alert alert-success"> <?php echo $success; ?> </div>
                    <?php elseif ($error): ?>
                        <div class="alert alert-danger"> <?php echo $error; ?> </div>
                    <?php endif; ?>
                    <form method="post" enctype="multipart/form-data" class="mb-4">
                        <div class="mb-3">
                            <label for="document_type" class="form-label">Document Type</label>
                            <select name="document_type" id="document_type" class="form-select" required>
                                <option value="">Select type</option>
                                <option value="id_card">ID Card</option>
                                <option value="certificate">Certificate</option>
                                <option value="license">License</option>
                                <option value="insurance">Insurance</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="document_name" class="form-label">Document Name</label>
                            <input type="text" name="document_name" id="document_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="document" class="form-label">Select File</label>
                            <input type="file" name="document" id="document" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-servigo">Upload Document</button>
                    </form>
                    <h5 class="mb-3">Uploaded Documents</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Name</th>
                                    <th>Status</th>
                                    <th>Uploaded At</th>
                                    <th>File</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($documents as $doc): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $doc['document_type']))); ?></td>
                                        <td><?php echo htmlspecialchars($doc['document_name']); ?></td>
                                        <td>
                                            <?php if (!empty($doc['status'])): ?>
                                                <?php if ($doc['status'] === 'approved'): ?>
                                                    <span class="badge bg-success">Approved</span>
                                                <?php elseif ($doc['status'] === 'rejected'): ?>
                                                    <span class="badge bg-danger">Rejected</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark">Pending</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($doc['uploaded_at']); ?></td>
                                        <td><a href="<?php echo $doc['file_path']; ?>" target="_blank">View</a></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($documents)): ?>
                                    <tr><td colspan="5" class="text-center">No documents uploaded yet.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
