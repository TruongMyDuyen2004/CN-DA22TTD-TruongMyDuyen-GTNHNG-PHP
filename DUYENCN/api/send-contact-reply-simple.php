<?php
session_start();
header('Content-Type: application/json');

// Kiểm tra quyền admin
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../config/database.php';
require_once '../config/mail.php';

try {
    $db = new Database();
    $conn = $db->connect();
    
    // Lấy dữ liệu từ request
    $data = json_decode(file_get_contents('php://input'), true);
    
    $contact_id = $data['contact_id'] ?? 0;
    $reply_message = trim($data['reply_message'] ?? '');
    $send_email = $data['send_email'] ?? true;
    
    // Validate
    if (!$contact_id || empty($reply_message)) {
        throw new Exception('Vui lòng nhập đầy đủ thông tin');
    }
    
    // Lấy thông tin liên hệ gốc
    $stmt = $conn->prepare("SELECT * FROM contacts WHERE id = ?");
    $stmt->execute([$contact_id]);
    $contact = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$contact) {
        throw new Exception('Không tìm thấy liên hệ');
    }
    
    // Tạo record mới cho tin nhắn admin (thay vì update record cũ)
    try {
        $stmt = $conn->prepare("
            INSERT INTO contacts (name, email, phone, message, status, is_admin_message, created_at)
            VALUES (?, ?, ?, ?, 'replied', 1, NOW())
        ");
        $stmt->execute([
            'Admin',
            $contact['email'],
            $contact['phone'],
            $reply_message
        ]);
        
        // Cập nhật trạng thái tin nhắn gốc
        $stmt = $conn->prepare("UPDATE contacts SET status = 'replied' WHERE id = ?");
        $stmt->execute([$contact_id]);
        
    } catch (PDOException $e) {
        // Fallback: Nếu không có cột is_admin_message, update theo cách cũ
        try {
            $stmt = $conn->prepare("
                UPDATE contacts 
                SET status = 'replied',
                    admin_reply = ?,
                    replied_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$reply_message, $contact_id]);
        } catch (PDOException $e2) {
            throw new Exception('Không thể gửi phản hồi: ' . $e2->getMessage());
        }
    }
    
    // Gửi email nếu được yêu cầu
    $email_sent = false;
    $email_error = '';
    
    if ($send_email && !empty($contact['email'])) {
        // Tạo nội dung email đẹp
        $htmlBody = createContactReplyEmail(
            $contact['name'],
            $contact['message'],
            $reply_message
        );
        
        $subject = "Phản hồi từ Ngon Gallery - Tin nhắn #" . $contact_id;
        
        // Gửi email
        $emailResult = sendEmail($contact['email'], $subject, $htmlBody);
        $email_sent = $emailResult['success'];
        $email_error = $emailResult['message'] ?? '';
    }
    
    $response = [
        'success' => true,
        'message' => 'Đã gửi phản hồi thành công',
        'email_sent' => $email_sent
    ];
    
    if (!$email_sent && $send_email) {
        $response['email_note'] = $email_error ?: 'Email chưa được gửi. Kiểm tra cấu hình SMTP trong config/mail.php';
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
