<?php

session_start();
require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$siteKey = $_ENV['RECAPTCHA_SITE_KEY'];

?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Management Login | Dormonitory</title>
    <link rel="icon" type="image/png" href="/Dormonitory/assets/img/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/auth-styles.css" />
</head>

<body>
    <main class="landing-container">
        <section class="hero-left signup-visual">
            <div class="pattern-overlay" style="background-image: url('../assets/img/translucent-bg.png');"></div>
            <div class="collage-wrapper">
                <img src="../assets/img/community-hands.png" alt="Community" class="col-img img-1">
                <img src="../assets/img/resident-smiling.png" alt="Resident" class="col-img img-2">
                <img src="../assets/img/security-house.png" alt="Security" class="col-img img-3">
                <div class="branding-float">
                    <img src="../assets/img/primary-logo.png" alt="Dormonitory" class="signup-logo">
                    <p class="signup-description">
                        Experience a seamless, secure, and social-first platform designed to elevate your daily living
                        experience.
                    </p>
                </div>
            </div>
        </section>

        <section class="hero-right">
            <div class="back-nav-fixed">
                <a href="login.html" class="btn-go-back">
                    <i class="bi bi-arrow-left"></i> Go Back
                </a>
            </div>

            <div class="login-box">
                <div class="portal-badge">
                    <div class="badge-icon-box">
                        <i class="bi bi-shield-lock-fill"></i>
                    </div>
                    <span>Management Access</span>
                </div>

                <h2 class="auth-title">Welcome!</h2>
                <p class="auth-subtitle">Securely manage your property operations and resident safety.</p>

                <!--PHP VALIDATION-->
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger" role="alert">
                        <?= $_SESSION['error'];
                        unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>
                <!--END-->

                <form action="management-login_validate.php" method="POST" class="needs-validation" novalidate>
                    <div class="mb-4">
                        <label for="email" class="form-label">Email Address</label>
                        <div class="input-group-custom">
                            <i class="bi bi-envelope"></i>
                            <input type="email" class="form-control" id="email" name="email"
                                placeholder="username@gmail.com" required>
                        </div>
                        <div class="invalid-feedback">Please enter a valid administrator email.</div>
                    </div>

                    <div class="mb-2">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group-custom">
                            <i class="bi bi-lock"></i>
                            <input type="password" class="form-control" id="password" name="password"
                                placeholder="Enter your password" required>
                        </div>
                        <div class="invalid-feedback">Password is required.</div>
                    </div>

                    <div class="text-end mb-4">
                        <a href="forgot-password.php" class="forgot-link">Forgot Password?</a>
                    </div>

                    <div class="mb-3 text-center">
                        <div class="g-recaptcha" data-sitekey="<?= htmlspecialchars($siteKey) ?>"></div>
                    </div>

                    <button type="submit" class="btn-auth-primary w-100">Login</button>

                    <div class="auth-divider">
                        <span>or Log In with</span>
                    </div>

                    <a href="../googleAuth/google-login.php" class="btn-google-auth w-100"
                        style="display:flex; align-items:center; justify-content:center; gap:8px; text-decoration:none;">
                        <img src="https://www.gstatic.com/images/branding/product/1x/gsa_512dp.png" alt="Google Logo"
                            style="width: 20px;">
                        Continue with Google
                    </a>
                </form>

                <div class="login-redirect mt-4 text-center">
                    Don't have an account? <a href="signup-management-access.php" class="fw-bold">Sign Up</a>
                </div>
            </div>
        </section>
    </main>

    <script>
        (() => {
            'use strict'
            const forms = document.querySelectorAll('.needs-validation')
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
        })()

        // reCAPTCHA form display
        Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {

                // reCAPTCHA check
                const recaptchaResponse = grecaptcha.getResponse();
                if (recaptchaResponse.length === 0) {
                    event.preventDefault();
                    event.stopPropagation();
                    alert('Please complete the reCAPTCHA.');
                    return;
                }

                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }
                form.classList.add('was-validated')
            }, false)
        })
    </script>

    <!-- reCAPTCHA form display-->
    <script src="https://www.google.com/recaptcha/api.js"></script>

</body>

</html>