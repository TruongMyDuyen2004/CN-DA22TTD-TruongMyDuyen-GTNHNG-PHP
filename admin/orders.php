<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    echo '<script>window.location.href = "login.php";</script>';
    exit;
}

$db = new Database();
$conn = $db->connect();

// Xử lý cập nhật trạng thái
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

// Lọc
$status_filter = $_GET['status'] ?? 'all';
$where = "1=1";
if ($status_filter != 'all') {
    $where .= " AND o.status = '$status_filter'";
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
                <h1><i class="fas fa-shopping-cart"></i> Quản lý đơn hàng</h1>
            </div>
            
            <!-- Thống kê hôm nay -->
            <div class="stats-grid">
                <div class="stat-card stat-primary">
                    <div class="stat-icon"><i class="fas fa-shopping-bag"></i></div>
                    <div class="stat-content">
                        <h3><?php echo $stats['total']; ?></h3>
                        <p>Đơn hàng hôm nay</p>
                    </div>
                </div>
                <div class="stat-card stat-success">
                    <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['revenue']); ?>đ</h3>
                        <p>Doanh thu hôm nay</p>
                    </div>
                </div>
                <div class="stat-card stat-warning">
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                    <div class="stat-content">
                        <h3><?php echo $stats['pending']; ?></h3>
                        <p>Chờ xác nhận</p>
                    </div>
                </div>
                <div class="stat-card stat-info">
                    <div class="stat-icon"><i class="fas fa-truck"></i></div>
                    <div class="stat-content">
                        <h3><?php echo $stats['delivering']; ?></h3>
                        <p>Đang giao</p>
                    </div>
                </div>
            </div>
            
            <!-- Bộ lọc -->
            <div class="card">
                <div class="card-body">
                    <form method="GET" class="filter-form">
                        <div class="form-group">
                            <label>Trạng thái</label>
                            <select name="status" onchange="this.form.submit()">
                                <option value="all">Tất cả</option>
                                <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Chờ xác nhận</option>
                                <option value="confirmed" <?php echo $status_filter == 'confirmed' ? 'selected' : ''; ?>>Đã xác nhận</option>
                                <option value="preparing" <?php echo $status_filter == 'preparing' ? 'selected' : ''; ?>>Đang chuẩn bị</option>
                                <option value="delivering" <?php echo $status_filter == 'delivering' ? 'selected' : ''; ?>>Đang giao</option>
                                <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Hoàn thành</option>
                                <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Đã hủy</option>
                            </select>
                        </div>
                    </form>
                </div>
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
                                        <?php
                                        $payment_methods = [
                                            'cash' => 'Tiền mặt',
                                            'transfer' => 'Chuyển khoản',
                                            'card' => 'Thẻ'
                                        ];
                                        echo $payment_methods[$order['payment_method']] ?? $order['payment_method'];
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        $badges = [
                                            'pending' => '<span class="badge badge-warning">Chờ xác nhận</span>',
                                            'confirmed' => '<span class="badge badge-info">Đã xác nhận</span>',
                                            'preparing' => '<span class="badge badge-primary">Đang chuẩn bị</span>',
                                            'delivering' => '<span class="badge badge-info">Đang giao</span>',
                                            'completed' => '<span class="badge badge-success">Hoàn thành</span>',
                                            'cancelled' => '<span class="badge badge-danger">Đã hủy</span>'
                                        ];
                                        echo $badges[$order['status']] ?? $order['status'];
                                        ?>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <?php if($order['status'] == 'pending'): ?>
                                            <a href="?action=update_status&id=<?php echo $order['id']; ?>&status=confirmed" class="btn btn-sm btn-success" title="Xác nhận">
                                                <i class="fas fa-check"></i>
                                            </a>
                                            <?php elseif($order['status'] == 'confirmed'): ?>
                                            <a href="?action=update_status&id=<?php echo $order['id']; ?>&status=preparing" class="btn btn-sm btn-primary" title="Chuẩn bị">
                                                <i class="fas fa-utensils"></i>
                                            </a>
                                            <?php elseif($order['status'] == 'preparing'): ?>
                                            <a href="?action=update_status&id=<?php echo $order['id']; ?>&status=delivering" class="btn btn-sm btn-info" title="Giao hàng">
                                                <i class="fas fa-truck"></i>
                                            </a>
                                            <?php elseif($order['status'] == 'delivering'): ?>
                                            <a href="?action=update_status&id=<?php echo $order['id']; ?>&status=completed" class="btn btn-sm btn-success" title="Hoàn thành">
                                                <i class="fas fa-check-double"></i>
                                            </a>
                                            <?php endif; ?>
                                            <a href="order_detail.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-primary" title="Chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
