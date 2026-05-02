<?php

session_start();

?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Resident Registration | Dormonitory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/auth-styles.css" />
</head>

<body>
    <main class="landing-container">
        <section class="hero-left signup-visual">
            <div class="pattern-overlay"></div>

            <div class="join-container">
                <img src="../assets/img/brandmark-box.png" alt="Join Community" class="brandmark-box-img">

                <div class="join-content">
                    <h2 class="join-title">Join the Community!</h2>
                    <p class="join-text">Connect with fellow residents, manage your stay, and stay updated with
                        everything happening in your dormitory.</p>
                </div>
            </div>
        </section>

        <section class="hero-right">
            <div class="back-nav-fixed">
                <a href="signup.html" class="btn-go-back">
                    <i class="bi bi-arrow-left"></i> Go Back
                </a>
            </div>

            <div class="login-box" style="max-width: 500px;">
                <div class="portal-badge">
                    <div class="badge-icon-box">
                        <i class="bi bi-person-fill"></i>
                    </div>
                    <span>Resident Registration</span>
                </div>

                <h2 class="auth-title">Join Us!</h2>
                <p class="auth-subtitle">Create your resident account to start your digital dormitory journey.</p>

                <!--- PHP VALIDATION DESIGN --->
                <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger" role="alert">
                    <?= $_SESSION['error'];
                    unset($_SESSION['error']); ?>
                </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success" role="alert">
                    <?= $_SESSION['success'];
                    unset($_SESSION['success']); ?>
                </div>
                <?php endif; ?>
                <!--- END --->

                <form action="resident-signup_validate.php" method="POST" class="needs-validation" novalidate>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label">First Name</label>
                            <div class="input-group-custom">
                                <i class="bi bi-person"></i>
                                <input type="text" class="form-control" name="first_name" placeholder="e.g., Juan"
                                    required>
                            </div>
                            <div class="invalid-feedback">First name is required.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Last Name</label>
                            <div class="input-group-custom">
                                <i class="bi bi-person"></i>
                                <input type="text" class="form-control" name="last_name" placeholder="e.g., Dela Cruz"
                                    required>
                            </div>
                            <div class="invalid-feedback">Last name is required.</div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Contact Number</label>
                        <div class="input-group-custom">
                            <i class="bi bi-telephone"></i>
                            <input type="text" class="form-control" id="contactNumber" name="contactNumber"
                                placeholder="09XXXXXXXXX" pattern="09[0-9]{9}" maxlength="11" inputmode="numeric"
                                required>
                        </div>
                        <div class="invalid-feedback">
                            Enter an 11-digit contact number.
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Email Address</label>
                        <div class="input-group-custom">
                            <i class="bi bi-envelope"></i>
                            <input type="email" class="form-control" name="email" placeholder="username@gmail.com"
                                required>
                        </div>
                        <div class="invalid-feedback">Please enter a valid email address.</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Password</label>
                        <div class="input-group-custom">
                            <i class="bi bi-lock"></i>
                            <input type="password" class="form-control" name="password"
                                placeholder="Enter your password" required>
                        </div>
                        <div class="invalid-feedback">Password is required.</div>
                    </div>

                    <button type="submit" class="btn-auth-primary w-100 mt-2">Create My Account</button>

                    <div class="auth-divider">
                        <span>or Sign Up with</span>
                    </div>

                    <a href="../googleAuth/google-login.php" class="btn-google-auth w-100"
                        style="display:flex; align-items:center; justify-content:center; gap:8px; text-decoration:none;">
                        <img src="https://www.gstatic.com/images/branding/product/1x/gsa_512dp.png" alt="Google Logo"
                            style="width: 20px;">
                        Continue with Google
                    </a>
                </form>

                <div class="login-redirect mt-4 text-center">
                    Already have an account? <a href="login-resident-portal.php" class="fw-bold"
                        style="text-decoration:none; color:var(--brand-blue);">Sign In</a>
                </div>
            </div>
        </section>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const contactInput = document.getElementById('contactNumber');
            const forms = document.querySelectorAll('.needs-validation');

            // 1. Force numeric input only for the contact field
            contactInput.addEventListener('input', (e) => {
                e.target.value = e.target.value.replace(/\D/g, '');
            });

            // 2. Handle Form Validation
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {

                    if (contactInput.value.length > 0 && !contactInput.value.startsWith('09')) {
                        contactInput.setCustomValidity('Invalid');
                    } else if (contactInput.value.length !== 11) {
                        contactInput.setCustomValidity('Invalid');
                    } else {
                        contactInput.setCustomValidity('');
                    }

                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }

                    form.classList.add('was-validated');
                }, false);
            });
        });
    </script>
</body>

</html>