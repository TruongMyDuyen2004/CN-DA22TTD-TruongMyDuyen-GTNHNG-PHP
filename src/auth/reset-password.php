<?php
session_start();
require_once '../config/database.php';

$error = '';
$success = '';
$validToken = false;
$token = $_GET['token'] ?? '';

if (empty($token)) {
    $error = 'Link không hợp lệ';
} else {
    $db = new Database();
    $conn = $db->connect();
    
    // Kiểm tra bảng password_resets có tồn tại không
    try {
        $tableCheck = $conn->query("SHOW TABLES LIKE 'password_resets'");
        if ($tableCheck->rowCount() == 0) {
            $error = 'Bảng password_resets chưa được tạo. Vui lòng chạy setup-password-reset.php trước.';
        }
    } catch (PDOException $e) {
        $error = 'Lỗi database: ' . $e->getMessage();
    }
    
    if (empty($error)) {
        // Debug: Kiểm tra tất cả token trong bảng
        $allTokens = $conn->query("SELECT token, email, expires_at FROM password_resets")->fetchAll(PDO::FETCH_ASSOC);
        
        // Kiểm tra token có hợp lệ không
        $stmt = $conn->prepare("SELECT * FROM password_resets WHERE token = ?");
        $stmt->execute([$token]);
        $reset = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$reset) {
            // Token không tồn tại
            $error = 'Token không tồn tại trong database. ';
            if (count($allTokens) == 0) {
                $error .= 'Bảng password_resets đang trống.';
            } else {
                $error .= 'Có ' . count($allTokens) . ' token trong bảng.';
            }
        } elseif (strtotime($reset['expires_at']) < time()) {
            // Token đã hết hạn
            $error = 'Token đã hết hạn vào: ' . $reset['expires_at'];
        }
    }
    
    if (!empty($error)) {
        // Đã có lỗi từ trước
    } elseif ($reset) {
        $validToken = true;
        
        // Xử lý đặt lại mật khẩu
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            if (empty($email)) {
                $error = 'Vui lòng nhập email';
            } elseif ($email !== $reset['email']) {
                $error = 'Email không khớp với tài khoản yêu cầu đặt lại mật khẩu';
            } elseif (empty($password)) {
                $error = 'Vui lòng nhập mật khẩu mới';
            } elseif (strlen($password) < 6) {
                $error = 'Mật khẩu phải có ít nhất 6 ký tự';
            } elseif ($password !== $confirm_password) {
                $error = 'Mật khẩu xác nhận không khớp';
            } else {
                // Cập nhật mật khẩu
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE customers SET password = ? WHERE email = ?");
                
                if ($stmt->execute([$hashed, $reset['email']])) {
                    // Xóa token đã sử dụng
                    $stmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
                    $stmt->execute([$reset['email']]);
                    
                    $success = 'Đặt lại mật khẩu thành công! Bạn có thể đăng nhập ngay.';
                    $validToken = false;
                } else {
                    $error = 'Có lỗi xảy ra. Vui lòng thử lại.';
                }
            }
        }
    } else {
        $error = 'Link đã hết hạn hoặc không hợp lệ';
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt lại mật khẩu - Ngon Gallery</title>
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
                <h1>Đặt Lại Mật Khẩu</h1>
                <p>Nhập mật khẩu mới cho tài khoản</p>
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
            <div class="success-actions">
                <a href="login.php" class="btn-submit" style="text-decoration: none; display: inline-flex;">
                    <i class="fas fa-sign-in-alt"></i>
                    Đăng nhập ngay
                </a>
            </div>
            <?php endif; ?>

            <?php if ($validToken): ?>
            <form method="POST" action="" class="auth-form">
                <div class="form-field">
                    <label>Email đăng ký <span class="required">*</span></label>
                    <div class="input-group">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" placeholder="Nhập email đã đăng ký" required 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-field">
                    <label>Mật khẩu mới <span class="required">*</span></label>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" id="password" placeholder="Tối thiểu 6 ký tự" required minlength="6">
                        <button type="button" class="password-toggle" onclick="togglePassword('password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-field">
                    <label>Xác nhận mật khẩu <span class="required">*</span></label>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="confirm_password" id="confirm_password" placeholder="Nhập lại mật khẩu" required minlength="6">
                        <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fas fa-key"></i>
                    Đặt lại mật khẩu
                </button>
            </form>
            <?php endif; ?>

            <?php if (!$validToken && !$success): ?>
            <div class="expired-actions">
                <a href="forgot-password.php" class="btn-submit" style="text-decoration: none; display: inline-flex;">
                    <i class="fas fa-redo"></i>
                    Yêu cầu link mới
                </a>
            </div>
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
    .success-actions, .expired-actions {
        text-align: center;
        margin: 1.5rem 0;
    }
    </style>

    <script>
    function togglePassword(inputId) {
        const input = document.getElementById(inputId);
        const icon = input.nextElementSibling.querySelector('i');
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
    </script>
</body>
</html>
