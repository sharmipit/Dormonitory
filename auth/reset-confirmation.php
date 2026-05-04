<?php
session_start();
require '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entered_code = $_POST['code'];
    $email = $_SESSION['email'];
    $table = $_SESSION['table'];

    $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE email = ? AND reset_code = ?");
    $stmt->execute([$email, $entered_code]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $_SESSION['verified'] = true;
        header('Location: new-password.php');
        exit();
    } else {
        $_SESSION['error'] = "Invalid or expired code.";
        header('Location: reset-confirmation.php');
        exit();
    }
}
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Reset Confirmation | Dormonitory</title>
    <link rel="icon" type="image/png" href="/Dormonitory/assets/img/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/auth-styles.css" />
</head>

<body>
    <main class="landing-container verification-page">
        <section class="hero-left signup-visual">
            <div class="pattern-overlay"></div>
            <div class="join-container">
                <img src="../assets/img/brandmark-box.png" alt="Join Community" class="brandmark-box-img">
                <div class="join-content">
                    <h2 class="join-title">Don't Worry!</h2>
                    <p class="join-text">We'll help you get back into your account in no time. Your security is our top
                        priority.</p>
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
                <h2 class="auth-title">Enter Verification Code</h2>
                <p class="auth-subtitle mb-5">Verify your number with the 6-digit code we just sent via email. The code
                    expires in 5 minutes.</p>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger text-center">
                        <?= $_SESSION['error'];
                        unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <form action="reset-confirmation.php" method="POST" class="needs-validation" id="otp-form" novalidate>
                    <input type="hidden" name="code" id="full-code">
                    <div class="otp-container mb-4">
                        <input type="text" class="otp-input" maxlength="1" pattern="\d*" required inputmode="numeric">
                        <input type="text" class="otp-input" maxlength="1" pattern="\d*" required inputmode="numeric">
                        <input type="text" class="otp-input" maxlength="1" pattern="\d*" required inputmode="numeric">
                        <input type="text" class="otp-input" maxlength="1" pattern="\d*" required inputmode="numeric">
                        <input type="text" class="otp-input" maxlength="1" pattern="\d*" required inputmode="numeric">
                        <input type="text" class="otp-input" maxlength="1" pattern="\d*" required inputmode="numeric">
                    </div>

                    <p class="resend-text mb-3">
                        Didn’t received the code? <a href="forgot-password.php" class="forgot-link">Resend</a>
                    </p>

                    <div id="otp-error" class="invalid-feedback text-center mb-4">
                        Please enter the complete 6-digit verification code.
                    </div>

                    <button type="submit" class="btn-auth-primary w-100 py-3">
                        Verify
                    </button>
                </form>
            </div>
        </section>
    </main>

    <script>
        (() => {
            'use strict'
            const form = document.getElementById('otp-form');
            const inputs = document.querySelectorAll('.otp-input');
            const errorMsg = document.getElementById('otp-error');

            // 1. Handle Auto-focus and Backspacing
            inputs.forEach((input, index) => {
                input.addEventListener('input', (e) => {
                    // Only allow numbers
                    if (e.inputType === "insertText" && !/^\d+$/.test(e.data)) {
                        input.value = "";
                        return;
                    }

                    if (input.value.length === 1 && index < inputs.length - 1) {
                        inputs[index + 1].focus();
                    }

                    // Clear error states as user types
                    input.classList.remove('is-invalid');
                    if (Array.from(inputs).every(i => i.value.length === 1)) {
                        errorMsg.style.display = 'none';
                    }
                });

                input.addEventListener('keydown', (e) => {
                    if (e.key === 'Backspace' && !input.value && index > 0) {
                        inputs[index - 1].focus();
                    }
                });
            });

            // 2. Form Submission Validation
            form.addEventListener('submit', (event) => {
                const inputs = document.querySelectorAll('.otp-input');
                const hiddenInput = document.getElementById('full-code');

                let combinedCode = "";
                inputs.forEach(input => {
                    combinedCode += input.value;
                });

                hiddenInput.value = combinedCode;

                const allFilled = combinedCode.length === 6;

                if (!allFilled) {
                    event.preventDefault();
                    event.stopPropagation();

                    errorMsg.style.display = 'block';

                    inputs.forEach(input => {
                        if (input.value.length === 0) {
                            input.classList.add('is-invalid');
                        }
                    });
                } else {
                    errorMsg.style.display = 'none';
                }

                form.classList.add('was-validated');
            }, false);
        })()
    </script>
</body>

</html>