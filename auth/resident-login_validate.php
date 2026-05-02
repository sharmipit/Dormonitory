<?php

session_start();

require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

require '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    //reCAPTCHA verification
    $recaptchaSecret = $_ENV['RECAPTCHA_SECRET_KEY'];
    $recaptchaResponse = $_POST['g-recaptcha-response'];

    $verify = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$recaptchaSecret}&response={$recaptchaResponse}");
    $captchaSuccess = json_decode($verify);

    if (!$captchaSuccess->success) {
        $_SESSION['error'] = "Captcha Verification Failed. Try Again.";
        header('Location: login-resident-portal.php');
        exit;
    }

    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $_SESSION['error'] = "Please fill in all fields.";
        header('Location: login-resident-portal.php');
        exit;
    }

    // Find resident by email
    $stmt = $pdo->prepare("SELECT * FROM resident WHERE email = ?");
    $stmt->execute([$email]);
    $resident = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$resident) {
        $_SESSION['error'] = "Email doesn't exist.";
        header('Location: login-resident-portal.php');
        exit;
    }

    // Verify password against 'pass' column
    if (password_verify($password, $resident['pass'])) {
        $_SESSION['loggedin'] = true;
        $_SESSION['id'] = $resident['resident_id'];
        $_SESSION['email'] = $resident['email'];
        $_SESSION['firstname'] = $resident['first_name'];

        header('Location: ../resident/home.php');
        exit;
    } else {
        $_SESSION['error'] = "Invalid email or password.";
        header('Location: login-resident-portal.php');
        exit;
    }
}
?>