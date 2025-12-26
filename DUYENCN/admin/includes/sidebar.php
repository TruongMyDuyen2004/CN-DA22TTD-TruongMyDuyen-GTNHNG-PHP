<?php
// Lấy số lượng thông báo
require_once __DIR__ . '/../../config/database.php';
$db = new Database();
$conn = $db->connect();

// Đếm đơn hàng chờ xác nhận
$pending_orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'")->fetch()['count'] ?? 0;

// Đếm đặt bàn chờ xác nhận
$pending_reservations = $conn->query("SELECT COUNT(*) as count FROM reservations WHERE status = 'pending'")->fetch()['count'] ?? 0;

// Đếm liên hệ chưa đọc
$unread_contacts = $conn->query("SELECT COUNT(*) as count FROM contacts WHERE status = 'new'")->fetch()['count'] ?? 0;

// Đếm yêu cầu nạp tiền chờ duyệt
$pending_topups = 0;
try {
    $pending_topups = $conn->query("SELECT COUNT(*) as count FROM topup_requests WHERE status = 'waiting'")->fetch()['count'] ?? 0;
} catch (Exception $e) {
    // Bảng chưa tồn tại
}
?>
<!-- Green Theme Override CSS -->
<link rel="stylesheet" href="../assets/css/admin-green-override.css?v=<?php echo time(); ?>">
<aside class="sidebar">
    <div class="sidebar-header">
        <h2>Ngon Gallery</h2>
        <p>Admin Panel</p>
    </div>
    
    <nav class="sidebar-nav">
        <a href="index.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>
        
        <a href="customers.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'customers.php' || basename($_SERVER['PHP_SELF']) == 'customer_detail.php' ? 'active' : ''; ?>">
            <i class="fas fa-users"></i>
            <span>Khách hàng</span>
        </a>
        
        <a href="menu-manage.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'menu-manage.php' || basename($_SERVER['PHP_SELF']) == 'menu-table.php' || basename($_SERVER['PHP_SELF']) == 'menu.php' || basename($_SERVER['PHP_SELF']) == 'menu-simple.php' ? 'active' : ''; ?>">
            <i class="fas fa-utensils"></i>
            <span>Thực đơn</span>
        </a>
        
        <a href="stock-manage.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'stock-manage.php' ? 'active' : ''; ?>">
            <i class="fas fa-boxes"></i>
            <span>Tồn kho</span>
        </a>
        
        <a href="discount-manage.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'discount-manage.php' ? 'active' : ''; ?>">
            <i class="fas fa-percent"></i>
            <span>Giảm giá</span>
        </a>
        
        <a href="categories.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>">
            <i class="fas fa-folder-open"></i>
            <span>Danh mục</span>
        </a>
        
        <a href="combo-promotions.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'combo-promotions.php' ? 'active' : ''; ?>">
            <i class="fas fa-tags"></i>
            <span>Combo KM</span>
        </a>
        
        <a href="orders.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>">
            <i class="fas fa-shopping-cart"></i>
            <span>Đơn hàng</span>
            <?php if($pending_orders > 0): ?>
            <span class="badge-notification"><?php echo $pending_orders; ?></span>
            <?php endif; ?>
        </a>
        
        <a href="reservations.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'reservations.php' ? 'active' : ''; ?>">
            <i class="fas fa-calendar-alt"></i>
            <span>Đặt bàn</span>
            <?php if($pending_reservations > 0): ?>
            <span class="badge-notification pulse"><?php echo $pending_reservations; ?></span>
            <?php endif; ?>
        </a>
        
        <a href="reviews.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'reviews.php' ? 'active' : ''; ?>">
            <i class="fas fa-star"></i>
            <span>Đánh giá</span>
        </a>
        
        <a href="contacts.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'contacts.php' ? 'active' : ''; ?>">
            <i class="fas fa-envelope"></i>
            <span>Liên hệ</span>
            <?php if($unread_contacts > 0): ?>
            <span class="badge-notification"><?php echo $unread_contacts; ?></span>
            <?php endif; ?>
        </a>
        
        <a href="member-cards.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'member-cards.php' || basename($_SERVER['PHP_SELF']) == 'card-transactions.php' ? 'active' : ''; ?>">
            <i class="fas fa-credit-card"></i>
            <span>Thẻ thành viên</span>
        </a>
        
        <a href="topup-requests.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'topup-requests.php' ? 'active' : ''; ?>">
            <i class="fas fa-money-bill-wave"></i>
            <span>Yêu cầu nạp tiền</span>
            <?php if($pending_topups > 0): ?>
            <span class="badge-notification pulse"><?php echo $pending_topups; ?></span>
            <?php endif; ?>
        </a>
        
        <a href="settings.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">>
            <i class="fas fa-cog"></i>
            <span>Cài đặt</span>
        </a>
    </nav>
    
    <div class="sidebar-footer">
        <div class="admin-info">
            <i class="fas fa-user-shield"></i>
            <div>
                <strong><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></strong>
                <span>Quản trị viên</span>
            </div>
        </div>
        <a href="logout.php" class="btn-logout">
            <i class="fas fa-sign-out-alt"></i>
            Đăng xuất
        </a>
    </div>
</aside>

<!-- Auto-update notifications -->
<script src="includes/notification-counter.js"></script>
