<?php
// ─── DB CONNECTION ───────────────────────────────────────────
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once '../config/db.php';

// ─── SEARCH + PAGINATION ─────────────────────────────────────
// Filters visitor logs by visitor name, contact, or resident name
$perPage = 8;
$page = max(1, (int) ($_GET['page'] ?? 1));
$search = trim($_GET['search'] ?? '');
$searchLike = "%$search%";

$totalRows = 0;
$totalPages = 1;
$visitors = [];

try {

  // Count total matching rows for pagination
  $countStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM visitor_log vl
    LEFT JOIN resident r ON r.resident_id = vl.resident_id
    WHERE vl.visitor_name LIKE ?
       OR vl.contact_number LIKE ?
       OR CONCAT(r.first_name,' ',r.last_name) LIKE ?
  ");

  $countStmt->execute([$searchLike, $searchLike, $searchLike]);
  $totalRows = (int) $countStmt->fetchColumn();

  $totalPages = max(1, (int) ceil($totalRows / $perPage));
  $page = min($page, $totalPages);
  $offset = ($page - 1) * $perPage;

  // Fetch paginated visitor logs with their visited resident
  $stmt = $pdo->prepare("
  SELECT 
    vl.log_id,
    vl.visitor_name,
    vl.contact_number,
    vl.visit_date,
    vl.created_at,
    vl.qr_token,
    vl.resident_id,
    CONCAT(r.first_name,' ',r.last_name) AS resident_name
  FROM visitor_log vl
  LEFT JOIN resident r ON r.resident_id = vl.resident_id
  WHERE vl.visitor_name LIKE ?
     OR vl.contact_number LIKE ?
     OR CONCAT(r.first_name,' ',r.last_name) LIKE ?
  ORDER BY vl.created_at DESC
  LIMIT ? OFFSET ?
");

  $stmt->bindValue(1, $searchLike);
  $stmt->bindValue(2, $searchLike);
  $stmt->bindValue(3, $searchLike);
  $stmt->bindValue(4, $perPage, PDO::PARAM_INT);
  $stmt->bindValue(5, $offset, PDO::PARAM_INT);

  $stmt->execute();
  $visitors = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
  die("Database Error: " . $e->getMessage());
}
?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Visitor Log | Dormonitory</title>

  <link rel="icon" type="image/png" href="/Dormonitory/assets/img/favicon.ico">
  <link rel="stylesheet" href="/Dormonitory/assets/css/sidebar-navbar-styles.css" />
  <link rel="stylesheet" href="/Dormonitory/assets/css/admin-styles.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet" />
</head>

<style>
  .avatar,
  .alert-inline-icon,
  .actions i,
  .ann-menu-btn {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    width: 40px !important;
    height: 40px !important;
    =font-size: 1.25rem !important;
    border-radius: 10px !important;
    flex-shrink: 0 !important;
  }

  html {
    font-size: 16px;
  }

  @media (max-width: 768px) {
    html {
      font-size: 14px;
    }

    .main {
      padding: 15px !important;
    }

    .ann-topbar,
    .top-bar {
      flex-direction: column !important;
      gap: 15px !important;
      align-items: stretch !important;
    }

    .search,
    .ann-filter {
      width: 100% !important;
    }

    .card,
    .ann-wrap {
      overflow-x: auto !important;
      -webkit-overflow-scrolling: touch;
    }

    table,
    .ann-table-head,
    .ann-row {
      min-width: 600px;
    }

    .footer,
    .ann-footer {
      flex-direction: column !important;
      gap: 20px !important;
      text-align: center !important;
    }

    .ann-pagination {
      justify-content: center !important;
      width: 100% !important;
    }

    .modal-box,
    .confirm-box {
      width: 90% !important;
      padding: 20px !important;
    }
  }

  .actions {
    display: flex !important;
    gap: 8px !important;
    align-items: center;
  }

  .actions i {
    cursor: pointer;
    transition: transform 0.2s ease;
  }

  .actions i:hover {
    transform: scale(1.1);
  }
</style>

<body>
  <!-- Sidebar and navbar injected here via JS -->
  <div id="sidebar-navbar"></div>

  <div class="layout">
    <div class="main">

      <div class="card">

        <!-- SEARCH -->
        <div class="top-bar">
          <form method="GET" class="search">
            <i class="bi bi-search"></i>
            <input type="text" name="search" placeholder="Search visitor, resident, contact..."
              value="<?= htmlspecialchars($search) ?>" />
          </form>
        </div>

        <!-- TABLE -->
        <table class="visitor-table">
          <thead>
            <tr>
              <th>Visitor</th>
              <th>Resident Visiting</th>
              <th>Contact Number</th>
              <th>Visit Date</th>
            </tr>
          </thead>

          <tbody>
            <?php if (empty($visitors)): ?>
              <tr>
                <td colspan="4" style="text-align:center;padding:40px;color:#9e9a9a;">
                  No visitors found.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($visitors as $v): ?>
                <tr>
                  <td>
                    <div class="user">
                      <div class="avatar"><i class="bi bi-person"></i></div>
                      <div class="user-info">
                        <?= htmlspecialchars($v['visitor_name']) ?>
                        <small style="display:block; color:#9e9a9a; font-size:0.75rem;">
                          LOG ID: <?= htmlspecialchars($v['log_id']) ?>
                        </small>
                      </div>
                    </div>
                  </td>

                  <td><?= htmlspecialchars($v['resident_name'] ?? '—') ?></td>

                  <td><?= htmlspecialchars($v['contact_number'] ?? '—') ?></td>

                  <td>
                    <?= htmlspecialchars($v['visit_date'] ?? '—') ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>

        <!-- FOOTER: row count and pagination controls -->
        <div class="footer">
          <div>
            Showing <?= count($visitors) ?> of <?= $totalRows ?> visitors
          </div>

          <div class="ann-pagination">

            <button class="page-nav" <?= $page <= 1 ? 'disabled' : '' ?> onclick="window.location='?<?= http_build_query([
                       'page' => $page - 1,
                       'search' => $search
                     ]) ?>'">
              Previous
            </button>

            <!-- Numbered page buttons (windowed around current page) -->
            <?php
            $start = max(1, $page - 2);
            $end = min($totalPages, $page + 2);

            for ($p = $start; $p <= $end; $p++):
              ?>
              <button class="page-btn <?= $p === $page ? 'active' : '' ?>" onclick="window.location='?<?= http_build_query([
                        'page' => $p,
                        'search' => $search
                      ]) ?>'">
                <?= $p ?>
              </button>
            <?php endfor; ?>

            <button class="page-nav" <?= $page >= $totalPages ? 'disabled' : '' ?> onclick="window.location='?<?= http_build_query([
                       'page' => $page + 1,
                       'search' => $search
                     ]) ?>'">
              Next
            </button>

          </div>
        </div>

      </div>
    </div>
  </div>

  <script src="/Dormonitory/assets/js/sidebar-navbar.js"></script>
</body>

</html>