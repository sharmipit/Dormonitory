<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$display_name = $_SESSION['firstname'] ?? "Resident";
?>

<div class="navbar" id="navbar">
  <div class="navbar-left">
    <button class="hamburger" id="hamburger">
      <span></span><span></span><span></span>
    </button>
    <span class="navbar-title"></span>
  </div>

  <div class="navbar-right">
    <div class="nav-avatar" id="profile-trigger" style="cursor: pointer;">
      <img src="/Dormonitory/assets/img/cute-duck-for-good-luck.jpeg" alt="Profile" />
    </div>

    <div class="profile-modal" id="profile-modal">
      <div class="modal-header">
        <small>Signed in as</small>
        <h4 id="user-name-display"><?php echo htmlspecialchars($display_name); ?></h4>
        <span class="access-tag">Resident Access</span>
      </div>
      <div class="modal-divider"></div>
      <a href="/Dormonitory/resident/resident-profile.php" class="modal-btn btn-profile">
        <i class="bi bi-person"></i> My Profile
      </a>

      <a href="javascript:void(0)" class="modal-btn btn-signout" onclick="showSignOutModal()">
        <i class="bi bi-box-arrow-right"></i> Sign Out
      </a>
    </div>
  </div>
</div>

<aside class="sidebar" id="sidebar">
  <div class="brand">
    <img src="/Dormonitory/assets/img/secondary-logo.png" alt="Brand" />
  </div>
  <div class="divider"></div>
  <nav>
    <ul>
      <li><a href="/Dormonitory/resident/home.php"><i class="bi bi-house"></i><span>Home</span></a></li>
      <li><a href="/Dormonitory/resident/resident-profile.php"><i class="bi bi-person"></i><span>My Profile</span></a>
      </li>
      <li><a href="/Dormonitory/resident/digital-key.php"><i class="bi bi-key"></i><span>Digital Key</span></a></li>
      <li><a href="/Dormonitory/resident/invite-visitor.php"><i class="bi bi-person-plus"></i><span>Invite
            Visitor</span></a></li>
    </ul>
  </nav>
  <div class="sidebar-bottom">
    <div class="divider-bottom"></div>
    <a href="javascript:void(0)" class="signout-btn" onclick="showSignOutModal()">
      <i class="bi bi-box-arrow-right"></i><span>Sign Out</span>
    </a>
  </div>
</aside>

<!-- Sign Out Confirmation Modal (Admin Style) -->
<div class="signout-modal-overlay" id="signout-modal">
  <div class="signout-modal-content">
    <i class="bi bi-exclamation-circle" style="font-size: 48px; color: #dc2626;"></i>
    <h3>Confirm Sign Out</h3>
    <p>Are you sure you want to sign out of your account?</p>
    <div class="modal-actions">
      <button class="modal-btn cancel-btn" id="cancel-signout">Cancel</button>
      <a href="/Dormonitory/index.html" class="modal-btn confirm-btn" style="background: #dc2626; color: white;">Sign
        Out</a>
    </div>
  </div>
</div>

<script>
  function showSignOutModal() {
    document.getElementById('signout-modal').style.display = 'flex';
  }
  document.getElementById('cancel-signout').addEventListener('click', function () {
    document.getElementById('signout-modal').style.display = 'none';
  });
</script>