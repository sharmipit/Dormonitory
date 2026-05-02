<?php
session_start();
include('../config/db.php');

if (!isset($_SESSION['admin_id'])) {
    header("Location: /Dormonitory/index.html");
    exit();
}

$search_id = $_SESSION['admin_id'];
$firstName = $lastName = $email = "";

try {
    $query = "SELECT first_name, last_name, email FROM admin WHERE admin_id = :id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['id' => $search_id]);
    $adminData = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($adminData) {
        $firstName = $adminData['first_name'];
        $lastName = $adminData['last_name'];
        $email = $adminData['email'];
    }
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $newFirstName = trim($_POST['first_name']);
    $newLastName = trim($_POST['last_name']);

    try {
        $updateQuery = "UPDATE admin SET first_name = :fname, last_name = :lname WHERE admin_id = :id";
        $stmt = $pdo->prepare($updateQuery);
        $stmt->execute([
            'fname' => $newFirstName,
            'lname' => $newLastName,
            'id' => $search_id
        ]);

        $_SESSION['firstname'] = $newFirstName;
        header("Location: admin-profile.php?success=1");
        exit();
    } catch (PDOException $e) {
        error_log("Update Error: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile | Dormonitory</title>
    <link rel="stylesheet" href="/Dormonitory/assets/css/sidebar-navbar-styles.css">
    <link rel="stylesheet" href="/Dormonitory/assets/css/profile-styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .signout-modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-header-flex {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            margin-bottom: 10px;
        }

        .modal-icon-box {
            background: #3030b6;
            width: 45px;
            height: 45px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .modal-icon-box i {
            font-size: 1.4rem;
            color: #FFFFFF;
        }

        .modal-title-group {
            padding-top: 2px;
        }

        .modal-title-group h3 {
            margin: 0;
            line-height: 1.2;
            font-weight: 700;
            color: #1a1a3d;
        }

        .modal-sub-text {
            font-size: 0.9rem;
            color: #666;
            margin: 0;
            line-height: 1.1;
            margin-top: 4px;
        }
    </style>
</head>

<body class="admin-body">

    <div id="sidebar-navbar"></div>

    <main class="main-content" id="main-layout">
        <div class="profile-container">
            <div class="profile-card">
                <div class="profile-cover"></div>
                <div class="profile-content">
                    <div class="profile-avatar-wrapper">
                        <img src="/Dormonitory/assets/img/cute-duck-for-good-luck.jpeg" alt="Admin Avatar"
                            class="profile-avatar-big">
                    </div>

                    <div class="profile-info-header">
                        <h2 style="font-weight: 800; color: #1a1a3d; margin-bottom: 5px;">
                            <?php echo htmlspecialchars($firstName . " " . $lastName); ?>
                        </h2>
                        <span class="status-badge">SYSTEM ADMINISTRATOR</span>
                    </div>

                    <div class="info-grid">
                        <div class="info-group">
                            <label><i class="bi bi-person"></i> First Name</label>
                            <p><?php echo htmlspecialchars($firstName); ?></p>
                        </div>
                        <div class="info-group">
                            <label><i class="bi bi-person"></i> Last Name</label>
                            <p><?php echo htmlspecialchars($lastName); ?></p>
                        </div>
                        <div class="info-group full-width">
                            <label><i class="bi bi-envelope"></i> Email Address</label>
                            <p><?php echo htmlspecialchars($email); ?></p>
                        </div>
                    </div>

                    <div class="profile-actions">
                        <button class="edit-btn">
                            <i class="bi bi-pencil-square"></i> Edit Profile
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <div class="signout-modal-overlay" id="edit-profile-modal">
        <div class="signout-modal-content"
            style="text-align: left; width: 500px; background: white; padding: 30px; border-radius: 12px;">
            <div class="modal-header-flex">
                <div class="modal-icon-box">
                    <i class="bi bi-person-gear"></i>
                </div>
                <div class="modal-title-group">
                    <h3>Edit Profile Information</h3>
                    <p class="modal-sub-text">Update your name details below.</p>
                </div>
            </div>

            <form id="profile-edit-form">
                <div class="info-group" style="margin-bottom: 15px;">
                    <label>First Name</label>
                    <input type="text" id="input-first-name" name="first_name"
                        value="<?php echo htmlspecialchars($firstName); ?>"
                        style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #ddd;" required>
                </div>
                <div class="info-group" style="margin-bottom: 15px;">
                    <label>Last Name</label>
                    <input type="text" id="input-last-name" name="last_name"
                        value="<?php echo htmlspecialchars($lastName); ?>"
                        style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #ddd;" required>
                </div>
                <div class="info-group" style="margin-bottom: 25px;">
                    <label>Email Address (Cannot be changed)</label>
                    <input type="email" value="<?php echo htmlspecialchars($email); ?>"
                        style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #eee; background-color: #f9f9f9; color: #888;"
                        readonly>
                </div>

                <div class="modal-actions">
                    <button type="button" class="modal-btn cancel-btn" id="close-edit-modal">Cancel</button>
                    <button type="button" class="modal-btn confirm-btn" id="open-confirm-modal"
                        style="background: #3030b6; color: white; border: none; padding: 10px 20px; border-radius: 10px; cursor: pointer;">Update</button>
                </div>
            </form>
        </div>
    </div>

    <div class="signout-modal-overlay" id="confirm-changes-modal" style="z-index: 1001;">
        <div class="signout-modal-content"
            style="text-align: center; background: white; padding: 30px; border-radius: 12px;">
            <div
                style="background: #3030b6; width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                <i class="bi bi-question-lg" style="font-size: 2rem; color: #FFFF;"></i>
            </div>
            <h3>Save Changes?</h3>
            <p>Are you sure you want to update your profile information?</p>

            <form method="POST" action="">
                <input type="hidden" name="first_name" id="hidden-first-name">
                <input type="hidden" name="last_name" id="hidden-last-name">

                <div class="modal-actions">
                    <button type="button" class="modal-btn cancel-btn" id="close-confirm-modal">Cancel</button>
                    <button type="submit" name="update_profile" class="modal-btn confirm-btn"
                        style="background: #3030b6; color: white; border: none; padding: 10px 20px; border-radius: 10px; cursor: pointer;">Yes,
                        Save</button>
                </div>
            </form>
        </div>
    </div>

    <script src="/Dormonitory/assets/js/sidebar-navbar.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const editBtn = document.querySelector('.edit-btn');
            const editModal = document.getElementById('edit-profile-modal');
            const closeEditBtn = document.getElementById('close-edit-modal');

            const confirmModal = document.getElementById('confirm-changes-modal');
            const openConfirmBtn = document.getElementById('open-confirm-modal');
            const closeConfirmBtn = document.getElementById('close-confirm-modal');

            const inputFirst = document.getElementById('input-first-name');
            const inputLast = document.getElementById('input-last-name');
            const hiddenFirst = document.getElementById('hidden-first-name');
            const hiddenLast = document.getElementById('hidden-last-name');

            editBtn.addEventListener('click', () => { editModal.style.display = 'flex'; });
            closeEditBtn.addEventListener('click', () => { editModal.style.display = 'none'; });

            openConfirmBtn.addEventListener('click', () => {
                hiddenFirst.value = inputFirst.value;
                hiddenLast.value = inputLast.value;
                editModal.style.display = 'none';
                confirmModal.style.display = 'flex';
            });

            closeConfirmBtn.addEventListener('click', () => {
                confirmModal.style.display = 'none';
                editModal.style.display = 'flex';
            });

            window.addEventListener('click', (e) => {
                if (e.target === editModal) editModal.style.display = 'none';
                if (e.target === confirmModal) {
                    confirmModal.style.display = 'none';
                    editModal.style.display = 'flex';
                }
            });
        });
    </script>
</body>

</html>