<?php
require_once __DIR__ . '/../vendor/autoload.php';

session_start();
require_once __DIR__ . '/../config/db.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$client = new Google\Client();
$client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
$client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
$client->setRedirectUri($_ENV['GOOGLE_REDIRECT']);

if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

    if (!isset($token['error'])) {
        $client->setAccessToken($token['access_token']);

        $oauth2 = new Google\Service\Oauth2($client);
        $userInfo = $oauth2->userinfo->get();

        $email = $userInfo->email;
        $name = $userInfo->name;

        // Check if resident exists with this email
        $stmt = $pdo->prepare("SELECT * FROM resident WHERE email = ?");
        $stmt->execute([$email]);
        $resident = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($resident) {
            // Existing resident — log them in
            $_SESSION['loggedin'] = true;
            $_SESSION['id'] = $resident['resident_id'];
            $_SESSION['email'] = $resident['email'];
            $_SESSION['firstname'] = $resident['first_name'];
            header('Location: ../resident/home.php');
            exit();
        } else {
            // No account found
            $_SESSION['error'] = "No resident account found for this Google email. Please sign up first.";
            header('Location: ../auth/login-resident-portal.php');
            header('Location: ../auth/login-management-access.php');
            exit();
        }

    } else {
        $_SESSION['error'] = 'Google login failed. Please try again.';
        header('Location: ../auth/login-resident-portal.php');
        header('Location: ../auth/login-management-access.php');
        exit();
    }
} else {
    $_SESSION['error'] = 'Invalid login attempt.';
    header('Location: ../auth/login-resident-portal.php');
    header('Location: ../auth/login-management-access.php');
    exit();
}