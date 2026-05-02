<?php
session_start();
include('../config/db.php');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: ../auth/login-resident-portal.php');
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM visitor_log WHERE resident_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$_SESSION['id']]);
$visitors = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmtActive = $pdo->prepare("
    SELECT * FROM visitor_log 
    WHERE resident_id = ? 
    AND DATE_ADD(created_at, INTERVAL 30 MINUTE) > NOW()
    ORDER BY created_at DESC 
    LIMIT 1
");

$stmtActive->execute([$_SESSION['id']]);
$activePass = $stmtActive->fetch(PDO::FETCH_ASSOC);

$activeQrUrl = $activePass ? "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=" . urlencode($activePass['qr_token']) : null;
$activeExpiry = $activePass
    ? date('m/d/y H:i:s', strtotime($activePass['created_at'] . ' +30 minutes'))
    : null; ?>

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
                                <?php if ($activeQrUrl): ?>
                                    <img src="<?= htmlspecialchars($activeQrUrl) ?>" alt="Visitor QR" style="width:200px;">
                                <?php else: ?>
                                    <i class="bi bi-qr-code"></i>
                                    <p class="qr-instruction">Fill the form to generate QR</p>
                                <?php endif; ?>
                            </div>

                            <div class="qr-expiry" id="qrExpiry">
                                <p>Pass Expires in: <span id="expiryTimestamp">
                                        <?= $activeExpiry ? '--:--:--' : '--/--/-- --:--' ?>
                                    </span></p>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="card history-card">
                    <div class="card-header">
                        <h2><i class="bi bi-clock"></i> Recent Visitors</h2>
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
                                            <p>
                                                <?= date('M d, Y', strtotime($v['visit_date'])) ?> •
                                                <?= date('h:i A', strtotime($v['created_at'])) ?>
                                            </p>
                                        </div>
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

            const name = document.getElementById('visitorName').value.trim();
            const contact = document.getElementById('contactNumber').value.trim();

            if (!name) {
                document.getElementById('nameError').style.display = 'block';
                return;
            }
            if (!/^[0-9]{11}$/.test(contact)) {
                document.getElementById('contactError').style.display = 'block';
                return;
            }

            document.getElementById('nameError').style.display = 'none';
            document.getElementById('contactError').style.display = 'none';

            const formData = new FormData();
            formData.append('visitor_name', name);
            formData.append('contact_number', contact);

            const response = await fetch('invite-visitor-save.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.success) {
                alert('Visitor pass generated for ' + data.visitor_name + '!');
                window.location.reload();
            } else {
                alert(data.message || 'Something went wrong.');
            }
        });

        // Countdown Timer for Visitor Pass
        const expiryTime = "<?= $activeExpiry ?? '' ?>";

        if (expiryTime) {
            const countdownEl = document.getElementById('expiryTimestamp');

            const timer = setInterval(() => {
                const distance = new Date(expiryTime).getTime() - Date.now();

                if (distance <= 0) {
                    clearInterval(timer);
                    countdownEl.textContent = 'Expired';
                    countdownEl.style.color = '#ff4d4f';

                    // Replace QR with placeholder
                    const qrContainer = document.getElementById('qrContainer');
                    qrContainer.innerHTML = `
                        <i class="bi bi-qr-code"></i>
                        <p class="qr-instruction">Pass expired. Generate a new one.</p>
                    `;
                    return;
                }

                const h = Math.floor(distance / (1000 * 60 * 60));
                const m = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const s = Math.floor((distance % (1000 * 60)) / 1000);

                countdownEl.textContent =
                    `${String(h).padStart(2, '0')}h ${String(m).padStart(2, '0')}m ${String(s).padStart(2, '0')}s`;
            }, 1000);
        }
    </script>
</body>

</html>