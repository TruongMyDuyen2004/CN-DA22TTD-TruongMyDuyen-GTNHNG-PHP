<?php
if (!isset($_SESSION['customer_id'])) {
    echo '<script>window.location.href = "auth/login.php";</script>';
    exit;
}

$db = new Database();
$conn = $db->connect();

// Xử lý xóa đơn hàng
$delete_message = '';
$delete_type = '';
if (isset($_POST['delete_order'])) {
    $order_id = intval($_POST['order_id']);
    
    try {
        // Kiểm tra đơn hàng thuộc về user này
        $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND customer_id = ?");
        $stmt->execute([$order_id, $_SESSION['customer_id']]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($order) {
            // Chỉ cho phép xóa đơn đã hoàn thành, đã hủy hoặc đang chờ
            if (in_array($order['status'], ['completed', 'cancelled', 'pending'])) {
                // Xóa order_items trước
                $conn->prepare("DELETE FROM order_items WHERE order_id = ?")->execute([$order_id]);
                // Xóa order
                $conn->prepare("DELETE FROM orders WHERE id = ?")->execute([$order_id]);
                
                $delete_message = "Đã xóa đơn hàng #{$order['order_number']} thành công!";
                $delete_type = 'success';
            } else {
                $delete_message = "Không thể xóa đơn hàng đang xử lý. Vui lòng liên hệ nhà hàng!";
                $delete_type = 'error';
            }
        } else {
            $delete_message = "Không tìm thấy đơn hàng!";
            $delete_type = 'error';
        }
    } catch (Exception $e) {
        $delete_message = "Có lỗi xảy ra!";
        $delete_type = 'error';
    }
}

// Xử lý xóa tất cả đơn hàng cũ
if (isset($_POST['delete_all_old_orders'])) {
    try {
        // Lấy tất cả đơn hàng có thể xóa (completed, cancelled, pending)
        $stmt = $conn->prepare("SELECT id FROM orders WHERE customer_id = ? AND status IN ('completed', 'cancelled', 'pending')");
        $stmt->execute([$_SESSION['customer_id']]);
        $deletable_orders = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (count($deletable_orders) > 0) {
            // Xóa order_items trước
            $placeholders = implode(',', array_fill(0, count($deletable_orders), '?'));
            $conn->prepare("DELETE FROM order_items WHERE order_id IN ($placeholders)")->execute($deletable_orders);
            // Xóa orders
            $conn->prepare("DELETE FROM orders WHERE id IN ($placeholders)")->execute($deletable_orders);
            
            $delete_message = "Đã xóa " . count($deletable_orders) . " đơn hàng cũ thành công!";
            $delete_type = 'success';
        } else {
            $delete_message = "Không có đơn hàng nào có thể xóa!";
            $delete_type = 'error';
        }
    } catch (Exception $e) {
        $delete_message = "Có lỗi xảy ra!";
        $delete_type = 'error';
    }
}

// Xử lý hủy đơn hàng (chỉ cho pending)
if (isset($_POST['cancel_order'])) {
    $order_id = intval($_POST['order_id']);
    $cancel_reason = trim($_POST['cancel_reason'] ?? '');
    
    try {
        $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND customer_id = ?");
        $stmt->execute([$order_id, $_SESSION['customer_id']]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($order && $order['status'] == 'pending') {
            $full_reason = "Khách hàng tự hủy: " . $cancel_reason;
            $conn->prepare("UPDATE orders SET status = 'cancelled', note = CONCAT(IFNULL(note, ''), ' | ', ?) WHERE id = ?")->execute([$full_reason, $order_id]);
            
            $delete_message = "Đã hủy đơn hàng #{$order['order_number']} thành công!";
            $delete_type = 'success';
        } elseif ($order && $order['status'] != 'pending') {
            $delete_message = "Không thể hủy đơn hàng đã được xác nhận. Vui lòng liên hệ nhà hàng!";
            $delete_type = 'error';
        } else {
            $delete_message = "Không tìm thấy đơn hàng!";
            $delete_type = 'error';
        }
    } catch (Exception $e) {
        $delete_message = "Có lỗi xảy ra!";
        $delete_type = 'error';
    }
}

// Lấy filter status từ URL
$filter_status = $_GET['status'] ?? '';

// Lấy danh sách đơn hàng
if (!empty($filter_status)) {
    $stmt = $conn->prepare("
        SELECT * FROM orders 
        WHERE customer_id = ? AND status = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$_SESSION['customer_id'], $filter_status]);
} else {
    $stmt = $conn->prepare("
        SELECT * FROM orders 
        WHERE customer_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$_SESSION['customer_id']]);
}
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$status_config = [
    'pending' => ['label' => 'Chờ xác nhận', 'icon' => 'fa-clock', 'color' => '#f59e0b'],
    'confirmed' => ['label' => 'Đã xác nhận', 'icon' => 'fa-check-circle', 'color' => '#22c55e'],
    'preparing' => ['label' => 'Đang chuẩn bị', 'icon' => 'fa-utensils', 'color' => '#8b5cf6'],
    'delivering' => ['label' => 'Đang giao', 'icon' => 'fa-motorcycle', 'color' => '#06b6d4'],
    'completed' => ['label' => 'Hoàn thành', 'icon' => 'fa-check-double', 'color' => '#10b981'],
    'cancelled' => ['label' => 'Đã hủy', 'icon' => 'fa-times-circle', 'color' => '#ef4444']
];

// Thống kê
$total_orders = count($orders);
$completed_orders = count(array_filter($orders, fn($o) => $o['status'] == 'completed'));
$pending_orders = count(array_filter($orders, fn($o) => $o['status'] == 'pending'));
?>

<section class="orders-page">
    <div class="orders-container">
        <!-- Header -->
        <div class="orders-header">
            <div class="header-content">
                <h1><i class="fas fa-receipt"></i> Đơn hàng của tôi</h1>
                <p class="header-subtitle">Theo dõi và quản lý các đơn hàng của bạn</p>
            </div>
            <div class="header-actions">
                <?php 
                // Đếm số đơn có thể xóa
                $deletable_count = count(array_filter($orders, fn($o) => in_array($o['status'], ['completed', 'cancelled', 'pending'])));
                if ($deletable_count > 0): 
                ?>
                <button type="button" class="delete-all-btn" onclick="confirmDeleteAllOrders(<?php echo $deletable_count; ?>)">
                    <i class="fas fa-trash-alt"></i> Xóa tất cả (<?php echo $deletable_count; ?>)
                </button>
                <?php endif; ?>
                <a href="?page=menu" class="new-order-btn">
                    <i class="fas fa-plus"></i> Đặt món mới
                </a>
            </div>
        </div>

        <?php if ($delete_message): ?>
        <div class="delete-alert <?php echo $delete_type; ?>" id="deleteAlert">
            <i class="fas <?php echo $delete_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
            <?php echo $delete_message; ?>
        </div>
        <script>
            setTimeout(function() {
                var alert = document.getElementById('deleteAlert');
                if (alert) {
                    alert.style.transition = 'opacity 0.5s, transform 0.5s';
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-20px)';
                    setTimeout(function() {
                        alert.style.display = 'none';
                    }, 500);
                }
            }, 5000);
        </script>
        <?php endif; ?>

        <!-- Filter -->
        <div class="orders-filter-bar">
            <div class="status-filters">
                <a href="?page=orders" class="filter-btn <?php echo !isset($_GET['status']) ? 'active' : ''; ?>">Tất cả</a>
                <a href="?page=orders&status=pending" class="filter-btn <?php echo ($_GET['status'] ?? '') == 'pending' ? 'active' : ''; ?>">Chờ xác nhận</a>
                <a href="?page=orders&status=confirmed" class="filter-btn <?php echo ($_GET['status'] ?? '') == 'confirmed' ? 'active' : ''; ?>">Đã xác nhận</a>
                <a href="?page=orders&status=preparing" class="filter-btn <?php echo ($_GET['status'] ?? '') == 'preparing' ? 'active' : ''; ?>">Đang chuẩn bị</a>
                <a href="?page=orders&status=delivering" class="filter-btn <?php echo ($_GET['status'] ?? '') == 'delivering' ? 'active' : ''; ?>">Đang giao</a>
                <a href="?page=orders&status=completed" class="filter-btn <?php echo ($_GET['status'] ?? '') == 'completed' ? 'active' : ''; ?>">Hoàn thành</a>
            </div>
        </div>

        <?php if (empty($orders)): ?>
        <!-- Empty State -->
        <div class="empty-state">
            <div class="empty-icon">
                <i class="fas fa-shopping-basket"></i>
            </div>
            <h3>Chưa có đơn hàng nào</h3>
            <p>Hãy khám phá thực đơn và đặt món ngay!</p>
            <a href="?page=menu" class="btn-explore">
                <i class="fas fa-utensils"></i> Xem thực đơn
            </a>
        </div>
        <?php else: ?>
        <!-- Orders List -->
        <div class="orders-list">
            <?php foreach ($orders as $order): 
                $status = $status_config[$order['status']] ?? $status_config['pending'];
                
                // Lấy chi tiết đơn hàng
                $stmt = $conn->prepare("
                    SELECT oi.*, m.name, m.image 
                    FROM order_items oi 
                    JOIN menu_items m ON oi.menu_item_id = m.id 
                    WHERE oi.order_id = ?
                ");
                $stmt->execute([$order['id']]);
                $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            ?>
            <div class="order-card" data-status="<?php echo $order['status']; ?>">
                <!-- Order Header -->
                <div class="order-card-header">
                    <div class="order-id-section">
                        <span class="order-number">#<?php echo htmlspecialchars($order['order_number']); ?></span>
                        <span class="order-date">
                            <i class="far fa-calendar-alt"></i>
                            <?php echo date('d/m/Y', strtotime($order['created_at'])); ?>
                            <i class="far fa-clock"></i>
                            <?php echo date('H:i', strtotime($order['created_at'])); ?>
                        </span>
                    </div>
                    <div class="order-status-badge" style="--status-color: <?php echo $status['color']; ?>">
                        <i class="fas <?php echo $status['icon']; ?>"></i>
                        <?php echo $status['label']; ?>
                    </div>
                </div>

                <!-- Order Status Timeline -->
                <?php if ($order['status'] != 'cancelled'): ?>
                <div class="order-timeline">
                    <?php
                    $timeline_steps = [
                        'pending' => ['label' => 'Chờ xác nhận', 'icon' => 'fa-clock'],
                        'confirmed' => ['label' => 'Đã xác nhận', 'icon' => 'fa-check-circle'],
                        'preparing' => ['label' => 'Đang chuẩn bị', 'icon' => 'fa-utensils'],
                        'delivering' => ['label' => 'Đang giao', 'icon' => 'fa-motorcycle'],
                        'completed' => ['label' => 'Hoàn thành', 'icon' => 'fa-check-double']
                    ];
                    $status_order = ['pending', 'confirmed', 'preparing', 'delivering', 'completed'];
                    $current_index = array_search($order['status'], $status_order);
                    ?>
                    <?php foreach ($status_order as $index => $step_key): 
                        $step = $timeline_steps[$step_key];
                        $is_completed = $index < $current_index;
                        $is_current = $index == $current_index;
                        $is_future = $index > $current_index;
                    ?>
                    <div class="timeline-step <?php echo $is_completed ? 'completed' : ($is_current ? 'current' : 'future'); ?>">
                        <div class="step-icon">
                            <?php if ($is_completed): ?>
                                <i class="fas fa-check"></i>
                            <?php else: ?>
                                <i class="fas <?php echo $step['icon']; ?>"></i>
                            <?php endif; ?>
                        </div>
                        <span class="step-label"><?php echo $step['label']; ?></span>
                        <?php if ($index < count($status_order) - 1): ?>
                        <div class="step-line <?php echo $is_completed ? 'completed' : ''; ?>"></div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="order-cancelled-notice">
                    <i class="fas fa-times-circle"></i>
                    <span>Đơn hàng đã bị hủy</span>
                </div>
                <?php endif; ?>

                <!-- Order Items Preview -->
                <div class="order-items-preview">
                    <div class="items-images">
                        <?php 
                        $show_items = array_slice($items, 0, 3);
                        foreach ($show_items as $item): 
                        ?>
                        <div class="item-thumb">
                            <?php if (!empty($item['image'])): ?>
                            <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="">
                            <?php else: ?>
                            <i class="fas fa-utensils"></i>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                        <?php if (count($items) > 3): ?>
                        <div class="item-thumb more">+<?php echo count($items) - 3; ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="items-summary">
                        <span class="items-count"><?php echo count($items); ?> món</span>
                        <span class="items-names">
                            <?php echo implode(', ', array_map(fn($i) => $i['name'], array_slice($items, 0, 2))); ?>
                            <?php if (count($items) > 2) echo '...'; ?>
                        </span>
                    </div>
                </div>

                <!-- Order Details (Expandable) -->
                <div class="order-details-section">
                    <div class="detail-row">
                        <div class="detail-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <div>
                                <label>Địa chỉ giao</label>
                                <span><?php echo htmlspecialchars($order['delivery_address']); ?></span>
                            </div>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-phone-alt"></i>
                            <div>
                                <label>Số điện thoại</label>
                                <span><?php echo htmlspecialchars($order['delivery_phone']); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="payment-method-row">
                        <span class="payment-label">Thanh toán:</span>
                        <?php if ($order['payment_method'] == 'transfer'): ?>
                        <span class="payment-badge transfer">
                            <i class="fas fa-university"></i> Chuyển khoản
                        </span>
                        <?php 
                        // Hiển thị trạng thái thanh toán cho chuyển khoản
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
                        <?php elseif ($order['payment_method'] == 'card'): ?>
                        <span class="payment-badge card">
                            <i class="fas fa-credit-card"></i> Thẻ thành viên
                        </span>
                        <span class="payment-status-badge paid">
                            <i class="fas fa-check-circle"></i> Đã thanh toán
                        </span>
                        <?php else: ?>
                        <span class="payment-badge cash">
                            <i class="fas fa-money-bill-wave"></i> Tiền mặt (COD)
                        </span>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($order['note'])): ?>
                    <div class="order-note">
                        <i class="fas fa-sticky-note"></i>
                        <span><?php echo htmlspecialchars($order['note']); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php 
                    // Hiển thị thông báo chờ xác nhận thanh toán cho đơn chuyển khoản
                    $payment_status = $order['payment_status'] ?? 'pending';
                    if ($order['payment_method'] == 'transfer' && $payment_status === 'pending'): 
                    ?>
                    <div class="transfer-pending-notice">
                        <div class="notice-header">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Chờ xác nhận thanh toán</strong>
                        </div>
                        <p>Vui lòng chuyển khoản theo thông tin bên dưới:</p>
                        <div class="transfer-details">
                            <p><span>Ngân hàng:</span> <strong>Vietcombank</strong></p>
                            <p><span>Chủ TK:</span> <strong>TRUONG MY DUYEN</strong></p>
                            <p><span>Số TK:</span> <strong>9384848127</strong></p>
                            <p><span>Số tiền:</span> <strong class="amount"><?php echo number_format($order['total_amount'], 0, ',', '.'); ?>đ</strong></p>
                            <p><span>Nội dung:</span> <strong class="code"><?php echo $order['order_number']; ?></strong></p>
                        </div>
                        <p class="notice-tip"><i class="fas fa-info-circle"></i> Sau khi chuyển khoản, gửi ảnh chụp màn hình qua chat để được xác nhận nhanh hơn.</p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Order Footer -->
                <div class="order-card-footer">
                    <div class="order-total">
                        <span class="total-label">Tổng tiền</span>
                        <span class="total-amount"><?php echo number_format($order['total_amount'], 0, ',', '.'); ?>đ</span>
                    </div>
                    <div class="order-actions">
                        <button class="btn-detail" onclick="toggleOrderDetail(this)">
                            <i class="fas fa-chevron-down"></i> Chi tiết
                        </button>
                        <a href="?page=invoice&id=<?php echo $order['id']; ?>" target="_blank" class="btn-invoice">
                            <i class="fas fa-file-invoice"></i> Hóa đơn
                        </a>
                        <?php if ($order['status'] == 'completed'): ?>
                        <a href="?page=review&order_id=<?php echo $order['id']; ?>" class="btn-review">
                            <i class="fas fa-star"></i> Đánh giá
                        </a>
                        <?php endif; ?>
                        <?php if ($order['status'] == 'pending'): ?>
                        <button type="button" class="btn-cancel-order" onclick="openCancelOrderModal(<?php echo $order['id']; ?>, '<?php echo htmlspecialchars($order['order_number']); ?>')" title="Hủy đơn hàng">
                            <i class="fas fa-times"></i> Hủy
                        </button>
                        <?php endif; ?>
                        <?php if (in_array($order['status'], ['completed', 'cancelled', 'pending'])): ?>
                        <button type="button" class="btn-delete-order" onclick="confirmDeleteOrder(<?php echo $order['id']; ?>, '<?php echo $order['status']; ?>', '<?php echo htmlspecialchars($order['order_number']); ?>')" title="Xóa đơn hàng">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Expanded Detail -->
                <div class="order-expanded-detail" style="display: none;">
                    <div class="expanded-header">
                        <h4><i class="fas fa-list"></i> Chi tiết đơn hàng</h4>
                    </div>
                    <div class="order-items-list">
                        <?php foreach ($items as $item): ?>
                        <div class="order-item-row">
                            <div class="item-image">
                                <?php if (!empty($item['image'])): ?>
                                <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="">
                                <?php else: ?>
                                <div class="no-image"><i class="fas fa-utensils"></i></div>
                                <?php endif; ?>
                            </div>
                            <div class="item-info">
                                <span class="item-name"><?php echo htmlspecialchars($item['name']); ?></span>
                                <span class="item-qty">x<?php echo $item['quantity']; ?></span>
                            </div>
                            <div class="item-price">
                                <?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?>đ
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="order-summary">
                        <div class="summary-row">
                            <span>Tạm tính</span>
                            <span><?php echo number_format($order['total_amount'] - ($order['delivery_fee'] ?? 0) + ($order['discount_amount'] ?? 0), 0, ',', '.'); ?>đ</span>
                        </div>
                        <?php if (!empty($order['delivery_fee']) && $order['delivery_fee'] > 0): ?>
                        <div class="summary-row">
                            <span>Phí giao hàng</span>
                            <span><?php echo number_format($order['delivery_fee'], 0, ',', '.'); ?>đ</span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($order['discount_amount']) && $order['discount_amount'] > 0): ?>
                        <div class="summary-row discount">
                            <span>Giảm giá</span>
                            <span>-<?php echo number_format($order['discount_amount'], 0, ',', '.'); ?>đ</span>
                        </div>
                        <?php endif; ?>
                        <div class="summary-row total">
                            <span>Tổng cộng</span>
                            <span><?php echo number_format($order['total_amount'], 0, ',', '.'); ?>đ</span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Form xóa đơn hàng ẩn -->
<form id="deleteOrderForm" method="POST" style="display: none;">
    <input type="hidden" name="delete_order" value="1">
    <input type="hidden" name="order_id" id="deleteOrderId">
</form>

<!-- Form xóa tất cả đơn hàng ẩn -->
<form id="deleteAllOrdersForm" method="POST" style="display: none;">
    <input type="hidden" name="delete_all_old_orders" value="1">
</form>

<!-- Modal Hủy đơn hàng -->
<div id="cancelOrderModal" class="cancel-order-modal" style="display: none;">
    <div class="modal-overlay" onclick="closeCancelOrderModal()"></div>
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="fas fa-times-circle"></i> Hủy đơn hàng</h3>
            <button class="modal-close" onclick="closeCancelOrderModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" class="cancel-form">
            <input type="hidden" name="cancel_order" value="1">
            <input type="hidden" name="order_id" id="cancelOrderId">
            
            <div class="cancel-info-box">
                <p>Bạn đang hủy đơn hàng <strong id="cancelOrderNumber"></strong></p>
                <small>Hành động này không thể hoàn tác</small>
            </div>
            
            <div class="cancel-reason-input">
                <label>Lý do hủy</label>
                <textarea name="cancel_reason" id="cancelOrderReason" rows="3" placeholder="Vui lòng cho chúng tôi biết lý do..." required></textarea>
                
                <span class="quick-reasons-label">Chọn nhanh:</span>
                <div class="quick-reasons">
                    <span class="quick-btn" data-reason="Đặt nhầm món">Nhầm món</span>
                    <span class="quick-btn" data-reason="Thay đổi kế hoạch">Đổi kế hoạch</span>
                    <span class="quick-btn" data-reason="Muốn đặt lại với thông tin khác">Đặt lại</span>
                    <span class="quick-btn" data-reason="Tìm được nơi khác">Nơi khác</span>
                </div>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn-back" onclick="closeCancelOrderModal()">
                    Quay lại
                </button>
                <button type="submit" class="btn-confirm-cancel">
                    Xác nhận hủy
                </button>
            </div>
        </form>
    </div>
</div>


<style>
/* Orders Page - Modern White & Green Theme */

/* DISABLE ALL ANIMATIONS */
.orders-page * {
    animation: none !important;
    transition: none !important;
    transform: none !important;
}

/* Force black text for stat numbers */
.orders-page .stat-number,
body.dark-theme .orders-page .stat-number,
.orders-stats .stat-number {
    color: #000 !important;
    -webkit-text-fill-color: #000 !important;
    text-shadow: none !important;
    background: none !important;
}

.orders-page .stat-label,
body.dark-theme .orders-page .stat-label,
.orders-stats .stat-label {
    color: #374151 !important;
    -webkit-text-fill-color: #374151 !important;
    text-shadow: none !important;
}

.orders-page {
    min-height: 100vh;
    padding: 2rem 0 4rem;
    background: linear-gradient(180deg, #f0fdf4 0%, #f8fafc 100%) !important;
}

.orders-container {
    max-width: 900px;
    margin: 0 auto;
    padding: 0 1.5rem;
}

/* Header */
.orders-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: #fff;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
}

.orders-header h1 {
    font-size: 1.75rem;
    color: #111827 !important;
    margin: 0 0 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.orders-header h1 i {
    color: #22c55e !important;
}

.header-subtitle {
    color: #6b7280 !important;
    margin: 0;
    font-size: 0.9rem;
}

.new-order-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: #22c55e !important;
    color: #fff !important;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
}

.new-order-btn:hover {
    background: #16a34a !important;
}

/* Header Actions */
.header-actions {
    display: flex;
    gap: 0.75rem;
    align-items: center;
}

.delete-all-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    background: #fee2e2 !important;
    color: #dc2626 !important;
    border: 2px solid #fecaca;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    font-size: 0.9rem;
}
.delete-all-btn:hover {
    background: #ef4444 !important;
    color: #fff !important;
    border-color: #ef4444;
}

/* Filter Bar - Full Width */
.orders-filter-bar {
    background: #fff !important;
    border: 2px solid #22c55e !important;
    border-radius: 12px !important;
    padding: 0.75rem 1rem !important;
    margin-bottom: 1rem !important;
    display: flex !important;
    flex-direction: row !important;
    flex-wrap: wrap !important;
    gap: 0.5rem !important;
    align-items: center !important;
    justify-content: center !important;
    box-shadow: 0 2px 6px rgba(34, 197, 94, 0.1) !important;
    width: 100% !important;
}

.status-filters {
    display: flex !important;
    flex-direction: row !important;
    gap: 0.4rem !important;
    flex-wrap: wrap !important;
    justify-content: center !important;
}

.orders-page .filter-btn,
body.dark-theme .orders-page .filter-btn,
.orders-filter-bar .filter-btn {
    width: auto !important;
    flex: none !important;
    padding: 0.4rem 0.85rem !important;
    border: 1px solid #d1d5db !important;
    border-radius: 6px !important;
    background: #fff !important;
    color: #374151 !important;
    font-size: 0.75rem !important;
    font-weight: 600 !important;
    text-decoration: none !important;
    cursor: pointer !important;
    white-space: nowrap !important;
    display: inline-block !important;
}

.orders-page .filter-btn:hover,
body.dark-theme .orders-page .filter-btn:hover,
.orders-filter-bar .filter-btn:hover {
    border-color: #22c55e !important;
    color: #16a34a !important;
    background: #f0fdf4 !important;
}

.orders-page .filter-btn.active,
body.dark-theme .orders-page .filter-btn.active,
.orders-filter-bar .filter-btn.active {
    background: #22c55e !important;
    color: #fff !important;
    border-color: #22c55e !important;
}

@media (max-width: 768px) {
    .orders-filter-bar {
        display: flex !important;
        flex-wrap: wrap !important;
        width: 100% !important;
        padding: 0.5rem !important;
    }
    .status-filters {
        flex-wrap: wrap !important;
        width: 100% !important;
        justify-content: center !important;
    }
}

/* Stats */
.orders-stats {
    display: flex;
    justify-content: center;
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-item {
    background: #fff !important;
    border: 1px solid #d1d5db !important;
    border-radius: 12px;
    padding: 1.25rem 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    text-decoration: none !important;
    cursor: pointer;
    opacity: 1 !important;
    min-width: 180px;
}

.stat-item:hover {
    border-color: #22c55e !important;
    background: #f0fdf4 !important;
}

.stat-item.active {
    border-color: #22c55e !important;
    background: #f0fdf4 !important;
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
}

.stat-icon.total {
    background: #dcfce7 !important;
    color: #16a34a !important;
}

.stat-icon.pending {
    background: #fef3c7 !important;
    color: #d97706 !important;
}

.stat-icon.completed {
    background: #d1fae5 !important;
    color: #059669 !important;
}

.stat-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.stat-number {
    font-size: 2rem;
    font-weight: 800;
    color: #000 !important;
    line-height: 1;
    opacity: 1 !important;
}

.stat-label {
    font-size: 0.9rem;
    color: #111827 !important;
    font-weight: 600;
    opacity: 1 !important;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    background: #fff;
    border-radius: 20px;
    border: 2px dashed #d1d5db;
}

.empty-icon {
    width: 100px;
    height: 100px;
    margin: 0 auto 1.5rem;
    background: rgba(34, 197, 94, 0.1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.empty-icon i {
    font-size: 2.5rem;
    color: #22c55e;
}

.empty-state h3 {
    color: #000000 !important;
    margin: 0 0 0.5rem;
    font-size: 1.25rem;
}

.empty-state p {
    color: #000000 !important;
    margin: 0 0 1.5rem;
}

.btn-explore {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.85rem 2rem;
    background: #22c55e;
    color: #fff;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
}

.btn-explore:hover {
    background: #16a34a;
}

/* Order Card - Compact Design */
.order-card {
    background: #fff !important;
    border: 2px solid #e2e8f0 !important;
    border-radius: 12px;
    margin-bottom: 0.75rem;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.order-card:hover {
    border-color: #22c55e !important;
    box-shadow: 0 4px 12px rgba(34, 197, 94, 0.12);
}

.order-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 1rem;
    background: #fff !important;
    border-bottom: 1px solid #f0fdf4;
}

/* Order Timeline - Compact */
.order-timeline {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.6rem 1rem;
    background: #fafffe !important;
    border-bottom: 1px solid #e5e7eb;
    position: relative;
}

.timeline-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
    flex: 1;
}

.timeline-step .step-icon {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    z-index: 2;
}

.timeline-step.completed .step-icon {
    background: #22c55e !important;
    color: white !important;
}

.timeline-step.current .step-icon {
    background: #22c55e !important;
    color: white !important;
}

.timeline-step.future .step-icon {
    background: #f1f5f9 !important;
    color: #94a3b8 !important;
    border: 1px dashed #cbd5e1;
}

.timeline-step .step-label {
    font-size: 0.6rem;
    margin-top: 0.3rem;
    text-align: center;
    font-weight: 500;
    white-space: nowrap;
}

.timeline-step.completed .step-label {
    color: #16a34a !important;
}

.timeline-step.current .step-label {
    color: #16a34a !important;
}

.timeline-step.future .step-label {
    color: #94a3b8 !important;
}

.timeline-step .step-line {
    position: absolute;
    top: 14px;
    left: 50%;
    width: 100%;
    height: 2px;
    background: #e2e8f0 !important;
    z-index: 1;
}

.timeline-step .step-line.completed {
    background: #22c55e !important;
}

.order-cancelled-notice {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    padding: 0.5rem 0.75rem;
    background: #fef2f2 !important;
    border-bottom: 1px solid #fecaca;
    color: #dc2626 !important;
    font-weight: 600;
    font-size: 0.75rem;
}

.order-cancelled-notice i {
    font-size: 0.85rem;
}

.order-number {
    font-size: 0.9rem;
    font-weight: 700;
    color: #1e293b !important;
    display: block;
    margin-bottom: 0.15rem;
}

.order-date {
    font-size: 0.75rem;
    color: #64748b !important;
    display: flex;
    align-items: center;
    gap: 0.4rem;
}

.order-date i {
    font-size: 0.7rem;
    color: #22c55e !important;
}

.order-status-badge {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.35rem 0.7rem;
    background: #fff !important;
    border: 1px solid var(--status-color);
    color: var(--status-color);
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 600;
}

/* Items Preview - Compact */
.order-items-preview {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.6rem 1rem;
    border-bottom: 1px solid #f1f5f9;
    background: #fff !important;
}

.items-images {
    display: flex;
    gap: -6px;
}

.item-thumb {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    overflow: hidden;
    border: 2px solid #fff;
    margin-left: -6px;
    background: #f8fafc;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
}

.item-thumb:first-child {
    margin-left: 0;
}

.item-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.item-thumb i {
    color: #cbd5e1;
    font-size: 0.8rem;
}

.item-thumb.more {
    background: #f0fdf4;
    color: #16a34a;
    font-size: 0.7rem;
    font-weight: 700;
}

.items-summary {
    flex: 1;
}

.items-count {
    display: block;
    font-weight: 600;
    color: #1e293b !important;
    margin-bottom: 0.1rem;
    font-size: 0.85rem;
}

.items-names {
    font-size: 0.75rem;
    color: #64748b !important;
}

/* Details Section - Compact */
.order-details-section {
    padding: 0.6rem 1rem;
    border-bottom: 1px solid #f1f5f9;
    background: #fff !important;
}

.detail-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.75rem;
    margin-bottom: 0.5rem;
}

.detail-item {
    display: flex;
    gap: 0.5rem;
    align-items: flex-start;
    padding: 0.5rem;
    background: #f9fafb;
    border-radius: 6px;
}

.detail-item > i {
    color: #22c55e !important;
    font-size: 0.8rem;
    margin-top: 0.1rem;
}

.detail-item label {
    display: block;
    font-size: 0.6rem;
    color: #64748b !important;
    margin-bottom: 0.2rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 700;
}

.detail-item span {
    color: #1e293b !important;
    font-size: 0.8rem;
    font-weight: 500;
}

.payment-method-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.payment-label {
    color: #64748b !important;
    font-size: 0.8rem;
    font-weight: 500;
}

.payment-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.25rem 0.6rem;
    border-radius: 15px;
    font-size: 0.7rem;
    font-weight: 600;
}

.payment-badge.transfer {
    background: #f0fdf4 !important;
    color: #16a34a !important;
    border: 1px solid #bbf7d0;
}

.payment-badge.cash {
    background: #f0fdf4 !important;
    color: #16a34a !important;
    border: 1px solid #bbf7d0;
}

.payment-badge.card {
    background: #ede9fe !important;
    color: #7c3aed !important;
    border: 1px solid #c4b5fd;
}

/* Payment Status Badge */
.payment-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.25rem 0.6rem;
    border-radius: 15px;
    font-size: 0.7rem;
    font-weight: 600;
    margin-left: 0.5rem;
}

.payment-status-badge.pending {
    background: #fef3c7 !important;
    color: #b45309 !important;
    border: 1px solid #fcd34d;
    animation: pulse-pending 2s infinite;
}

@keyframes pulse-pending {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

.payment-status-badge.paid {
    background: #dcfce7 !important;
    color: #15803d !important;
    border: 1px solid #86efac;
}

/* Transfer Pending Notice in Orders */
.transfer-pending-notice {
    margin-top: 0.75rem;
    padding: 0.75rem;
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%) !important;
    border: 1px solid #f59e0b;
    border-radius: 8px;
}

.transfer-pending-notice .notice-header {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    color: #b45309;
    margin-bottom: 0.5rem;
}

.transfer-pending-notice .notice-header i {
    color: #f59e0b;
}

.transfer-pending-notice > p {
    font-size: 0.75rem;
    color: #92400e;
    margin: 0 0 0.5rem 0;
}

.transfer-pending-notice .transfer-details {
    background: #fff;
    border-radius: 6px;
    padding: 0.5rem 0.75rem;
    margin-bottom: 0.5rem;
}

.transfer-pending-notice .transfer-details p {
    display: flex;
    justify-content: space-between;
    margin: 0.25rem 0;
    font-size: 0.75rem;
    color: #78350f;
}

.transfer-pending-notice .transfer-details .amount {
    color: #dc2626;
}

.transfer-pending-notice .transfer-details .code {
    color: #16a34a;
    font-family: monospace;
}

.transfer-pending-notice .notice-tip {
    font-size: 0.7rem;
    color: #a16207;
    margin: 0;
    padding-top: 0.4rem;
    border-top: 1px dashed #fcd34d;
}

.order-note {
    margin-top: 0.5rem;
    padding: 0.4rem 0.6rem;
    background: #fffbeb !important;
    border-radius: 6px;
    display: flex;
    align-items: flex-start;
    gap: 0.4rem;
    border: 1px solid #fef3c7;
}

.order-note i {
    color: #f59e0b;
    font-size: 0.75rem;
}

.order-note span {
    color: #92400e !important;
    font-size: 0.7rem;
    font-weight: 500;
}

/* Footer - Compact */
.order-card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.6rem 1rem;
    background: #fafafa !important;
    border-top: 1px solid #e5e7eb;
}

.order-total {
    display: flex;
    flex-direction: column;
    gap: 0.1rem;
}

.total-label {
    display: block;
    font-size: 0.7rem;
    color: #64748b !important;
    font-weight: 500;
}

.total-amount {
    font-size: 1.1rem;
    font-weight: 700;
    color: #ef4444 !important;
}

.order-actions {
    display: flex;
    gap: 0.4rem;
}

.btn-detail {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.4rem 0.7rem;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    color: #64748b;
    font-size: 0.75rem;
    font-weight: 500;
    cursor: pointer;
}

.btn-detail:hover {
    background: #f8fafc;
    color: #1e293b;
    border-color: #22c55e;
}

.btn-detail.active i {
    transform: rotate(180deg);
}

.btn-invoice {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.4rem 0.7rem;
    background: #22c55e;
    border-radius: 6px;
    color: #fff;
    font-size: 0.75rem;
    font-weight: 600;
    text-decoration: none;
}

.btn-invoice:hover {
    background: #16a34a;
}

.btn-review {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.4rem 0.7rem;
    background: #fbbf24;
    border-radius: 6px;
    color: #1a1a1a;
    font-size: 0.75rem;
    font-weight: 600;
    text-decoration: none;
}

.btn-review:hover {
    background: #f59e0b;
}

/* Delete Order Button */
.btn-delete-order {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    background: #f3f4f6;
    border: 2px solid #e5e7eb;
    border-radius: 6px;
    color: #9ca3af;
    font-size: 0.75rem;
    cursor: pointer;
    transition: all 0.2s;
}
.btn-delete-order:hover {
    background: #fee2e2;
    border-color: #fecaca;
    color: #dc2626;
}

/* Cancel Order Button */
.btn-cancel-order {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.4rem 0.7rem;
    background: #fee2e2;
    border: 2px solid #fecaca;
    border-radius: 6px;
    color: #dc2626;
    font-size: 0.75rem;
    font-weight: 600;
    cursor: pointer;
}
.btn-cancel-order:hover {
    background: #ef4444;
    border-color: #ef4444;
    color: white;
}

/* Cancel Order Modal */
.cancel-order-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}
.cancel-order-modal .modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.4);
    backdrop-filter: blur(4px);
}
.cancel-order-modal .modal-box {
    position: relative;
    background: white;
    border-radius: 20px;
    max-width: 450px;
    width: 100%;
    overflow: hidden;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
    border: 2px solid #e5e7eb;
}
.cancel-order-modal .modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.25rem;
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    border-bottom: 2px solid #ef4444;
}
.cancel-order-modal .modal-header h3 {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 700;
    color: #b91c1c;
}
.cancel-order-modal .modal-header h3 i {
    margin-right: 0.5rem;
}
.cancel-order-modal .modal-close {
    background: white;
    border: 2px solid #e5e7eb;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    color: #6b7280;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}
.cancel-order-modal .modal-close:hover {
    background: #fee2e2;
    border-color: #fecaca;
    color: #ef4444;
}
.cancel-order-modal .cancel-info-box {
    padding: 1rem 1.25rem;
    background: #fef3c7;
    border-bottom: 2px solid #fbbf24;
}
.cancel-order-modal .cancel-info-box p {
    margin: 0;
    color: #78350f;
    font-size: 0.95rem;
    font-weight: 600;
}
.cancel-order-modal .cancel-info-box small {
    display: block;
    color: #a16207;
    font-size: 0.8rem;
    margin-top: 0.25rem;
}
.cancel-order-modal .cancel-reason-input {
    padding: 1.25rem;
    background: white;
}
.cancel-order-modal .cancel-reason-input label {
    display: block;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 0.75rem;
}
.cancel-order-modal .cancel-reason-input textarea {
    width: 100%;
    padding: 0.85rem 1rem;
    border: 2px solid #22c55e;
    border-radius: 12px;
    font-size: 0.95rem;
    resize: none;
    font-family: inherit;
    background: #ffffff !important;
    color: #1f2937 !important;
}
.cancel-order-modal .cancel-reason-input textarea:focus {
    outline: none;
    border-color: #16a34a;
    box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.15);
}
.cancel-order-modal .quick-reasons-label {
    display: block;
    color: #1f2937;
    font-weight: 600;
    font-size: 0.9rem;
    margin: 1rem 0 0.5rem;
}
.cancel-order-modal .quick-reasons {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.5rem;
}
.cancel-order-modal .quick-btn {
    display: block;
    background: #dcfce7;
    border: 2px solid #22c55e;
    color: #166534;
    padding: 0.65rem 0.5rem;
    font-size: 0.85rem;
    font-weight: 600;
    border-radius: 10px;
    text-align: center;
    cursor: pointer;
}
.cancel-order-modal .quick-btn:hover {
    background: #22c55e;
    color: #ffffff;
}
.cancel-order-modal .modal-actions {
    display: flex;
    gap: 0.75rem;
    padding: 1rem 1.25rem;
    background: #f9fafb;
    border-top: 1px solid #e5e7eb;
}
.cancel-order-modal .btn-back {
    flex: 1;
    padding: 0.75rem;
    background: white;
    border: 2px solid #22c55e;
    border-radius: 10px;
    color: #22c55e;
    font-weight: 600;
    cursor: pointer;
    font-size: 0.9rem;
}
.cancel-order-modal .btn-back:hover {
    background: #f0fdf4;
}
.cancel-order-modal .btn-confirm-cancel {
    flex: 1;
    padding: 0.75rem;
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    border: none;
    border-radius: 10px;
    color: white;
    font-weight: 600;
    cursor: pointer;
    font-size: 0.9rem;
}
.cancel-order-modal .btn-confirm-cancel:hover {
    box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4);
}

/* Delete Alert */
.delete-alert {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 1.5rem;
    border-radius: 12px;
    margin-bottom: 1.5rem;
    font-weight: 600;
}
.delete-alert.success {
    background: #dcfce7;
    border: 2px solid #22c55e;
    color: #15803d;
}
.delete-alert.error {
    background: #fee2e2;
    border: 2px solid #ef4444;
    color: #b91c1c;
}

/* Expanded Detail */
.order-expanded-detail {
    border-top: 1px solid #f1f5f9;
    background: #fafafa;
}

.expanded-header {
    padding: 0.85rem 1.25rem;
    border-bottom: 1px solid #f1f5f9;
}

.expanded-header h4 {
    margin: 0;
    color: #1e293b;
    font-size: 0.85rem;
    display: flex;
    align-items: center;
    gap: 0.4rem;
}

.expanded-header h4 i {
    color: #22c55e;
    font-size: 0.8rem;
}

.order-items-list {
    padding: 0.85rem 1.25rem;
}

.order-item-row {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    padding: 0.6rem 0;
    border-bottom: 1px solid #f1f5f9;
}

.order-item-row:last-child {
    border-bottom: none;
}

.item-image {
    width: 42px;
    height: 42px;
    border-radius: 8px;
    overflow: hidden;
    flex-shrink: 0;
}

.item-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.item-image .no-image {
    width: 100%;
    height: 100%;
    background: #f8fafc;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #cbd5e1;
}

.item-info {
    flex: 1;
}

.item-name {
    display: block;
    color: #1e293b;
    font-weight: 500;
    margin-bottom: 0.15rem;
    font-size: 0.85rem;
}

.item-qty {
    font-size: 0.8rem;
    color: #64748b;
}

.item-price {
    color: #ef4444;
    font-weight: 600;
    font-size: 0.85rem;
}

.order-summary {
    padding: 0.85rem 1.25rem;
    background: #fff;
    border-top: 1px solid #f1f5f9;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    padding: 0.35rem 0;
    color: #64748b;
    font-size: 0.8rem;
}

.summary-row.discount {
    color: #22c55e;
}

.summary-row.total {
    border-top: 1px solid #f1f5f9;
    margin-top: 0.4rem;
    padding-top: 0.75rem;
    font-weight: 700;
    color: #1e293b;
}

.summary-row.total span:last-child {
    color: #ef4444;
    font-size: 1rem;
}

/* Responsive */
@media (max-width: 768px) {
    .orders-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
    }
    
    .orders-stats {
        grid-template-columns: 1fr;
    }
    
    .detail-row {
        grid-template-columns: 1fr;
    }
    
    .order-card-footer {
        flex-direction: column;
        gap: 0.75rem;
        align-items: stretch;
    }
    
    .order-actions {
        justify-content: stretch;
    }
    
    .order-actions button,
    .order-actions a {
        flex: 1;
        justify-content: center;
    }
}
</style>

<script>
function toggleOrderDetail(btn) {
    const card = btn.closest('.order-card');
    const detail = card.querySelector('.order-expanded-detail');
    
    if (detail.style.display === 'none') {
        detail.style.display = 'block';
        btn.classList.add('active');
        btn.innerHTML = '<i class="fas fa-chevron-up"></i> Thu gọn';
    } else {
        detail.style.display = 'none';
        btn.classList.remove('active');
        btn.innerHTML = '<i class="fas fa-chevron-down"></i> Chi tiết';
    }
}

function confirmDeleteOrder(orderId, status, orderNumber) {
    var statusText = '';
    if (status === 'completed') statusText = 'đã hoàn thành';
    else if (status === 'cancelled') statusText = 'đã hủy';
    else if (status === 'pending') statusText = 'đang chờ xác nhận';
    
    var message = 'Bạn có chắc muốn xóa đơn hàng #' + orderNumber + ' (' + statusText + ') khỏi danh sách?\n\nHành động này không thể hoàn tác!';
    
    if (confirm(message)) {
        document.getElementById('deleteOrderId').value = orderId;
        document.getElementById('deleteOrderForm').submit();
    }
}

function confirmDeleteAllOrders(count) {
    var message = 'Bạn có chắc muốn xóa TẤT CẢ ' + count + ' đơn hàng cũ?\n\n(Bao gồm: đã hoàn thành, đã hủy, chờ xác nhận)\n\n⚠️ Hành động này KHÔNG THỂ hoàn tác!';
    
    if (confirm(message)) {
        document.getElementById('deleteAllOrdersForm').submit();
    }
}

function openCancelOrderModal(orderId, orderNumber) {
    document.getElementById('cancelOrderId').value = orderId;
    document.getElementById('cancelOrderNumber').textContent = '#' + orderNumber;
    document.getElementById('cancelOrderReason').value = '';
    document.getElementById('cancelOrderModal').style.display = 'flex';
}

function closeCancelOrderModal() {
    document.getElementById('cancelOrderModal').style.display = 'none';
}

// Quick reason buttons for cancel modal
document.addEventListener('DOMContentLoaded', function() {
    var quickBtns = document.querySelectorAll('.cancel-order-modal .quick-btn');
    quickBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.getElementById('cancelOrderReason').value = this.getAttribute('data-reason');
        });
    });
});

function filterOrders() {
    const searchText = document.getElementById('searchOrder').value.toLowerCase();
    const orderCards = document.querySelectorAll('.order-card');
    
    orderCards.forEach(card => {
        const orderNumber = card.querySelector('.order-number')?.textContent.toLowerCase() || '';
        const itemNames = card.querySelector('.items-names')?.textContent.toLowerCase() || '';
        const address = card.querySelector('.detail-item span')?.textContent.toLowerCase() || '';
        
        if (orderNumber.includes(searchText) || itemNames.includes(searchText) || address.includes(searchText)) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

// Auto-check payment status for pending transfer orders
(function() {
    const pendingNotices = document.querySelectorAll('.transfer-pending-notice');
    if (pendingNotices.length === 0) return;
    
    pendingNotices.forEach(notice => {
        const card = notice.closest('.order-card');
        if (!card) return;
        
        // Lấy order_id từ link hóa đơn
        const invoiceLink = card.querySelector('.btn-invoice');
        if (!invoiceLink) return;
        
        const urlParams = new URLSearchParams(invoiceLink.href.split('?')[1]);
        const orderId = urlParams.get('id');
        if (!orderId) return;
        
        // Kiểm tra mỗi 5 giây
        const checkInterval = setInterval(function() {
            fetch('api/check-payment-status.php?order_id=' + orderId)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.payment_status === 'paid') {
                        clearInterval(checkInterval);
                        
                        // Cập nhật badge trạng thái thanh toán
                        const paymentRow = card.querySelector('.payment-method-row');
                        if (paymentRow) {
                            const pendingBadge = paymentRow.querySelector('.payment-status-badge.pending');
                            if (pendingBadge) {
                                pendingBadge.className = 'payment-status-badge paid';
                                pendingBadge.innerHTML = '<i class="fas fa-check-circle"></i> Đã xác nhận';
                            }
                        }
                        
                        // Ẩn thông báo chờ xác nhận
                        notice.innerHTML = `
                            <div class="notice-header" style="color: #16a34a;">
                                <i class="fas fa-check-circle"></i>
                                <strong>Thanh toán đã được xác nhận!</strong>
                            </div>
                            <p style="color: #15803d;">Cảm ơn bạn đã thanh toán. Đơn hàng đang được xử lý.</p>
                        `;
                        notice.style.background = '#dcfce7';
                        notice.style.borderColor = '#86efac';
                    }
                })
                .catch(err => console.log('Error:', err));
        }, 5000);
        
        // Dừng sau 10 phút
        setTimeout(() => clearInterval(checkInterval), 600000);
    });
})();
</script>
