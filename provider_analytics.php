<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'provider') {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$db = getDB();
$provider = get_provider_data($user_id);
$provider_id = $provider['id'];

// Total jobs
$stmt = $db->prepare("SELECT COUNT(*) FROM service_requests WHERE provider_id = ?");
$stmt->execute([$provider_id]);
$total_jobs = $stmt->fetchColumn();

// Completed jobs
$stmt = $db->prepare("SELECT COUNT(*) FROM service_requests WHERE provider_id = ? AND status = 'completed'");
$stmt->execute([$provider_id]);
$completed_jobs = $stmt->fetchColumn();

// Average rating
$average_rating = calculate_average_rating($provider_id);

// Total earnings
$stmt = $db->prepare("
    SELECT SUM(amount) FROM payments WHERE request_id IN (SELECT id FROM service_requests WHERE provider_id = ?) AND status = 'completed'
");
$stmt->execute([$provider_id]);
$total_earnings = $stmt->fetchColumn() ?: 0;

// Jobs per month (last 6 months)
$stmt = $db->prepare("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count
    FROM service_requests
    WHERE provider_id = ?
    GROUP BY month
    ORDER BY month DESC
    LIMIT 6
");
$stmt->execute([$provider_id]);
$jobs_per_month = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Analytics & Insights | Provider</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-light">
<div class="container py-5">
    <h2 class="mb-4 text-center">Analytics & Insights</h2>
    <div class="mb-3 text-end">
        <a href="provider_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <h6>Total Jobs</h6>
                    <h3><?php echo $total_jobs; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <h6>Completed Jobs</h6>
                    <h3><?php echo $completed_jobs; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <h6>Average Rating</h6>
                    <h3 class="text-warning">★ <?php echo $average_rating; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <h6>Total Earnings</h6>
                    <h3 class="text-success">FCFA <?php echo number_format($total_earnings, 0, ',', ' '); ?></h3>
                </div>
            </div>
        </div>
    </div>
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="mb-3">Jobs Per Month (Last 6 Months)</h5>
            <canvas id="jobsChart" height="100"></canvas>
        </div>
    </div>
</div>
<script>
const jobsLabels = <?php echo json_encode(array_column($jobs_per_month, 'month')); ?>;
const jobsData = <?php echo json_encode(array_column($jobs_per_month, 'count')); ?>;
new Chart(document.getElementById('jobsChart'), {
    type: 'bar',
    data: {
        labels: jobsLabels,
        datasets: [{
            label: 'Jobs',
            data: jobsData,
            backgroundColor: '#007bff'
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
    }
});
</script>
</body>
</html> 