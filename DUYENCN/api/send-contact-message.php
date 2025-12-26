<?php
session_start();
header('Content-Type: application/json');

// Check login
if (!isset($_SESSION['customer_id']) || empty($_SESSION['customer_email'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit;
}

// Get message
$data = json_decode(file_get_contents('php://input'), true);
$message = trim($data['message'] ?? '');

if (empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Tin nhắn không được để trống']);
    exit;
}

try {
    require_once '../config/database.php';
    $db = new Database();
    $conn = $db->connect();
    
    $stmt = $conn->prepare("INSERT INTO contacts (name, email, message, status, created_at) VALUES (?, ?, ?, 'new', NOW())");
    $stmt->execute([
        $_SESSION['customer_name'] ?? 'Khách hàng',
        $_SESSION['customer_email'],
        htmlspecialchars($message)
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Đã gửi tin nhắn']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
}
