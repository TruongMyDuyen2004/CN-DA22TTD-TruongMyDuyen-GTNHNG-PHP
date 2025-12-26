<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['customer_id']) || empty($_SESSION['customer_email'])) {
    echo json_encode(['success' => false]);
    exit;
}

try {
    require_once '../config/database.php';
    $db = new Database();
    $conn = $db->connect();
    
    $stmt = $conn->prepare("UPDATE contacts SET user_read_at = NOW() WHERE email = ? AND status = 'replied' AND (user_read_at IS NULL OR user_read_at = '')");
    $stmt->execute([$_SESSION['customer_email']]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false]);
}
