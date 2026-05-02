<?php
session_start();
include('../config/db.php');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$resident_id = $_SESSION['id'];
$visitor_name = trim($_POST['visitor_name']);
$contact_number = trim($_POST['contact_number']);

if (empty($visitor_name) || empty($contact_number)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit();
}

// Generate unique QR token
$qr_token = bin2hex(random_bytes(32));
$visit_date = date('Y-m-d');

// Save to visitor_log
$stmt = $pdo->prepare("INSERT INTO visitor_log (resident_id, visitor_name, contact_number, qr_token, visit_date) VALUES (?, ?, ?, ?, ?)");
$stmt->execute([$resident_id, $visitor_name, $contact_number, $qr_token, $visit_date]);

echo json_encode([
    'success' => true,
    'visitor_name' => $visitor_name,
    'qr_token' => $qr_token,
    'visit_date' => $visit_date,
    'qr_url' => "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=" . urlencode($qr_token)
]);