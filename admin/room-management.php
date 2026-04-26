<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dormitory</title>
  <link rel="stylesheet" href="/Dormonitory/assets/css/sidebar-navbar-styles.css" />
  <link rel="stylesheet" href="/Dormonitory/assets/css/admin-styles.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet" />
</head>

<body>
  <div id="sidebar-navbar"></div>

  <div class="layout">
    <div class="main">
      <button class="add-res-btn">
        <i class="bi bi-plus-lg"></i> Add New Room
      </button>

      <div class="card">
        <div class="top-bar">
          <div class="search">
            <i class="bi bi-search"></i>
            <input type="text" placeholder="Search room..." />
          </div>
        </div>

        <div class="room-grid">
          <!-- Room 101 – FULL -->
          <div class="room-card">
            <div class="room-top">
              <div class="room-icon"><i class="bi bi-door-open"></i></div>
              <span class="room-status room-full">FULL</span>
            </div>
            <div class="room-meta">
              <h3>Room 101</h3>
              <p>Single Suite</p>
            </div>
            <div class="occupancy-section">
              <div class="occupancy-label">
                <span>Occupancy</span>
                <span>1/1</span>
              </div>
              <div class="progress-bar">
                <div class="progress-fill room-full" style="width: 100%"></div>
              </div>
            </div>
            <div class="room-actions">
              <button class="btn-view">View Details</button>
              <button class="btn-remove">Remove</button>
            </div>
          </div>

          <!-- Room 102 – AVAILABLE -->
          <div class="room-card">
            <div class="room-top">
              <div class="room-icon"><i class="bi bi-door-open"></i></div>
              <span class="room-status room-available">AVAILABLE</span>
            </div>
            <div class="room-meta">
              <h3>Room 102</h3>
              <p>Double Suite</p>
            </div>
            <div class="occupancy-section">
              <div class="occupancy-label">
                <span>Occupancy</span>
                <span>1/2</span>
              </div>
              <div class="progress-bar">
                <div class="progress-fill" style="width: 50%"></div>
              </div>
            </div>
            <div class="room-actions">
              <button class="btn-view">View Details</button>
              <button class="btn-remove">Remove</button>
            </div>
          </div>

          <!-- Room 103 – MAINTENANCE -->
          <div class="room-card">
            <div class="room-top">
              <div class="room-icon"><i class="bi bi-door-open"></i></div>
              <span class="room-status room-maintenance">MAINTENANCE</span>
            </div>
            <div class="room-meta">
              <h3>Room 103</h3>
              <p>Double Suite</p>
            </div>
            <div class="occupancy-section">
              <div class="occupancy-label">
                <span>Occupancy</span>
                <span>0/2</span>
              </div>
              <div class="progress-bar">
                <div class="progress-fill" style="width: 0%"></div>
              </div>
            </div>
            <div class="room-actions">
              <button class="btn-view">View Details</button>
              <button class="btn-remove">Remove</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="/Dormonitory/assets/js/sidebar-navbar.js"></script>
</body>

</html>