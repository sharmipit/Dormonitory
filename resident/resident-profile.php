<?php
session_start();
include('../config/db.php');

if (!isset($_SESSION['id'])) {
    header("Location: /Dormonitory/index.html");
    exit();
}

$search_id = $_SESSION['id'];
$firstName = $lastName = $email = $contact = $roomId = "";

try {
    $query = "SELECT first_name, last_name, email, contact_number, room_id FROM resident WHERE resident_id = :id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['id' => $search_id]);
    $resData = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($resData) {
        $firstName = $resData['first_name'];
        $lastName = $resData['last_name'];
        $email = $resData['email'];
        $contact = $resData['contact_number'];
        $roomId = $resData['room_id'];
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $newFirstName = trim($_POST['first_name']);
    $newLastName = trim($_POST['last_name']);
    $newContact = trim($_POST['contact_number']);

    try {
        $updateQuery = "UPDATE resident SET first_name = :fname, last_name = :lname, contact_number = :contact WHERE resident_id = :id";
        $stmt = $pdo->prepare($updateQuery);
        $stmt->execute([
            'fname' => $newFirstName,
            'lname' => $newLastName,
            'contact' => $newContact,
            'id' => $search_id
        ]);

        $_SESSION['firstname'] = $newFirstName;
        header("Location: resident-profile.php?success=1");
        exit();
    } catch (PDOException $e) {
        error_log($e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | Dormonitory</title>
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

<body class="resident-body">

    <div id="sidebar-navbar"></div>

    <main class="main-content" id="main-layout">
        <div class="profile-container">
            <div class="profile-card">
                <div class="profile-cover"></div>
                <div class="profile-content">
                    <div class="profile-avatar-wrapper">
                        <img src="/Dormonitory/assets/img/cute-duck-for-good-luck.jpeg" alt="Avatar"
                            class="profile-avatar-big">
                    </div>

                    <div class="profile-info-header">
                        <h2><?php echo htmlspecialchars($firstName . " " . $lastName); ?></h2>
                        <span class="status-badge">RESIDENT • ROOM <?php echo htmlspecialchars($roomId); ?></span>
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
                        <div class="info-group">
                            <label><i class="bi bi-telephone"></i> Contact</label>
                            <p><?php echo htmlspecialchars($contact); ?></p>
                        </div>
                        <div class="info-group">
                            <label><i class="bi bi-envelope"></i> Email</label>
                            <p><?php echo htmlspecialchars($email); ?></p>
                        </div>
                    </div>

                    <div class="profile-actions">
                        <button class="edit-btn" id="open-edit-modal"><i class="bi bi-pencil-square"></i> Edit
                            Profile</button>
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
            <form>
                <div class="info-group" style="margin-bottom: 15px;">
                    <label>First Name</label>
                    <input type="text" id="input-first-name" class="modal-input"
                        value="<?php echo htmlspecialchars($firstName); ?>"
                        style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px;">
                </div>
                <div class="info-group" style="margin-bottom: 15px;">
                    <label>Last Name</label>
                    <input type="text" id="input-last-name" class="modal-input"
                        value="<?php echo htmlspecialchars($lastName); ?>"
                        style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px;">
                </div>
                <div class="info-group" style="margin-bottom: 25px;">
                    <label>Contact Number</label>
                    <input type="text" id="input-contact" class="modal-input"
                        value="<?php echo htmlspecialchars($contact); ?>"
                        style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px;">
                </div>
                <div class="modal-actions">
                    <button type="button" class="modal-btn cancel-btn" id="close-edit-modal">Cancel</button>
                    <button type="button" class="modal-btn confirm-btn" id="proceed-confirm"
                        style="background: #3030b6; color: white;">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <div class="signout-modal-overlay" id="confirm-changes-modal" style="z-index: 1100;">
        <div class="signout-modal-content">
            <div
                style="background: #3030b6; width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                <i class="bi bi-question-lg" style="font-size: 2rem; color: #FFFF;"></i>
            </div>
            <h3>Save changes?</h3>
            <p>Are you sure you want to update your profile details?</p>
            <form method="POST">
                <input type="hidden" name="first_name" id="hidden-first-name">
                <input type="hidden" name="last_name" id="hidden-last-name">
                <input type="hidden" name="contact_number" id="hidden-contact">
                <div class="modal-actions">
                    <button type="button" class="modal-btn cancel-btn" id="back-to-edit">Go Back</button>
                    <button type="submit" name="update_profile" class="modal-btn confirm-btn"
                        style="background: #3030b6; color: white;">Confirm</button>
                </div>
            </form>
        </div>
    </div>

    <script src="/Dormonitory/assets/js/sidebar-navbar.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const editBtn = document.getElementById('open-edit-modal');
            const editModal = document.getElementById('edit-profile-modal');
            const confirmModal = document.getElementById('confirm-changes-modal');

            editBtn.addEventListener('click', () => editModal.style.display = 'flex');
            document.getElementById('close-edit-modal').addEventListener('click', () => editModal.style.display = 'none');

            document.getElementById('proceed-confirm').addEventListener('click', () => {
                document.getElementById('hidden-first-name').value = document.getElementById('input-first-name').value;
                document.getElementById('hidden-last-name').value = document.getElementById('input-last-name').value;
                document.getElementById('hidden-contact').value = document.getElementById('input-contact').value;
                editModal.style.display = 'none';
                confirmModal.style.display = 'flex';
            });

            document.getElementById('back-to-edit').addEventListener('click', () => {
                confirmModal.style.display = 'none';
                editModal.style.display = 'flex';
            });
        });
    </script>
</body>

</html>