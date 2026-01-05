<?php
/**
 * API kiểm tra tin nhắn mới (polling)
 */
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

if (!isset($_SESSION['customer_id']) || empty($_SESSION['customer_email'])) {
    echo json_encode(['success' => false, 'count' => 0]);
    exit;
}

try {
    $db = new Database();
    $conn = $db->connect();
    
    // Đếm số phản hồi chưa đọc
    $stmt = $conn->prepare("
        SELECT COUNT(*) as cnt 
        FROM contacts 
        WHERE email = ? 
        AND status = 'replied' 
        AND (user_read_at IS NULL OR user_read_at = '')
    ");
    $stmt->execute([$_SESSION['customer_email']]);
    $unread = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0;
    
    // Lấy tin nhắn mới nhất nếu có (kèm ID để tracking)
    $latest_message = null;
    if ($unread > 0) {
        $stmt = $conn->prepare("
            SELECT id, admin_reply, replied_at 
            FROM contacts 
            WHERE email = ? 
            AND status = 'replied' 
            AND (user_read_at IS NULL OR user_read_at = '')
            ORDER BY replied_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$_SESSION['customer_email']]);
        $latest_message = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    echo json_encode([
        'success' => true,
        'count' => (int)$unread,
        'latest' => $latest_message
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'count' => 0, 'error' => $e->getMessage()]);
}
?>
