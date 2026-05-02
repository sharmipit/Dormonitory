<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/../vendor/autoload.php';

session_start();
require '../config/db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];

    $stmt = $pdo->prepare("SELECT * FROM resident WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $table = 'resident';

    if (!$user) {
        $stmt = $pdo->prepare("SELECT * FROM admin WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $table = 'admin';
    }

    if ($user) {
        $reset_code = rand(100000, 999999);

        $update = $pdo->prepare("UPDATE $table SET reset_code = ? WHERE email = ?");
        $update->execute([$reset_code, $email]);

        $_SESSION['email'] = $email;
        $_SESSION['table'] = $table;

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'regalado.erin2006@gmail.com';
            $mail->Password = 'gxkl gsvp crtb dkhv';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('regalado.erin2006@gmail.com', 'Dormonitory');
            $mail->addAddress($email, 'CLIENT');

            $mail->isHTML(true);
            $mail->Subject = "Password Reset Code";

            $mail->Body = "<p>Hello, This is your Password Reset Code: {$reset_code}</p>";
            $mail->AltBody = "Hello, Use the Code below to Reset your Password \n\n{$reset_code}\n\n";
            $mail->send();

            $_SESSION['email_sent'] = true;

            $_SESSION['success'] = "A verification code has been sent to your email";
            header('Location: reset-confirmation.php'); //currently in html, pls change erin if done na
            exit();

        } catch (Exception $e) {
            $_SESSION['error'] = "Message could not be sent";
            header('Location: forgot-password.php');
            exit();
        }


    } else {
        $_SESSION['error'] = "No user found with that email";
        header('Location: forgot-password.php');
        exit();
    }
}

?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Forgot Password | Dormonitory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/auth-styles.css" />
</head>

<body>
    <main class="landing-container forgot-password-page">
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
                <a href="login.html" class="btn-go-back">
                    <i class="bi bi-arrow-left"></i> Go Back
                </a>
            </div>

            <div class="login-box">
                <h2 class="auth-title">Reset Password</h2>
                <p class="auth-subtitle mb-5">Enter your email and we'll send you a link to reset your password.</p>

                <?php
                if (isset($_SESSION['success'])) {
                    echo '<div class="alert alert-success text-center">' . $_SESSION['success'] . '</div>';
                    unset($_SESSION['success']);
                }

                if (isset($_SESSION['error'])) {
                    echo '<div class="alert alert-danger text-center">' . $_SESSION['error'] . '</div>';
                    unset($_SESSION['error']);
                }
                ?>

                <form action="forgot-password.php" method="POST" class="needs-validation" novalidate>
                    <div class="mb-5">
                        <label for="email" class="form-label">Email Address</label>

                        <div class="input-group-custom">
                            <i class="bi bi-envelope"></i>
                            <input type="email" class="form-control" id="email" name="email"
                                placeholder="username@gmail.com" required>
                        </div>

                        <div class="invalid-feedback">
                            Please enter a valid email address containing "@".
                        </div>
                    </div>

                    <button type="submit"
                        class="btn-auth-primary w-100 py-3 d-flex align-items-center justify-content-center gap-2">
                        <span>Send Reset Code</span>
                        <i class="bi bi-send"></i>
                    </button>
                </form>
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
    </script>

</body>

</html>