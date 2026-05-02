<?php
session_start();
include('../config/db.php');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
  header('Location: ../auth/login-resident-portal.php');
  exit();
}

$stmt = $pdo->prepare("
    SELECT r.first_name, r.last_name, ro.room_number 
    FROM resident r
    LEFT JOIN room ro ON r.room_id = ro.room_id
    WHERE r.resident_id = ?
");
$stmt->execute([$_SESSION['id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$fullname = $user ? $user['first_name'] . ' ' . $user['last_name'] : 'Resident';
$room_number = $user && $user['room_number'] ? 'Room ' . $user['room_number'] : 'No Room Assigned';

// Fetch the latest log entry for this resident
$logStmt = $pdo->prepare("
    SELECT log_type 
    FROM resident_log 
    WHERE resident_id = ? 
    ORDER BY log_time DESC 
    LIMIT 1
");
$logStmt->execute([$_SESSION['id']]);
$latestLog = $logStmt->fetch(PDO::FETCH_ASSOC);

// Determine status
if ($latestLog && $latestLog['log_type'] === 'inside') {
  $statusLabel = 'INSIDE THE BUILDING';
  $statusClass = 'status-inside';
} elseif ($latestLog && $latestLog['log_type'] === 'outside') {
  $statusLabel = 'OUTSIDE THE BUILDING';
  $statusClass = 'status-outside';
} else {
  $statusLabel = 'STATUS UNKNOWN';
  $statusClass = 'status-unknown';
}
?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Home | Dormonitory</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/Dormonitory/assets/css/sidebar-navbar-styles.css" />
  <link rel="stylesheet" href="/Dormonitory/assets/css/resident-styles.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />

  <style>
    .status-box.status-inside {
      background-color: #16a34a;
      color: #fff;
    }

    .status-box.status-outside {
      background-color: #dc2626;
      color: #fff;
    }

    .status-box.status-unknown {
      background-color: #6b7280;
      color: #fff;
    }
  </style>
</head>

<body>
  <div id="sidebar-navbar"></div>

  <div class="layout">
    <div class="main">
      <header class="welcome-banner">
        <div class="user-info">
          <h1>Hi, <?php echo htmlspecialchars($fullname); ?>!</h1>
          <p><i class="bi bi-geo-alt-fill"></i> <?php echo htmlspecialchars($room_number); ?> • Rubia Dormitory</p>
        </div>
        <div class="status-badge">
          <span class="label">CURRENT STATUS</span>
          <span class="status-box <?php echo $statusClass; ?>">
            <?php echo $statusLabel; ?>
          </span>
        </div>
      </header>

      <div class="dashboard-grid">
        <section class="card activities-card">
          <div class="card-header">
            <h2><i class="bi bi-clock"></i> Activities</h2>
            <a href="#" class="view-all">View All</a>
          </div>
          <div class="activity-list">
            <div class="activity-item" data-location="Main Lobby Entrance" data-device="iPhone 13 Pro"
              data-method="Digital Key (NFC)" data-status="Authorized">
              <div class="item-top">
                <div class="icon-box"><i class="bi bi-box-arrow-in-right"></i></div>
                <div class="item-content">
                  <h3>Entry Log</h3>
                  <p>Access granted at the Main Entrance using your personal Digital Key...
                    <span class="full-text" style="display:none;">Access granted at the Main Entrance using your
                      personal Digital Key. The system verified your credentials and unlocked the security gate.</span>
                  </p>
                </div>
              </div>
              <div class="item-footer">
                <span class="time">About 5 hours ago</span>
                <a href="javascript:void(0)" class="read-more activity-btn">Read More <i
                    class="bi bi-arrow-right"></i></a>
              </div>
            </div>

            <div class="activity-item" data-location="Main Lobby Exit" data-device="iPhone 13 Pro"
              data-method="Digital Key (NFC)" data-status="Departure Logged">
              <div class="item-top">
                <div class="icon-box"><i class="bi bi-box-arrow-right"></i></div>
                <div class="item-content">
                  <h3>Exit Log</h3>
                  <p>Exit recorded at the Main Entrance. Your Digital Key was used to unlock...
                    <span class="full-text" style="display:none;">Exit recorded at the Main Entrance. Your Digital Key
                      was used to unlock the outward-bound security gate.</span>
                  </p>
                </div>
              </div>
              <div class="item-footer">
                <span class="time">1 day ago</span>
                <a href="javascript:void(0)" class="read-more activity-btn">Read More <i
                    class="bi bi-arrow-right"></i></a>
              </div>
            </div>

            <div class="activity-item" data-location="Visitor Management System" data-device="Sharmagne Gamboa"
              data-method="04-15-26, 10:00AM" data-status="Lobby & Second Floor">
              <div class="item-top">
                <div class="icon-box"><i class="bi bi-person-check"></i></div>
                <div class="item-content">
                  <h3>Guest Pass Created</h3>
                  <p>A temporary digital access code has been generated for your visitor...
                    <span class="full-text" style="display:none;">A temporary digital access code has been generated for
                      your visitor, Sharmagne Gamboa. This pass is valid for 24 hours.</span>
                  </p>
                </div>
              </div>
              <div class="item-footer">
                <span class="time">1 day ago</span>
                <a href="javascript:void(0)" class="read-more activity-btn">Read More <i
                    class="bi bi-arrow-right"></i></a>
              </div>
            </div>

            <div class="activity-item" data-location="North Wing Entrance" data-device="iPhone 13 Pro"
              data-method="Digital Key (NFC)" data-status="Authorized">
              <div class="item-top">
                <div class="icon-box"><i class="bi bi-box-arrow-in-right"></i></div>
                <div class="item-content">
                  <h3>Entry Log</h3>
                  <p>Access granted at the North Wing side entrance. This entrance is restricted...
                    <span class="full-text" style="display:none;">Access granted at the North Wing side entrance. This
                      entrance is restricted to residents only after 10:00 PM.</span>
                  </p>
                </div>
              </div>
              <div class="item-footer">
                <span class="time">3 days ago</span>
                <a href="javascript:void(0)" class="read-more activity-btn">Read More <i
                    class="bi bi-arrow-right"></i></a>
              </div>
            </div>
          </div>
        </section>

        <section class="card announcements-card">
          <div class="card-header">
            <h2><i class="bi bi-megaphone"></i> Announcements</h2>
            <div class="filters">
              <button class="btn-filter active">All</button>
              <button class="btn-filter">Security</button>
              <button class="btn-filter">Community</button>
              <button class="btn-filter">Maintenance</button>
            </div>
          </div>
          <div class="announcement-grid">
            <article class="announcement-item">
              <div class="image-container">
                <img src="../assets/img/img1.png" alt="Maintenance">
                <span class="category-badge-inline">MAINTENANCE</span>
              </div>
              <div class="item-body">
                <h3>Maintenance Notice</h3>
                <p>Dear Residents, please be informed that the main elevators in Block...</p>
                <div class="item-footer">
                  <span>About 2 hours ago</span>
                  <a href="javascript:void(0)" class="read-more announcement-btn" data-title="Maintenance Notice"
                    data-category="MAINTENANCE" data-date="April 2, 2026" data-img="../assets/img/img1.png"
                    data-content="Dear Residents, please be informed that the main elevators in Block B will be undergoing essential maintenance tomorrow, April 2nd, from 10:00 AM to 2:00 PM. During this period, the elevators will be completely out of service. We recommend using the service stairs or planning your movements accordingly. We apologize for any inconvenience this may cause and thank you for your cooperation in keeping our facilities in top condition.">
                    Read More <i class="bi bi-arrow-right"></i></a>
                </div>
              </div>
            </article>

            <article class="announcement-item">
              <div class="image-container">
                <img src="../assets/img/img2.png" alt="Community">
                <span class="category-badge-inline">COMMUNITY</span>
              </div>
              <div class="item-body">
                <h3>Community Event</h3>
                <p>Get ready for our monthly Rooftop BBQ! Join your fellow residents this Friday...</p>
                <div class="item-footer">
                  <span>Posted 2 days ago</span>
                  <a href="javascript:void(0)" class="read-more announcement-btn" data-title="Community Event"
                    data-category="COMMUNITY" data-date="April 4, 2026" data-img="../assets/img/img2.png"
                    data-content="Get ready for our monthly Rooftop BBQ! Join your fellow residents this Friday at 6:00 PM on the Level 15 terrace. We will provide the grills, charcoal, and a selection of meats and vegetarian options. Please bring your own beverages and any specific side dishes you would like to share. It is a great opportunity to meet your neighbors and enjoy the sunset. See you there!">
                    Read More <i class="bi bi-arrow-right"></i></a>
                </div>
              </div>
            </article>

            <article class="announcement-item">
              <div class="image-container">
                <img src="../assets/img/img3.png" alt="Gym">
                <span class="category-badge-inline">COMMUNITY</span>
              </div>
              <div class="item-body">
                <h3>New Gym Equipment</h3>
                <p>We are excited to announce that the BukSU Fitness Center has received new gear...</p>
                <div class="item-footer">
                  <span>Posted 4 days ago</span>
                  <a href="javascript:void(0)" class="read-more announcement-btn" data-title="New Gym Equipment"
                    data-category="COMMUNITY" data-date="April 5, 2026" data-img="../assets/img/img3.png"
                    data-content="We are excited to announce that the BukSU Fitness Center has been upgraded with state-of-the-art equipment! We have added three new high-performance treadmills with built-in entertainment screens and two adjustable weight benches. These additions are part of our ongoing commitment to providing the best amenities for our residents. Please remember to wipe down the equipment after use and follow all posted safety guidelines.">
                    Read More <i class="bi bi-arrow-right"></i></a>
                </div>
              </div>
            </article>

            <article class="announcement-item">
              <div class="image-container">
                <img src="../assets/img/img4.png" alt="Security">
                <span class="category-badge-inline">SECURITY</span>
              </div>
              <div class="item-body">
                <h3>Security Update</h3>
                <p>Following a recent security review, we would like to implement new lobby access...</p>
                <div class="item-footer">
                  <span>Posted 1 week ago</span>
                  <a href="javascript:void(0)" class="read-more announcement-btn" data-title="Security Update"
                    data-category="SECURITY" data-date="April 6, 2026" data-img="../assets/img/img4.png"
                    data-content="Following a recent security review, we would like to remind all residents of the importance of maintaining building security. Please ensure your unit door is locked at all times, even when you are inside. Do not share your digital access keys or visitor codes with unauthorized individuals. If you notice any suspicious activity or unauthorized persons in the building, please contact the 24/7 security desk immediately at extension 99. Thank you for helping us keep our community safe.">
                    Read More <i class="bi bi-arrow-right"></i></a>
                </div>
              </div>
            </article>
          </div>
        </section>
      </div>
    </div>
  </div>

  <div id="universalModal" class="modal-overlay">
    <div class="modal-container" id="modalContainer">
    </div>
  </div>

  <template id="announcementTemplate">
    <div class="modal-image-wrapper">
      <img id="modalImg" src="" alt="Announcement">
      <span id="modalCategory" class="category-badge"></span>
      <button class="close-modal" onclick="closeUniversalModal()">&times;</button>
    </div>
    <div class="modal-body">
      <div class="modal-date"><i class="bi bi-calendar-event"></i> <span id="modalDate"></span></div>
      <h2 id="modalTitle"></h2>
      <p id="modalDescription" class="modal-description"></p>
    </div>
  </template>

  <template id="viewAllTemplate">
    <div class="modal-header-simple">
      <div class="header-title">
        <i class="bi bi-clock"></i>
        <div>
          <h2>Recent Activities</h2>
          <p>Full log of your entries, exits, and guest passes</p>
        </div>
      </div>
      <button class="close-modal" onclick="closeUniversalModal()">&times;</button>
    </div>
    <div class="modal-list-body" id="fullActivityList"></div>
  </template>

  <template id="activityDetailTemplate">
    <div class="modal-detail-wrapper">
      <button class="close-modal-fixed" onclick="closeUniversalModal()">&times;</button>
      <div class="detail-card">
        <div class="detail-icon-box" id="detailIcon"></div>
        <h2 id="detailTitle">LOG</h2>
        <p class="detail-subtitle" id="detailSubtitle">Activity Detail</p>
        <p class="detail-text" id="detailFullDescription"></p>
        <div class="detail-divider"></div>
        <div class="location-tag"><i class="bi bi-geo-alt-fill"></i> <span id="detailLocation"></span></div>
        <div class="detail-divider"></div>
        <div class="info-grid">
          <div class="info-row"><span>Device</span><strong id="detailDevice"></strong></div>
          <div class="info-row"><span>Method</span><strong id="detailMethod"></strong></div>
          <div class="info-row"><span>Status</span><strong id="detailStatus"></strong></div>
        </div>
      </div>
      <div class="detail-time-footer"><i class="bi bi-clock"></i> <span id="detailTime"></span></div>
    </div>
  </template>
  <script src="/Dormonitory/assets/js/sidebar-navbar.js?v=2"></script>
  <script src="/Dormonitory/assets/js/main.js"></script>
</body>

</html>