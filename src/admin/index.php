<?php
session_start();
require_once '../config/database.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$db = new Database();
$conn = $db->connect();

// Dashboard chỉ hiển thị thống kê tổng quan, không xử lý actions

// Lấy thống kê tổng quan
$stmt = $conn->query("SELECT COUNT(*) as total FROM reservations WHERE status = 'pending'");
$pending_reservations = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM reservations");
$total_reservations = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM contacts WHERE status = 'new'");
$new_contacts = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM contacts");
$total_contacts = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM menu_items");
$total_items = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM orders WHERE status = 'pending'");
$pending_orders = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM orders");
$total_orders = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM customers");
$total_customers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Thống kê doanh thu hôm nay
$stmt = $conn->query("SELECT COALESCE(SUM(total_amount), 0) as revenue FROM orders WHERE DATE(created_at) = CURDATE() AND status != 'cancelled'");
$today_revenue = $stmt->fetch(PDO::FETCH_ASSOC)['revenue'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Ngon Gallery Admin</title>
    <link rel="stylesheet" href="../assets/css/admin-luxury.css">
    <link rel="stylesheet" href="../assets/css/admin-fix.css">
    <link rel="stylesheet" href="../assets/css/admin-unified.css">
    <link rel="stylesheet" href="../assets/css/admin-orange-theme.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1>
                <i class="fas fa-chart-line"></i>
                Dashboard
            </h1>
            <div class="header-actions">
                <a href="../index.php" target="_blank" class="btn btn-secondary">
                    <i class="fas fa-external-link-alt"></i>
                    Xem Website
                </a>
            </div>
        </div>
        
        <!-- Stats Grid -->
        <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
            <!-- Đơn hàng -->
            <a href="orders.php" style="text-decoration: none; color: inherit;">
                <div class="stat-card" style="cursor: pointer; transition: transform 0.2s;">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $total_orders; ?></h3>
                        <p>Tổng đơn hàng</p>
                        <?php if ($pending_orders > 0): ?>
                        <small style="color: #f59e0b; font-weight: 600;">
                            <i class="fas fa-clock"></i> <?php echo $pending_orders; ?> chờ xử lý
                        </small>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
            
            <!-- Đặt bàn -->
            <a href="reservations.php" style="text-decoration: none; color: inherit;">
                <div class="stat-card" style="cursor: pointer; transition: transform 0.2s;">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $total_reservations; ?></h3>
                        <p>Tổng đặt bàn</p>
                        <?php if ($pending_reservations > 0): ?>
                        <small style="color: #f59e0b; font-weight: 600;">
                            <i class="fas fa-clock"></i> <?php echo $pending_reservations; ?> chờ xác nhận
                        </small>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
            
            <!-- Liên hệ -->
            <a href="contacts.php" style="text-decoration: none; color: inherit;">
                <div class="stat-card" style="cursor: pointer; transition: transform 0.2s;">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $total_contacts; ?></h3>
                        <p>Tổng liên hệ</p>
                        <?php if ($new_contacts > 0): ?>
                        <small style="color: #ef4444; font-weight: 600;">
                            <i class="fas fa-envelope-open"></i> <?php echo $new_contacts; ?> chưa đọc
                        </small>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
            
            <!-- Khách hàng -->
            <a href="customers.php" style="text-decoration: none; color: inherit;">
                <div class="stat-card" style="cursor: pointer; transition: transform 0.2s;">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $total_customers; ?></h3>
                        <p>Khách hàng</p>
                    </div>
                </div>
            </a>
            
            <!-- Thực đơn -->
            <a href="menu.php" style="text-decoration: none; color: inherit;">
                <div class="stat-card" style="cursor: pointer; transition: transform 0.2s;">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $total_items; ?></h3>
                        <p>Món ăn</p>
                    </div>
                </div>
            </a>
            
            <!-- Doanh thu hôm nay -->
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($today_revenue); ?>đ</h3>
                    <p>Doanh thu hôm nay</p>
                </div>
            </div>
        </div>
        
        <?php if ($dashboard_message): ?>
        <div class="alert alert-success" style="margin-bottom: 1.5rem;">
            ✅ <?php echo $dashboard_message; ?>
        </div>
        <?php endif; ?>

        <!-- Quick Actions -->
        <div class="table-container">
            <div style="padding: 1.5rem; border-bottom: 1px solid var(--gray-200);">
                <h2 style="font-size: 1.3rem; font-weight: 700; color: var(--gray-900); display: flex; align-items: center; gap: 0.75rem;">
                    <i class="fas fa-bolt" style="color: var(--primary);"></i>
                    Thao tác nhanh
                </h2>
            </div>
            <div style="padding: 2rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <a href="orders.php" class="btn btn-primary" style="padding: 1.5rem; text-align: center; display: flex; flex-direction: column; gap: 0.5rem;">
                    <i class="fas fa-shopping-cart" style="font-size: 2rem;"></i>
                    <span>Quản lý đơn hàng</span>
                </a>
                <a href="reservations.php" class="btn btn-primary" style="padding: 1.5rem; text-align: center; display: flex; flex-direction: column; gap: 0.5rem;">
                    <i class="fas fa-calendar-alt" style="font-size: 2rem;"></i>
                    <span>Quản lý đặt bàn</span>
                </a>
                <a href="contacts.php" class="btn btn-primary" style="padding: 1.5rem; text-align: center; display: flex; flex-direction: column; gap: 0.5rem;">
                    <i class="fas fa-envelope" style="font-size: 2rem;"></i>
                    <span>Quản lý liên hệ</span>
                </a>
                <a href="menu.php" class="btn btn-primary" style="padding: 1.5rem; text-align: center; display: flex; flex-direction: column; gap: 0.5rem;">
                    <i class="fas fa-utensils" style="font-size: 2rem;"></i>
                    <span>Quản lý thực đơn</span>
                </a>
                <a href="customers.php" class="btn btn-primary" style="padding: 1.5rem; text-align: center; display: flex; flex-direction: column; gap: 0.5rem;">
                    <i class="fas fa-users" style="font-size: 2rem;"></i>
                    <span>Quản lý khách hàng</span>
                </a>
                <a href="reviews.php" class="btn btn-primary" style="padding: 1.5rem; text-align: center; display: flex; flex-direction: column; gap: 0.5rem;">
                    <i class="fas fa-star" style="font-size: 2rem;"></i>
                    <span>Quản lý đánh giá</span>
                </a>
            </div>
        </div>
        
        <style>
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
        }
        </style>
    </div>
</body>
</html>
