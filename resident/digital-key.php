<?php
session_start();
include('../config/db.php');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: ../auth/login-resident-portal.php');
    exit();
}

$stmt = $pdo->prepare("
    SELECT * FROM resident_qr 
    WHERE resident_id = ? 
      AND status = 'Active' 
      AND expires_at > NOW() 
    ORDER BY created_at DESC 
    LIMIT 1
");
$stmt->execute([$_SESSION['id']]);
$activeKey = $stmt->fetch(PDO::FETCH_ASSOC);

$qrUrl = $activeKey ? "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=" . urlencode($activeKey['qr_code']) : null;
$expiresAt = $activeKey ? $activeKey['expires_at'] : null;
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Digital Key | Dormonitory</title>
    <link rel="icon" type="image/png" href="/Dormonitory/assets/img/favicon.ico">
    <link rel="stylesheet" href="../assets/css/sidebar-navbar-styles.css" />
    <link rel="stylesheet" href="../assets/css/resident-styles.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
</head>

<body>
    <input type="hidden" id="keyExpiresAt" value="<?= $expiresAt ?? '' ?>">

    <div id="sidebar-navbar"></div>
    <div class="layout">
        <div class="main">
            <div class="key-card-container">
                <div class="key-card">
                    <div id="activeKeyDisplay" class="qr-main-wrapper">
                        <img src="<?= $qrUrl ?? 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=NO_KEY' ?>"
                            alt="Digital Entry Key" id="mainQrImage">
                    </div>
                    <div class="key-info-header">
                        <h2>Here's Your Digital Key</h2>
                        <p>Scan this code at any building entry point for instant access.</p>
                    </div>
                    <div class="key-status-grid">
                        <div class="status-box-pill">
                            <span class="pill-label">STATUS</span>
                            <div class="status-pill active">ACTIVE</div>
                        </div>
                        <div class="status-box-pill">
                            <span class="pill-label">EXPIRES</span>
                            <div class="expiry-timer" id="keyTimer">in 01h 02m</div>
                        </div>
                    </div>
                    <button id="btnGenerateKey" class="btn-generate-primary">
                        <i class="bi bi-arrow-clockwise"></i> Generate New Key
                    </button>
                    <div class="security-footer">
                        <i class="bi bi-shield-check"></i> Encrypted Dynamic Access
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/sidebar-navbar.js"></script>
    <script>
        let countdownInterval = null;

        function startTimer(expiresAt) {
            clearInterval(countdownInterval);
            countdownInterval = setInterval(() => {
                const distance = new Date(expiresAt).getTime() - Date.now();

                if (distance <= 0) {
                    clearInterval(countdownInterval);
                    document.getElementById('keyTimer').textContent = 'Expired';
                    const statusPill = document.querySelector('.status-pill');
                    if (statusPill) {
                        statusPill.textContent = 'EXPIRED';
                        statusPill.style.background = '#fff1f0';
                        statusPill.style.color = '#ff4d4f';
                    }
                    return;
                }

                const h = Math.floor(distance / (1000 * 60 * 60));
                const m = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const s = Math.floor((distance % (1000 * 60)) / 1000);

                document.getElementById('keyTimer').textContent =
                    `in ${String(h).padStart(2, '0')}h ${String(m).padStart(2, '0')}m ${String(s).padStart(2, '0')}s`;
            }, 1000);
        }

        const expiresAt = "<?= $expiresAt ?>";
        if (expiresAt) {
            startTimer(expiresAt);
        } else {
            const statusPill = document.querySelector('.status-pill');
            if (statusPill) {
                statusPill.textContent = 'INACTIVE';
                statusPill.style.background = '#f4f7fe';
                statusPill.style.color = '#a3aed0';
            }
            document.getElementById('keyTimer').textContent = 'No active key';
        }

        document.getElementById('btnGenerateKey').addEventListener('click', async () => {
            const btn = document.getElementById('btnGenerateKey');
            btn.disabled = true;
            btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Generating...';

            const res = await fetch('generate-key.php');
            const data = await res.json();

            if (data.success) {
                document.getElementById('mainQrImage').src = data.qr_url;

                const statusPill = document.querySelector('.status-pill');
                if (statusPill) {
                    statusPill.textContent = 'ACTIVE';
                    statusPill.style.background = '#e6fff9';
                    statusPill.style.color = '#05cd99';
                }

                startTimer(new Date(Date.now() + 24 * 60 * 60 * 1000).toISOString());
            }

            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Generate New Key';
        });
    </script>
</body>

</html>