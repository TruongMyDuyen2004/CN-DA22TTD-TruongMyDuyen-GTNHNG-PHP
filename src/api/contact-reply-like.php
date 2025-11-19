<?php
session_start();
header('Content-Type: application/json');

require_once '../config/database.php';

try {
    $db = new Database();
    $conn = $db->connect();
    
    $data = json_decode(file_get_contents('php://input'), true);
    $contact_id = intval($data['contact_id'] ?? 0);
    $action = $data['action'] ?? 'toggle'; // toggle, like, unlike
    
    if (!$contact_id) {
        throw new Exception('Invalid contact ID');
    }
    
    // Xác định người dùng (đã đăng nhập hoặc chưa)
    $customer_id = $_SESSION['customer_id'] ?? null;
    $customer_email = null;
    
    if (!$customer_id) {
        // Nếu chưa đăng nhập, lấy email từ request
        $customer_email = $data['email'] ?? null;
        if (!$customer_email || !filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Please login or provide valid email');
        }
    }
    
    // Kiểm tra đã like chưa
    if ($customer_id) {
        $stmt = $conn->prepare("SELECT id FROM contact_reply_likes WHERE contact_id = ? AND customer_id = ?");
        $stmt->execute([$contact_id, $customer_id]);
    } else {
        $stmt = $conn->prepare("SELECT id FROM contact_reply_likes WHERE contact_id = ? AND customer_email = ?");
        $stmt->execute([$contact_id, $customer_email]);
    }
    
    $already_liked = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($action === 'toggle' || ($action === 'unlike' && $already_liked) || ($action === 'like' && !$already_liked)) {
        if ($already_liked) {
            // Unlike
            if ($customer_id) {
                $stmt = $conn->prepare("DELETE FROM contact_reply_likes WHERE contact_id = ? AND customer_id = ?");
                $stmt->execute([$contact_id, $customer_id]);
            } else {
                $stmt = $conn->prepare("DELETE FROM contact_reply_likes WHERE contact_id = ? AND customer_email = ?");
                $stmt->execute([$contact_id, $customer_email]);
            }
            
            // Giảm likes_count
            $conn->exec("UPDATE contacts SET likes_count = GREATEST(0, likes_count - 1) WHERE id = $contact_id");
            
            $liked = false;
        } else {
            // Like
            $stmt = $conn->prepare("INSERT INTO contact_reply_likes (contact_id, customer_id, customer_email) VALUES (?, ?, ?)");
            $stmt->execute([$contact_id, $customer_id, $customer_email]);
            
            // Tăng likes_count
            $conn->exec("UPDATE contacts SET likes_count = likes_count + 1 WHERE id = $contact_id");
            
            $liked = true;
        }
    }
    
    // Lấy số lượng likes hiện tại
    $stmt = $conn->prepare("SELECT likes_count FROM contacts WHERE id = ?");
    $stmt->execute([$contact_id]);
    $contact = $stmt->fetch(PDO::FETCH_ASSOC);
    $likes_count = $contact['likes_count'] ?? 0;
    
    echo json_encode([
        'success' => true,
        'liked' => $liked ?? !$already_liked,
        'likes_count' => $likes_count
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
