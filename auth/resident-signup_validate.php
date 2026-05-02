<?php

session_start();

require '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $contact_number = trim($_POST['contactNumber']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    //checks if email exists
    $stmt = $pdo->prepare("SELECT * FROM resident WHERE email = ?");
    $stmt->execute([$email]);

    if ($stmt->rowCount() > 0) {
        $_SESSION['error'] = "Email already exists.";
        header('Location: signup-resident-portal.php');
        exit();
    }

    //checks if contact number exists
    $stmt = $pdo->prepare("SELECT * FROM resident WHERE contact_number = ?");
    $stmt->execute([$contact_number]);
    if ($stmt->rowCount() > 0) {
        $_SESSION['error'] = "Contact number already in use.";
        header('Location: signup-resident-portal.php');
        exit();
    }

    //inserts new resident
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO resident (first_name, last_name, contact_number, email, pass) VALUES (?, ?, ?, ?, ?)");

    if ($stmt->execute([$first_name, $last_name, $contact_number, $email, $hashed_password])) {
        $_SESSION['success'] = "Your account has been created. You can now Login.";
        header('Location: signup-resident-portal.php');
        exit();
    } else {
        echo "There was an error creating your account.";
        exit();
    }

}