<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
function site_avatar_path($path) {
  if (!$path) return '/SERVIGO/assets/images/default-profile.png';
  // If already absolute starting with '/' return as-is; otherwise prefix with site root
  if (strpos($path, '/') === 0) return $path;
  return '/SERVIGO/' . ltrim($path, '/');
}
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
  <div class="container">
    <a class="navbar-brand text-primary-servigo fw-bold" href="/SERVIGO/index.php"><i class="fas fa-tools me-2"></i>ServiGo</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#siteMainNav" aria-controls="siteMainNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="offcanvas offcanvas-end" tabindex="-1" id="siteMainNav">
      <div class="offcanvas-header">
        <h5 class="offcanvas-title">Menu</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
      </div>
      <div class="offcanvas-body">
        <ul class="navbar-nav me-auto">
          <li class="nav-item"><a href="/SERVIGO/index.php" class="nav-link">Home</a></li>
          <li class="nav-item"><a href="/SERVIGO/services.php" class="nav-link">Services</a></li>
          <li class="nav-item"><a href="/SERVIGO/providers.php" class="nav-link">Service Providers</a></li>
          <li class="nav-item"><a href="/SERVIGO/about.php" class="nav-link">About</a></li>
          <li class="nav-item"><a href="/SERVIGO/contact.php" class="nav-link">Contact</a></li>
        </ul>
        <ul class="navbar-nav ms-auto">
          <?php if(isset($_SESSION['user_id'])): ?>
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" data-bs-toggle="dropdown" aria-expanded="false">
                <img src="<?php echo site_avatar_path($_SESSION['profile_image'] ?? null); ?>" class="rounded-circle me-2" style="width:28px;height:28px;object-fit:cover;" alt="Profile">
                <?php echo htmlspecialchars($_SESSION['first_name'] ?? 'Account'); ?>
              </a>
              <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="/SERVIGO/dashboard.php">Dashboard</a></li>
                <li><a class="dropdown-item" href="/SERVIGO/edit_profile.php">Edit Profile</a></li>
                <?php if(isset($_SESSION['user_type']) && $_SESSION['user_type']==='admin'): ?>
                <li><a class="dropdown-item" href="/SERVIGO/admin/dashboard.php">Admin Dashboard</a></li>
                <?php endif; ?>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="/SERVIGO/logout.php">Logout</a></li>
              </ul>
            </li>
          <?php else: ?>
            <li class="nav-item"><a href="/SERVIGO/login.php" class="nav-link">Login</a></li>
            <li class="nav-item"><a href="/SERVIGO/register.php" class="btn btn-servigo ms-lg-2">Register</a></li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </div>
</nav>
