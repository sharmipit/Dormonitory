<?php
session_start();
require '../config/db.php';

// Block access if not verified
if (!isset($_SESSION['verified']) || !$_SESSION['verified']) {
    header('Location: forgot-password.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];
    $email = $_SESSION['email'];
    $table = $_SESSION['table'];

    if ($new_password !== $confirm) {
        $_SESSION['error'] = "Passwords do not match.";
        header('Location: new-password.php');
        exit();
    }

    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE {$table} SET pass = ?, reset_code = NULL WHERE email = ?");
    $stmt->execute([$hashed, $email]);

    // Clear session
    unset($_SESSION['email'], $_SESSION['verified']);

    $_SESSION['success'] = "Password reset successful. You can now log in.";
    header('Location: login.html');
    exit();
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>New Password | Dormonitory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/auth-styles.css" />
</head>
<body>
    <main class="landing-container new-password-page">
        <section class="hero-left signup-visual">
            <div class="pattern-overlay"></div>
            <div class="join-container">
                <img src="../assets/img/brandmark-box.png" alt="Join Community" class="brandmark-box-img">
                <div class="join-content">
                    <h2 class="join-title">Secure Your Account</h2>
                    <p class="join-text">Create a new strong password to ensure your account stays safe.</p>
                </div>
            </div>
        </section>  

        <section class="hero-right">
             <div class="back-nav-fixed">
                <a href="forgot-password.php" class="btn-go-back">
                    <i class="bi bi-arrow-left"></i> Go Back
                </a>
            </div>

            <div class="login-box">
                <h2 class="auth-title">New Password</h2>
                <p class="auth-subtitle">Set a strong password to protect your account.</p>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger">
                        <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <form action="new-password.php" method="POST" class="needs-validation" novalidate>
                    
                    <div class="mb-4">
                        <label for="password" class="form-label">New Password</label>
                        <div class="input-group-custom">
                            <i class="bi bi-lock"></i>
                            <input type="password" class="form-control no-validate-icon" id="password" name="new_password" placeholder="Enter your new password" required>
                            <i class="bi bi-eye toggle-password" id="togglePassword"></i>
                        </div>
                        <div class="invalid-feedback">Please enter a new password.</div>
                    </div>

                    <div class="mb-5">
                        <label for="confirmPassword" class="form-label">Confirm Password</label>
                        <div class="input-group-custom">
                            <i class="bi bi-lock"></i>
                            <input type="password" class="form-control no-validate-icon" id="confirmPassword" name="confirm_password" placeholder="Confirm your new password" required>
                        </div>
                        <div id="confirmFeedback" class="invalid-feedback">Please confirm your password.</div>
                    </div>

                    <button type="submit" class="btn-auth-primary w-100">
                        <span>Update Password</span>
                        <i class="bi bi-check-circle ms-2"></i>
                    </button>
                </form>
            </div>
        </section>
    </main>

<script>
    (() => {
        'use strict'
        const form = document.querySelector('.needs-validation');
        const p1 = document.getElementById('password');
        const p2 = document.getElementById('confirmPassword');
        const feedback = document.getElementById('confirmFeedback');
        const toggleBtn = document.getElementById('togglePassword');

        // 1. Password Visibility Toggle 
        toggleBtn.addEventListener('click', function () {

            const type = p1.getAttribute('type') === 'password' ? 'text' : 'password';
            p1.setAttribute('type', type);
            p2.setAttribute('type', type);
            
            this.classList.toggle('bi-eye');
            this.classList.toggle('bi-eye-slash');
        });

        // 2. Form Submission & Custom Validation 
        form.addEventListener('submit', (event) => {

            p2.setCustomValidity("");

            if (p2.value === "") {
                feedback.textContent = "Please confirm your password.";
            } else if (p1.value !== p2.value) {
                feedback.textContent = "Passwords do not match.";
                p2.setCustomValidity("No Match"); 
            }

            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }

            form.classList.add('was-validated');
        }, false);

        // --- 3. Real-time feedback cleanup ---
        p2.addEventListener('input', () => {
            p2.setCustomValidity("");
        });
    })()
</script>
</body>
</html>