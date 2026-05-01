<?php
session_start();
include('../config/db.php');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: ../auth/login-resident-portal.php');
    exit();
}

// Fetch visitor history for this resident
$stmt = $pdo->prepare("SELECT * FROM visitor_log WHERE resident_id = ? ORDER BY created_at DESC LIMIT 10");
$stmt->execute([$_SESSION['id']]);
$visitors = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Invite Visitor | Dormonitory</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/sidebar-navbar-styles.css" />
    <link rel="stylesheet" href="../assets/css/resident-styles.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
</head>

<body>
    <div id="sidebar-navbar"></div>
    <div class="layout">
        <div class="main">
            <div class="dashboard-grid">

                <section class="card invite-form-card">
                    <div class="card-header-with-subtitle">
                        <div class="header-main">
                            <div class="icon-box"><i class="bi bi-person-plus-fill"></i></div>
                            <div>
                                <h2>Invite Visitor</h2>
                                <p class="subtitle">Generate a temporary access pass</p>
                            </div>
                        </div>
                    </div>

                    <div class="invite-content-wrapper">
                        <form class="portal-form" id="visitorForm" novalidate>
                            <div class="form-group">
                                <label for="visitorName">Visitor Name</label>
                                <input type="text" id="visitorName" placeholder="e.g., Juan Dela Cruz" required>
                                <span class="error-msg" id="nameError">Please enter the visitor's name.</span>
                            </div>

                            <div class="form-group">
                                <label for="contactNumber">Contact Number</label>
                                <input type="tel" id="contactNumber" placeholder="09XXXXXXXXX" required
                                    pattern="[0-9]{11}">
                                <span class="error-msg" id="contactError">Please enter a valid 11-digit number.</span>
                            </div>

                            <button type="submit" class="btn-primary-portal">Generate Visitor Pass</button>
                        </form>

                        <div class="qr-display-section">
                            <div class="qr-placeholder" id="qrContainer">
                                <i class="bi bi-qr-code"></i>
                                <p class="qr-instruction">Fill the form to generate QR</p>
                            </div>
                            <div class="qr-expiry" id="qrExpiry">
                                <p>Pass Expires: <span id="expiryTimestamp">--/--/-- --:--</span></p>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="card history-card">
                    <div class="card-header">
                        <h2><i class="bi bi-clock"></i> Visitor History</h2>
                        <a href="#" class="view-all">View All</a>
                    </div>

                    <div class="activity-list" id="visitorHistoryList">
                        <?php if (empty($visitors)): ?>
                            <p class="text-center text-muted mt-3">No visitor history yet.</p>
                        <?php else: ?>
                            <?php foreach ($visitors as $v): ?>
                            <div class="activity-item history-item">
                                <div class="item-top">
                                    <div class="icon-box"><i class="bi bi-person-fill"></i></div>
                                    <div class="item-content">
                                        <h3><?= htmlspecialchars($v['visitor_name']) ?></h3>
                                        <p><?= date('M d, Y', strtotime($v['visit_date'])) ?> • <?= htmlspecialchars($v['contact_number']) ?></p>
                                    </div>
                                    <div class="chevron"><i class="bi bi-chevron-right"></i></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>

            </div>
        </div>
    </div>

    <div id="universalModal" class="modal-overlay">
        <div class="modal-container" id="modalContainer"></div>
    </div>

    <script src="../assets/js/sidebar-navbar.js"></script>
    <script src="../assets/js/main.js"></script>
    <script>
        document.getElementById('visitorForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const name    = document.getElementById('visitorName').value.trim();
            const contact = document.getElementById('contactNumber').value.trim();

            // Basic validation
            if (!name) { document.getElementById('nameError').style.display = 'block'; return; }
            if (!/^[0-9]{11}$/.test(contact)) { document.getElementById('contactError').style.display = 'block'; return; }

            document.getElementById('nameError').style.display    = 'none';
            document.getElementById('contactError').style.display = 'none';

            const formData = new FormData();
            formData.append('visitor_name',   name);
            formData.append('contact_number', contact);

            const response = await fetch('invite-visitor-save.php', {
                method: 'POST',
                body:   formData
            });
            const data = await response.json();

            if (data.success) {
                // Show QR code
                const qrContainer = document.getElementById('qrContainer');
                qrContainer.innerHTML = `<img src="${data.qr_url}" alt="Visitor QR" style="width:200px;">`;

                // Show expiry
                const today = new Date();
                today.setDate(today.getDate() + 1);
                document.getElementById('expiryTimestamp').textContent = today.toLocaleString();
                document.getElementById('qrExpiry').style.display = 'block';

                // Add to history list dynamically
                const historyList = document.getElementById('visitorHistoryList');
                const newItem = document.createElement('div');
                newItem.className = 'activity-item history-item';
                newItem.innerHTML = `
                    <div class="item-top">
                        <div class="icon-box"><i class="bi bi-person-fill"></i></div>
                        <div class="item-content">
                            <h3>${data.visitor_name}</h3>
                            <p>Today • ${new Date().toLocaleTimeString()}</p>
                        </div>
                        <div class="chevron"><i class="bi bi-chevron-right"></i></div>
                    </div>`;
                historyList.prepend(newItem);

                // Reset form
                document.getElementById('visitorForm').reset();
            } else {
                alert(data.message || 'Something went wrong.');
            }
        });
    </script>
</body>

</html>