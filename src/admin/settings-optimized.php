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
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/admin-unified.css">
    <link rel="stylesheet" href="../assets/css/admin-orange-theme.css">
    <style>
        .settings-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .settings-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        @media (max-width: 768px) {
            .settings-row {
                grid-template-columns: 1fr;
            }
        }
        .settings-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .settings-card h3 {
            color: #1f2937;
            margin: 0 0 1.25rem 0;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid #f3f4f6;
        }
        .settings-card h3 i {
            color: #f97316;
        }
        .stat-grid {
            display: grid;
            gap: 0.75rem;
        }
        .stat-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem;
            background: #f9fafb;
            border-radius: 8px;
        }
        .stat-label {
            color: #6b7280;
            font-size: 0.9rem;
        }
        .stat-value {
            font-weight: 700;
            color: #1f2937;
            font-size: 1.1rem;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        .form-group input {
            width: 100%;
            padding: 0.65rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 0.95rem;
        }
        .form-group input:focus {
            outline: none;
            border-color: #f97316;
        }
        .alert {
            padding: 0.875rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
        }
        .alert-success {
            background: #d1fae5;
            color: #065f46;
        }
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="settings-container">
            <div class="page-header">
                <h1><i class="fas fa-cog"></i> Cài đặt</h1>
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
