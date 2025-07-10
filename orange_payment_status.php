<?php
$referenceId = $_GET['ref'] ?? null;
if (!$referenceId) {
    die('Invalid reference');
}
echo "<h2>Orange Money Payment Reference: " . htmlspecialchars($referenceId) . "</h2>";
echo "<p>This is a placeholder. Integrate with Orange Money API for real status updates.</p>";
echo '<a href="dashboard.php">Back to Dashboard</a>';
?> 