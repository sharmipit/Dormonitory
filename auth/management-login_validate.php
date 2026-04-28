<?php

session_start();
require '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $_SESSION['error'] = "Please fill in all fields.";
        header('Location: login-management-access.php');
        exit;
    }

    // Find admin by email
    $stmt = $pdo->prepare("SELECT * FROM admin WHERE email = ?");
    $stmt->execute([$email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin) {
        $_SESSION['error'] = "Email doesn't exist.";
        header('Location: login-management-access.php');
        exit;
    }

    // Verify password against 'pass' column
    if (password_verify($password, $admin['pass'])) {
        $_SESSION['loggedin'] = true;
        $_SESSION['admin_id'] = $admin['admin_id'];
        $_SESSION['email'] = $admin['email'];
        $_SESSION['firstname'] = $admin['first_name'];

        header('Location: ../admin/dashboard.php');
        exit;
    } else {
        $_SESSION['error'] = "Invalid email or password.";
        header('Location: login-management-access.php');
        exit;
    }
}
?>