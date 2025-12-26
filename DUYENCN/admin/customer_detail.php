<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

require_once '../config/database.php';
$db = new Database();
$conn = $db->connect();

$customer_id = intval($_GET['id'] ?? 0);

// Lấy thông tin khách hàng
$stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$customer_id]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    header("Location: customers.php");
    exit;
}

// Lấy đơn hàng
$stmt = $conn->prepare("
    SELECT * FROM orders 
    WHERE customer_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$customer_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy đánh giá
$stmt = $conn->prepare("
    SELECT r.*, m.name as menu_name 
    FROM reviews r
    LEFT JOIN menu_items m ON r.menu_item_id = m.id
    WHERE r.customer_id = ?
    ORDER BY r.created_at DESC
");
$stmt->execute([$customer_id]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Thống kê
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_orders,
        SUM(total_amount) as total_spent,
        AVG(total_amount) as avg_order
    FROM orders 
    WHERE customer_id = ?
");
$stmt->execute([$customer_id]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết khách hàng - Admin</title>
    <link rel="stylesheet" href="../assets/css/admin-dark-modern.css">
    <link rel="stylesheet" href="../assets/css/admin-green-override.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* ========================================
           CUSTOMER DETAIL - MODERN MINIMAL DESIGN
           ======================================== */
        
        /* Profile + Info Row */
        .profile-info-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 24px;
        }
        
        /* Profile Card - Modern Glass Effect */
        .profile-hero {
            background: linear-gradient(145deg, #ffffff 0%, #f8fffe 100%);
            border: 2px solid #22c55e;
            border-radius: 16px;
            padding: 24px;
            display: flex;
            align-items: flex-start;
            gap: 20px;
            box-shadow: 0 4px 20px rgba(34, 197, 94, 0.08);
            position: relative;
            overflow: hidden;
        }
        
        .profile-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #22c55e, #16a34a, #22c55e);
        }
        
        .profile-avatar {
            width: 72px;
            height: 72px;
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            font-weight: 700;
            color: #ffffff;
            flex-shrink: 0;
            box-shadow: 0 6px 20px rgba(34, 197, 94, 0.3);
        }
        
        .profile-info {
            flex: 1;
            min-width: 0;
        }
        
        .profile-info h2 {
            font-size: 1.4rem;
            font-weight: 700;
            color: #1f2937;
            margin: 0 0 12px 0;
        }
        
        .profile-info p {
            margin: 8px 0;
            color: #4b5563;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .profile-info p i {
            color: #22c55e;
            width: 18px;
            font-size: 0.85rem;
        }
        
        .profile-meta {
            display: flex;
            gap: 10px;
            margin-top: 14px;
            flex-wrap: wrap;
        }
        
        .meta-item {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border: 1px solid rgba(34, 197, 94, 0.2);
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 0.8rem;
            color: #166534;
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 600;
        }
        
        .status-badge {
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .status-badge.active {
            background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
            color: #166534;
        }
        
        .status-badge.blocked {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
        }
        
        .status-badge i {
            font-size: 0.5rem;
        }
        
        /* Info Card Right */
        .info-card-right {
            background: #ffffff;
            border: 2px solid #d1d5db;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
        }
        
        .info-card-right .card-header-modern {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 2px solid #d1d5db;
        }
        
        .info-card-right .card-body-modern {
            padding: 16px 20px;
        }
        
        .info-card-right .info-row {
            padding: 12px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .info-card-right .info-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        
        /* Stats Cards - Modern Minimal */
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }
        
        .stat-card-modern {
            background: #ffffff;
            border: 2px solid #d1d5db;
            border-radius: 14px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            transition: all 0.25s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card-modern::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, transparent, #22c55e, transparent);
            opacity: 0;
            transition: opacity 0.25s ease;
        }
        
        .stat-card-modern:hover {
            border-color: #22c55e;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(34, 197, 94, 0.12);
        }
        
        .stat-card-modern:hover::after {
            opacity: 1;
        }
        
        .stat-icon-modern {
            width: 52px;
            height: 52px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            color: #ffffff;
            flex-shrink: 0;
        }
        
        .stat-icon-modern.blue { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); box-shadow: 0 4px 14px rgba(59, 130, 246, 0.3); }
        .stat-icon-modern.green { background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); box-shadow: 0 4px 14px rgba(34, 197, 94, 0.3); }
        .stat-icon-modern.orange { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); box-shadow: 0 4px 14px rgba(245, 158, 11, 0.3); }
        .stat-icon-modern.purple { background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); box-shadow: 0 4px 14px rgba(139, 92, 246, 0.3); }
        
        .stat-content {
            flex: 1;
            min-width: 0;
        }
        
        .stat-number {
            display: block;
            font-size: 1.5rem;
            font-weight: 700;
            color: #1f2937;
            line-height: 1.2;
        }
        
        .stat-label {
            font-size: 0.85rem;
            color: #6b7280;
            font-weight: 500;
            margin-top: 4px;
        }
        
        /* Modern Cards */
        .info-cards-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
            margin-bottom: 24px;
        }
        
        .modern-card {
            background: #ffffff;
            border: 2px solid #d1d5db;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
            transition: all 0.25s ease;
        }
        
        .modern-card:hover {
            box-shadow: 0 4px 20px rgba(34, 197, 94, 0.1);
            border-color: #22c55e;
        }
        
        .modern-card.full-width {
            grid-column: span 1;
        }
        
        .card-header-modern {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 2px solid #d1d5db;
        }
        
        .card-header-modern i {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            color: #ffffff;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            box-shadow: 0 4px 12px rgba(34, 197, 94, 0.25);
        }
        
        .card-header-modern span {
            font-size: 1.05rem;
            font-weight: 600;
            color: #1f2937;
        }
        
        .badge-count {
            margin-left: auto;
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            color: #ffffff;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .card-body-modern {
            padding: 20px;
        }
        
        /* Info Rows */
        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .info-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        
        .info-label {
            font-weight: 500;
            color: #6b7280;
            font-size: 0.9rem;
        }
        
        .info-value {
            font-weight: 600;
            color: #1f2937;
            font-size: 0.9rem;
        }
        
        /* Orders List - Modern Style */
        .orders-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .order-item {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px;
            background: linear-gradient(135deg, #fafafa 0%, #f5f5f5 100%);
            border: 2px solid #d1d5db;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.25s ease;
        }
        
        .order-item:hover {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border-color: #22c55e;
            transform: translateX(4px);
        }
        
        .order-info {
            flex: 1;
        }
        
        .order-id {
            display: block;
            font-weight: 600;
            color: #1f2937;
            font-size: 0.95rem;
        }
        
        .order-date {
            font-size: 0.8rem;
            color: #6b7280;
            margin-top: 2px;
        }
        
        .order-amount {
            font-weight: 700;
            color: #16a34a;
            font-size: 1rem;
            background: rgba(34, 197, 94, 0.1);
            padding: 6px 12px;
            border-radius: 8px;
        }
        
        .order-status {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }
        
        .order-status.green { background: #22c55e; box-shadow: 0 0 8px rgba(34, 197, 94, 0.5); }
        .order-status.orange { background: #f59e0b; box-shadow: 0 0 8px rgba(245, 158, 11, 0.5); }
        .order-status.blue { background: #3b82f6; box-shadow: 0 0 8px rgba(59, 130, 246, 0.5); }
        .order-status.purple { background: #8b5cf6; box-shadow: 0 0 8px rgba(139, 92, 246, 0.5); }
        .order-status.red { background: #ef4444; box-shadow: 0 0 8px rgba(239, 68, 68, 0.5); }
        .order-status.gray { background: #6b7280; }
        
        /* Reviews Grid - Modern Cards */
        .reviews-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 16px;
        }
        
        .review-card {
            background: linear-gradient(135deg, #fafafa 0%, #f5f5f5 100%);
            border: 2px solid #d1d5db;
            border-radius: 12px;
            padding: 18px;
            transition: all 0.25s ease;
        }
        
        .review-card:hover {
            border-color: #22c55e;
            box-shadow: 0 4px 16px rgba(34, 197, 94, 0.1);
        }
        
        .review-stars {
            display: flex;
            align-items: center;
            gap: 4px;
            margin-bottom: 12px;
        }
        
        .review-stars i {
            color: #e5e7eb;
            font-size: 0.9rem;
        }
        
        .review-stars i.filled {
            color: #f59e0b;
        }
        
        .review-stars .review-date {
            margin-left: auto;
            font-size: 0.75rem;
            color: #9ca3af;
            font-weight: 500;
        }
        
        .review-product {
            font-size: 0.9rem;
            color: #374151;
            margin-bottom: 10px;
            font-weight: 500;
        }
        
        .review-product i {
            color: #22c55e;
            margin-right: 8px;
        }
        
        .review-text {
            font-size: 0.85rem;
            color: #4b5563;
            line-height: 1.6;
            font-style: italic;
            margin: 0;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #9ca3af;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 16px;
            opacity: 0.5;
        }
        
        .empty-state p {
            margin: 0;
            font-size: 0.95rem;
            font-weight: 500;
        }
        
        /* Responsive */
        @media (max-width: 1200px) {
            .stats-cards {
                grid-template-columns: repeat(2, 1fr);
            }
            .profile-info-row {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .profile-hero {
                flex-direction: column;
                text-align: center;
            }
            .profile-meta {
                justify-content: center;
            }
            .stats-cards {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-user"></i> Chi tiết khách hàng</h1>
            <a href="customers.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Quay lại
            </a>
        </div>

        <!-- Profile + Info Section - Cùng 1 hàng -->
        <div class="profile-info-row">
            <!-- Profile Card bên trái -->
            <div class="profile-hero">
                <div class="profile-avatar">
                    <?php echo strtoupper(substr($customer['full_name'], 0, 1)); ?>
                </div>
                <div class="profile-info">
                    <h2><?php echo htmlspecialchars($customer['full_name']); ?></h2>
                    <p class="profile-email"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($customer['email']); ?></p>
                    <p class="profile-phone"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($customer['phone'] ?? 'Chưa cập nhật'); ?></p>
                    <div class="profile-meta">
                        <span class="meta-item"><i class="fas fa-calendar"></i> Tham gia: <?php echo date('d/m/Y', strtotime($customer['created_at'])); ?></span>
                        <?php 
                        $status = $customer['status'] ?? 'active';
                        $statusClass = $status == 'active' ? 'active' : 'blocked';
                        $statusText = $status == 'active' ? 'Đang hoạt động' : 'Đã khóa';
                        ?>
                        <span class="status-badge <?php echo $statusClass; ?>"><i class="fas fa-circle"></i> <?php echo $statusText; ?></span>
                    </div>
                </div>
            </div>

            <!-- Thông tin chi tiết bên phải -->
            <div class="modern-card info-card-right">
                <div class="card-header-modern">
                    <i class="fas fa-id-card"></i>
                    <span>Thông tin chi tiết</span>
                </div>
                <div class="card-body-modern">
                    <div class="info-row">
                        <span class="info-label">Họ và tên</span>
                        <span class="info-value"><?php echo htmlspecialchars($customer['full_name']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Email</span>
                        <span class="info-value"><?php echo htmlspecialchars($customer['email']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Số điện thoại</span>
                        <span class="info-value"><?php echo htmlspecialchars($customer['phone'] ?? 'Chưa cập nhật'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Địa chỉ</span>
                        <span class="info-value"><?php echo htmlspecialchars($customer['address'] ?? 'Chưa cập nhật'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Ngày đăng ký</span>
                        <span class="info-value"><?php echo date('d/m/Y H:i', strtotime($customer['created_at'])); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-cards">
            <div class="stat-card-modern">
                <div class="stat-icon-modern blue">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <div class="stat-content">
                    <span class="stat-number"><?php echo number_format($stats['total_orders']); ?></span>
                    <span class="stat-label">Đơn hàng</span>
                </div>
            </div>
            <div class="stat-card-modern">
                <div class="stat-icon-modern green">
                    <i class="fas fa-wallet"></i>
                </div>
                <div class="stat-content">
                    <span class="stat-number"><?php echo number_format($stats['total_spent'], 0, ',', '.'); ?>đ</span>
                    <span class="stat-label">Tổng chi tiêu</span>
                </div>
            </div>
            <div class="stat-card-modern">
                <div class="stat-icon-modern orange">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-content">
                    <span class="stat-number"><?php echo number_format($stats['avg_order'], 0, ',', '.'); ?>đ</span>
                    <span class="stat-label">TB/đơn hàng</span>
                </div>
            </div>
            <div class="stat-card-modern">
                <div class="stat-icon-modern purple">
                    <i class="fas fa-star"></i>
                </div>
                <div class="stat-content">
                    <span class="stat-number"><?php echo count($reviews); ?></span>
                    <span class="stat-label">Đánh giá</span>
                </div>
            </div>
        </div>

        <!-- Info Grid -->
        <div class="info-cards-grid">
            <!-- Đơn hàng gần đây -->
            <div class="modern-card">
                <div class="card-header-modern">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Đơn hàng gần đây</span>
                    <span class="badge-count"><?php echo count($orders); ?></span>
                </div>
                <div class="card-body-modern">
                    <?php if (empty($orders)): ?>
                        <div class="empty-state">
                            <i class="fas fa-box-open"></i>
                            <p>Chưa có đơn hàng nào</p>
                        </div>
                    <?php else: ?>
                        <div class="orders-list">
                            <?php foreach (array_slice($orders, 0, 5) as $order): ?>
                            <div class="order-item" onclick="viewOrderDetail(<?php echo $order['id']; ?>)">
                                <div class="order-info">
                                    <span class="order-id">#<?php echo htmlspecialchars($order['order_number']); ?></span>
                                    <span class="order-date"><?php echo date('d/m/Y', strtotime($order['created_at'])); ?></span>
                                </div>
                                <div class="order-amount"><?php echo number_format($order['total_amount'], 0, ',', '.'); ?>đ</div>
                                <?php
                                $statusColors = [
                                    'pending' => 'orange',
                                    'confirmed' => 'blue',
                                    'preparing' => 'blue',
                                    'delivering' => 'purple',
                                    'completed' => 'green',
                                    'cancelled' => 'red'
                                ];
                                ?>
                                <span class="order-status <?php echo $statusColors[$order['status']] ?? 'gray'; ?>"></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Đánh giá -->
        <div class="modern-card full-width">
            <div class="card-header-modern">
                <i class="fas fa-star"></i>
                <span>Đánh giá của khách hàng</span>
                <span class="badge-count"><?php echo count($reviews); ?></span>
            </div>
            <div class="card-body-modern">
                <?php if (empty($reviews)): ?>
                    <div class="empty-state">
                        <i class="fas fa-comment-slash"></i>
                        <p>Chưa có đánh giá nào</p>
                    </div>
                <?php else: ?>
                    <div class="reviews-grid">
                        <?php foreach ($reviews as $review): ?>
                        <div class="review-card">
                            <div class="review-stars">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'filled' : ''; ?>"></i>
                                <?php endfor; ?>
                                <span class="review-date"><?php echo date('d/m/Y', strtotime($review['created_at'])); ?></span>
                            </div>
                            <?php if ($review['menu_name']): ?>
                                <p class="review-product"><i class="fas fa-utensils"></i> <?php echo htmlspecialchars($review['menu_name']); ?></p>
                            <?php endif; ?>
                            <?php if ($review['comment']): ?>
                                <p class="review-text">"<?php echo htmlspecialchars($review['comment']); ?>"</p>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Modal Chi tiết đơn hàng -->
    <div id="orderDetailModal" class="modal" style="display: none;">
        <div class="modal-overlay" onclick="closeOrderModal()"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-shopping-cart"></i> Chi tiết đơn hàng</h3>
                <button class="modal-close" onclick="closeOrderModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="orderModalBody">
                <div style="text-align: center; padding: 2rem;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #f97316;"></i>
                </div>
            </div>
        </div>
    </div>
    
    <style>
    .modal {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 10000;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .modal-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(4px);
    }
    .modal-content {
        position: relative;
        background: white;
        border-radius: 20px;
        max-width: 600px;
        width: 90%;
        max-height: 80vh;
        overflow: hidden;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
    }
    .modal-header {
        padding: 20px 24px;
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        color: white;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .modal-header h3 { 
        margin: 0;
        font-size: 1.2rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .modal-close {
        background: rgba(255, 255, 255, 0.2);
        border: none;
        width: 40px;
        height: 40px;
        border-radius: 10px;
        color: white;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 1rem;
    }
    .modal-close:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: scale(1.1);
    }
    .modal-body {
        padding: 24px;
        overflow-y: auto;
        max-height: calc(80vh - 80px);
    }
    .info-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
    }
    .info-box {
        background: #f8fafc;
        padding: 16px;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
    }
    .info-box label {
        display: block;
        font-size: 0.8rem;
        color: #6b7280;
        margin-bottom: 6px;
        font-weight: 500;
    }
    .info-box span {
        font-weight: 700;
        color: #111827;
        font-size: 1rem;
    }
    </style>
    
    <script>
    const ordersData = <?php echo json_encode($orders); ?>;
    
    function viewOrderDetail(orderId) {
        document.getElementById('orderDetailModal').style.display = 'flex';
        
        const order = ordersData.find(o => o.id == orderId);
        if (!order) {
            document.getElementById('orderModalBody').innerHTML = '<p style="text-align:center;color:#ef4444;">Không tìm thấy đơn hàng</p>';
            return;
        }
        
        const statuses = {
            'pending': 'Chờ xác nhận',
            'confirmed': 'Đã xác nhận',
            'preparing': 'Đang chuẩn bị',
            'delivering': 'Đang giao',
            'completed': 'Hoàn thành',
            'cancelled': 'Đã hủy'
        };
        
        const html = `
            <div class="info-grid">
                <div class="info-box">
                    <label>Mã đơn hàng</label>
                    <span>#${order.order_number || order.id}</span>
                </div>
                <div class="info-box">
                    <label>Trạng thái</label>
                    <span>${statuses[order.status] || order.status}</span>
                </div>
                <div class="info-box">
                    <label>Ngày đặt</label>
                    <span>${new Date(order.created_at).toLocaleString('vi-VN')}</span>
                </div>
                <div class="info-box">
                    <label>Tổng tiền</label>
                    <span style="color: #ea580c; font-size: 1.1rem;">${Number(order.total_amount).toLocaleString('vi-VN')}đ</span>
                </div>
                <div class="info-box" style="grid-column: span 2;">
                    <label>Địa chỉ giao hàng</label>
                    <span>${order.delivery_address || 'N/A'}</span>
                </div>
                <div class="info-box">
                    <label>Số điện thoại</label>
                    <span>${order.delivery_phone || 'N/A'}</span>
                </div>
                <div class="info-box">
                    <label>Thanh toán</label>
                    <span>${order.payment_method === 'cash' ? 'Tiền mặt' : order.payment_method === 'transfer' ? 'Chuyển khoản' : order.payment_method}</span>
                </div>
            </div>
        `;
        
        document.getElementById('orderModalBody').innerHTML = html;
    }
    
    function closeOrderModal() {
        document.getElementById('orderDetailModal').style.display = 'none';
    }
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeOrderModal();
    });
    </script>
</body>
</html>
