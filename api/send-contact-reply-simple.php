<?php
session_start();
header('Content-Type: application/json');

// Kiểm tra quyền admin
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../config/database.php';

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
    
    // Lấy thông tin liên hệ
    $stmt = $conn->prepare("SELECT * FROM contacts WHERE id = ?");
    $stmt->execute([$contact_id]);
    $contact = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$contact) {
        throw new Exception('Không tìm thấy liên hệ');
    }
    
    // Cập nhật trạng thái liên hệ - CHỈ CẬP NHẬT CÁC CỘT CƠ BẢN
    try {
        // Thử cập nhật với tất cả các cột
        $stmt = $conn->prepare("
            UPDATE contacts 
            SET status = 'replied',
                admin_reply = ?,
                replied_at = NOW(),
                replied_by = ?
            WHERE id = ?
        ");
        $stmt->execute([$reply_message, $_SESSION['admin_id'], $contact_id]);
    } catch (PDOException $e) {
        // Nếu lỗi, thử chỉ cập nhật status và admin_reply
        try {
            $stmt = $conn->prepare("
                UPDATE contacts 
                SET status = 'replied',
                    admin_reply = ?
                WHERE id = ?
            ");
            $stmt->execute([$reply_message, $contact_id]);
        } catch (PDOException $e2) {
            throw new Exception('Không thể cập nhật phản hồi: ' . $e2->getMessage());
        }
    }
    
    // Gửi email nếu được yêu cầu
    $email_sent = false;
    if ($send_email && !empty($contact['email'])) {
        $to = $contact['email'];
        $subject = "Phản hồi từ Ngon Gallery";
        
        $message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #FF6B35; color: white; padding: 20px; text-align: center; }
                .content { background: #f9f9f9; padding: 30px; }
                .reply { background: white; padding: 20px; margin: 20px 0; border-left: 4px solid #FF6B35; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Ngon Gallery</h1>
                    <p>Phản hồi từ chúng tôi</p>
                </div>
                <div class='content'>
                    <p>Xin chào <strong>" . htmlspecialchars($contact['name']) . "</strong>,</p>
                    <p>Cảm ơn bạn đã liên hệ với Ngon Gallery.</p>
                    <div class='reply'>
                        <h3>Phản hồi:</h3>
                        <p>" . nl2br(htmlspecialchars($reply_message)) . "</p>
                    </div>
                    <p>Trân trọng,<br>Đội ngũ Ngon Gallery</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: Ngon Gallery <noreply@ngongallery.com>" . "\r\n";
        
        $email_sent = @mail($to, $subject, $message, $headers);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Đã gửi phản hồi thành công',
        'email_sent' => $email_sent
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
