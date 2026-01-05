<?php
/**
 * API kiểm tra trạng thái thanh toán của đơn hàng
 */
session_start();
header('Content-Type: application/json');

require_once '../config/database.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if ($order_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid order ID']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->connect();
    
    // Chỉ cho phép xem đơn hàng của chính mình
    $stmt = $conn->prepare("SELECT id, order_number, payment_status, payment_method, status FROM orders WHERE id = ? AND customer_id = ?");
    $stmt->execute([$order_id, $_SESSION['customer_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        echo json_encode(['success' => false, 'error' => 'Order not found']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'order_id' => $order['id'],
        'order_number' => $order['order_number'],
        'payment_status' => $order['payment_status'] ?? 'pending',
        'payment_method' => $order['payment_method'],
        'status' => $order['status']
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
