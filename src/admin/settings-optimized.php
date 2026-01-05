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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin-dark-modern.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        
        .main-content {
            background: transparent;
            padding: 2rem;
        }
        
        .settings-container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 3rem;
            padding: 2rem 0;
        }
        
        .page-header h1 {
            font-size: 3.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #60a5fa 0%, #a78bfa 50%, #ec4899 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1rem;
            letter-spacing: -0.02em;
            text-transform: uppercase;
        }
        
        .page-header h1 i {
            background: linear-gradient(135deg, #60a5fa 0%, #a78bfa 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .page-subtitle {
            color: #94a3b8;
            font-size: 1.1rem;
            font-weight: 400;
        }
        
        .settings-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        @media (max-width: 768px) {
            .settings-row {
                grid-template-columns: 1fr;
            }
            .page-header h1 {
                font-size: 2.5rem;
            }
        }
        
        .settings-card {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(148, 163, 184, 0.1);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .settings-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #60a5fa, #a78bfa, #ec4899);
        }
        
        .settings-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 25px 70px rgba(96, 165, 250, 0.2);
            border-color: rgba(148, 163, 184, 0.2);
        }
        
        .settings-card h3 {
            color: #f1f5f9;
            margin: 0 0 1.5rem 0;
            font-size: 1.3rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(148, 163, 184, 0.1);
        }
        
        .settings-card h3 i {
            background: linear-gradient(135deg, #60a5fa, #a78bfa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 1.4rem;
        }
        
        .stat-grid {
            display: grid;
            gap: 1rem;
        }
        
        .stat-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.25rem;
            background: rgba(15, 23, 42, 0.5);
            border: 1px solid rgba(148, 163, 184, 0.1);
            border-radius: 12px;
            transition: all 0.3s ease;
        }
        
        .stat-item:hover {
            background: rgba(30, 41, 59, 0.7);
            border-color: rgba(96, 165, 250, 0.3);
            transform: translateX(5px);
        }
        
        .stat-label {
            color: #94a3b8;
            font-size: 0.95rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .stat-label i {
            color: #60a5fa;
        }
        
        .stat-value {
            font-weight: 700;
            background: linear-gradient(135deg, #60a5fa, #a78bfa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 1.4rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            color: #cbd5e1;
            margin-bottom: 0.75rem;
            font-size: 0.95rem;
        }
        
        .form-group input {
            width: 100%;
            padding: 0.875rem 1rem;
            background: rgba(15, 23, 42, 0.5);
            border: 2px solid rgba(148, 163, 184, 0.2);
            border-radius: 12px;
            font-size: 0.95rem;
            color: #e2e8f0;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #60a5fa;
            background: rgba(15, 23, 42, 0.7);
            box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.1);
        }
        
        .form-group input::placeholder {
            color: #64748b;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #60a5fa 0%, #a78bfa 100%);
            color: white;
            border: none;
            padding: 0.875rem 2rem;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 10px 30px rgba(96, 165, 250, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 40px rgba(96, 165, 250, 0.4);
        }
        
        .btn-primary:active {
            transform: translateY(0);
        }
        
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            font-size: 0.95rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            backdrop-filter: blur(10px);
        }
        
        .alert-success {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #6ee7b7;
        }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }
        
        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .settings-card {
            animation: fadeInUp 0.6s ease-out;
        }
        
        .settings-card:nth-child(1) { animation-delay: 0.1s; }
        .settings-card:nth-child(2) { animation-delay: 0.2s; }
        .settings-card:nth-child(3) { animation-delay: 0.3s; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="settings-container">
            <div class="page-header">
                <h1><i class="fas fa-cog"></i> CÀI ĐẶT HỆ THỐNG</h1>
                <p class="page-subtitle">Quản lý cấu hình và thông tin tài khoản của bạn</p>
            </div>

            <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo $message_type === 'success' ? '✅' : '❌'; ?> <?php echo $message; ?>
            </div>
            <?php endif; ?>

            <div class="settings-row">
                <!-- Thống kê -->
                <div class="settings-card">
                    <h3><i class="fas fa-chart-bar"></i> Thống kê hệ thống</h3>
                    <div class="stat-grid">
                        <div class="stat-item">
                            <span class="stat-label"><i class="fas fa-users"></i> Khách hàng</span>
                            <span class="stat-value"><?php echo number_format($stats['customers']); ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label"><i class="fas fa-utensils"></i> Món ăn</span>
                            <span class="stat-value"><?php echo number_format($stats['menu_items']); ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label"><i class="fas fa-shopping-cart"></i> Đơn hàng</span>
                            <span class="stat-value"><?php echo number_format($stats['orders']); ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label"><i class="fas fa-star"></i> Đánh giá</span>
                            <span class="stat-value"><?php echo number_format($stats['reviews']); ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label"><i class="fas fa-calendar-alt"></i> Đặt bàn</span>
                            <span class="stat-value"><?php echo number_format($stats['reservations']); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Thông tin Admin -->
                <div class="settings-card">
                    <h3><i class="fas fa-user-shield"></i> Thông tin Admin</h3>
                    <div class="stat-grid">
                        <div class="stat-item">
                            <span class="stat-label">Username</span>
                            <span class="stat-value"><?php echo htmlspecialchars($admin_info['username']); ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Email</span>
                            <span class="stat-value"><?php echo htmlspecialchars($admin_info['email'] ?? 'Chưa cập nhật'); ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Ngày tạo</span>
                            <span class="stat-value"><?php echo date('d/m/Y', strtotime($admin_info['created_at'])); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Đổi mật khẩu -->
            <div class="settings-card">
                <h3><i class="fas fa-key"></i> Đổi mật khẩu</h3>
                <form method="POST" style="max-width: 500px;">
                    <div class="form-group">
                        <label>Mật khẩu hiện tại *</label>
                        <input type="password" name="current_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Mật khẩu mới * (tối thiểu 6 ký tự)</label>
                        <input type="password" name="new_password" required minlength="6">
                    </div>
                    
                    <div class="form-group">
                        <label>Xác nhận mật khẩu mới *</label>
                        <input type="password" name="confirm_password" required minlength="6">
                    </div>
                    
                    <button type="submit" name="change_password" class="btn btn-primary">
                        <i class="fas fa-save"></i> Đổi mật khẩu
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
