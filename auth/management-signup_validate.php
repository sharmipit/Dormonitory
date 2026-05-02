<?php

session_start();

require '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    //checks if email exists
    $stmt = $pdo->prepare("SELECT * FROM admin WHERE email = ?");
    $stmt->execute([$email]);

    if ($stmt->rowCount() > 0) {
        $_SESSION['error'] = "Email already exists.";
        header('Location: signup-management-access.php');
        exit();
    }

    //inserts new admin
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO admin (first_name, last_name, email, pass) VALUES (?, ?, ?, ?)");

    if ($stmt->execute([$first_name, $last_name, $email, $hashed_password])) {
        $_SESSION['success'] = "Your account has been created. You can now Login.";
        header('Location: signup-management-access.php');
        exit();
    } else {
        echo "There was an error creating your account.";
        exit();
    }

}