<?php
// ─── DB Connection ───────────────────────────────────────────
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once '../config/db.php';


// ─── Handle DELETE ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
  try {
    $id = (int) $_POST['resident_id'];
    $pdo->prepare("DELETE FROM resident_log WHERE resident_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM resident_qr WHERE resident_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM visitor_log WHERE resident_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM resident WHERE resident_id = ?")->execute([$id]);
  } catch (PDOException $e) {
  }
  header("Location: " . $_SERVER['PHP_SELF'] . "?page=" . (int) ($_GET['page'] ?? 1) . "&search=" . urlencode($_GET['search'] ?? ''));
  exit;
}


// ─── Handle EDIT ────────────────────────────────────
$editError = '';
$editContactError = '';
$editEmailError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
  $id = (int) $_POST['resident_id'];
  $fname = trim($_POST['first_name']);
  $lname = trim($_POST['last_name']);
  $contact = trim($_POST['contact_number']);
  $email = trim($_POST['email']);

  $contactCheck = $pdo->prepare("SELECT COUNT(*) FROM resident WHERE contact_number = ? AND resident_id != ?");
  $contactCheck->execute([$contact, $id]);
  if ((int) $contactCheck->fetchColumn() > 0)
    $editContactError = 'That contact number is already in use by another resident.';

  $emailCheck = $pdo->prepare("SELECT COUNT(*) FROM resident WHERE email = ? AND resident_id != ?");
  $emailCheck->execute([$email, $id]);
  if ((int) $emailCheck->fetchColumn() > 0)
    $editEmailError = 'That email is already in use by another resident.';

  if (!$editContactError && !$editEmailError) {
    $pdo->prepare("UPDATE resident SET first_name=?, last_name=?, contact_number=?, email=? WHERE resident_id=?")
      ->execute([$fname, $lname, $contact, $email, $id]);
    header("Location: " . $_SERVER['PHP_SELF'] . "?page=" . (int) ($_GET['page'] ?? 1) . "&search=" . urlencode($_GET['search'] ?? ''));
    exit;
  }

  $pdo->prepare("UPDATE resident SET first_name=?, last_name=? WHERE resident_id=?")->execute([$fname, $lname, $id]);
  $editError = 'true';
}

// ─── Handle ASSIGN ROOM ──────────────────────────────────────
$assignError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'assign_room') {
  $id = (int) $_POST['resident_id'];
  $roomId = ($_POST['room_id'] ?? '') === '' ? null : (int) $_POST['room_id'];

  try {
    // Get resident's current room first (always)
    $currentStmt = $pdo->prepare("SELECT room_id FROM resident WHERE resident_id = ?");
    $currentStmt->execute([$id]);
    $currentRoom = $currentStmt->fetchColumn();
    $currentRoomNormalized = ($currentRoom === false || $currentRoom === null) ? null : (int) $currentRoom;

    if ($roomId !== null && $currentRoomNormalized !== $roomId) {
      $capStmt = $pdo->prepare("
        SELECT r.max_capacity,
               COUNT(res.resident_id) AS occupied
        FROM room r
        LEFT JOIN resident res ON res.room_id = r.room_id
        WHERE r.room_id = ?
        GROUP BY r.room_id
      ");
      $capStmt->execute([$roomId]);
      $capRow = $capStmt->fetch(PDO::FETCH_ASSOC);

      if (!$capRow) {
        $assignError = 'Room not found.';
      } elseif ((int) $capRow['occupied'] >= (int) $capRow['max_capacity']) {
        $assignError = 'That room is already at full capacity.';
      }
    }

    if (!$assignError) {
      if ($currentRoomNormalized !== $roomId) {
        $pdo->prepare("UPDATE resident SET room_id = ? WHERE resident_id = ?")->execute([$roomId, $id]);
      }

      header("Location: " . $_SERVER['PHP_SELF'] . "?page=" . (int) ($_GET['page'] ?? 1) . "&search=" . urlencode($_GET['search'] ?? ''));
      exit;
    }
  } catch (PDOException $e) {
    $assignError = 'Database error: ' . $e->getMessage();
  }
}

// ─── Fetch rooms for the assign modal ────────────────────────
$rooms = [];
try {
  $roomStmt = $pdo->query("
    SELECT r.room_id, r.room_number, r.room_type, r.max_capacity,
           COUNT(res.resident_id) AS occupied
    FROM room r
    LEFT JOIN resident res ON res.room_id = r.room_id
    GROUP BY r.room_id
    ORDER BY r.room_number ASC
  ");
  $rooms = $roomStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
}


// ─── Pagination & Search ─────────────────────────────────────
$perPage = 8;
$page = max(1, (int) ($_GET['page'] ?? 1));
$search = trim($_GET['search'] ?? '');
$searchLike = "%$search%";
$totalRows = 0;
$totalPages = 1;
$residents = [];

try {
  $countStmt = $pdo->prepare("
    SELECT COUNT(*) FROM resident
    WHERE CONCAT(first_name, ' ', last_name) LIKE ?
       OR CAST(resident_id AS CHAR) LIKE ?
  ");
  $countStmt->execute([$searchLike, $searchLike]);
  $totalRows = (int) $countStmt->fetchColumn();
  $totalPages = max(1, (int) ceil($totalRows / $perPage));
  $page = min($page, $totalPages);
  $offset = ($page - 1) * $perPage;

  $stmt = $pdo->prepare("
    SELECT
      r.resident_id,
      r.first_name,
      r.last_name,
      CONCAT(r.first_name, ' ', r.last_name) AS full_name,
      r.contact_number,
      r.email,
      r.room_id AS assigned_room_id,
      COALESCE(
        (SELECT rl.log_type FROM resident_log rl
         WHERE rl.resident_id = r.resident_id ORDER BY rl.log_time DESC LIMIT 1),
        'outside'
      ) AS current_status,
      (SELECT rl.log_time FROM resident_log rl
       WHERE rl.resident_id = r.resident_id ORDER BY rl.log_time DESC LIMIT 1) AS last_movement,
      (SELECT rm2.room_number FROM room rm2 WHERE rm2.room_id = r.room_id) AS room_number
    FROM resident r
    WHERE CONCAT(r.first_name, ' ', r.last_name) LIKE ?
       OR CAST(r.resident_id AS CHAR) LIKE ?
    ORDER BY r.resident_id ASC
    LIMIT ? OFFSET ?
  ");
  $stmt->bindValue(1, $searchLike);
  $stmt->bindValue(2, $searchLike);
  $stmt->bindValue(3, $perPage, PDO::PARAM_INT);
  $stmt->bindValue(4, $offset, PDO::PARAM_INT);
  $stmt->execute();
  $residents = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  $residents = [];
}
?>


// ─── HTML ─────────────────────────────────────
<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dormonitory - Resident Management</title>
  <link rel="stylesheet" href="/Dormonitory/assets/css/sidebar-navbar-styles.css" />
  <link rel="stylesheet" href="/Dormonitory/assets/css/admin-styles.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet" />

  <style>
    tbody tr.clickable-row {
      cursor: pointer;
      transition: background 0.15s;
    }

    tbody tr.clickable-row:hover {
      background: #f5f6ff;
    }

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
    }

    .modal-box h2 {
      font-size: 1.2rem;
      font-weight: 700;
      color: var(--text-primary, #3e4755);
      margin-bottom: 22px;
    }

    .modal-box label {
      display: block;
      font-size: 0.82rem;
      font-weight: 600;
      color: var(--text-secondary, #9e9a9a);
      margin-bottom: 4px;
      margin-top: 14px;
    }

    .modal-box input,
    .modal-box select {
      width: 100%;
      padding: 10px 14px;
      border: 1.5px solid #e5e7eb;
      border-radius: 10px;
      font-family: var(--font-main, "Inter", sans-serif);
      font-size: 0.95rem;
      color: var(--text-primary, #3e4755);
      outline: none;
      transition: border 0.2s;
      box-sizing: border-box;
    }

    .modal-box input:focus,
    .modal-box select:focus {
      border-color: var(--accent, #3030b6);
    }

    .modal-box input.input-error {
      border-color: #dc2626;
    }

    .field-error {
      font-size: 0.78rem;
      color: #dc2626;
      margin-top: 5px;
      display: none;
    }

    .field-error.visible {
      display: block;
    }

    .modal-actions {
      display: flex;
      justify-content: flex-end;
      gap: 12px;
      margin-top: 24px;
    }

    .btn-cancel {
      padding: 9px 22px;
      border-radius: 10px;
      border: 1.5px solid #e5e7eb;
      background: #fff;
      cursor: pointer;
      font-family: var(--font-main, "Inter", sans-serif);
      font-size: 0.9rem;
      color: var(--text-primary, #3e4755);
      transition: background 0.2s;
    }

    .btn-cancel:hover {
      background: #f1f2f6;
    }

    .btn-save {
      padding: 9px 22px;
      border-radius: 10px;
      border: none;
      background: var(--accent, #3030b6);
      color: #fff;
      cursor: pointer;
      font-family: var(--font-main, "Inter", sans-serif);
      font-size: 0.9rem;
      font-weight: 600;
      transition: background 0.2s;
    }

    .btn-save:hover {
      background: #2525a0;
    }

    .view-header {
      display: flex;
      align-items: center;
      gap: 16px;
      padding-bottom: 20px;
      margin-bottom: 4px;
      border-bottom: 1.5px solid #f1f2f6;
    }

    .view-avatar {
      width: 52px;
      height: 52px;
      border-radius: 50%;
      background: #eef0ff;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
      color: var(--accent, #3030b6);
      flex-shrink: 0;
    }

    .view-title {
      flex: 1;
    }

    .view-title strong {
      display: block;
      font-size: 1rem;
      font-weight: 700;
      color: var(--text-primary, #3e4755);
    }

    .view-title small {
      font-size: 0.78rem;
      color: var(--text-secondary, #9e9a9a);
    }

    .detail-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 11px 0;
      border-bottom: 1px solid #f1f2f6;
    }

    .detail-row:last-child {
      border-bottom: none;
    }

    .detail-label {
      font-size: 0.78rem;
      font-weight: 600;
      color: var(--text-secondary, #9e9a9a);
      text-transform: uppercase;
      letter-spacing: 0.04em;
    }

    .detail-value {
      font-size: 0.9rem;
      font-weight: 600;
      color: var(--text-primary, #3e4755);
      text-align: right;
    }

    .detail-badge-inside {
      display: inline-block;
      padding: 3px 10px;
      border-radius: 20px;
      font-size: 0.75rem;
      font-weight: 700;
      background: #dcfce7;
      color: #16a34a;
    }

    .detail-badge-outside {
      display: inline-block;
      padding: 3px 10px;
      border-radius: 20px;
      font-size: 0.75rem;
      font-weight: 700;
      background: #fee2e2;
      color: #dc2626;
    }

    .confirm-box {
      background: #fff;
      border-radius: 20px;
      padding: 32px;
      width: 360px;
      max-width: 95vw;
      box-shadow: 0 8px 40px rgba(220, 38, 38, 0.13);
      text-align: center;
    }

    .confirm-box .confirm-icon {
      font-size: 2.4rem;
      color: #dc2626;
      margin-bottom: 12px;
    }

    .confirm-box h2 {
      font-size: 1.1rem;
      font-weight: 700;
      margin-bottom: 8px;
      color: var(--text-primary, #3e4755);
    }

    .confirm-box p {
      font-size: 0.88rem;
      color: var(--text-secondary, #9e9a9a);
      margin-bottom: 22px;
    }

    .btn-delete-confirm {
      padding: 9px 22px;
      border-radius: 10px;
      border: none;
      background: #dc2626;
      color: #fff;
      cursor: pointer;
      font-family: var(--font-main, "Inter", sans-serif);
      font-size: 0.9rem;
      font-weight: 600;
      transition: background 0.2s;
    }

    .btn-delete-confirm:hover {
      background: #b91c1c;
    }

    .assign-error {
      font-size: 0.8rem;
      color: #dc2626;
      background: #fff5f5;
      border: 1px solid #fecaca;
      border-radius: 8px;
      padding: 8px 12px;
      margin-bottom: 12px;
      display: none;
    }

    .assign-error.visible {
      display: block;
    }

    .assign-hint {
      font-size: 0.78rem;
      color: var(--text-secondary, #9e9a9a);
      margin-top: 6px;
    }

    .search-form {
      display: contents;
    }

    .assign-btn {
      color: #16a34a;
      font-size: 1.05rem;
    }

    .assign-btn:hover {
      color: #15803d;
    }
  </style>
</head>

<body>
  <div id="sidebar-navbar"></div>

  <div class="layout">
    <div class="main">
      <div class="card">

        <!-- Top Bar: Search -->
        <div class="top-bar">
          <form method="GET" action="" class="search-form">
            <div class="search">
              <i class="bi bi-search"></i>
              <input type="text" name="search" id="searchInput" placeholder="Search by name or ID..."
                value="<?= htmlspecialchars($search) ?>" autocomplete="off" />
            </div>
          </form>
        </div>

        <!-- Residents Table -->
        <table>
          <thead>
            <tr>
              <th>Resident</th>
              <th>Room</th>
              <th>Contact Number</th>
              <th>Status</th>
              <th>Last Movement</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($residents)): ?>
              <tr>
                <td colspan="6" style="text-align:center; padding:40px; color:#9e9a9a;">No residents found.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($residents as $r):
                $status = strtolower($r['current_status']);
                $statusClass = $status === 'inside' ? 'resident-inside' : 'resident-outside';
                $statusLabel = $status === 'inside' ? 'INSIDE' : 'OUTSIDE';
                $time = $r['last_movement'] ? date('h:i A', strtotime($r['last_movement'])) : '—';
                $fullTime = $r['last_movement'] ? date('M d, Y h:i A', strtotime($r['last_movement'])) : '—';
                $room = $r['room_number'] ?? '—';
                $email = $r['email'] ?? '';
                $assignedRoomId = $r['assigned_room_id'] ?? '';
                ?>
                <tr class="clickable-row" onclick="openView(
                  <?= (int) $r['resident_id'] ?>,
                  '<?= addslashes(htmlspecialchars($r['full_name'])) ?>',
                  '<?= addslashes(htmlspecialchars($r['first_name'])) ?>',
                  '<?= addslashes(htmlspecialchars($r['last_name'])) ?>',
                  '<?= addslashes(htmlspecialchars($r['contact_number'])) ?>',
                  '<?= addslashes(htmlspecialchars($email)) ?>',
                  '<?= htmlspecialchars($status) ?>',
                  '<?= htmlspecialchars($room) ?>',
                  '<?= addslashes($fullTime) ?>'
                )">
                  <td>
                    <div class="user">
                      <div class="avatar"><i class="bi bi-person"></i></div>
                      <div class="user-info">
                        <?= htmlspecialchars($r['full_name']) ?>
                        <small>ID: <?= htmlspecialchars($r['resident_id']) ?></small>
                      </div>
                    </div>
                  </td>
                  <td><?= htmlspecialchars($room) ?></td>
                  <td><?= htmlspecialchars($r['contact_number']) ?></td>
                  <td><span class="resident-status <?= $statusClass ?>"><?= $statusLabel ?></span></td>
                  <td>
                    <div class="time"><i class="bi bi-clock"></i> <?= $time ?></div>
                  </td>
                  <td class="actions">
                    <?php if (empty($rooms)): ?>
                      <i class="bi bi-door-open" title="No rooms available"
                        style="opacity:0.3;cursor:not-allowed;font-size:1.05rem;" onclick="event.stopPropagation();"></i>
                    <?php else: ?>
                      <i class="bi bi-door-open assign-btn" title="Assign Room" onclick="event.stopPropagation(); openAssign(
                           <?= (int) $r['resident_id'] ?>,
                           '<?= addslashes(htmlspecialchars($r['full_name'])) ?>',
                           <?= $assignedRoomId !== null && $assignedRoomId !== '' ? (int) $assignedRoomId : 'null' ?>
                         )"></i>
                    <?php endif; ?>
                    <i class="bi bi-pencil-square edit-btn" title="Edit" onclick="event.stopPropagation(); openEdit(
                         <?= (int) $r['resident_id'] ?>,
                         '<?= addslashes(htmlspecialchars($r['first_name'])) ?>',
                         '<?= addslashes(htmlspecialchars($r['last_name'])) ?>',
                         '<?= addslashes(htmlspecialchars($r['contact_number'])) ?>',
                         '<?= addslashes(htmlspecialchars($email)) ?>'
                       )"></i>
                    <i class="bi bi-trash delete-btn" title="Delete" onclick="event.stopPropagation(); openDelete(
                         <?= (int) $r['resident_id'] ?>,
                         '<?= addslashes(htmlspecialchars($r['full_name'])) ?>'
                       )"></i>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>

        <!-- Footer -->
        <div class="footer">
          <div>Showing <?= count($residents) ?> of <?= $totalRows ?> resident<?= $totalRows !== 1 ? 's' : '' ?></div>
          <div class="pagination">
            <?php $buildUrl = fn($p) => '?' . http_build_query(['page' => $p, 'search' => $search]); ?>

            <?php if ($page > 1): ?>
              <span><a href="<?= $buildUrl($page - 1) ?>" style="text-decoration:none;color:inherit;">Previous</a></span>
            <?php else: ?>
              <span style="opacity:0.4;cursor:default;">Previous</span>
            <?php endif; ?>

            <?php
            $start = max(1, $page - 2);
            $end = min($totalPages, $page + 2);
            for ($p = $start; $p <= $end; $p++): ?>
              <div class="page <?= $p === $page ? 'active' : '' ?>">
                <a href="<?= $buildUrl($p) ?>" style="text-decoration:none;color:inherit;"><?= $p ?></a>
              </div>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
              <span><a href="<?= $buildUrl($page + 1) ?>" style="text-decoration:none;color:inherit;">Next</a></span>
            <?php else: ?>
              <span style="opacity:0.4;cursor:default;">Next</span>
            <?php endif; ?>
          </div>
        </div>

      </div><!-- /.card -->
    </div><!-- /.main -->
  </div><!-- /.layout -->


  <!-- ═══════════════════════════════════════════════
       VIEW MODAL
  ═══════════════════════════════════════════════ -->
  <div class="modal-overlay" id="viewModal">
    <div class="modal-box">
      <h2><i class="bi bi-person-lines-fill" style="color:var(--accent);margin-right:8px;"></i>Resident Details</h2>
      <div class="view-header">
        <div class="view-avatar"><i class="bi bi-person"></i></div>
        <div class="view-title">
          <strong id="view_name">—</strong>
          <small id="view_id_label">ID: —</small>
        </div>
      </div>
      <div class="detail-row"><span class="detail-label">First Name</span><span class="detail-value"
          id="view_fname">—</span></div>
      <div class="detail-row"><span class="detail-label">Last Name</span><span class="detail-value"
          id="view_lname">—</span></div>
      <div class="detail-row"><span class="detail-label">Contact Number</span><span class="detail-value"
          id="view_contact">—</span></div>
      <div class="detail-row"><span class="detail-label">Email</span><span class="detail-value" id="view_email">—</span>
      </div>
      <div class="detail-row"><span class="detail-label">Room</span><span class="detail-value" id="view_room">—</span>
      </div>
      <div class="detail-row"><span class="detail-label">Status</span><span class="detail-value"
          id="view_status">—</span></div>
      <div class="detail-row"><span class="detail-label">Last Movement</span><span class="detail-value"
          id="view_movement">—</span></div>
      <div class="modal-actions">
        <button type="button" class="btn-cancel" onclick="closeModal('viewModal')">Close</button>
      </div>
    </div>
  </div>


  <!-- ═══════════════════════════════════════════════
       EDIT MODAL
  ═══════════════════════════════════════════════ -->
  <div class="modal-overlay <?= $editError ? 'active' : '' ?>" id="editModal">
    <div class="modal-box">
      <h2><i class="bi bi-pencil-square" style="color:var(--accent);margin-right:8px;"></i>Edit Resident</h2>
      <form method="POST" action="">
        <input type="hidden" name="action" value="edit" />
        <input type="hidden" name="resident_id" id="edit_id"
          value="<?= $editError ? (int) $_POST['resident_id'] : '' ?>" />

        <label>First Name</label>
        <input type="text" name="first_name" id="edit_fname" required
          value="<?= $editError ? htmlspecialchars($_POST['first_name']) : '' ?>" />

        <label>Last Name</label>
        <input type="text" name="last_name" id="edit_lname" required
          value="<?= $editError ? htmlspecialchars($_POST['last_name']) : '' ?>" />

        <label>Contact Number</label>
        <input type="text" name="contact_number" id="edit_contact" class="<?= $editContactError ? 'input-error' : '' ?>"
          value="<?= $editError ? htmlspecialchars($_POST['contact_number']) : '' ?>" />
        <span class="field-error <?= $editContactError ? 'visible' : '' ?>" id="edit_contact_error">
          <?= htmlspecialchars($editContactError) ?>
        </span>

        <label>Email</label>
        <input type="email" name="email" id="edit_email" required class="<?= $editEmailError ? 'input-error' : '' ?>"
          value="<?= $editError ? htmlspecialchars($_POST['email']) : '' ?>" />
        <span class="field-error <?= $editEmailError ? 'visible' : '' ?>" id="edit_email_error">
          <?= htmlspecialchars($editEmailError) ?>
        </span>

        <div class="modal-actions">
          <button type="button" class="btn-cancel" onclick="closeModal('editModal')">Cancel</button>
          <button type="submit" class="btn-save">Save Changes</button>
        </div>
      </form>
    </div>
  </div>


  <!-- ═══════════════════════════════════════════════
       DELETE MODAL
  ═══════════════════════════════════════════════ -->
  <div class="modal-overlay" id="deleteModal">
    <div class="confirm-box">
      <div class="confirm-icon"><i class="bi bi-trash"></i></div>
      <h2>Remove Resident</h2>
      <p id="delete_msg">Are you sure you want to remove this resident? This action cannot be undone.</p>
      <form method="POST" action="">
        <input type="hidden" name="action" value="delete" />
        <input type="hidden" name="resident_id" id="delete_id" />
        <div class="modal-actions" style="justify-content:center;">
          <button type="button" class="btn-cancel" onclick="closeModal('deleteModal')">Cancel</button>
          <button type="submit" class="btn-delete-confirm">Yes, Remove</button>
        </div>
      </form>
    </div>
  </div>


  <!-- ═══════════════════════════════════════════════
     ASSIGN ROOM MODAL
═══════════════════════════════════════════════ -->
  <div class="modal-overlay <?= $assignError ? 'active' : '' ?>" id="assignModal">
    <div class="modal-box">
      <h2><i class="bi bi-door-open" style="color:#16a34a;margin-right:8px;"></i>Assign Room</h2>

      <div class="assign-error <?= $assignError ? 'visible' : '' ?>" id="assign_error_msg">
        <?= htmlspecialchars($assignError) ?>
      </div>

      <form method="POST" action="" id="assignForm">
        <input type="hidden" name="action" value="assign_room" />
        <input type="hidden" name="resident_id" id="assign_resident_id"
          value="<?= $assignError ? (int) $_POST['resident_id'] : '' ?>" />
        <input type="hidden" id="assign_original_room_id" value="" />

        <label>Resident</label>
        <input type="text" id="assign_name_display" disabled
          style="background:#f9fafb; color:var(--text-secondary,#9e9a9a); cursor:default;" />

        <label>Room</label>
        <select name="room_id" id="assign_room_select">
          <option value="">— Remove Assignment</option>
          <?php foreach ($rooms as $rm):
            $occupied = (int) $rm['occupied'];
            $capacity = (int) $rm['max_capacity'];
            $label = 'Room ' . htmlspecialchars($rm['room_number'])
              . ' — ' . htmlspecialchars($rm['room_type'])
              . ' (' . $occupied . '/' . $capacity . ')';
            ?>
            <option value="<?= (int) $rm['room_id'] ?>" data-occupied="<?= $occupied ?>" data-capacity="<?= $capacity ?>"
              <?= ($assignError && (int) ($_POST['room_id'] ?? 0) === (int) $rm['room_id']) ? 'selected' : '' ?>>
              <?= $label ?>
            </option>
          <?php endforeach; ?>
        </select>
        <p class="assign-hint">Full rooms are disabled. Occupancy shown as current / max.</p>

        <div class="modal-actions">
          <button type="button" class="btn-cancel" onclick="closeModal('assignModal')">Cancel</button>
          <button type="submit" class="btn-save">Confirm</button>
        </div>
      </form>
    </div>
  </div>

  <script src="/Dormonitory/assets/js/sidebar-navbar.js"></script>

  <script>
    // ── View Modal ─────────────────────────────────────────
    function openView(id, fullName, fname, lname, contact, email, status, room, movement) {
      document.getElementById('view_name').textContent = fullName;
      document.getElementById('view_id_label').textContent = 'ID: ' + id;
      document.getElementById('view_fname').textContent = fname;
      document.getElementById('view_lname').textContent = lname;
      document.getElementById('view_contact').textContent = contact || '—';
      document.getElementById('view_email').textContent = email || '—';
      document.getElementById('view_room').textContent = room;
      document.getElementById('view_movement').textContent = movement;
      const statusEl = document.getElementById('view_status');
      statusEl.innerHTML = `<span class="detail-badge-${status}">${status === 'inside' ? 'Inside' : 'Outside'}</span>`;
      document.getElementById('viewModal').classList.add('active');
    }

    // ── Edit Modal ─────────────────────────────────────────
    function openEdit(id, fname, lname, contact, email) {
      document.getElementById('edit_id').value = id;
      document.getElementById('edit_fname').value = fname;
      document.getElementById('edit_lname').value = lname;
      document.getElementById('edit_contact').value = contact;
      document.getElementById('edit_email').value = email;
      document.getElementById('edit_contact').classList.remove('input-error');
      document.getElementById('edit_contact_error').classList.remove('visible');
      document.getElementById('edit_email').classList.remove('input-error');
      document.getElementById('edit_email_error').classList.remove('visible');
      document.getElementById('editModal').classList.add('active');
    }

    // ── Delete Modal ───────────────────────────────────────
    function openDelete(id, name) {
      document.getElementById('delete_id').value = id;
      document.getElementById('delete_msg').textContent =
        `Are you sure you want to remove "${name}"? This action cannot be undone.`;
      document.getElementById('deleteModal').classList.add('active');
    }

    // ── Assign Room Modal ──────────────────────────────────
    function openAssign(id, name, currentRoomId) {
      document.getElementById('assign_resident_id').value = id;
      document.getElementById('assign_name_display').value = name;
      document.getElementById('assign_error_msg').classList.remove('visible');
      document.getElementById('assign_original_room_id').value = currentRoomId !== null ? currentRoomId : '';

      const select = document.getElementById('assign_room_select');

      // Reset all options: disable full ones, enable non-full ones
      Array.from(select.options).forEach(opt => {
        if (!opt.value) return; // skip "Remove Assignment"
        const occupied = parseInt(opt.dataset.occupied);
        const capacity = parseInt(opt.dataset.capacity);
        const isCurrentRoom = currentRoomId !== null && parseInt(opt.value) === currentRoomId;

        // Never disable the resident's current room (they're already counted in it)
        if (isCurrentRoom) {
          opt.disabled = false;
          opt.textContent = opt.textContent.replace(' · Full', '');
        } else if (occupied >= capacity) {
          opt.disabled = true;
          if (!opt.textContent.includes(' · Full')) opt.textContent += ' · Full';
        } else {
          opt.disabled = false;
          opt.textContent = opt.textContent.replace(' · Full', '');
        }
      });

      // Pre-select current room
      select.value = currentRoomId !== null ? String(currentRoomId) : '';
      if (currentRoomId !== null && select.value !== String(currentRoomId)) {
        select.value = '';
      }

      document.getElementById('assignModal').classList.add('active');
    }

    // ── Close any modal ────────────────────────────────────
    function closeModal(id) {
      document.getElementById(id).classList.remove('active');
    }

    // Close on backdrop click
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
      overlay.addEventListener('click', function (e) {
        if (e.target === this) this.classList.remove('active');
      });
    });

    // ── Debounced Search ───────────────────────────────────
    const searchInput = document.getElementById('searchInput');
    let debounceTimer;
    searchInput.addEventListener('input', function () {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => { this.closest('form').submit(); }, 500);
    });
  </script>
</body>

</html>