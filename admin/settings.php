<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$db = new Database();
$conn = $db->connect();

// Xử lý đổi mật khẩu
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if ($new_password !== $confirm_password) {
        $message = 'Mật khẩu xác nhận không khớp';
        $message_type = 'error';
    } elseif (strlen($new_password) < 6) {
        $message = 'Mật khẩu phải có ít nhất 6 ký tự';
        $message_type = 'error';
    } else {
        $stmt = $conn->prepare("SELECT password FROM admins WHERE id = ?");
        $stmt->execute([$_SESSION['admin_id']]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (password_verify($current_password, $admin['password'])) {
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE admins SET password = ? WHERE id = ?");
            $stmt->execute([$new_hash, $_SESSION['admin_id']]);
            
            $message = 'Đã đổi mật khẩu thành công';
            $message_type = 'success';
        } else {
            $message = 'Mật khẩu hiện tại không đúng';
            $message_type = 'error';
        }
    }
}

// Lấy thống kê
$stats = [
    'customers' => $conn->query("SELECT COUNT(*) FROM customers")->fetchColumn(),
    'menu_items' => $conn->query("SELECT COUNT(*) FROM menu_items")->fetchColumn(),
    'orders' => $conn->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'reviews' => $conn->query("SELECT COUNT(*) FROM reviews")->fetchColumn(),
    'reservations' => $conn->query("SELECT COUNT(*) FROM reservations")->fetchColumn()
];

// Thông tin admin
$stmt = $conn->prepare("SELECT username, email, created_at FROM admins WHERE id = ?");
$stmt->execute([$_SESSION['admin_id']]);
$admin_info = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cài đặt - Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/admin-fix.css">
    <link rel="stylesheet" href="../assets/css/admin-unified.css">
    <link rel="stylesheet" href="../assets/css/admin-orange-theme.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <div class="admin-layout">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="admin-main">
            <div class="admin-header">
                <h1><i class="fas fa-cog"></i> Cài đặt</h1>
            </div>

            <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>" style="margin-bottom: 1.5rem;">
                <?php echo $message_type === 'success' ? '✅' : '❌'; ?> <?php echo $message; ?>
            </div>
            <?php endif; ?>

            <!-- Thống kê hệ thống -->
            <div class="stats-grid" style="margin-bottom: 2rem;">
                <div class="stat-card stat-primary">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['customers']); ?></h3>
                        <p>Khách hàng</p>
                    </div>
                </div>
                <div class="stat-card stat-success">
                    <div class="stat-icon"><i class="fas fa-utensils"></i></div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['menu_items']); ?></h3>
                        <p>Món ăn</p>
                    </div>
                </div>
                <div class="stat-card stat-warning">
                    <div class="stat-icon"><i class="fas fa-shopping-cart"></i></div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['orders']); ?></h3>
                        <p>Đơn hàng</p>
                    </div>
                </div>
                <div class="stat-card stat-info">
                    <div class="stat-icon"><i class="fas fa-star"></i></div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['reviews']); ?></h3>
                        <p>Đánh giá</p>
                    </div>
                </div>
                <div class="stat-card stat-danger">
                    <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['reservations']); ?></h3>
                        <p>Đặt bàn</p>
                    </div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <!-- Thông tin Admin -->
                <div class="card admin-info-card">
                    <div class="card-header">
                        <h2><i class="fas fa-user-shield"></i> Thông tin Admin</h2>
                    </div>
                    <div class="card-body" style="padding: 2rem;">
                        <div class="admin-info-item">
                            <div class="info-icon">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="info-content">
                                <label>Username</label>
                                <span><?php echo htmlspecialchars($admin_info['username']); ?></span>
                            </div>
                        </div>
                        <div class="admin-info-item">
                            <div class="info-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="info-content">
                                <label>Email</label>
                                <span><?php echo htmlspecialchars($admin_info['email'] ?? 'Chưa cập nhật'); ?></span>
                            </div>
                        </div>
                        <div class="admin-info-item">
                            <div class="info-icon">
                                <i class="fas fa-calendar"></i>
                            </div>
                            <div class="info-content">
                                <label>Ngày tạo</label>
                                <span><?php echo date('d/m/Y H:i', strtotime($admin_info['created_at'])); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Đổi mật khẩu -->
                <div class="card password-change-card">
                    <div class="card-header">
                        <h2><i class="fas fa-key"></i> Đổi mật khẩu</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="passwordForm">
                            <div class="password-input-group">
                                <label><i class="fas fa-lock"></i> Mật khẩu hiện tại *</label>
                                <div class="input-with-icon">
                                    <input type="password" name="current_password" id="currentPassword" class="form-control" required>
                                    <button type="button" class="toggle-password" onclick="togglePassword('currentPassword', this)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="password-input-group">
                                <label><i class="fas fa-key"></i> Mật khẩu mới * <small style="color: #6b7280;">(tối thiểu 6 ký tự)</small></label>
                                <div class="input-with-icon">
                                    <input type="password" name="new_password" id="newPassword" class="form-control" required minlength="6" oninput="checkPasswordStrength(this.value)">
                                    <button type="button" class="toggle-password" onclick="togglePassword('newPassword', this)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="password-strength" id="passwordStrength" style="display: none;">
                                    <div class="strength-bar">
                                        <div class="strength-fill" id="strengthFill"></div>
                                    </div>
                                    <span class="strength-text" id="strengthText"></span>
                                </div>
                            </div>
                            
                            <div class="password-input-group">
                                <label><i class="fas fa-check-circle"></i> Xác nhận mật khẩu mới *</label>
                                <div class="input-with-icon">
                                    <input type="password" name="confirm_password" id="confirmPassword" class="form-control" required minlength="6" oninput="checkPasswordMatch()">
                                    <button type="button" class="toggle-password" onclick="togglePassword('confirmPassword', this)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="password-match" id="passwordMatch" style="display: none;"></div>
                            </div>
                            
                            <button type="submit" name="change_password" class="btn-change-password">
                                <i class="fas fa-shield-alt"></i> Đổi mật khẩu
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <style>
    .alert-success {
        background: #d1fae5;
        color: #065f46;
        padding: 1rem;
        border-radius: 8px;
        border-left: 4px solid #10b981;
        font-weight: 500;
    }
    .alert-error {
        background: #fee2e2;
        color: #991b1b;
        padding: 1rem;
        border-radius: 8px;
        border-left: 4px solid #ef4444;
        font-weight: 500;
    }
    
    /* Mini Stats */
    .mini-stat {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.875rem;
        background: #f9fafb;
        border-radius: 10px;
        transition: all 0.3s;
    }
    
    .mini-stat:hover {
        background: #f3f4f6;
    }
    
    .mini-stat-icon {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.1rem;
        flex-shrink: 0;
    }
    
    .mini-stat-info {
        flex: 1;
        min-width: 0;
    }
    
    .mini-stat-info h4 {
        margin: 0;
        font-size: 1.5rem;
        font-weight: 800;
        color: #1f2937;
        line-height: 1;
    }
    
    .mini-stat-info p {
        margin: 0.25rem 0 0 0;
        font-size: 0.8rem;
        color: #6b7280;
        font-weight: 600;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    @media (max-width: 1400px) {
        .mini-stat {
            flex-direction: column;
            text-align: center;
            gap: 0.5rem;
        }
        
        .mini-stat-info h4 {
            font-size: 1.3rem;
        }
        
        .mini-stat-info p {
            font-size: 0.75rem;
        }
    }
    
    .password-change-card .card-body {
        padding: 2rem;
    }
    
    .password-input-group {
        margin-bottom: 1.5rem;
    }
    
    .password-input-group label {
        display: block;
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.5rem;
        font-size: 0.95rem;
    }
    
    .password-input-group label i {
        color: #f97316;
        margin-right: 0.5rem;
        width: 18px;
    }
    
    .input-with-icon {
        position: relative;
    }
    
    .input-with-icon input {
        width: 100%;
        padding: 0.875rem 3rem 0.875rem 1rem;
        border: 2px solid #e5e7eb;
        border-radius: 10px;
        font-size: 0.95rem;
        transition: all 0.3s;
    }
    
    .input-with-icon input:focus {
        outline: none;
        border-color: #f97316;
        box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
    }
    
    .toggle-password {
        position: absolute;
        right: 0.75rem;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: #9ca3af;
        cursor: pointer;
        padding: 0.5rem;
        transition: color 0.2s;
    }
    
    .toggle-password:hover {
        color: #f97316;
    }
    
    .toggle-password i {
        font-size: 1.1rem;
    }
    
    .password-strength {
        margin-top: 0.75rem;
    }
    
    .strength-bar {
        height: 6px;
        background: #e5e7eb;
        border-radius: 3px;
        overflow: hidden;
        margin-bottom: 0.5rem;
    }
    
    .strength-fill {
        height: 100%;
        transition: all 0.3s;
        border-radius: 3px;
    }
    
    .strength-text {
        font-size: 0.85rem;
        font-weight: 600;
    }
    
    .password-match {
        margin-top: 0.5rem;
        font-size: 0.85rem;
        font-weight: 600;
        padding: 0.5rem;
        border-radius: 6px;
    }
    
    .match-success {
        color: #059669;
        background: #d1fae5;
    }
    
    .match-error {
        color: #dc2626;
        background: #fee2e2;
    }
    
    .btn-change-password {
        width: 100%;
        padding: 1rem;
        background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
        color: white;
        border: none;
        border-radius: 10px;
        font-size: 1rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s;
        margin-top: 0.5rem;
        box-shadow: 0 4px 12px rgba(249, 115, 22, 0.3);
    }
    
    .btn-change-password:hover {
        box-shadow: 0 6px 20px rgba(249, 115, 22, 0.4);
    }
    
    .btn-change-password:active {
        opacity: 0.9;
    }
    
    .btn-change-password i {
        margin-right: 0.5rem;
    }
    
    /* Admin Info Card Styles */
    .admin-info-card .card-body {
        padding: 2rem;
    }
    
    .admin-info-item {
        display: flex;
        align-items: center;
        gap: 1.25rem;
        padding: 1.25rem;
        background: #f9fafb;
        border-radius: 12px;
        margin-bottom: 1rem;
        transition: all 0.3s;
    }
    
    .admin-info-item:last-child {
        margin-bottom: 0;
    }
    
    .admin-info-item:hover {
        background: #f3f4f6;
        transform: translateX(4px);
    }
    
    .info-icon {
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.4rem;
        flex-shrink: 0;
        box-shadow: 0 4px 12px rgba(249, 115, 22, 0.3);
    }
    
    .info-content {
        flex: 1;
    }
    
    .info-content label {
        display: block;
        font-size: 0.85rem;
        color: #6b7280;
        font-weight: 600;
        margin-bottom: 0.25rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .info-content span {
        display: block;
        font-size: 1.05rem;
        color: #1f2937;
        font-weight: 600;
    }
    </style>
    
    <script>
    function togglePassword(inputId, button) {
        const input = document.getElementById(inputId);
        const icon = button.querySelector('i');
        
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
    
    function checkPasswordStrength(password) {
        const strengthDiv = document.getElementById('passwordStrength');
        const strengthFill = document.getElementById('strengthFill');
        const strengthText = document.getElementById('strengthText');
        
        if (password.length === 0) {
            strengthDiv.style.display = 'none';
            return;
        }
        
        strengthDiv.style.display = 'block';
        
        let strength = 0;
        let text = '';
        let color = '';
        
        // Độ dài
        if (password.length >= 6) strength += 25;
        if (password.length >= 8) strength += 25;
        
        // Có chữ hoa
        if (/[A-Z]/.test(password)) strength += 15;
        
        // Có chữ thường
        if (/[a-z]/.test(password)) strength += 15;
        
        // Có số
        if (/[0-9]/.test(password)) strength += 10;
        
        // Có ký tự đặc biệt
        if (/[^A-Za-z0-9]/.test(password)) strength += 10;
        
        if (strength < 40) {
            text = 'Yếu';
            color = '#ef4444';
        } else if (strength < 70) {
            text = 'Trung bình';
            color = '#f59e0b';
        } else {
            text = 'Mạnh';
            color = '#10b981';
        }
        
        strengthFill.style.width = strength + '%';
        strengthFill.style.background = color;
        strengthText.textContent = text;
        strengthText.style.color = color;
    }
    
    function checkPasswordMatch() {
        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        const matchDiv = document.getElementById('passwordMatch');
        
        if (confirmPassword.length === 0) {
            matchDiv.style.display = 'none';
            return;
        }
        
        matchDiv.style.display = 'block';
        
        if (newPassword === confirmPassword) {
            matchDiv.textContent = '✓ Mật khẩu khớp';
            matchDiv.className = 'password-match match-success';
        } else {
            matchDiv.textContent = '✗ Mật khẩu không khớp';
            matchDiv.className = 'password-match match-error';
        }
    }
    </script>
</body>
</html>
