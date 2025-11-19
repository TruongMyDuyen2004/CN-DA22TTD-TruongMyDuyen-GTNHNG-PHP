<?php
if (!isset($_SESSION['customer_id'])) {
    echo '<script>window.location.href = "auth/login.php";</script>';
    exit;
}

$db = new Database();
$conn = $db->connect();

// Lấy danh sách đơn hàng
$stmt = $conn->prepare("
    SELECT * FROM orders 
    WHERE customer_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$_SESSION['customer_id']]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$status_labels = [
    'pending' => __('pending'),
    'confirmed' => __('confirmed'),
    'preparing' => __('preparing'),
    'delivering' => __('delivering'),
    'completed' => __('completed'),
    'cancelled' => __('cancelled')
];
?>

<section class="orders-section">
    <div class="container">
        <h2><?php echo __('orders_title'); ?></h2>
        
        <?php if (empty($orders)): ?>
            <div class="empty-orders">
                <p><?php echo __('no_orders'); ?></p>
                <a href="?page=menu" class="btn btn-primary"><?php echo __('order_now'); ?></a>
            </div>
        <?php else: ?>
            <div class="orders-list">
                <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <h3><?php echo __('order_number'); ?> #<?php echo htmlspecialchars($order['order_number']); ?></h3>
                            <p class="order-date"><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></p>
                        </div>
                        <span class="order-status status-<?php echo $order['status']; ?>">
                            <?php echo $status_labels[$order['status']]; ?>
                        </span>
                    </div>
                    
                    <div class="order-details">
                        <?php
                        // Lấy chi tiết đơn hàng
                        $stmt = $conn->prepare("
                            SELECT oi.*, m.name 
                            FROM order_items oi 
                            JOIN menu_items m ON oi.menu_item_id = m.id 
                            WHERE oi.order_id = ?
                        ");
                        $stmt->execute([$order['id']]);
                        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        
                        <div class="order-items">
                            <?php foreach ($items as $item): ?>
                            <div class="order-item">
                                <span><?php echo htmlspecialchars($item['name']); ?> x<?php echo $item['quantity']; ?></span>
                                <span><?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?>đ</span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="order-info">
                            <p><strong><?php echo __('address'); ?>:</strong> <?php echo htmlspecialchars($order['delivery_address']); ?></p>
                            <p><strong><?php echo __('phone'); ?>:</strong> <?php echo htmlspecialchars($order['delivery_phone']); ?></p>
                            <?php if ($order['note']): ?>
                            <p><strong><?php echo __('note_label'); ?>:</strong> <?php echo htmlspecialchars($order['note']); ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="order-total">
                            <span><?php echo __('total'); ?>:</span>
                            <span class="total-amount"><?php echo number_format($order['total_amount'], 0, ',', '.'); ?>đ</span>
                        </div>
                        
                        <?php if ($order['status'] == 'completed'): ?>
                        <a href="?page=review&order_id=<?php echo $order['id']; ?>" class="btn btn-secondary"><?php echo __('review_order'); ?></a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
