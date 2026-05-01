<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "dormonitory_db";
try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET time_zone = '+08:00'");
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}
?>