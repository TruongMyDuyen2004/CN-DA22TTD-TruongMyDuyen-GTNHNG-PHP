<?php
/**
 * Cấu hình gửi email qua SMTP
 * Hỗ trợ Gmail, Outlook, hoặc SMTP server khác
 */

// Cấu hình SMTP
define('MAIL_HOST', 'smtp.gmail.com');          // SMTP server
define('MAIL_PORT', 587);                        // Port (587 cho TLS, 465 cho SSL)
define('MAIL_USERNAME', 'your-email@gmail.com'); // Email của bạn
define('MAIL_PASSWORD', 'your-app-password');    // App Password (không phải mật khẩu Gmail)
define('MAIL_FROM_EMAIL', 'your-email@gmail.com');
define('MAIL_FROM_NAME', 'Ngon Gallery');
define('MAIL_ENCRYPTION', 'tls');                // tls hoặc ssl

/**
 * Hướng dẫn lấy App Password cho Gmail:
 * 1. Vào https://myaccount.google.com/security
 * 2. Bật "Xác minh 2 bước" (2-Step Verification)
 * 3. Vào "Mật khẩu ứng dụng" (App passwords)
 * 4. Chọn "Mail" và "Windows Computer"
 * 5. Copy mật khẩu 16 ký tự được tạo ra
 * 6. Dán vào MAIL_PASSWORD ở trên
 */

/**
 * Hàm gửi email đơn giản sử dụng PHPMailer
 */
function sendEmail($to, $subject, $htmlBody, $textBody = '') {
    // Kiểm tra PHPMailer
    $phpmailerPath = __DIR__ . '/../vendor/PHPMailer/src/PHPMailer.php';
    
    if (file_exists($phpmailerPath)) {
        // Sử dụng PHPMailer
        require_once __DIR__ . '/../vendor/PHPMailer/src/Exception.php';
        require_once __DIR__ . '/../vendor/PHPMailer/src/PHPMailer.php';
        require_once __DIR__ . '/../vendor/PHPMailer/src/SMTP.php';
        
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        try {
            // Cấu hình SMTP
            $mail->isSMTP();
            $mail->Host = MAIL_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = MAIL_USERNAME;
            $mail->Password = MAIL_PASSWORD;
            $mail->SMTPSecure = MAIL_ENCRYPTION;
            $mail->Port = MAIL_PORT;
            $mail->CharSet = 'UTF-8';
            
            // Người gửi và người nhận
            $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
            $mail->addAddress($to);
            
            // Nội dung
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->AltBody = $textBody ?: strip_tags($htmlBody);
            
            $mail->send();
            return ['success' => true, 'message' => 'Email đã được gửi thành công'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Lỗi gửi email: ' . $mail->ErrorInfo];
        }
    } else {
        // Fallback: sử dụng mail() function
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM_EMAIL . ">\r\n";
        
        if (@mail($to, $subject, $htmlBody, $headers)) {
            return ['success' => true, 'message' => 'Email đã được gửi'];
        } else {
            return ['success' => false, 'message' => 'Không thể gửi email. Vui lòng cài đặt PHPMailer.'];
        }
    }
}

/**
 * Tạo template email phản hồi liên hệ
 */
function createContactReplyEmail($customerName, $originalMessage, $replyMessage) {
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 0 auto; }
            .header { background: linear-gradient(135deg, #d4a574 0%, #c89456 100%); color: white; padding: 30px; text-align: center; }
            .header h1 { margin: 0; font-size: 28px; }
            .header p { margin: 10px 0 0; opacity: 0.9; }
            .content { background: #ffffff; padding: 30px; }
            .greeting { font-size: 18px; margin-bottom: 20px; }
            .original-message { background: #f5f5f5; padding: 20px; border-left: 4px solid #ddd; margin: 20px 0; border-radius: 4px; }
            .original-message h4 { margin: 0 0 10px; color: #666; font-size: 14px; }
            .reply-message { background: #e8f5e9; padding: 20px; border-left: 4px solid #4CAF50; margin: 20px 0; border-radius: 4px; }
            .reply-message h4 { margin: 0 0 10px; color: #2e7d32; font-size: 14px; }
            .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #666; font-size: 14px; }
            .footer a { color: #d4a574; text-decoration: none; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🍽️ Ngon Gallery</h1>
                <p>Phản hồi từ chúng tôi</p>
            </div>
            <div class="content">
                <p class="greeting">Xin chào <strong>' . htmlspecialchars($customerName) . '</strong>,</p>
                <p>Cảm ơn bạn đã liên hệ với Ngon Gallery. Chúng tôi đã nhận được tin nhắn của bạn và xin gửi phản hồi như sau:</p>
                
                <div class="original-message">
                    <h4>📩 Tin nhắn của bạn:</h4>
                    <p style="margin: 0;">' . nl2br(htmlspecialchars($originalMessage)) . '</p>
                </div>
                
                <div class="reply-message">
                    <h4>✅ Phản hồi từ Ngon Gallery:</h4>
                    <p style="margin: 0;">' . nl2br(htmlspecialchars($replyMessage)) . '</p>
                </div>
                
                <p>Nếu bạn có thêm câu hỏi, đừng ngần ngại liên hệ lại với chúng tôi.</p>
                <p>Trân trọng,<br><strong>Đội ngũ Ngon Gallery</strong></p>
            </div>
            <div class="footer">
                <p>📍 126 Nguyễn Thiện Thành, Phường 5, TP. Trà Vinh</p>
                <p>📞 0384848127 | ✉️ info@ngongallery.vn</p>
                <p><a href="#">Xem phản hồi trên website</a></p>
            </div>
        </div>
    </body>
    </html>';
}
