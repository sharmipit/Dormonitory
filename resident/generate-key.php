<?php
session_start();
include('../config/db.php');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    exit();
}

$resident_id = $_SESSION['id'];

// Revoke any existing active keys
$revoke = $pdo->prepare("UPDATE resident_qr SET status = 'Revoked' WHERE resident_id = ? AND status = 'Active'");
$revoke->execute([$resident_id]);

// Generate a unique token
$token = bin2hex(random_bytes(32));

// Save to database — let MySQL handle the time so timezone doesn't cause issues
$stmt = $pdo->prepare("
    INSERT INTO resident_qr (resident_id, qr_code, status, expires_at, created_at) 
    VALUES (?, ?, 'Active', DATE_ADD(NOW(), INTERVAL 24 HOUR), NOW())");
$stmt->execute([$resident_id, $token]);

// Return JSON response
echo json_encode([
    'success' => true,
    'token' => $token,
    'qr_url' => "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=" . urlencode($token)
]);