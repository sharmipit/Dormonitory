<?php
session_start();
include('../config/db.php');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['admin_id'])) {
  header("Location: /Dormonitory/auth/login-management-access.php");
  exit();
}

// Fetch admin name
$stmt = $pdo->prepare("SELECT first_name, last_name FROM admin WHERE admin_id = ?");
$stmt->execute([$_SESSION['admin_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$fullname = $user ? $user['first_name'] . ' ' . $user['last_name'] : 'Admin';

// ── STAT 1: Total residents ───────────────────────────────────────────────────
$stmt = $pdo->query("SELECT COUNT(*) FROM resident");
$totalResidents = (int) $stmt->fetchColumn();

// ── STAT 2: Occupancy % ───────────────────────────────────────────────────────
$stmt = $pdo->query("SELECT SUM(max_capacity) FROM room");
$totalCapacity = (int) $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM resident WHERE room_id IS NOT NULL");
$totalAssigned = (int) $stmt->fetchColumn();

$occupancyPct = $totalCapacity > 0
  ? round(($totalAssigned / $totalCapacity) * 100)
  : 0;

if ($occupancyPct >= 90)
  $occupancyBadge = 'Near full';
elseif ($occupancyPct >= 70)
  $occupancyBadge = 'Stable';
else
  $occupancyBadge = 'Available slots';

// ── STAT 3: Total visitors today ─────────────────────────────────────────────
$stmt = $pdo->query("SELECT COUNT(*) FROM visitor_log WHERE visit_date = CURDATE()");
$totalVisitors = (int) $stmt->fetchColumn();
?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard</title>
  <link rel="stylesheet" href="/Dormonitory/assets/css/sidebar-navbar-styles.css" />
  <link rel="stylesheet" href="/Dormonitory/assets/css/admin-styles.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
</head>

<body>

  <div id="sidebar-navbar"></div>

  <div class="layout">
    <div class="main">
      <div class="greeting-card">
        <h1>Hi, <?php echo htmlspecialchars($fullname); ?></h1> <!-- DISPLAYS NAME FROM DATABASE -->
        <p>Here's what's happening with your property today.</p>
      </div>

      <div class="quick-actions-card">
        <h2>Quick Actions</h2>
        <div class="actions-grid">
          <a href="resident-management.php" class="quick-action-link">
            <button class="action-btn">
              <div class="icon-placeholder">
                <i class="bi bi-person"></i>
              </div>
              Manage Residents
            </button>
          </a>

          <a href="room-management.php" class="quick-action-link">
            <button class="action-btn">
              <div class="icon-placeholder">
                <i class="bi bi-door-open"></i>
              </div>
              Manage Rooms
            </button>
          </a>

          <a href="visitor-management.php" class="quick-action-link">
            <button class="action-btn">
              <div class="icon-placeholder">
                <i class="bi bi-person-badge-fill"></i>
              </div>
              View Visitor Log
            </button>
          </a>

          <a href="announcements.php" class="quick-action-link">
            <button class="action-btn">
              <div class="icon-placeholder">
                <i class="bi bi-file-earmark-text"></i>
              </div>
              Generate Report
            </button>
          </a>
        </div>
      </div>

      <div class="stat-grid">
        <div class="stat-card">
          <div class="stat-left">
            <div class="stat-top">
              <div class="stat-icon"><i class="bi bi-people"></i></div>
              <span class="stat-label">Total<br />Residents</span>
            </div>
            <div class="stat-bottom">
              <div class="stat-value">
                <?php echo $totalResidents; ?>
              </div>
            </div>
          </div>
          <i class="bi bi-people stat-deco"></i>
          <div class="stat-badge">Registered residents</div>
        </div>

        <div class="stat-card">
          <div class="stat-left">
            <div class="stat-top">
              <div class="stat-icon"><i class="bi bi-door-open"></i></div>
              <span class="stat-label">Current<br />Occupancy</span>
            </div>
            <div class="stat-bottom">
              <div class="stat-value">
                <?php echo $occupancyPct; ?>%
              </div>
            </div>
          </div>
          <i class="bi bi-door-open stat-deco"></i>
          <div class="stat-badge">
            <?php echo $occupancyBadge; ?> ·
            <?php echo $totalAssigned; ?>/
            <?php echo $totalCapacity; ?> beds
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-left">
            <div class="stat-top">
              <div class="stat-icon"><i class="bi bi-person"></i></div>
              <span class="stat-label">Total<br />Visitors</span>
            </div>
            <div class="stat-bottom">
              <div class="stat-value">
                <?php echo $totalVisitors; ?>
              </div>
            </div>
          </div>
          <i class="bi bi-person stat-deco"></i>
          <div class="stat-badge">Today</div>
        </div>

        <!-- Security Alerts — keep as-is -->
        <div class="stat-card">
          <div class="stat-left">
            <div class="stat-top">
              <div class="stat-icon"><i class="bi bi-exclamation-circle"></i></div>
              <span class="stat-label">Security<br />Alerts</span>
            </div>
            <div class="stat-bottom">
              <div class="stat-value">10</div>
            </div>
          </div>
          <i class="bi bi-exclamation-circle stat-deco"></i>
          <div class="stat-badge">Requires attention</div>
        </div>
      </div>

      <div class="main-grid">
        <div class="chart-card">
          <div class="chart-header">
            <div>
              <div class="chart-title">Building Occupancy Trends</div>
              <div class="chart-subtitle">7-day movement analysis</div>
            </div>
            <button class="range-btn">
              Last Seven Days
              <svg viewBox="0 0 24 24">
                <polyline points="6 9 12 15 18 9" />
              </svg>
            </button>
          </div>
          <div class="chart-wrap">
            <svg class="chart" viewBox="0 0 620 240" preserveAspectRatio="none">
              <defs>
                <linearGradient id="areaGrad" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="0%" stop-color="#4646d6" stop-opacity="0.18" />
                  <stop offset="100%" stop-color="#4646d6" stop-opacity="0.01" />
                </linearGradient>
              </defs>
              <line x1="44" y1="20" x2="610" y2="20" class="grid-line" />
              <line x1="44" y1="80" x2="610" y2="80" class="grid-line" />
              <line x1="44" y1="140" x2="610" y2="140" class="grid-line" />
              <line x1="44" y1="200" x2="610" y2="200" class="grid-line" />

              <text x="36" y="24" class="y-label" text-anchor="end">400</text>
              <text x="36" y="84" class="y-label" text-anchor="end">300</text>
              <text x="36" y="144" class="y-label" text-anchor="end">
                200
              </text>
              <text x="36" y="204" class="y-label" text-anchor="end">
                100
              </text>
              <text x="36" y="230" class="y-label" text-anchor="end">0</text>

              <path class="area-path" d="
                  M 92,110
                  C 130,120 155,130 183,132.5
                  C 210,135 230,138 275,140
                  C 295,141 310,150 348,168.5
                  C 375,172 410,165 440,155
                  C 470,145 490,130 525,120
                  C 555,112 575,60 608,20
                  L 608,230 L 92,230 Z
                " />

              <path class="line-path" d="
                  M 92,110
                  C 130,120 155,130 183,132.5
                  C 210, 135 230,138 275,140
                  C 295,141 310,150 348,168.5
                  C 375,172 410,165 440,155
                  C 470,145 490,130 525,120
                  C 555,112 575,60 608,20
                " />

              <g class="tooltip-box">
                <rect x="310" y="115" width="96" height="44" rx="9" fill="#2e2e9e" />
                <text x="358" y="133" text-anchor="middle" fill="#fff" font-size="12" font-weight="700"
                  font-family="'Inter',sans-serif">
                  Thursday
                </text>
                <text x="358" y="150" text-anchor="middle" fill="rgba(255,255,255,0.78)" font-size="11"
                  font-family="'Inter',sans-serif">
                  Count: 102
                </text>
                <line x1="348" y1="159" x2="348" y2="178" stroke="#2e2e9e" stroke-width="1.5" />
                <circle cx="348" cy="168.5" r="5" fill="#fff" stroke="#4646d6" stroke-width="2" />
              </g>

              <text x="92" y="230" class="x-label">Mon</text>
              <text x="183" y="230" class="x-label">Tue</text>
              <text x="275" y="230" class="x-label">Wed</text>
              <text x="348" y="230" class="x-label">Thu</text>
              <text x="440" y="230" class="x-label">Fri</text>
              <text x="525" y="230" class="x-label">Sat</text>
              <text x="608" y="230" class="x-label">Sun</text>
            </svg>
          </div>
        </div>

        <div class="traffic-card">
          <div class="traffic-header">
            <div class="traffic-title">Live Traffic Monitor</div>
            <span class="live-badge">Live Updates</span>
          </div>

          <div class="traffic-list">
            <div class="traffic-item">
              <div class="person-avatar">
                <i class="bi bi-person-fill" style="color: white"></i>
              </div>
              <div class="person-info">
                <div class="person-name">Erin Zoe Regalado</div>
                <div class="person-meta">10:45 AM</div>
              </div>
              <span class="status-tag status-inside">INSIDE</span>
            </div>

            <div class="traffic-item">
              <div class="person-avatar">
                <i class="bi bi-person-fill" style="color: white"></i>
              </div>
              <div class="person-info">
                <div class="person-name">Sharmagne Gamboa</div>
                <div class="person-meta">08:27 AM</div>
              </div>
              <span class="status-tag status-outside">OUTSIDE</span>
            </div>

            <div class="traffic-item">
              <div class="person-avatar">
                <i class="bi bi-person-fill" style="color: white"></i>
              </div>
              <div class="person-info">
                <div class="person-name">Gwyneth Miasco</div>
                <div class="person-meta">09:04 AM</div>
              </div>
              <span class="status-tag status-inside">INSIDE</span>
            </div>

            <div class="traffic-item">
              <div class="person-avatar">
                <i class="bi bi-person-fill" style="color: white"></i>
              </div>
              <div class="person-info">
                <div class="person-name">Hazel Ann Carillo</div>
                <div class="person-meta">01:43 PM</div>
              </div>
              <span class="status-tag status-inside">INSIDE</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="/Dormonitory/assets/js/sidebar-navbar.js"></script>
</body>

</html>