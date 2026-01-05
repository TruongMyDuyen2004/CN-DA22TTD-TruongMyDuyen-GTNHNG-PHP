<?php
session_start();
require_once '../config/database.php';
require_once '../config/google-oauth.php';

$error = '';

// Xử lý lỗi từ Google callback
if (isset($_GET['error'])) {
    $errorMessages = [
        'google_failed' => 'Đăng nhập Google thất bại. Vui lòng thử lại.',
        'google_token_failed' => 'Không thể xác thực với Google. Vui lòng thử lại.',
        'google_no_token' => 'Không nhận được token từ Google.',
        'google_userinfo_failed' => 'Không thể lấy thông tin từ Google.',
        'database_error' => 'Lỗi hệ thống. Vui lòng thử lại sau.'
    ];
    $error = $errorMessages[$_GET['error']] ?? 'Có lỗi xảy ra.';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Vui lòng nhập email và mật khẩu';
    } else {
        $db = new Database();
        $conn = $db->connect();
        
        $stmt = $conn->prepare("SELECT * FROM customers WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['customer_id'] = $user['id'];
            $_SESSION['customer_name'] = $user['full_name'];
            $_SESSION['customer_email'] = $user['email'];
            header("Location: ../index.php");
            exit;
        } else {
            $error = 'Email hoặc mật khẩu không đúng';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - Ngon Gallery</title>
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
                <h1>Đăng Nhập</h1>
                <p>Chào mừng bạn quay trở lại</p>
            </div>

            <?php if ($error): ?>
            <div class="auth-alert error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="" class="auth-form">
                <div class="form-field">
                    <label>Email <span class="required">*</span></label>
                    <div class="input-group">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" placeholder="email@example.com" required 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-field">
                    <label>Mật khẩu <span class="required">*</span></label>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" id="password" placeholder="Nhập mật khẩu" required>
                        <button type="button" class="password-toggle" onclick="togglePassword('password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="forgot-password-link">
                    <a href="forgot-password.php"><i class="fas fa-key"></i> Quên mật khẩu?</a>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fas fa-sign-in-alt"></i>
                    Đăng nhập
                </button>
            </form>

            <!-- Divider -->
            <div class="auth-divider">
                <span>hoặc</span>
            </div>

            <!-- Social Login -->
            <div class="social-login">
                <a href="<?php echo getGoogleLoginUrl(); ?>" class="btn-google">
                    <svg viewBox="0 0 24 24" width="20" height="20">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                    </svg>
                    Đăng nhập với Google
                </a>
            </div>

            <div class="auth-footer">
                <p>Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a></p>
                <a href="../index.php" class="back-home">
                    <i class="fas fa-arrow-left"></i>
                    Về trang chủ
                </a>
            </div>
        </div>
    </div>

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
