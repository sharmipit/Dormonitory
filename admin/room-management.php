<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
require_once '../config/db.php';

/* =========================
   AJAX: VIEW ROOM
========================= */
if (isset($_GET['ajax_view'])) {
  ob_clean();
  header('Content-Type: application/json; charset=utf-8');
  try {
    $roomId = (int) ($_GET['id'] ?? 0);
    if ($roomId <= 0) {
      echo json_encode(['error' => 'Invalid room ID']);
      exit;
    }

    $roomStmt = $pdo->prepare("SELECT room_number, room_type FROM room WHERE room_id = ?");
    $roomStmt->execute([$roomId]);
    $room = $roomStmt->fetch(PDO::FETCH_ASSOC);

    if (!$room) {
      echo json_encode(['error' => 'Room not found']);
      exit;
    }

    $resStmt = $pdo->prepare("SELECT first_name, last_name FROM resident WHERE room_id = ?");
    $resStmt->execute([$roomId]);

    echo json_encode(['room' => $room, 'residents' => $resStmt->fetchAll(PDO::FETCH_ASSOC)]);
  } catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
    error_log($e->getMessage());
  }
  exit;
}

/* =========================
   ADD ROOM
========================= */
$addError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_room') {
  $room_number = trim($_POST['room_number']);
  $room_type = trim($_POST['room_type']);
  $capacity = (int) $_POST['max_capacity'];

  try {
    $check = $pdo->prepare("SELECT COUNT(*) FROM room WHERE room_number = ?");
    $check->execute([$room_number]);

    if ($check->fetchColumn() > 0) {
      $addError = "Room already exists.";
    } else {
      $pdo->prepare("INSERT INTO room (room_number, room_type, max_capacity) VALUES (?, ?, ?)")
        ->execute([$room_number, $room_type, $capacity]);
      header("Location: " . $_SERVER['PHP_SELF']);
      exit;
    }
  } catch (PDOException $e) {
    $addError = "Database error.";
    error_log($e->getMessage());
  }
}

/* =========================
   DELETE ROOM
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_room') {
  ob_clean();
  header('Content-Type: application/json');
  try {
    $id = (int) $_POST['room_id'];
    $resCheck = $pdo->prepare("SELECT COUNT(*) FROM resident WHERE room_id = ?");
    $resCheck->execute([$id]);

    if ((int) $resCheck->fetchColumn() > 0) {
      echo json_encode(['error' => 'Cannot remove this room while residents are still assigned. Please reassign them first.']);
    } else {
      $pdo->prepare("DELETE FROM room WHERE room_id = ?")->execute([$id]);
      echo json_encode(['success' => true]);
    }
  } catch (Throwable $e) {
    error_log($e->getMessage());
    echo json_encode(['error' => 'Database error.']);
  }
  exit;
}

/* =========================
   FETCH ROOMS
========================= */
$stmt = $pdo->query("
  SELECT 
    r.room_id,
    r.room_number,
    r.room_type,
    r.max_capacity,
    COUNT(res.resident_id) AS occupied
  FROM room r
  LEFT JOIN resident res ON res.room_id = r.room_id
  GROUP BY r.room_id
  ORDER BY 
    (COUNT(res.resident_id) >= r.max_capacity) ASC,
    CAST(r.room_number AS UNSIGNED) ASC
");
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Room Management</title>

  <link rel="stylesheet" href="/Dormonitory/assets/css/sidebar-navbar-styles.css" />
  <link rel="stylesheet" href="/Dormonitory/assets/css/admin-styles.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet" />

  <style>
    /* ============================================================
   MODALS (ROOM SYSTEM ONLY CLEAN FIX)
   ============================================================ */

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

    /* MAIN MODAL BOX */
    .modal-box {
      background: var(--bg-card);
      border-radius: 20px;
      padding: 32px;
      width: 420px;
      max-width: 95vw;
      box-shadow: 0 8px 40px rgba(48, 48, 182, 0.18);
    }

    /* TITLE */
    .modal-box h2 {
      font-size: 1.2rem;
      font-weight: 700;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 8px;
      color: var(--text-primary);
    }

    /* INPUTS */
    .modal-box label {
      display: block;
      font-size: 0.82rem;
      font-weight: 600;
      margin: 14px 0 6px;
      color: var(--text-secondary);
    }

    .modal-box input,
    .modal-box select {
      width: 100%;
      padding: 10px 14px;
      border: 1.5px solid #e5e7eb;
      border-radius: 10px;
      font-size: 0.95rem;
      outline: none;
      transition: 0.2s;
      color: var(--text-primary);
    }

    .modal-box input:focus,
    .modal-box select:focus {
      border-color: var(--accent);
    }

    /* ACTIONS */
    .modal-actions {
      display: flex;
      justify-content: flex-end;
      gap: 12px;
      margin-top: 24px;
    }

    /* BUTTONS */
    .btn-cancel {
      padding: 9px 22px;
      border-radius: 10px;
      border: 1.5px solid #e5e7eb;
      background: #fff;
      cursor: pointer;
      font-size: 0.9rem;
      color: var(--text-primary);
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
      font-size: 0.9rem;
      font-weight: 600;
    }

    .btn-save:hover {
      background: #2525a0;
    }

    /* DELETE MODAL */
    .confirm-box {
      background: var(--bg-card);
      border-radius: 20px;
      padding: 32px;
      width: 360px;
      max-width: 95vw;
      text-align: center;
      box-shadow: 0 8px 40px rgba(220, 38, 38, 0.13);
    }

    .btn-delete-confirm {
      padding: 9px 22px;
      border-radius: 10px;
      border: none;
      background: #dc2626;
      color: #fff;
      cursor: pointer;
      font-size: 0.9rem;
      font-weight: 600;
    }

    .btn-delete-confirm:hover {
      background: #b91c1c;
    }

    /* ============================================================
   ROOMS (ONLY CLEAN FIXES)
   ============================================================ */

    .room-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
      gap: 16px;
    }

    .room-card {
      background: var(--bg-card);
      border-radius: 20px;
      padding: 18px;
      border: 1px solid var(--border, #e5e7eb);
      box-shadow: var(--shadow-card);
      display: flex;
      flex-direction: column;
      gap: 12px;
      transition: 0.2s ease;
    }

    .room-card:hover {
      transform: translateY(-3px);
      box-shadow: var(--shadow-btn);
    }

    /* ICON */
    .room-icon {
      width: 46px;
      height: 46px;
      background: var(--accent);
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      font-size: 1.2rem;
    }

    /* TEXT */
    .room-meta h3 {
      font-size: 1rem;
      font-weight: 700;
      color: var(--text-primary);
    }

    .room-meta p {
      font-size: 0.82rem;
      color: var(--text-secondary);
    }

    /* OCCUPANCY */
    .room-occupancy-text {
      font-size: 0.82rem;
      font-weight: 600;
      color: var(--text-secondary);
    }

    /* PROGRESS */
    .progress-bar {
      width: 100%;
      height: 6px;
      background: #e5e7eb;
      border-radius: 999px;
      overflow: hidden;
    }

    .progress-fill {
      height: 100%;
      background: var(--accent);
      border-radius: 999px;
      transition: width 0.3s ease;
    }

    .progress-fill.full {
      background: #dc2626;
    }

    /* ACTIONS */
    .room-actions {
      display: flex;
      gap: 10px;
    }

    .btn-view,
    .btn-remove {
      flex: 1;
      padding: 8px 10px;
      border-radius: 10px;
      font-size: 0.82rem;
      font-weight: 600;
      cursor: pointer;
      border: none;
    }

    /* VIEW */
    .btn-view {
      background: #f3f4f6;
      color: var(--text-primary);
    }

    .btn-view:hover {
      background: #e5e7eb;
    }

    /* DELETE */
    .btn-remove {
      background: #fee2e2;
      color: #dc2626;
    }

    .btn-remove:hover {
      background: #f8caca;
    }

    .modal-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 12px;
    }

    .modal-table th {
      text-align: left;
      font-size: 0.75rem;
      text-transform: uppercase;
      color: var(--text-secondary);
      padding: 10px 0;
      border-bottom: 1px solid #f1f2f6;
    }

    .modal-table td {
      padding: 10px 0;
      border-bottom: 1px solid #f1f2f6;
      font-size: 0.9rem;
      font-weight: 500;
      color: var(--text-primary);
    }

    .modal-table tr:last-child td {
      border-bottom: none;
    }

    /* reuse your existing styles */
    .view-header {
      display: flex;
      align-items: center;
      gap: 16px;
      padding-bottom: 16px;
      margin-bottom: 8px;
      border-bottom: 1.5px solid #f1f2f6;
    }

    .view-avatar {
      width: 48px;
      height: 48px;
      border-radius: 50%;
      background: #eef0ff;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.4rem;
      color: var(--accent);
    }

    .view-title strong {
      display: block;
      font-size: 1rem;
      font-weight: 700;
    }

    .view-title small {
      font-size: 0.78rem;
      color: var(--text-secondary);
    }
  </style>
</head>

<body>

  <div id="sidebar-navbar"></div>
  <script src="/Dormonitory/assets/js/sidebar-navbar.js"></script>

  <div class="layout">
    <div class="main">

      <button class="add-res-btn" onclick="openModal('addModal')">
        <i class="bi bi-plus-lg"></i> Add Room
      </button>

      <div class="card">

        <div class="room-grid">

          <?php if (empty($rooms)): ?>
            <div style="
      grid-column: 1 / -1;
      text-align: center;
      padding: 50px 20px;
      color: #9e9a9a;
      font-size: 0.98rem;
      border-radius: 16px;
    ">
              No rooms available yet.
            </div>
          <?php else: ?>

            <?php foreach ($rooms as $r):
              $occupied = $r['occupied'];
              $cap = $r['max_capacity'];
              $percent = ($cap > 0) ? ($occupied / $cap) * 100 : 0;
              $full = $occupied >= $cap;
              ?>
              <div class="room-card">

                <div class="room-icon">
                  <i class="bi bi-door-open"></i>
                </div>

                <div class="room-meta">
                  <h3>Room <?php echo htmlspecialchars($r['room_number']); ?></h3>
                  <p><?php echo htmlspecialchars($r['room_type']); ?></p>
                </div>

                <div class="room-occupancy-text">
                  <?php echo $occupied; ?>/<?php echo $cap; ?> occupied
                </div>

                <div class="progress-bar">
                  <div class="progress-fill <?php echo $full ? 'full' : ''; ?>" style="width: <?php echo $percent; ?>%">
                  </div>
                </div>

                <div class="room-actions">
                  <button class="btn-view" onclick="viewRoom(<?php echo $r['room_id']; ?>)">
                    View
                  </button>

                  <button class="btn-remove"
                    onclick="openDeleteModal(<?= $r['room_id'] ?>, '<?= htmlspecialchars($r['room_number']) ?>')">
                    Remove
                  </button>
                </div>

              </div>
            <?php endforeach; ?>

          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- ADD MODAL -->
  <div class="modal-overlay" id="addModal">
    <div class="modal-box">
      <h2><i class="bi bi-plus-lg" style="color:var(--accent);margin-right:8px;"></i>Add Room</h2>

      <?php if ($addError): ?>
        <div style="font-size:0.82rem; color:#dc2626; background:#fff5f5; border:1px solid #fecaca;
                  border-radius:8px; padding:8px 12px; margin-bottom:4px;">
          <?= htmlspecialchars($addError) ?>
        </div>
      <?php endif; ?>

      <form method="POST">
        <input type="hidden" name="action" value="add_room">

        <label>Room Number</label>
        <input name="room_number" required value="<?= $addError ? htmlspecialchars($_POST['room_number']) : '' ?>">

        <label>Room Type</label>
        <select name="room_type" id="room_type_select" onchange="handleRoomTypeChange(this)">
          <option value="Single" <?= ($addError && ($_POST['room_type'] ?? '') === 'Single') ? 'selected' : '' ?>>Single
          </option>
          <option value="Shared" <?= ($addError && ($_POST['room_type'] ?? '') === 'Shared') ? 'selected' : '' ?>>Shared
          </option>
        </select>

        <label>Capacity</label>
        <input type="number" name="max_capacity" id="capacity_input" min="1" required
          value="<?= $addError ? (int) $_POST['max_capacity'] : '1' ?>">

        <div class="modal-actions">
          <button type="button" class="btn-cancel" onclick="closeModal('addModal')">Cancel</button>
          <button class="btn-save">Save</button>
        </div>
      </form>
    </div>
  </div>

  <!-- VIEW MODAL -->
  <div class="modal-overlay" id="viewModal">
    <div class="modal-box">
      <h2>
        <i class="bi bi-door-open" style="color:var(--accent);margin-right:8px;"></i>
        Room Residents
      </h2>

      <!-- Header -->
      <div class="view-header">
        <div class="view-avatar">
          <i class="bi bi-house-door"></i>
        </div>
        <div class="view-title">
          <strong id="view_room_name">Room —</strong>
          <small id="view_room_type">—</small>
        </div>
      </div>

      <!-- TABLE -->
      <table class="modal-table">
        <thead>
          <tr>
            <th>Resident Name</th>
          </tr>
        </thead>
        <tbody id="residentTableBody">
          <tr>
            <td style="text-align:center; color:#9e9a9a;">No residents</td>
          </tr>
        </tbody>
      </table>

      <div class="modal-actions">
        <button class="btn-cancel" onclick="closeModal('viewModal')">Close</button>
      </div>
    </div>
  </div>

  <!-- DELETE MODAL -->
  <div class="modal-overlay" id="deleteModal">
    <div class="confirm-box">
      <div class="confirm-icon" style="font-size:2.4rem; color:#dc2626; margin-bottom:12px;">
        <i class="bi bi-trash"></i>
      </div>
      <h2 style="font-size:1.1rem; font-weight:700; margin-bottom:8px; color:var(--text-primary);">Remove Room</h2>

      <!-- Error message (shown dynamically) -->
      <div id="deleteErrorBox" style="display:none; font-size:0.82rem; color:#dc2626; background:#fff5f5;
          border:1px solid #fecaca; border-radius:8px; padding:8px 12px; margin-bottom:12px;"></div>

      <p id="delete_room_msg" style="font-size:0.88rem; color:var(--text-secondary); margin-bottom:22px;">
        Are you sure you want to remove this room? This action cannot be undone.
      </p>

      <input type="hidden" id="delete_room_id" value="">

      <div class="modal-actions" style="justify-content:center;">
        <button type="button" class="btn-cancel" onclick="closeModal('deleteModal')">Cancel</button>
        <button type="button" class="btn-delete-confirm" onclick="submitDeleteRoom()">Yes, Remove</button>
      </div>
    </div>
  </div>

  <script>
    function openModal(id) {
      document.getElementById(id).classList.add('active');
    }

    function closeModal(id) {
      document.getElementById(id).classList.remove('active');
    }

    function openDeleteModal(roomId, roomNumber) {
      document.getElementById('delete_room_id').value = roomId;
      document.getElementById('delete_room_msg').textContent =
        `Are you sure you want to remove Room ${roomNumber}? This action cannot be undone.`;

      // Clear any previous error
      const errBox = document.getElementById('deleteErrorBox');
      errBox.style.display = 'none';
      errBox.textContent = '';

      openModal('deleteModal');
    }

    function submitDeleteRoom() {
      const roomId = document.getElementById('delete_room_id').value;
      const errBox = document.getElementById('deleteErrorBox');

      const formData = new FormData();
      formData.append('action', 'delete_room');
      formData.append('room_id', roomId);

      fetch(window.location.pathname, { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
          if (data.error) {
            // Show error inside modal — stay open
            errBox.textContent = data.error;
            errBox.style.display = 'block';
          } else {
            // Success — close modal and reload
            closeModal('deleteModal');
            window.location.reload();
          }
        })
        .catch(() => {
          errBox.textContent = 'Something went wrong. Please try again.';
          errBox.style.display = 'block';
        });
    }

    function handleRoomTypeChange(select) {
      const capacityInput = document.getElementById('capacity_input');
      if (select.value === 'Single') {
        capacityInput.value = 1;
        capacityInput.readOnly = true;
        capacityInput.style.background = '#f9fafb';
        capacityInput.style.cursor = 'not-allowed';
      } else {
        capacityInput.readOnly = false;
        capacityInput.style.background = '';
        capacityInput.style.cursor = '';
        if (capacityInput.value <= 1) capacityInput.value = 2;
      }
    }

    function viewRoom(id) {
      fetch("?ajax_view=1&id=" + encodeURIComponent(id))
        .then(res => res.json())
        .then(data => {
          if (data.error) {
            alert(data.error);
            return;
          }

          document.getElementById('view_room_name').textContent = "Room " + data.room.room_number;
          document.getElementById('view_room_type').textContent = data.room.room_type;

          const tbody = document.getElementById('residentTableBody');

          if (!data.residents.length) {
            tbody.innerHTML = `<tr><td style="text-align:center; color:#9e9a9a;">No residents</td></tr>`;
          } else {
            tbody.innerHTML = data.residents
              .map(r => `<tr><td>${r.first_name} ${r.last_name}</td></tr>`)
              .join('');
          }

          openModal('viewModal');
        })
        .catch(() => {
          alert("Failed to load room data.");
        });
    }

    // Auto-open modals on server-side error
    <?php if ($addError): ?>
      openModal('addModal');
    <?php endif; ?>


    // Init capacity field on page load
    handleRoomTypeChange(document.getElementById('room_type_select'));
  </script>
</body>

</html>