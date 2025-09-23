<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!isset($_GET['id'])) {
	header('Location: providers.php');
	exit();
}
$provider_id = intval($_GET['id']);
$db = getDB();

// Fetch provider and user info
$stmt = $db->prepare("SELECT sp.*, u.first_name, u.last_name, u.city, u.region, u.profile_image, u.address, u.id as user_id FROM service_providers sp JOIN users u ON sp.user_id = u.id WHERE sp.id = ?");
$stmt->execute([$provider_id]);
$provider = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$provider) { header('Location: providers.php'); exit(); }

$avg_rating = calculate_average_rating($provider_id);
$reviews = get_provider_reviews($provider_id, 20);

// Handle review submission
$review_error = $review_success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
	$customer_id = $_SESSION['user_id'];
	$rating = max(1, min(5, intval($_POST['rating'] ?? 0)));
	$comment = trim($_POST['comment'] ?? '');
	if (!can_review_provider($customer_id, $provider_id)) {
		$review_error = 'You can only review a provider after a completed service.';
	} elseif (has_reviewed_provider($customer_id, $provider_id)) {
		$review_error = 'You have already reviewed this provider.';
	} else {
		$stmt = $db->prepare("INSERT INTO reviews (customer_id, provider_id, request_id, rating, comment, created_at) VALUES (?, ?, 0, ?, ?, NOW())");
		if ($stmt->execute([$customer_id, $provider_id, $rating, $comment])) {
			update_provider_rating($provider_id);
			$review_success = 'Thank you for your review!';
			$avg_rating = calculate_average_rating($provider_id);
			$reviews = get_provider_reviews($provider_id, 20);
		} else {
			$review_error = 'Failed to submit review. Please try again.';
		}
	}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Provider Profile | ServiGo</title>
	<link rel="stylesheet" href="assets/css/style.css">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include 'includes/site_header.php'; ?>
<div class="container py-5">
	<div class="row">
		<div class="col-md-4">
			<div class="card p-3 text-center">
				<img src="<?php echo $provider['profile_image'] ? htmlspecialchars($provider['profile_image']) : 'assets/images/default-profile.png'; ?>" class="rounded-circle mb-3" style="width:110px;height:110px;object-fit:cover;" alt="Profile">
				<h4><?php echo htmlspecialchars($provider['first_name'] . ' ' . $provider['last_name']); ?></h4>
				<div class="text-muted mb-2"><?php echo htmlspecialchars($provider['business_name'] ?? ''); ?></div>
				<div class="mb-2"><i class="fas fa-map-marker-alt text-primary-servigo"></i> <?php echo $provider['address'] ? htmlspecialchars($provider['address'] . ', ' . $provider['region']) : htmlspecialchars($provider['city'] . ', ' . $provider['region']); ?></div>
				<div class="mb-2"><span class="text-warning"><i class="fas fa-star"></i></span> <?php echo $avg_rating; ?> / 5</div>
			</div>
		</div>
		<div class="col-md-8">
			<div class="card p-3 mb-4">
				<h5 class="mb-3">About</h5>
				<p class="mb-1"><?php echo nl2br(htmlspecialchars($provider['business_description'] ?? '')); ?></p>
				<div class="text-muted">Experience: <?php echo intval($provider['experience_years'] ?? 0); ?> years</div>
			</div>
			<div class="card p-3 mb-4">
				<h5 class="mb-3">Reviews</h5>
				<?php if ($review_success): ?><div class="alert alert-success"><?php echo $review_success; ?></div><?php endif; ?>
				<?php if ($review_error): ?><div class="alert alert-danger"><?php echo $review_error; ?></div><?php endif; ?>
				<?php if (count($reviews) > 0): ?>
					<?php foreach ($reviews as $rev): ?>
					<div class="border rounded p-3 mb-2">
						<div class="d-flex justify-content-between">
							<div>
								<strong><?php echo htmlspecialchars($rev['first_name'] . ' ' . $rev['last_name']); ?></strong>
								<span class="ms-2 text-warning">
									<?php for ($i=0; $i<5; $i++): ?>
										<i class="fas fa-star<?php echo $i < intval($rev['rating']) ? '' : '-o'; ?>"></i>
									<?php endfor; ?>
								</span>
							</div>
							<small class="text-muted"><?php echo htmlspecialchars($rev['created_at']); ?></small>
						</div>
						<div class="mt-2"><?php echo nl2br(htmlspecialchars($rev['comment'])); ?></div>
					</div>
					<?php endforeach; ?>
				<?php else: ?>
					<div class="text-muted">No reviews yet.</div>
				<?php endif; ?>
			</div>
			<?php if (isset($_SESSION['user_id']) && ($_SESSION['user_type'] ?? '') === 'customer'): ?>
			<div class="card p-3">
				<h5 class="mb-3">Write a Review</h5>
				<form method="post">
					<div class="mb-3">
						<label class="form-label">Rating</label>
						<select name="rating" class="form-select" required>
							<option value="">Select rating</option>
							<?php for ($r=5; $r>=1; $r--): ?>
								<option value="<?php echo $r; ?>"><?php echo $r; ?> star<?php echo $r>1?'s':''; ?></option>
							<?php endfor; ?>
						</select>
					</div>
					<div class="mb-3">
						<label class="form-label">Comment (optional)</label>
						<textarea name="comment" class="form-control" rows="3" placeholder="Share your experience..."></textarea>
					</div>
					<button class="btn btn-servigo" type="submit">Submit Review</button>
				</form>
			</div>
			<?php endif; ?>
		</div>
	</div>
	<div class="text-center mt-4">
		<a href="providers.php" class="btn btn-outline-primary">Back to Providers</a>
	</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
