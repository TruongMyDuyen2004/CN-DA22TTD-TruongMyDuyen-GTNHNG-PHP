<?php
// Force no cache
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

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
    <link rel="stylesheet" href="../assets/css/admin-dark-modern.css">
    <link rel="stylesheet" href="../assets/css/admin-green-override.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #f8fafc !important; }
        .main-content { background: #f8fafc !important; padding: 1.5rem 2rem !important; }
        
        .page-header { margin-bottom: 1.5rem; }
        .page-header h1 {
            color: #1f2937 !important;
            font-size: 1.5rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin: 0;
        }
        .page-header h1 i { color: #22c55e; }
        
        /* Alert Messages */
        .alert {
            padding: 1rem 1.25rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .alert-success {
            background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
            color: #166534;
            border: 2px solid #86efac;
        }
        .alert-error {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
            border: 2px solid #fca5a5;
        }
        
        /* Stats Grid */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .stat-box {
            background: white;
            border-radius: 16px;
            padding: 1.25rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            border: 2px solid #e5e7eb;
            transition: all 0.3s ease;
            text-decoration: none;
            cursor: pointer;
        }
        .stat-box:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        .stat-box:nth-child(1):hover { border-color: #3b82f6; }
        .stat-box:nth-child(2):hover { border-color: #22c55e; }
        .stat-box:nth-child(3):hover { border-color: #f59e0b; }
        .stat-box:nth-child(4):hover { border-color: #8b5cf6; }
        .stat-box:nth-child(5):hover { border-color: #ef4444; }
        
        .stat-icon {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            color: white;
            flex-shrink: 0;
        }
        .stat-icon.blue { background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); }
        .stat-icon.green { background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); }
        .stat-icon.orange { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
        .stat-icon.purple { background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); }
        .stat-icon.red { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }
        
        .stat-info h3 {
            font-size: 1.75rem;
            font-weight: 800;
            color: #1f2937;
            margin: 0;
            line-height: 1;
        }
        .stat-info p {
            font-size: 0.85rem;
            color: #6b7280;
            margin: 0.25rem 0 0 0;
            font-weight: 600;
        }
        
        /* Cards Grid */
        .cards-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }
        
        .settings-card {
            background: white;
            border-radius: 20px;
            border: 2px solid #e5e7eb;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .settings-card:hover {
            border-color: #22c55e;
            box-shadow: 0 10px 30px rgba(34, 197, 94, 0.1);
        }
        
        .card-header {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            padding: 1.25rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .card-header h2 {
            color: white !important;
            font-size: 1.1rem;
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .card-header i { font-size: 1.1rem; }
        
        .card-body { padding: 1.5rem; }
        
        /* Admin Info Items */
        .info-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.25rem;
            background: #f8fafc;
            border-radius: 14px;
            margin-bottom: 1rem;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }
        .info-item:last-child { margin-bottom: 0; }
        .info-item:hover {
            border-color: #22c55e;
            transform: translateX(5px);
        }
        
        .info-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            flex-shrink: 0;
        }
        
        .info-content label {
            display: block;
            font-size: 0.75rem;
            color: #6b7280;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.25rem;
        }
        .info-content span {
            display: block;
            font-size: 1rem;
            color: #1f2937;
            font-weight: 600;
        }
        
        /* Password Form */
        .form-group {
            margin-bottom: 1.25rem;
        }
        .form-group label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            color: #374151;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .form-group label i {
            color: #22c55e;
            width: 16px;
        }
        .form-group label small {
            color: #9ca3af;
            font-weight: 500;
        }
        
        .input-wrapper {
            position: relative;
        }
        .input-wrapper input {
            width: 100%;
            padding: 0.875rem 3rem 0.875rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 0.95rem;
            color: #1f2937;
            transition: all 0.3s ease;
            background: #f8fafc;
        }
        .input-wrapper input:focus {
            outline: none;
            border-color: #22c55e;
            background: white;
            box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.1);
        }
        
        .toggle-btn {
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
        .toggle-btn:hover { color: #22c55e; }
        
        /* Password Strength */
        .strength-meter {
            margin-top: 0.75rem;
        }
        .strength-bar {
            height: 6px;
            background: #e5e7eb;
            border-radius: 3px;
            overflow: hidden;
            margin-bottom: 0.4rem;
        }
        .strength-fill {
            height: 100%;
            border-radius: 3px;
            transition: all 0.3s ease;
        }
        .strength-text {
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .match-indicator {
            margin-top: 0.5rem;
            padding: 0.5rem 0.75rem;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .match-success {
            background: #dcfce7;
            color: #166534;
        }
        .match-error {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .btn-submit {
            width: 100%;
            padding: 1rem 1.5rem;
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            margin-top: 0.5rem;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(34, 197, 94, 0.35);
        }
        .btn-submit:active {
            transform: translateY(0);
        }
        
        @media (max-width: 1200px) {
            .stats-row { grid-template-columns: repeat(3, 1fr); }
            .cards-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .stats-row { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-cog"></i> Cài đặt</h1>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <!-- Stats Row -->
        <div class="stats-row">
            <a href="customers.php" class="stat-box">
                <div class="stat-icon blue"><i class="fas fa-users"></i></div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['customers']); ?></h3>
                    <p>Khách hàng</p>
                </div>
            </a>
            <a href="menu-manage.php" class="stat-box">
                <div class="stat-icon green"><i class="fas fa-utensils"></i></div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['menu_items']); ?></h3>
                    <p>Món ăn</p>
                </div>
            </a>
            <a href="orders.php" class="stat-box">
                <div class="stat-icon orange"><i class="fas fa-shopping-cart"></i></div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['orders']); ?></h3>
                    <p>Đơn hàng</p>
                </div>
            </a>
            <a href="reviews.php" class="stat-box">
                <div class="stat-icon purple"><i class="fas fa-star"></i></div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['reviews']); ?></h3>
                    <p>Đánh giá</p>
                </div>
            </a>
            <a href="reservations.php" class="stat-box">
                <div class="stat-icon red"><i class="fas fa-calendar-alt"></i></div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['reservations']); ?></h3>
                    <p>Đặt bàn</p>
                </div>
            </a>
        </div>

        <!-- Cards Grid -->
        <div class="cards-grid">
            <!-- Admin Info Card -->
            <div class="settings-card">
                <div class="card-header" style="background: #f8fafc; padding: 1.25rem 1.5rem; border-radius: 20px 20px 0 0; border-bottom: 2px solid #e5e7eb;">
                    <h2 style="color: #1f2937 !important; font-size: 1.1rem; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 0.5rem;"><i class="fas fa-user-shield" style="color: #22c55e;"></i> Thông tin Admin</h2>
                </div>
                <div class="card-body">
                    <div class="info-item">
                        <div class="info-icon"><i class="fas fa-user"></i></div>
                        <div class="info-content">
                            <label>Username</label>
                            <span><?php echo htmlspecialchars($admin_info['username']); ?></span>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon"><i class="fas fa-envelope"></i></div>
                        <div class="info-content">
                            <label>Email</label>
                            <span><?php echo htmlspecialchars($admin_info['email'] ?? 'Chưa cập nhật'); ?></span>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon"><i class="fas fa-calendar"></i></div>
                        <div class="info-content">
                            <label>Ngày tạo</label>
                            <span><?php echo date('d/m/Y H:i', strtotime($admin_info['created_at'])); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Password Change Card -->
            <div class="settings-card">
                <div class="card-header" style="background: #f8fafc; padding: 1.25rem 1.5rem; border-radius: 20px 20px 0 0; border-bottom: 2px solid #e5e7eb;">
                    <h2 style="color: #1f2937 !important; font-size: 1.1rem; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 0.5rem;"><i class="fas fa-key" style="color: #22c55e;"></i> Đổi mật khẩu</h2>
                </div>
                <div class="card-body">
                    <form method="POST" id="passwordForm">
                        <div class="form-group">
                            <label><i class="fas fa-lock"></i> Mật khẩu hiện tại *</label>
                            <div class="input-wrapper">
                                <input type="password" name="current_password" id="currentPassword" required>
                                <button type="button" class="toggle-btn" onclick="togglePassword('currentPassword', this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-key"></i> Mật khẩu mới * <small>(tối thiểu 6 ký tự)</small></label>
                            <div class="input-wrapper">
                                <input type="password" name="new_password" id="newPassword" required minlength="6" oninput="checkStrength(this.value)">
                                <button type="button" class="toggle-btn" onclick="togglePassword('newPassword', this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="strength-meter" id="strengthMeter" style="display: none;">
                                <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
                                <span class="strength-text" id="strengthText"></span>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-check-circle"></i> Xác nhận mật khẩu mới *</label>
                            <div class="input-wrapper">
                                <input type="password" name="confirm_password" id="confirmPassword" required minlength="6" oninput="checkMatch()">
                                <button type="button" class="toggle-btn" onclick="togglePassword('confirmPassword', this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="match-indicator" id="matchIndicator" style="display: none;"></div>
                        </div>
                        
                        <button type="submit" name="change_password" class="btn-submit">
                            <i class="fas fa-shield-alt"></i> Đổi mật khẩu
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>
    
    <script>
    function togglePassword(id, btn) {
        const input = document.getElementById(id);
        const icon = btn.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    }
    
    function checkStrength(pwd) {
        const meter = document.getElementById('strengthMeter');
        const fill = document.getElementById('strengthFill');
        const text = document.getElementById('strengthText');
        
        if (!pwd) { meter.style.display = 'none'; return; }
        meter.style.display = 'block';
        
        let score = 0;
        if (pwd.length >= 6) score += 25;
        if (pwd.length >= 8) score += 25;
        if (/[A-Z]/.test(pwd)) score += 15;
        if (/[a-z]/.test(pwd)) score += 15;
        if (/[0-9]/.test(pwd)) score += 10;
        if (/[^A-Za-z0-9]/.test(pwd)) score += 10;
        
        let label, color;
        if (score < 40) { label = 'Yếu'; color = '#ef4444'; }
        else if (score < 70) { label = 'Trung bình'; color = '#f59e0b'; }
        else { label = 'Mạnh'; color = '#22c55e'; }
        
        fill.style.width = score + '%';
        fill.style.background = color;
        text.textContent = label;
        text.style.color = color;
    }
    
    function checkMatch() {
        const newPwd = document.getElementById('newPassword').value;
        const confirmPwd = document.getElementById('confirmPassword').value;
        const indicator = document.getElementById('matchIndicator');
        
        if (!confirmPwd) { indicator.style.display = 'none'; return; }
        indicator.style.display = 'block';
        
        if (newPwd === confirmPwd) {
            indicator.textContent = '✓ Mật khẩu khớp';
            indicator.className = 'match-indicator match-success';
        } else {
            indicator.textContent = '✗ Mật khẩu không khớp';
            indicator.className = 'match-indicator match-error';
        }
    }
    </script>
</body>
</html>
