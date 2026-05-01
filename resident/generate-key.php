<?php
session_start();
include('../config/db.php');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    exit();
}

$resident_id = $_SESSION['id'];

// Revoke any existing active keys for this resident
$revoke = $pdo->prepare("UPDATE resident_qr SET status = 'Revoked' WHERE resident_id = ? AND status = 'Active'");
$revoke->execute([$resident_id]);

// Generate a unique token
$token = bin2hex(random_bytes(32));

// Set expiry to 1 hour from now
$expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

// Save to database
$stmt = $pdo->prepare("INSERT INTO resident_qr (resident_id, qr_code, status, expires_at) VALUES (?, ?, 'Active', ?)");
$stmt->execute([$resident_id, $token, $expires_at]);

// Return JSON response to JavaScript
echo json_encode([
    'success'    => true,
    'token'      => $token,
    'expires_at' => $expires_at,
    'qr_url'     => "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=" . urlencode($token)
]);