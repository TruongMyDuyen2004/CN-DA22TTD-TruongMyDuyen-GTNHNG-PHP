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
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/admin-fix.css">
    <link rel="stylesheet" href="../assets/css/admin-unified.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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

        <div class="detail-grid">
            <!-- Thông tin cá nhân -->
            <div class="detail-card">
                <h3><i class="fas fa-id-card"></i> Thông tin cá nhân</h3>
                <div class="info-group">
                    <div class="info-item">
                        <label>Họ tên:</label>
                        <span><?php echo htmlspecialchars($customer['full_name']); ?></span>
                    </div>
                    <div class="info-item">
                        <label>Email:</label>
                        <span><?php echo htmlspecialchars($customer['email']); ?></span>
                    </div>
                    <div class="info-item">
                        <label>Số điện thoại:</label>
                        <span><?php echo htmlspecialchars($customer['phone'] ?? 'Chưa cập nhật'); ?></span>
                    </div>
                    <div class="info-item">
                        <label>Địa chỉ:</label>
                        <span><?php echo htmlspecialchars($customer['address'] ?? 'Chưa cập nhật'); ?></span>
                    </div>
                    <div class="info-item">
                        <label>Ngày đăng ký:</label>
                        <span><?php echo date('d/m/Y H:i', strtotime($customer['created_at'])); ?></span>
                    </div>
                    <div class="info-item">
                        <label>Trạng thái:</label>
                        <?php 
                        $status = $customer['status'] ?? 'active';
                        $statusClass = $status == 'active' ? 'success' : 'danger';
                        $statusText = $status == 'active' ? 'Hoạt động' : 'Đã khóa';
                        ?>
                        <span class="badge badge-<?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                    </div>
                </div>
            </div>

            <!-- Thống kê -->
            <div class="detail-card">
                <h3><i class="fas fa-chart-line"></i> Thống kê mua hàng</h3>
                <div class="stats-list">
                    <div class="stat-item">
                        <i class="fas fa-shopping-cart"></i>
                        <div>
                            <strong><?php echo number_format($stats['total_orders']); ?></strong>
                            <span>Tổng đơn hàng</span>
                        </div>
                    </div>
                    <div class="stat-item">
                        <i class="fas fa-money-bill-wave"></i>
                        <div>
                            <strong><?php echo number_format($stats['total_spent'], 0, ',', '.'); ?>đ</strong>
                            <span>Tổng chi tiêu</span>
                        </div>
                    </div>
                    <div class="stat-item">
                        <i class="fas fa-receipt"></i>
                        <div>
                            <strong><?php echo number_format($stats['avg_order'], 0, ',', '.'); ?>đ</strong>
                            <span>Giá trị TB/đơn</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lịch sử đơn hàng -->
        <div class="detail-card">
            <h3><i class="fas fa-history"></i> Lịch sử đơn hàng (<?php echo count($orders); ?>)</h3>
            <?php if (empty($orders)): ?>
                <p class="text-center text-muted">Chưa có đơn hàng nào</p>
            <?php else: ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Mã đơn</th>
                                <th>Ngày đặt</th>
                                <th>Tổng tiền</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><strong>#<?php echo htmlspecialchars($order['order_number']); ?></strong></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                                <td><strong><?php echo number_format($order['total_amount'], 0, ',', '.'); ?>đ</strong></td>
                                <td>
                                    <?php
                                    $statusLabels = [
                                        'pending' => ['Chờ xác nhận', 'warning'],
                                        'confirmed' => ['Đã xác nhận', 'info'],
                                        'preparing' => ['Đang chuẩn bị', 'info'],
                                        'delivering' => ['Đang giao', 'primary'],
                                        'completed' => ['Hoàn thành', 'success'],
                                        'cancelled' => ['Đã hủy', 'danger']
                                    ];
                                    $statusInfo = $statusLabels[$order['status']];
                                    ?>
                                    <span class="badge badge-<?php echo $statusInfo[1]; ?>"><?php echo $statusInfo[0]; ?></span>
                                </td>
                                <td>
                                    <a href="order_detail.php?id=<?php echo $order['id']; ?>" class="btn-icon">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Đánh giá -->
        <div class="detail-card">
            <h3><i class="fas fa-star"></i> Đánh giá (<?php echo count($reviews); ?>)</h3>
            <?php if (empty($reviews)): ?>
                <p class="text-center text-muted">Chưa có đánh giá nào</p>
            <?php else: ?>
                <div class="reviews-list">
                    <?php foreach ($reviews as $review): ?>
                    <div class="review-item">
                        <div class="review-header">
                            <div class="rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'active' : ''; ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <span class="review-date"><?php echo date('d/m/Y', strtotime($review['created_at'])); ?></span>
                        </div>
                        <?php if ($review['menu_name']): ?>
                            <p class="review-product"><strong>Món:</strong> <?php echo htmlspecialchars($review['menu_name']); ?></p>
                        <?php endif; ?>
                        <?php if ($review['comment']): ?>
                            <p class="review-comment"><?php echo htmlspecialchars($review['comment']); ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
