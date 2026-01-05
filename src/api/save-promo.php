<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit;
}

require_once '../config/database.php';
$db = new Database();
$conn = $db->connect();

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';
$code = $data['code'] ?? '';

if (empty($code)) {
    echo json_encode(['success' => false, 'message' => 'Mã không hợp lệ']);
    exit;
}

// Tạo bảng nếu chưa có
$conn->exec("CREATE TABLE IF NOT EXISTS saved_promotions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    promo_code VARCHAR(50) NOT NULL,
    saved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_save (customer_id, promo_code)
)");

try {
    if ($action === 'save') {
        $stmt = $conn->prepare("INSERT IGNORE INTO saved_promotions (customer_id, promo_code) VALUES (?, ?)");
        $stmt->execute([$_SESSION['customer_id'], $code]);
        echo json_encode(['success' => true, 'message' => 'Đã lưu mã']);
    } elseif ($action === 'unsave') {
        $stmt = $conn->prepare("DELETE FROM saved_promotions WHERE customer_id = ? AND promo_code = ?");
        $stmt->execute([$_SESSION['customer_id'], $code]);
        echo json_encode(['success' => true, 'message' => 'Đã bỏ lưu']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
}
