<?php
session_start();
include('../config/db.php');

// Redirect to login if not authenticated
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

// Calculate occupancy percentage; default to 0 if no capacity exists
$occupancyPct = $totalCapacity > 0
  ? round(($totalAssigned / $totalCapacity) * 100)
  : 0;

// Set occupancy status badge based on percentage thresholds
if ($occupancyPct >= 90)
  $occupancyBadge = 'Near full';
elseif ($occupancyPct >= 70)
  $occupancyBadge = 'Stable';
else
  $occupancyBadge = 'Available slots';

// ── STAT 3: Total visitors today ─────────────────────────────────────────────
$stmt = $pdo->query("SELECT COUNT(*) FROM visitor_log WHERE visit_date = CURDATE()");
$totalVisitors = (int) $stmt->fetchColumn();

// ── STAT 4: Available beds ────────────────────────────────────────────────────
$availableBeds = $totalCapacity - $totalAssigned;

// ── CHART: 7-day ins and outs ─────────────────────────────────────────────────
$stmt = $pdo->query("
  SELECT 
    DATE(log_time) AS day,
    SUM(log_type = 'inside') AS ins,
    SUM(log_type = 'outside') AS outs
  FROM resident_log
  WHERE log_time >= CURDATE() - INTERVAL 6 DAY
  GROUP BY DATE(log_time)
  ORDER BY day ASC
");
$rawTrend = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Build a full 7-day array with default 0 values
$trendData = [];
for ($i = 6; $i >= 0; $i--) {
  $date = date('Y-m-d', strtotime("-$i days"));
  $trendData[] = [
    'label' => date('D', strtotime($date)),
    'date' => $date,
    'ins' => 0,
    'outs' => 0,
  ];
}

// Merge actual DB results into the 7-day array
foreach ($rawTrend as $row) {
  foreach ($trendData as &$point) {
    if ($point['date'] === $row['day']) {
      $point['ins'] = (int) $row['ins'];
      $point['outs'] = (int) $row['outs'];
    }
  }
}
unset($point);

// Encode trend data for use in Chart.js
$trendJson = json_encode($trendData);

// ── Live Traffic: 5 latest resident movements ─────────────────────────────────
$stmt = $pdo->query("
  SELECT 
    rl.log_type,
    rl.log_time,
    r.first_name,
    r.last_name
  FROM resident_log rl
  JOIN resident r ON rl.resident_id = r.resident_id
  ORDER BY rl.log_time DESC
  LIMIT 5
");
$liveTraffic = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard | Dormonitory</title>
  <link rel="icon" type="image/png" href="/Dormonitory/assets/img/favicon.ico">
  <link rel="stylesheet" href="/Dormonitory/assets/css/sidebar-navbar-styles.css" />
  <link rel="stylesheet" href="/Dormonitory/assets/css/admin-styles.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />

  <style>
    .modal-overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.45);
      z-index: 9999;
      align-items: center;
      justify-content: center;
    }

    .modal-overlay.active {
      display: flex;
    }

    .modal-box {
      background: #fff;
      border-radius: 20px;
      padding: 32px;
      width: 420px;
      max-width: 95vw;
      box-shadow: 0 8px 40px rgba(48, 48, 182, 0.18);
      animation: pop 0.18s ease-out;
      font-family: var(--font-main);
    }

    @keyframes pop {
      from {
        transform: translateY(10px);
        opacity: 0;
      }

      to {
        transform: translateY(0);
        opacity: 1;
      }
    }

    .modal-box h2 {
      font-size: 1.1rem;
      font-weight: 800;
      color: var(--text-primary);
      margin-bottom: 18px;
    }

    .modal-box label {
      display: block;
      font-size: 0.8rem;
      font-weight: 600;
      color: var(--text-secondary);
      margin-bottom: 6px;
      margin-top: 14px;
    }

    .modal-box select {
      width: 100%;
      padding: 10px 14px;
      border: 1.5px solid #e5e7eb;
      border-radius: 10px;
      font-family: var(--font-main);
      font-size: 0.95rem;
      color: var(--text-primary);
      outline: none;
      transition: border 0.2s;
      box-sizing: border-box;
    }

    .modal-box select:focus {
      border-color: var(--accent);
    }

    .modal-actions {
      display: flex;
      justify-content: flex-end;
      gap: 10px;
      margin-top: 22px;
    }

    .btn-cancel {
      padding: 9px 22px;
      border-radius: 10px;
      border: 1.5px solid #e5e7eb;
      background: #fff;
      cursor: pointer;
      font-family: var(--font-main);
      font-size: 0.9rem;
      color: var(--text-primary);
      transition: background 0.2s;
    }

    .btn-cancel:hover {
      background: #f1f2f6;
    }

    .btn-save {
      padding: 9px 22px;
      border-radius: 10px;
      border: none;
      background: var(--accent);
      color: #fff;
      cursor: pointer;
      font-family: var(--font-main);
      font-size: 0.9rem;
      font-weight: 600;
      transition: background 0.2s;
    }

    .btn-save:hover {
      background: #2525a0;
    }

    .chart-wrap {
      position: relative;
      height: 260px;
    }

    .chart-wrap canvas {
      width: 100% !important;
      height: 100% !important;
    }

    .greeting-card,
    .quick-actions-card,
    .chart-card,
    .traffic-card {
      border-radius: 25px !important;
      box-shadow: 0px 10px 30px rgba(0, 0, 0, 0.03) !important;
      padding: 2rem !important;
    }

    .greeting-card h1 {
      font-size: 1.75rem !important;
      letter-spacing: -0.5px;
    }

    .greeting-card p,
    .chart-subtitle {
      color: #a3aed0 !important;
      font-size: 0.95rem !important;
    }

    .stat-card {
      border-radius: 20px !important;
      height: 130px !important;
    }

    .stat-value {
      font-size: 2.2rem !important;
      letter-spacing: -1px;
    }

    .traffic-item {
      border-radius: 20px !important;
      background: #f8faff !important;
      border: 1px solid #f1f4f9;
    }


    /* --- RESPONSIVE OVERRIDES FOR STAT CARDS --- */

    .stat-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: 1.5rem;
      margin-bottom: 2rem;
    }

    .stat-card {
      height: auto !important;
      min-height: 130px;
      position: relative;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      padding: 1.25rem !important;
    }


    @media (max-width: 1200px) {
      .stat-deco {
        font-size: 4rem !important;
        opacity: 0.05 !important;
      }

      .stat-value {
        font-size: 1.8rem !important;
      }
    }

    @media (max-width: 768px) {
      .stat-grid {
        grid-template-columns: 1fr;
      }

      .stat-card {
        padding: 1rem !important;
      }

      .stat-label {
        font-size: 0.85rem !important;
        line-height: 1.2;
      }

      .stat-value {
        font-size: 2rem !important;
      }

      .stat-deco {
        display: none;
      }
    }

    .actions-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 1rem;
    }

    .action-btn {
      width: 100%;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    /* --- RESPONSIVE CHART & TRAFFIC LAYOUT --- */

    .main-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
      gap: 1.5rem;
      margin-top: 1.5rem;
    }

    .chart-card {
      min-width: 0;
      width: 100%;
    }

    .chart-wrap {
      position: relative;
      height: 320px !important;
      width: 100%;
    }

    @media (max-width: 992px) {
      .main-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>

<body>
  <!-- Sidebar and navbar injected here via JS -->
  <div id="sidebar-navbar"></div>

  <div class="layout">
    <div class="main">

      <!-- Greeting card with admin name -->
      <div class="greeting-card">
        <h1>Hi, <?php echo htmlspecialchars($fullname); ?></h1>
        <p>Here's what's happening with your property today.</p>
      </div>

      <!-- Quick action buttons -->
      <div class="quick-actions-card">
        <h2>Quick Actions</h2>
        <div class="actions-grid">
          <a href="resident-management.php" class="quick-action-link">
            <button class="action-btn">
              <div class="icon-placeholder"><i class="bi bi-person"></i></div>
              Manage Residents
            </button>
          </a>
          <a href="room-management.php" class="quick-action-link">
            <button class="action-btn">
              <div class="icon-placeholder"><i class="bi bi-door-open"></i></div>
              Manage Rooms
            </button>
          </a>
          <a href="visitor-management.php" class="quick-action-link">
            <button class="action-btn">
              <div class="icon-placeholder"><i class="bi bi-person-badge-fill"></i></div>
              View Visitor Log
            </button>
          </a>
          <!-- Opens the report generation modal -->
          <button class="action-btn" onclick="openReportModal()">
            <div class="icon-placeholder"><i class="bi bi-file-earmark-text"></i></div>
            Generate Report
          </button>
        </div>
      </div>

      <!-- Stat cards: residents, occupancy, visitors, available beds -->
      <div class="stat-grid">

        <div class="stat-card">
          <div class="stat-left">
            <div class="stat-top">
              <div class="stat-icon"><i class="bi bi-people"></i></div>
              <span class="stat-label">Total<br />Residents</span>
            </div>
            <div class="stat-bottom">
              <div class="stat-value"><?php echo $totalResidents; ?></div>
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
              <div class="stat-value"><?php echo $occupancyPct; ?>%</div>
            </div>
          </div>
          <i class="bi bi-door-open stat-deco"></i>
          <div class="stat-badge">
            <?php echo $occupancyBadge; ?> ·
            <?php echo $totalAssigned; ?>/<?php echo $totalCapacity; ?> beds
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-left">
            <div class="stat-top">
              <div class="stat-icon"><i class="bi bi-person-badge-fill"></i></div>
              <span class="stat-label">Total<br />Visitors</span>
            </div>
            <div class="stat-bottom">
              <div class="stat-value"><?php echo $totalVisitors; ?></div>
            </div>
          </div>
          <i class="bi bi-person-badge-fill stat-deco"></i>
          <div class="stat-badge">Today</div>
        </div>

        <div class="stat-card">
          <div class="stat-left">
            <div class="stat-top">
              <div class="stat-icon"><i class="bi bi-door-closed"></i></div>
              <span class="stat-label">Available<br />Beds</span>
            </div>
            <div class="stat-bottom">
              <div class="stat-value"><?php echo $availableBeds; ?></div>
            </div>
          </div>
          <i class="bi bi-door-closed stat-deco"></i>
          <div class="stat-badge">Open slots</div>
        </div>

      </div>

      <div class="main-grid">

        <!-- CHART CARD -->
        <div class="chart-card">
          <div class="chart-header">
            <div>
              <div class="chart-title">Resident Movement Trends</div>
              <div class="chart-subtitle">Ins &amp; outs — last 7 days</div>
            </div>
          </div>
          <div class="chart-wrap">
            <canvas id="occupancyChart"></canvas>
          </div>
        </div>

        <!-- LIVE TRAFFIC CARD -->
        <div class="traffic-card">
          <div class="traffic-header">
            <div class="traffic-title">Live Traffic Monitor</div>
            <span class="live-badge">Live Updates</span>
          </div>

          <div class="traffic-list">
            <?php if (empty($liveTraffic)): ?>
              <p style="color:var(--text-secondary);font-size:0.85rem;text-align:center;padding:12px 0;">
                No movement logs yet.
              </p>
            <?php else: ?>
              <?php foreach ($liveTraffic as $entry): ?>
                <?php
                $name = htmlspecialchars($entry['first_name'] . ' ' . $entry['last_name']);
                $time = date('h:i A', strtotime($entry['log_time']));
                $type = strtolower($entry['log_type']);
                $badgeClass = $type === 'inside' ? 'status-inside' : 'status-outside';
                $badgeText = strtoupper($type);
                ?>
                <div class="traffic-item">
                  <div class="person-avatar">
                    <i class="bi bi-person-fill" style="color:white"></i>
                  </div>
                  <div class="person-info">
                    <div class="person-name"><?= $name ?></div>
                    <div class="person-meta"><?= $time ?></div>
                  </div>
                  <span class="status-tag <?= $badgeClass ?>"><?= $badgeText ?></span>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>

      </div>
    </div>
  </div>

  <!-- REPORT MODAL -->
  <div class="modal-overlay" id="reportModal">
    <div class="modal-box">
      <h2>
        <i class="bi bi-file-earmark-text" style="color:var(--accent);margin-right:8px;"></i>
        Generate Report
      </h2>
      <form method="GET" action="/Dormonitory/admin/generate-report.php">
        <label>Report Type</label>
        <select name="type" required>
          <option value="">— Select Report —</option>
          <option value="daily">Daily Report</option>
          <option value="weekly">Weekly Report</option>
          <option value="monthly">Monthly Report</option>
          <option value="yearly">Yearly Report</option>
        </select>
        <div class="modal-actions">
          <button type="button" class="btn-cancel" onclick="closeModal('reportModal')">Cancel</button>
          <button type="submit" class="btn-save">Generate</button>
        </div>
      </form>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <script>
    // Pass PHP trend data to JS
    const trendData = <?= $trendJson ?>;

    // Initialize the 7-day ins/outs line chart
    const ctx = document.getElementById('occupancyChart').getContext('2d');

    // Create modern gradients for the reveal effect
    const insGradient = ctx.createLinearGradient(0, 0, 0, 400);
    insGradient.addColorStop(0, 'rgba(70, 70, 214, 0.3)');
    insGradient.addColorStop(1, 'rgba(70, 70, 214, 0)');

    const outsGradient = ctx.createLinearGradient(0, 0, 0, 400);
    outsGradient.addColorStop(0, 'rgba(229, 83, 83, 0.25)');
    outsGradient.addColorStop(1, 'rgba(229, 83, 83, 0)');

    new Chart(ctx, {
      type: 'line',
      data: {
        labels: trendData.map(d => d.label),
        datasets: [
          {
            // VVV The Fix: Adding spaces before the text to create a gap VVV
            label: '     Entries',
            data: trendData.map(d => d.ins),
            fill: true,
            backgroundColor: insGradient,
            borderColor: '#4646d6',
            borderWidth: 3,
            tension: 0.45,
            pointRadius: 4,
            pointHoverRadius: 7,
            pointBackgroundColor: '#fff',
            pointBorderWidth: 3,
          },
          {

            label: '     Exits',
            data: trendData.map(d => d.outs),
            fill: true,
            backgroundColor: outsGradient,
            borderColor: '#e55353',
            borderWidth: 3,
            tension: 0.45,
            pointRadius: 4,
            pointHoverRadius: 7,
            pointBackgroundColor: '#fff',
            pointBorderWidth: 3,
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
          mode: 'index',
          intersect: false,
        },
        plugins: {
          legend: {
            position: 'top',
            align: 'end',
            labels: {
              boxWidth: 8,
              padding: 30,
              usePointStyle: true,
              pointStyle: 'circle',
              font: { family: 'Inter', size: 12, weight: '600' }
            }
          },
          tooltip: {
            backgroundColor: '#1e1e2d',
            titleColor: '#ffffff',
            titleFont: { family: 'Inter', size: 14, weight: 'bold' },
            bodyFont: { family: 'Inter', size: 13 },
            bodySpacing: 8,
            padding: 15,
            cornerRadius: 12,
            displayColors: true,
            usePointStyle: true,
            borderColor: 'rgba(255,255,255,0.1)',
            borderWidth: 1,
            callbacks: {
              label: function (context) {
                let label = (context.dataset.label || '').trim();
                if (label) { label += ': '; }
                label += context.parsed.y + ' residents';
                return label;
              }
            }
          }
        },
        scales: {
          x: {
            grid: { display: false },
            ticks: { color: '#94a3b8', font: { family: 'Inter' } }
          },
          y: {
            beginAtZero: true,
            grid: { color: 'rgba(0,0,0,0.03)', drawBorder: false },
            ticks: {
              color: '#94a3b8',
              stepSize: 1,
              font: { family: 'Inter' }
            }
          }
        }
      }
    });

    // Modal Control Functions
    function openReportModal() {
      document.getElementById('reportModal').classList.add('active');
    }

    function closeModal(id) {
      document.getElementById(id).classList.remove('active');
    }

    // Modal behavior setup
    document.addEventListener("DOMContentLoaded", function () {
      document.querySelectorAll('.modal-overlay').forEach(modal => {
        modal.addEventListener('click', function (e) {
          if (e.target === this) this.classList.remove('active');
        });
      });
    });

    // Auto-refresh every 30s for live traffic update
    setTimeout(() => location.reload(), 30000);
  </script>

  <script src="/Dormonitory/assets/js/sidebar-navbar.js"></script>

</body>

</html>