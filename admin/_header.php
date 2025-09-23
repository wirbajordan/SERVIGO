<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
function admin_avatar_path($path) {
  if (!$path) return '/SERVIGO/assets/images/default-profile.png';
  // Absolute URL or site-root path
  if (preg_match('#^https?://#i', $path) || strpos($path, '/') === 0) return $path;
  // Stored relative path like assets/images/...
  return '/SERVIGO/' . ltrim($path, '/');
}
function admin_link_active($absPath) {
  $uri = $_SERVER['REQUEST_URI'] ?? '';
  $uri = strtok($uri, '?');
  return $uri === $absPath;
}
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-bold text-primary-servigo" href="/SERVIGO/index.php">
      <i class="fas fa-tools me-2"></i>ServiGo
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminMainNav" aria-controls="adminMainNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="offcanvas offcanvas-end" tabindex="-1" id="adminMainNav">
      <div class="offcanvas-header">
        <h5 class="offcanvas-title">Menu</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
      </div>
      <div class="offcanvas-body">
        <ul class="navbar-nav me-auto">
          <li class="nav-item"><a class="nav-link<?php echo admin_link_active('/SERVIGO/index.php') ? ' active' : ''; ?>" href="/SERVIGO/index.php" aria-current="<?php echo admin_link_active('/SERVIGO/index.php') ? 'page' : 'false'; ?>">Home</a></li>
          <li class="nav-item"><a class="nav-link<?php echo admin_link_active('/SERVIGO/services.php') ? ' active' : ''; ?>" href="/SERVIGO/services.php" aria-current="<?php echo admin_link_active('/SERVIGO/services.php') ? 'page' : 'false'; ?>">Services</a></li>
          <li class="nav-item"><a class="nav-link<?php echo admin_link_active('/SERVIGO/providers.php') ? ' active' : ''; ?>" href="/SERVIGO/providers.php" aria-current="<?php echo admin_link_active('/SERVIGO/providers.php') ? 'page' : 'false'; ?>">Service Providers</a></li>
          <li class="nav-item"><a class="nav-link<?php echo admin_link_active('/SERVIGO/about.php') ? ' active' : ''; ?>" href="/SERVIGO/about.php" aria-current="<?php echo admin_link_active('/SERVIGO/about.php') ? 'page' : 'false'; ?>">About</a></li>
          <li class="nav-item"><a class="nav-link<?php echo admin_link_active('/SERVIGO/contact.php') ? ' active' : ''; ?>" href="/SERVIGO/contact.php" aria-current="<?php echo admin_link_active('/SERVIGO/contact.php') ? 'page' : 'false'; ?>">Contact</a></li>
          <li class="nav-item"><a class="nav-link<?php echo admin_link_active('/SERVIGO/admin/dashboard.php') ? ' active' : ''; ?>" href="/SERVIGO/admin/dashboard.php" aria-current="<?php echo admin_link_active('/SERVIGO/admin/dashboard.php') ? 'page' : 'false'; ?>">Dashboard</a></li>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">Admin</a>
            <ul class="dropdown-menu" aria-labelledby="adminDropdown">
              <li><a class="dropdown-item" href="provider_verification.php">Provider Verification</a></li>
              <li><a class="dropdown-item" href="users.php">Users</a></li>
              <li><a class="dropdown-item" href="requests.php">Service Requests</a></li>
              <li><a class="dropdown-item" href="audits.php">Audit Logs</a></li>
              <li><a class="dropdown-item" href="notifications.php">Admin Notifications</a></li>
            </ul>
          </li>
        </ul>
        <ul class="navbar-nav ms-auto">
          <li class="nav-item me-2">
            <a class="nav-link position-relative" href="../notifications.php" aria-label="Notifications">
              <i class="fas fa-bell"></i>
            </a>
          </li>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              <img src="<?php echo admin_avatar_path($_SESSION['profile_image'] ?? null); ?>" class="rounded-circle me-2" style="width:28px;height:28px;object-fit:cover;" alt="Profile">
              <?php echo isset($_SESSION['first_name']) ? htmlspecialchars($_SESSION['first_name']) : 'Admin'; ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
              <li><a class="dropdown-item" href="../dashboard.php">My Dashboard</a></li>
              <?php if(isset($_SESSION['user_type']) && $_SESSION['user_type']==='admin'): ?>
              <li><a class="dropdown-item" href="../dashboard.php?main=1">Main Dashboard</a></li>
              <?php endif; ?>
              <li><a class="dropdown-item" href="../edit_profile.php">Edit Profile</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-danger" href="../logout.php">Logout</a></li>
            </ul>
          </li>
        </ul>
      </div>
    </div>
  </div>
</nav>
