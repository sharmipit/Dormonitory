<?php

session_start();
require '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email    = trim($_POST['email']);
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
        $_SESSION['loggedin']  = true;
        $_SESSION['id']        = $resident['resident_id'];
        $_SESSION['email']     = $resident['email'];
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