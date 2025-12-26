<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    echo '<script>window.location.href = "login.php";</script>';
    exit;
}

$db = new Database();
$conn = $db->connect();

// Tự động thêm cột payment_status nếu chưa có
try {
    $checkCol = $conn->query("SHOW COLUMNS FROM orders LIKE 'payment_status'");
    if ($checkCol->rowCount() == 0) {
        $conn->exec("ALTER TABLE orders ADD COLUMN payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending' AFTER payment_method");
        // Cập nhật đơn hàng COD thành paid
        $conn->exec("UPDATE orders SET payment_status = 'paid' WHERE payment_method = 'cash'");
    }
} catch (Exception $e) {
    // Bỏ qua lỗi
}

// Xử lý cập nhật trạng thái đơn hàng
if (isset($_GET['action']) && $_GET['action'] == 'update_status') {
    $order_id = $_GET['id'] ?? 0;
    $status = $_GET['status'] ?? '';
    
    if ($order_id && $status) {
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$status, $order_id]);
    }
    
    echo '<script>window.location.href = "orders.php";</script>';
    exit;
}

// Xử lý xác nhận thanh toán chuyển khoản
if (isset($_GET['action']) && $_GET['action'] == 'confirm_payment') {
    $order_id = $_GET['id'] ?? 0;
    
    if ($order_id) {
        // Kiểm tra cột payment_status có tồn tại không
        try {
            $checkCol = $conn->query("SHOW COLUMNS FROM orders LIKE 'payment_status'");
            if ($checkCol->rowCount() > 0) {
                $stmt = $conn->prepare("UPDATE orders SET payment_status = 'paid' WHERE id = ?");
                $stmt->execute([$order_id]);
            } else {
                // Nếu chưa có cột, tự động thêm cột
                $conn->exec("ALTER TABLE orders ADD COLUMN payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending' AFTER payment_method");
                $stmt = $conn->prepare("UPDATE orders SET payment_status = 'paid' WHERE id = ?");
                $stmt->execute([$order_id]);
            }
        } catch (Exception $e) {
            // Bỏ qua lỗi
        }
    }
    
    echo '<script>window.location.href = "orders.php";</script>';
    exit;
}

// Lọc
$status_filter = $_GET['status'] ?? 'all';
$payment_filter = $_GET['payment'] ?? 'all';
$where = "1=1";
if ($status_filter != 'all') {
    $where .= " AND o.status = '$status_filter'";
}
if ($payment_filter != 'all') {
    $where .= " AND o.payment_method = '$payment_filter'";
}

// Lấy danh sách đơn hàng
$stmt = $conn->query("
    SELECT o.*, c.full_name, c.email, c.phone
    FROM orders o
    LEFT JOIN customers c ON o.customer_id = c.id
    WHERE $where
    ORDER BY o.created_at DESC
");
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Thống kê
$stats = $conn->query("
    SELECT 
        COUNT(*) as total,
        SUM(total_amount) as revenue,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
        SUM(CASE WHEN status = 'delivering' THEN 1 ELSE 0 END) as delivering,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
    FROM orders
    WHERE DATE(created_at) = CURDATE()
")->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý đơn hàng - Admin</title>
    <link rel="stylesheet" href="../assets/css/admin-dark-modern.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
    /* Modern Green & White Theme for Orders */
    body { background: #f8fafc !important; }
    .main-content { background: #f8fafc !important; padding: 2rem; }
    
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        padding-bottom: 1.5rem;
        border-bottom: 2px solid #e2e8f0;
    }
    .page-header h1 {
        color: #1f2937 !important;
        font-size: 1.75rem;
        font-weight: 800;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin: 0;
    }
    .page-header h1 i { color: #22c55e; }
    .btn-secondary {
        background: white !important;
        color: #374151 !important;
        border: 2px solid #e5e7eb !important;
        padding: 0.6rem 1.25rem;
        border-radius: 10px;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.2s;
    }
    .btn-secondary:hover {
        border-color: #22c55e !important;
        color: #22c55e !important;
    }
    
    /* Stats Cards - Horizontal Layout like Discount Page */
    .stats-grid {
        display: grid !important;
        grid-template-columns: repeat(4, 1fr) !important;
        gap: 1rem !important;
        margin-bottom: 1.5rem !important;
    }
    .stat-card {
        background: white !important;
        border-radius: 14px !important;
        padding: 1.25rem 1.5rem !important;
        display: flex !important;
        align-items: center !important;
        gap: 1rem !important;
        border: 1px solid #e5e7eb !important;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04) !important;
        transition: all 0.3s !important;
    }
    .stat-card:hover {
        transform: translateY(-3px) !important;
        box-shadow: 0 8px 25px rgba(0,0,0,0.1) !important;
    }
    .stat-icon {
        width: 52px !important;
        height: 52px !important;
        border-radius: 12px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: 1.3rem !important;
        color: white !important;
        flex-shrink: 0 !important;
    }
    
    /* Colorful variants - with !important */
    .stat-card.stat-primary .stat-icon { 
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%) !important; 
    }
    .stat-card.stat-primary:hover { border-color: #22c55e !important; }
    
    .stat-card.stat-success .stat-icon { 
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%) !important; 
    }
    .stat-card.stat-success:hover { border-color: #22c55e !important; }
    
    .stat-card.stat-warning .stat-icon { 
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important; 
    }
    .stat-card.stat-warning:hover { border-color: #f59e0b !important; }
    
    .stat-card.stat-info .stat-icon { 
        background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%) !important; 
    }
    .stat-card.stat-info:hover { border-color: #8b5cf6 !important; }
    
    .stat-content h3 {
        font-size: 1.75rem !important;
        font-weight: 800 !important;
        color: #1f2937 !important;
        margin: 0 !important;
        line-height: 1 !important;
    }
    .stat-content p {
        color: #6b7280 !important;
        font-size: 0.8rem !important;
        margin: 0.2rem 0 0 !important;
        font-weight: 500 !important;
    }
    
    @media (max-width: 1200px) {
        .stats-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 600px) {
        .stats-grid { grid-template-columns: 1fr; }
    }
    
    /* Filter Card - Modern Design */
    .filter-card {
        background: white;
        border-radius: 16px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        border: 2px solid #e5e7eb;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    }
    .filter-form {
        display: flex;
        gap: 1.5rem;
        align-items: flex-end;
        flex-wrap: wrap;
    }
    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        flex: 1;
        min-width: 200px;
    }
    .filter-group label {
        font-size: 0.85rem;
        font-weight: 700;
        color: #374151;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .filter-group label i {
        color: #22c55e;
    }
    .filter-group select {
        padding: 0.85rem 2.5rem 0.85rem 1rem;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        font-size: 0.95rem;
        color: #374151;
        background: white url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2322c55e'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E") no-repeat right 1rem center;
        background-size: 1.25rem;
        cursor: pointer;
        -webkit-appearance: none;
        -moz-appearance: none;
        transition: all 0.2s;
        font-weight: 500;
    }
    .filter-group select:hover {
        border-color: #22c55e;
    }
    .filter-group select:focus {
        outline: none;
        border-color: #22c55e;
        box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.15);
    }
    .filter-actions {
        display: flex;
        align-items: center;
    }
    .reset-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.85rem 1.5rem;
        background: #f3f4f6;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        color: #6b7280;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s;
    }
    .reset-btn:hover {
        background: #fee2e2;
        border-color: #fecaca;
        color: #dc2626;
    }
    
    /* Orders Table Card */
    .orders-card {
        background: white;
        border-radius: 16px;
        border: 2px solid #e5e7eb;
        overflow: hidden;
    }
    .orders-card-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 2px solid #f3f4f6;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    .orders-card-header h2 {
        margin: 0;
        font-size: 1.1rem;
        font-weight: 700;
        color: #1f2937;
    }
    .orders-card-header i { color: #22c55e; }
    
    /* Table */
    .orders-table {
        width: 100%;
        border-collapse: collapse;
    }
    .orders-table th {
        background: #f9fafb;
        padding: 1rem 1rem;
        text-align: left;
        font-size: 0.8rem;
        font-weight: 700;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 2px solid #e5e7eb;
    }
    .orders-table td {
        padding: 1rem;
        border-bottom: 1px solid #f3f4f6;
        color: #374151;
        font-size: 0.9rem;
        vertical-align: middle;
    }
    .orders-table tbody tr {
        transition: all 0.2s;
    }
    .orders-table tbody tr:hover {
        background: #f0fdf4;
    }
    .order-id {
        font-weight: 700;
        color: #1f2937;
        font-size: 0.85rem;
    }
    .customer-info {
        display: flex;
        flex-direction: column;
        gap: 0.2rem;
    }
    .customer-name {
        font-weight: 600;
        color: #1f2937;
    }
    .customer-phone {
        font-size: 0.8rem;
        color: #6b7280;
    }
    .order-amount {
        font-weight: 700;
        color: #22c55e;
        font-size: 0.95rem;
    }
    .order-date {
        font-size: 0.85rem;
        color: #6b7280;
    }
    
    /* Payment Badges */
    .payment-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.4rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        white-space: nowrap;
    }
    .payment-cash {
        background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
        color: #15803d;
    }
    .payment-transfer {
        background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
        color: #15803d;
    }
    
    /* Status Badges */
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.4rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .status-pending {
        background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
        color: #92400e;
    }
    .status-confirmed {
        background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
        color: #15803d;
    }
    .status-preparing {
        background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
        color: #5b21b6;
    }
    .status-delivering {
        background: linear-gradient(135deg, #cffafe 0%, #a5f3fc 100%);
        color: #0e7490;
    }
    .status-completed {
        background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
        color: #15803d;
    }
    .status-cancelled {
        background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
        color: #b91c1c;
    }
    
    /* Mini Timeline - Rõ ràng hơn */
    .mini-timeline {
        display: flex;
        align-items: center;
        gap: 2px;
        margin-top: 10px;
        padding: 8px 10px;
        background: #f8fafc;
        border-radius: 8px;
        border: 1px solid #e5e7eb;
    }
    .mini-step {
        display: flex;
        align-items: center;
        gap: 2px;
    }
    .mini-step .step-dot {
        width: 22px;
        height: 22px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.65rem;
        background: #e5e7eb;
        color: #9ca3af;
        transition: all 0.3s;
        flex-shrink: 0;
    }
    .mini-step.done .step-dot {
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        color: white;
        box-shadow: 0 2px 6px rgba(34, 197, 94, 0.4);
    }
    .mini-step.current .step-dot {
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        color: white;
        box-shadow: 0 2px 6px rgba(34, 197, 94, 0.4);
        animation: pulse-dot 1.5s infinite;
    }
    @keyframes pulse-dot {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.15); }
    }
    .mini-step .step-line {
        width: 12px;
        height: 3px;
        background: #e5e7eb;
        border-radius: 2px;
    }
    .mini-step.done .step-line {
        background: #22c55e;
    }
    .mini-step .step-name {
        display: none;
    }
    
    /* Tooltip for step names */
    .mini-step .step-dot {
        position: relative;
    }
    .mini-step .step-dot:hover::after {
        content: attr(data-title);
        position: absolute;
        bottom: 100%;
        left: 50%;
        transform: translateX(-50%);
        background: #1f2937;
        color: white;
        padding: 4px 8px;
        border-radius: 6px;
        font-size: 0.7rem;
        white-space: nowrap;
        margin-bottom: 5px;
        z-index: 100;
    }
    
    /* Cancelled Notice */
    .cancelled-notice {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-top: 8px;
        padding: 6px 12px;
        background: #fef2f2;
        border: 1px solid #fecaca;
        border-radius: 8px;
        color: #dc2626;
        font-size: 0.8rem;
        font-weight: 600;
    }
    
    /* Action Buttons */
    .action-btns {
        display: flex;
        gap: 0.5rem;
    }
    .action-btn {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        transition: all 0.2s;
        text-decoration: none;
    }
    .action-btn:hover {
        transform: scale(1.1);
    }
    .btn-confirm {
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        color: white;
    }
    .btn-prepare {
        background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
        color: white;
    }
    .btn-deliver {
        background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
        color: white;
    }
    .btn-complete {
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        color: white;
    }
    .btn-view {
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        color: white;
    }
    .btn-cancel {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
    }
    .btn-payment {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        color: white;
    }
    
    /* Payment Status Badge */
    .payment-status-badge {
        display: block;
        margin-top: 6px;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 0.7rem;
        font-weight: 600;
        text-align: center;
    }
    .payment-status-badge.pending {
        background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
        color: #92400e;
        border: 1px solid #fcd34d;
    }
    .payment-status-badge.paid {
        background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
        color: #15803d;
        border: 1px solid #86efac;
    }
    
    /* Responsive */
    @media (max-width: 1200px) {
        .stats-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 768px) {
        .stats-grid { grid-template-columns: 1fr; }
        .filter-card { flex-direction: column; align-items: stretch; }
        .filter-group select { width: 100%; }
    }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
        
    <div class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-shopping-cart"></i> Quản lý đơn hàng</h1>
            <a href="../index.php" target="_blank" class="btn-secondary">
                <i class="fas fa-external-link-alt"></i> Xem Website
            </a>
        </div>
            
        <!-- Thống kê hôm nay - Style giống trang giảm giá -->
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.25rem; margin-bottom: 1.5rem;">
            <div style="background: white; border-radius: 14px; padding: 1.25rem 1.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.08); display: flex; align-items: center; gap: 1.25rem; border: 2px solid #d1d5db; transition: all 0.2s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(0,0,0,0.12)'; this.style.borderColor='#22c55e';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.08)'; this.style.borderColor='#d1d5db';">
                <div style="width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; color: white; background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); flex-shrink: 0;">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <div>
                    <h3 style="font-size: 1.75rem; font-weight: 800; color: #1f2937; margin: 0; line-height: 1;"><?php echo $stats['total']; ?></h3>
                    <p style="color: #6b7280; margin: 0.25rem 0 0; font-size: 0.9rem; font-weight: 500;">Đơn hàng hôm nay</p>
                </div>
            </div>
            <div style="background: white; border-radius: 14px; padding: 1.25rem 1.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.08); display: flex; align-items: center; gap: 1.25rem; border: 2px solid #d1d5db; transition: all 0.2s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(0,0,0,0.12)'; this.style.borderColor='#22c55e';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.08)'; this.style.borderColor='#d1d5db';">
                <div style="width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; color: white; background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); flex-shrink: 0;">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div>
                    <h3 style="font-size: 1.5rem; font-weight: 800; color: #1f2937; margin: 0; line-height: 1;"><?php echo number_format($stats['revenue']); ?>đ</h3>
                    <p style="color: #6b7280; margin: 0.25rem 0 0; font-size: 0.9rem; font-weight: 500;">Doanh thu hôm nay</p>
                </div>
            </div>
            <div style="background: white; border-radius: 14px; padding: 1.25rem 1.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.08); display: flex; align-items: center; gap: 1.25rem; border: 2px solid #d1d5db; transition: all 0.2s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(0,0,0,0.12)'; this.style.borderColor='#f59e0b';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.08)'; this.style.borderColor='#d1d5db';">
                <div style="width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; color: white; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); flex-shrink: 0;">
                    <i class="fas fa-clock"></i>
                </div>
                <div>
                    <h3 style="font-size: 1.75rem; font-weight: 800; color: #1f2937; margin: 0; line-height: 1;"><?php echo $stats['pending']; ?></h3>
                    <p style="color: #6b7280; margin: 0.25rem 0 0; font-size: 0.9rem; font-weight: 500;">Chờ xác nhận</p>
                </div>
            </div>
            <div style="background: white; border-radius: 14px; padding: 1.25rem 1.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.08); display: flex; align-items: center; gap: 1.25rem; border: 2px solid #d1d5db; transition: all 0.2s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(0,0,0,0.12)'; this.style.borderColor='#8b5cf6';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.08)'; this.style.borderColor='#d1d5db';">
                <div style="width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; color: white; background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); flex-shrink: 0;">
                    <i class="fas fa-truck"></i>
                </div>
                <div>
                    <h3 style="font-size: 1.75rem; font-weight: 800; color: #1f2937; margin: 0; line-height: 1;"><?php echo $stats['delivering']; ?></h3>
                    <p style="color: #6b7280; margin: 0.25rem 0 0; font-size: 0.9rem; font-weight: 500;">Đang giao</p>
                </div>
            </div>
        </div>
            
            <!-- Bộ lọc - Modern Design -->
            <div class="filter-card">
                <form method="GET" class="filter-form">
                    <div class="filter-group">
                        <label><i class="fas fa-filter"></i> Trạng thái</label>
                        <select name="status" onchange="this.form.submit()">
                            <option value="all">📋 Tất cả trạng thái</option>
                            <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>🕐 Chờ xác nhận</option>
                            <option value="confirmed" <?php echo $status_filter == 'confirmed' ? 'selected' : ''; ?>>✅ Đã xác nhận</option>
                            <option value="preparing" <?php echo $status_filter == 'preparing' ? 'selected' : ''; ?>>🍳 Đang chuẩn bị</option>
                            <option value="delivering" <?php echo $status_filter == 'delivering' ? 'selected' : ''; ?>>🏍️ Đang giao</option>
                            <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>✔️ Hoàn thành</option>
                            <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>❌ Đã hủy</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label><i class="fas fa-credit-card"></i> Thanh toán</label>
                        <select name="payment" onchange="this.form.submit()">
                            <option value="all">💳 Tất cả</option>
                            <option value="cash" <?php echo $payment_filter == 'cash' ? 'selected' : ''; ?>>💵 Tiền mặt (COD)</option>
                            <option value="transfer" <?php echo $payment_filter == 'transfer' ? 'selected' : ''; ?>>🏦 Chuyển khoản</option>
                        </select>
                    </div>
                    <div class="filter-actions">
                        <a href="orders.php" class="reset-btn"><i class="fas fa-redo"></i> Đặt lại</a>
                    </div>
                </form>
            </div>
            
            <!-- Danh sách đơn hàng -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-list"></i> Danh sách đơn hàng</h2>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Mã đơn</th>
                                    <th>Khách hàng</th>
                                    <th>Địa chỉ giao</th>
                                    <th>Tổng tiền</th>
                                    <th>Thanh toán</th>
                                    <th>Trạng thái</th>
                                    <th>Ngày đặt</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($orders as $order): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></td>
                                    <td>
                                        <?php echo htmlspecialchars($order['full_name'] ?? 'N/A'); ?><br>
                                        <small><?php echo htmlspecialchars($order['delivery_phone']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($order['delivery_address']); ?></td>
                                    <td><strong><?php echo number_format($order['total_amount']); ?>đ</strong></td>
                                    <td>
                                        <?php if ($order['payment_method'] == 'transfer'): ?>
                                            <span class="payment-badge payment-transfer">
                                                <i class="fas fa-university"></i> Chuyển khoản
                                            </span>
                                            <?php 
                                            // Kiểm tra trạng thái thanh toán
                                            $payment_status = $order['payment_status'] ?? 'pending';
                                            if ($payment_status === 'pending'): ?>
                                            <span class="payment-status-badge pending">
                                                <i class="fas fa-clock"></i> Chờ xác nhận
                                            </span>
                                            <?php elseif ($payment_status === 'paid'): ?>
                                            <span class="payment-status-badge paid">
                                                <i class="fas fa-check-circle"></i> Đã xác nhận
                                            </span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="payment-badge payment-cash">
                                                <i class="fas fa-money-bill-wave"></i> Tiền mặt (COD)
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $status_labels = [
                                            'pending' => ['label' => 'Chờ xác nhận', 'class' => 'status-pending', 'icon' => 'fa-clock'],
                                            'confirmed' => ['label' => 'Đã xác nhận', 'class' => 'status-confirmed', 'icon' => 'fa-check'],
                                            'preparing' => ['label' => 'Đang chuẩn bị', 'class' => 'status-preparing', 'icon' => 'fa-utensils'],
                                            'delivering' => ['label' => 'Đang giao', 'class' => 'status-delivering', 'icon' => 'fa-motorcycle'],
                                            'completed' => ['label' => 'Hoàn thành', 'class' => 'status-completed', 'icon' => 'fa-check-double'],
                                            'cancelled' => ['label' => 'Đã hủy', 'class' => 'status-cancelled', 'icon' => 'fa-times']
                                        ];
                                        $current_status = $status_labels[$order['status']] ?? ['label' => $order['status'], 'class' => '', 'icon' => 'fa-question'];
                                        ?>
                                        <span class="status-badge <?php echo $current_status['class']; ?>">
                                            <i class="fas <?php echo $current_status['icon']; ?>"></i>
                                            <?php echo $current_status['label']; ?>
                                        </span>
                                        
                                        <!-- Mini Timeline với icons -->
                                        <?php if ($order['status'] != 'cancelled'): ?>
                                        <div class="mini-timeline">
                                            <?php
                                            $steps = [
                                                'pending' => ['icon' => 'fa-clock', 'name' => 'Chờ'],
                                                'confirmed' => ['icon' => 'fa-check', 'name' => 'Xác nhận'],
                                                'preparing' => ['icon' => 'fa-utensils', 'name' => 'Chuẩn bị'],
                                                'delivering' => ['icon' => 'fa-motorcycle', 'name' => 'Giao'],
                                                'completed' => ['icon' => 'fa-check-double', 'name' => 'Xong']
                                            ];
                                            $step_keys = array_keys($steps);
                                            $current_index = array_search($order['status'], $step_keys);
                                            foreach ($step_keys as $i => $step_key):
                                                $step = $steps[$step_key];
                                                $is_done = $i < $current_index;
                                                $is_current = $i == $current_index;
                                                $class = $is_done ? 'done' : ($is_current ? 'current' : '');
                                            ?>
                                            <div class="mini-step <?php echo $class; ?>">
                                                <div class="step-dot" data-title="<?php echo $status_labels[$step_key]['label']; ?>">
                                                    <?php if ($is_done): ?>
                                                        <i class="fas fa-check"></i>
                                                    <?php else: ?>
                                                        <i class="fas <?php echo $step['icon']; ?>"></i>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if ($i < count($step_keys) - 1): ?>
                                                <div class="step-line"></div>
                                                <?php endif; ?>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php else: ?>
                                        <div class="cancelled-notice">
                                            <i class="fas fa-ban"></i> Đã hủy
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                                    <td>
                                        <div class="action-buttons" style="display: flex; gap: 6px; flex-wrap: wrap;">
                                            <?php 
                                            // Nút xác nhận thanh toán cho đơn chuyển khoản chưa xác nhận
                                            $payment_status = $order['payment_status'] ?? 'pending';
                                            if ($order['payment_method'] == 'transfer' && $payment_status === 'pending'): ?>
                                            <a href="?action=confirm_payment&id=<?php echo $order['id']; ?>" class="action-btn btn-payment" title="Xác nhận thanh toán" onclick="return confirm('Xác nhận đã nhận được tiền chuyển khoản?')">
                                                <i class="fas fa-dollar-sign"></i>
                                            </a>
                                            <?php endif; ?>
                                            
                                            <?php if($order['status'] == 'pending'): ?>
                                            <a href="?action=update_status&id=<?php echo $order['id']; ?>&status=confirmed" class="action-btn btn-confirm" title="Xác nhận đơn">
                                                <i class="fas fa-check"></i>
                                            </a>
                                            <?php elseif($order['status'] == 'confirmed'): ?>
                                            <a href="?action=update_status&id=<?php echo $order['id']; ?>&status=preparing" class="action-btn btn-prepare" title="Bắt đầu chuẩn bị">
                                                <i class="fas fa-utensils"></i>
                                            </a>
                                            <?php elseif($order['status'] == 'preparing'): ?>
                                            <a href="?action=update_status&id=<?php echo $order['id']; ?>&status=delivering" class="action-btn btn-deliver" title="Giao hàng">
                                                <i class="fas fa-motorcycle"></i>
                                            </a>
                                            <?php elseif($order['status'] == 'delivering'): ?>
                                            <a href="?action=update_status&id=<?php echo $order['id']; ?>&status=completed" class="action-btn btn-complete" title="Hoàn thành">
                                                <i class="fas fa-check-double"></i>
                                            </a>
                                            <?php endif; ?>
                                            <button onclick="viewOrderDetail(<?php echo $order['id']; ?>)" class="action-btn btn-view" title="Chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if($order['status'] != 'completed' && $order['status'] != 'cancelled'): ?>
                                            <a href="?action=update_status&id=<?php echo $order['id']; ?>&status=cancelled" class="action-btn btn-cancel" title="Hủy đơn" onclick="return confirm('Bạn có chắc muốn hủy đơn hàng này?')">
                                                <i class="fas fa-times"></i>
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
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
                    <p style="margin-top: 1rem; color: #6b7280;">Đang tải...</p>
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
        animation: fadeIn 0.3s;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
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
        border-radius: 24px;
        max-width: 700px;
        width: 90%;
        max-height: 85vh;
        overflow: hidden;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
        animation: slideUp 0.3s;
    }
    
    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .modal-header {
        padding: 1.5rem 2rem;
        background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
        color: white;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .modal-header h3 {
        margin: 0;
        font-size: 1.3rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 0.8rem;
    }
    
    .modal-close {
        background: rgba(255, 255, 255, 0.2);
        border: none;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        color: white;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
    }
    
    .modal-close:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: rotate(90deg);
    }
    
    .modal-body {
        padding: 1.5rem 2rem;
        overflow-y: auto;
        max-height: calc(85vh - 80px);
    }
    
    .order-info-section {
        margin-bottom: 1.5rem;
    }
    
    .order-info-section h4 {
        font-size: 1rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #f3f4f6;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .order-info-section h4 i {
        color: #f97316;
    }
    
    .info-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }
    
    .info-item {
        background: #f9fafb;
        padding: 0.75rem 1rem;
        border-radius: 8px;
    }
    
    .info-item label {
        display: block;
        font-size: 0.8rem;
        color: #6b7280;
        font-weight: 600;
        margin-bottom: 0.25rem;
    }
    
    .info-item span {
        font-size: 0.95rem;
        color: #1f2937;
        font-weight: 500;
    }
    
    .order-items-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .order-items-table th,
    .order-items-table td {
        padding: 0.75rem;
        text-align: left;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .order-items-table th {
        background: #f9fafb;
        font-weight: 600;
        color: #374151;
        font-size: 0.85rem;
    }
    
    .order-items-table td {
        font-size: 0.9rem;
    }
    
    .order-total {
        text-align: right;
        padding: 1rem;
        background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);
        border-radius: 8px;
        margin-top: 1rem;
    }
    
    .order-total strong {
        font-size: 1.2rem;
        color: #ea580c;
    }
    
    /* Payment badges */
    .payment-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        white-space: nowrap;
    }
    
    .payment-transfer {
        background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
        color: #15803d;
        border: 1px solid #86efac;
    }
    
    .payment-transfer i {
        color: #16a34a;
    }
    
    .payment-cash {
        background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
        color: #15803d;
        border: 1px solid #86efac;
    }
    
    .payment-cash i {
        color: #16a34a;
    }
    </style>
    
    <script>
    // Lưu trữ dữ liệu đơn hàng
    const ordersData = <?php echo json_encode($orders); ?>;
    
    async function viewOrderDetail(orderId) {
        document.getElementById('orderDetailModal').style.display = 'flex';
        
        // Tìm đơn hàng trong dữ liệu
        const order = ordersData.find(o => o.id == orderId);
        
        if (!order) {
            document.getElementById('orderModalBody').innerHTML = `
                <div style="text-align: center; padding: 2rem; color: #ef4444;">
                    <i class="fas fa-exclamation-circle" style="font-size: 2rem;"></i>
                    <p style="margin-top: 1rem;">Không tìm thấy đơn hàng</p>
                </div>
            `;
            return;
        }
        
        const paymentMethods = {
            'cash': 'Tiền mặt',
            'transfer': 'Chuyển khoản',
            'card': 'Thẻ'
        };
        
        const statuses = {
            'pending': '<span class="badge badge-warning">Chờ xác nhận</span>',
            'confirmed': '<span class="badge badge-info">Đã xác nhận</span>',
            'preparing': '<span class="badge badge-primary">Đang chuẩn bị</span>',
            'delivering': '<span class="badge badge-info">Đang giao</span>',
            'completed': '<span class="badge badge-success">Hoàn thành</span>',
            'cancelled': '<span class="badge badge-danger">Đã hủy</span>'
        };
        
        const html = `
            <div class="order-info-section">
                <h4><i class="fas fa-info-circle"></i> Thông tin đơn hàng</h4>
                <div class="info-grid">
                    <div class="info-item">
                        <label>Mã đơn hàng</label>
                        <span><strong>${order.order_number || '#' + order.id}</strong></span>
                    </div>
                    <div class="info-item">
                        <label>Trạng thái</label>
                        <span>${statuses[order.status] || order.status}</span>
                    </div>
                    <div class="info-item">
                        <label>Ngày đặt</label>
                        <span>${new Date(order.created_at).toLocaleString('vi-VN')}</span>
                    </div>
                    <div class="info-item">
                        <label>Thanh toán</label>
                        <span>${order.payment_method === 'transfer' 
                            ? '<span class="payment-badge payment-transfer"><i class="fas fa-university"></i> Chuyển khoản</span>' 
                            : '<span class="payment-badge payment-cash"><i class="fas fa-money-bill-wave"></i> Tiền mặt (COD)</span>'}</span>
                    </div>
                </div>
            </div>
            
            <div class="order-info-section">
                <h4><i class="fas fa-user"></i> Thông tin khách hàng</h4>
                <div class="info-grid">
                    <div class="info-item">
                        <label>Họ tên</label>
                        <span>${order.full_name || 'N/A'}</span>
                    </div>
                    <div class="info-item">
                        <label>Số điện thoại</label>
                        <span>${order.delivery_phone || 'N/A'}</span>
                    </div>
                    <div class="info-item" style="grid-column: span 2;">
                        <label>Địa chỉ giao hàng</label>
                        <span>${order.delivery_address || 'N/A'}</span>
                    </div>
                    ${order.note ? `
                    <div class="info-item" style="grid-column: span 2;">
                        <label>Ghi chú</label>
                        <span>${order.note}</span>
                    </div>
                    ` : ''}
                </div>
            </div>
            
            <div class="order-total">
                <span>Tổng tiền: </span>
                <strong>${Number(order.total_amount).toLocaleString('vi-VN')}đ</strong>
            </div>
        `;
        
        document.getElementById('orderModalBody').innerHTML = html;
    }
    
    function closeOrderModal() {
        document.getElementById('orderDetailModal').style.display = 'none';
    }
    
    // Đóng modal khi nhấn ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeOrderModal();
        }
    });
    </script>
</body>
</html>
