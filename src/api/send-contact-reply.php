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
    
    // Lưu phản hồi vào bảng contact_replies (nếu bảng tồn tại)
    try {
        $stmt = $conn->prepare("
            INSERT INTO contact_replies (contact_id, admin_id, reply_message)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$contact_id, $_SESSION['admin_id'], $reply_message]);
    } catch (PDOException $e) {
        // Bỏ qua nếu bảng chưa tồn tại
        error_log("Warning: contact_replies table not found - " . $e->getMessage());
    }
    
    // Cập nhật trạng thái liên hệ
    $stmt = $conn->prepare("
        UPDATE contacts 
        SET status = 'replied',
            admin_reply = ?,
            replied_at = NOW(),
            replied_by = ?
        WHERE id = ?
    ");
    $stmt->execute([$reply_message, $_SESSION['admin_id'], $contact_id]);
    
    // Gửi email nếu được yêu cầu
    $email_sent = false;
    if ($send_email && !empty($contact['email'])) {
        $to = $contact['email'];
        $subject = "Phản hồi từ Ngon Gallery - Re: " . substr($contact['message'], 0, 50);
        
        $message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #FF6B35 0%, #FF8C42 100%); color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
                .original-message { background: white; padding: 15px; margin: 20px 0; border-left: 4px solid #FF6B35; }
                .reply { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; }
                .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🍜 Ngon Gallery</h1>
                    <p>Cảm ơn bạn đã liên hệ với chúng tôi</p>
                </div>
                <div class='content'>
                    <p>Xin chào <strong>" . htmlspecialchars($contact['name']) . "</strong>,</p>
                    
                    <p>Cảm ơn bạn đã liên hệ với Ngon Gallery. Chúng tôi đã nhận được tin nhắn của bạn và xin gửi phản hồi như sau:</p>
                    
                    <div class='reply'>
                        <h3 style='color: #FF6B35; margin-top: 0;'>📧 Phản hồi từ chúng tôi:</h3>
                        <p>" . nl2br(htmlspecialchars($reply_message)) . "</p>
                    </div>
                    
                    <div class='original-message'>
                        <h4 style='margin-top: 0;'>Tin nhắn gốc của bạn:</h4>
                        <p>" . nl2br(htmlspecialchars($contact['message'])) . "</p>
                        <p style='color: #666; font-size: 12px; margin-bottom: 0;'>
                            Gửi lúc: " . date('d/m/Y H:i', strtotime($contact['created_at'])) . "
                        </p>
                    </div>
                    
                    <p>Nếu bạn có thêm câu hỏi, vui lòng liên hệ lại với chúng tôi.</p>
                    
                    <p style='margin-top: 30px;'>
                        <strong>Trân trọng,</strong><br>
                        Đội ngũ Ngon Gallery
                    </p>
                </div>
                <div class='footer'>
                    <p>Email này được gửi tự động từ hệ thống Ngon Gallery</p>
                    <p>📍 Địa chỉ: 126 Nguyễn Thiện Thành, Phường 5, TP. Trà Vinh | 📞 Hotline: 1900-xxxx</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: Ngon Gallery <noreply@ngongallery.com>" . "\r\n";
        $headers .= "Reply-To: contact@ngongallery.com" . "\r\n";
        
        $email_sent = mail($to, $subject, $message, $headers);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Đã gửi phản hồi thành công',
        'email_sent' => $email_sent
    ]);
    
} catch (Exception $e) {
    // Log lỗi chi tiết
    error_log("Contact Reply Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_detail' => $e->getTraceAsString() // Chỉ để debug
    ]);
}
