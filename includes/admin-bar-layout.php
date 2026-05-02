<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$display_name = $_SESSION['firstname'] ?? "Admin User";
?>

<div class="navbar" id="navbar">
  <div class="navbar-left">
    <button class="hamburger" id="hamburger">
      <span></span>
      <span></span>
      <span></span>
    </button>
    <span class="navbar-title"></span>
  </div>
<div class="navbar-right">
  <div class="nav-avatar" id="profile-trigger">
    <img src="/Dormonitory/assets/img/cute-duck-for-good-luck.jpeg" alt="Profile" />
  </div>

  <div class="profile-modal" id="profile-modal">
    <div class="modal-header">
      <small>Signed in as</small>
      <h4 id="user-name-display"><?php echo htmlspecialchars($display_name); ?></h4>
        <span class="access-tag">Admin Access</span>
    </div>

    <div class="modal-divider"></div>

    <a href="/Dormonitory/admin/admin-profile.php" class="modal-btn btn-profile">
      <i class="bi bi-person"></i>
      My Profile
    </a>

    <a href="#" class="modal-btn btn-signout">
      <i class="bi bi-box-arrow-right"></i>
      Sign Out
    </a>
  </div>
</div>

<aside class="sidebar" id="sidebar">
  <div class="brand">
    <img src="/Dormonitory/assets/img/secondary-logo.png" alt="Brand" />
  </div>
  <div class="divider"></div>

  <nav>
    <ul>
   
      <li>
        <a href="/Dormonitory/admin/dashboard.php">
          <i class="bi bi-grid-1x2"></i><span>Dashboard</span>
        </a>
      </li>

      <li>
        <a href="/Dormonitory/admin/admin-profile.php">
          <i class="bi bi-person"></i><span>My Profile</span>
        </a>
      </li>

      <li>
        <a href="/Dormonitory/admin/resident-management.php">
          <i class="bi bi-people"></i><span>Residents</span>
        </a>
      </li>

      <!-- Visitor Log -->
      <li>
        <a href="/Dormonitory/admin/visitor-management.php">
          <i class="bi bi-person-badge"></i><span>Visitor Log</span>
        </a>
      </li>

      <li>
        <a href="/Dormonitory/admin/room-management.php">
          <i class="bi bi-door-open"></i><span>Rooms</span>
        </a>
      </li>

      <!-- Announcements -->
      <li>
        <a href="/Dormonitory/admin/announcements.php">
          <i class="bi bi-megaphone"></i><span>Announcements</span>
        </a>
      </li>

      <li>
        <a href="/Dormonitory/admin/security-center.php">
          <i class="bi bi-shield-exclamation"></i><span>Security Center</span>
        </a>
      </li>
    </ul>
  </nav>

  <div class="sidebar-bottom">
    <div class="divider-bottom"></div>
    <a href="#" class="signout-btn">
      <i class="bi bi-box-arrow-right"></i>
      <span>Sign Out</span>
    </a>
  </div>
</aside>

<div class="signout-modal-overlay" id="signout-modal">
  <div class="signout-modal-content">
    <i class="bi bi-exclamation-circle" style="font-size: 40px; color: #dc2626;"></i>
    <h3>Confirm Sign Out</h3>
    <p>Are you sure you want to sign out of your account?</p>
    <div class="modal-actions">
      <button class="modal-btn cancel-btn" id="cancel-signout">Cancel</button>
      <a href="/Dormonitory/index.html" class="modal-btn confirm-btn">Sign Out</a>
    </div>
  </div>
</div>