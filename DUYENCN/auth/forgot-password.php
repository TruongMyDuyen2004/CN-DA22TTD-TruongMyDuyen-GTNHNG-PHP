<?php
session_start();
require_once '../config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = 'Vui lòng nhập email';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email không hợp lệ';
    } else {
        $db = new Database();
        $conn = $db->connect();
        
        // Kiểm tra email có tồn tại không
        $stmt = $conn->prepare("SELECT id, full_name FROM customers WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Tạo token reset password
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Xóa token cũ nếu có
            $stmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
            $stmt->execute([$email]);
            
            // Lưu token mới
            $stmt = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
            $stmt->execute([$email, $token, $expires]);
            
            // Lưu vào session để hiển thị link (trong thực tế sẽ gửi email)
            $_SESSION['reset_token'] = $token;
            $_SESSION['reset_email'] = $email;
            
            $success = 'Đã tạo link đặt lại mật khẩu. Vui lòng kiểm tra bên dưới.';
        } else {
            // Vẫn hiển thị thông báo thành công để bảo mật
            $success = 'Nếu email tồn tại trong hệ thống, bạn sẽ nhận được hướng dẫn đặt lại mật khẩu.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quên mật khẩu - Ngon Gallery</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/auth-dark.css?v=8">
</head>
<body>
    <div class="auth-wrapper">
        <!-- Logo -->
        <div class="auth-logo">
            <a href="../index.php">
                <img src="../assets/images/logo.jpg" alt="Ngon Gallery" onerror="this.style.display='none'">
                <div class="auth-logo-text">
                    <span class="auth-logo-name">Ngon Gallery</span>
                    <span class="auth-logo-sub">Vietnamese Cuisine</span>
                </div>
            </a>
        </div>

        <!-- Auth Card -->
        <div class="auth-card">
            <div class="auth-header">
                <h1>Quên Mật Khẩu</h1>
                <p>Nhập email để lấy lại mật khẩu</p>
            </div>

            <?php if ($error): ?>
            <div class="auth-alert error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="auth-alert success">
                <i class="fas fa-check-circle"></i>
                <?php echo $success; ?>
            </div>
            
            <?php if (isset($_SESSION['reset_token'])): 
                $resetToken = $_SESSION['reset_token'];
                // Không xóa session ngay - để user có thể copy link
            ?>
            <div class="reset-link-box">
                <p><strong>Link đặt lại mật khẩu:</strong></p>
                <a href="reset-password.php?token=<?php echo $resetToken; ?>" class="reset-link" target="_blank">
                    <i class="fas fa-key"></i> Nhấn vào đây để đặt lại mật khẩu
                </a>
                <p class="reset-note"><i class="fas fa-clock"></i> Link có hiệu lực trong 1 giờ</p>
            </div>
            <?php endif; ?>
            <?php endif; ?>

            <?php if (!$success): ?>
            <form method="POST" action="" class="auth-form">
                <div class="form-field">
                    <label>Email đăng ký <span class="required">*</span></label>
                    <div class="input-group">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" placeholder="email@example.com" required 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fas fa-paper-plane"></i>
                    Gửi yêu cầu
                </button>
            </form>
            <?php endif; ?>

            <div class="auth-footer">
                <p>Đã nhớ mật khẩu? <a href="login.php">Đăng nhập</a></p>
                <a href="../index.php" class="back-home">
                    <i class="fas fa-arrow-left"></i>
                    Về trang chủ
                </a>
            </div>
        </div>
    </div>

    <style>
    .reset-link-box {
        background: linear-gradient(135deg, #f0fdf4, #dcfce7);
        border: 2px solid #22c55e;
        border-radius: 16px;
        padding: 1.5rem;
        margin: 1.5rem 0;
        text-align: center;
    }
    
    .reset-link-box p {
        color: #166534;
        margin-bottom: 1rem;
    }
    
    .reset-link {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        color: white;
        padding: 0.8rem 1.5rem;
        border-radius: 12px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(34, 197, 94, 0.3);
    }
    
    .reset-link:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(34, 197, 94, 0.4);
    }
    
    .reset-note {
        margin-top: 1rem !important;
        font-size: 0.85rem;
        color: #6b7280 !important;
    }
    </style>
</body>
</html>
