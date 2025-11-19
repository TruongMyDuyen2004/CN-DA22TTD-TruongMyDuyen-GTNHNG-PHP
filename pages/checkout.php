<?php
if (!isset($_SESSION['customer_id'])) {
    echo '<script>window.location.href = "auth/login.php";</script>';
    exit;
}

$db = new Database();
$conn = $db->connect();

// Lấy thông tin khách hàng
$stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$_SESSION['customer_id']]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

// Lấy giỏ hàng
$stmt = $conn->prepare("
    SELECT c.*, m.name, m.price, m.is_available 
    FROM cart c 
    JOIN menu_items m ON c.menu_item_id = m.id 
    WHERE c.customer_id = ?
");
$stmt->execute([$_SESSION['customer_id']]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($cart_items)) {
    echo '<script>window.location.href = "?page=cart";</script>';
    exit;
}

$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$delivery_fee = 20000;
$total = $subtotal + $delivery_fee;

$error = '';
$success = '';

// Xử lý đặt hàng
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $delivery_address = trim($_POST['delivery_address'] ?? '');
    $delivery_phone = trim($_POST['delivery_phone'] ?? '');
    $note = trim($_POST['note'] ?? '');
    $payment_method = $_POST['payment_method'] ?? 'cash';
    
    if (empty($delivery_address) || empty($delivery_phone)) {
        $error = __('fill_delivery_info');
    } else {
        try {
            $conn->beginTransaction();
            
            // Tạo mã đơn hàng
            $order_number = 'DH' . date('YmdHis') . rand(100, 999);
            
            // Tạo đơn hàng
            $stmt = $conn->prepare("
                INSERT INTO orders (customer_id, order_number, delivery_address, delivery_phone, 
                                   total_amount, delivery_fee, note, payment_method) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $_SESSION['customer_id'], $order_number, $delivery_address, $delivery_phone,
                $total, $delivery_fee, $note, $payment_method
            ]);
            
            $order_id = $conn->lastInsertId();
            
            // Thêm chi tiết đơn hàng
            $stmt = $conn->prepare("
                INSERT INTO order_items (order_id, menu_item_id, quantity, price, note)
                SELECT ?, menu_item_id, quantity, (SELECT price FROM menu_items WHERE id = menu_item_id), note
                FROM cart WHERE customer_id = ?
            ");
            $stmt->execute([$order_id, $_SESSION['customer_id']]);
            
            // Xóa giỏ hàng
            $stmt = $conn->prepare("DELETE FROM cart WHERE customer_id = ?");
            $stmt->execute([$_SESSION['customer_id']]);
            
            $conn->commit();
            
            $success = __('order_success') . ' ' . $order_number;
            // Sử dụng JavaScript redirect thay vì header để tránh lỗi "headers already sent"
            echo '<script>setTimeout(function(){ window.location.href = "?page=orders"; }, 3000);</script>';
        } catch (Exception $e) {
            $conn->rollBack();
            $error = __('order_error');
        }
    }
}
?>

<section class="checkout-section">
    <div class="container">
        <h2><?php echo __('checkout'); ?></h2>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php else: ?>
        
        <div class="checkout-grid">
            <div class="checkout-form">
                <h3><?php echo __('delivery_info'); ?></h3>
                <form method="POST">
                    <div class="form-group">
                        <label><?php echo __('full_name'); ?></label>
                        <input type="text" value="<?php echo htmlspecialchars($customer['full_name']); ?>" disabled>
                    </div>
                    
                    <div class="form-group">
                        <label><?php echo __('delivery_phone_label'); ?> *</label>
                        <input type="tel" name="delivery_phone" required value="<?php echo htmlspecialchars($customer['phone'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label><?php echo __('delivery_address_label'); ?> *</label>
                        <textarea name="delivery_address" rows="3" required><?php echo htmlspecialchars($customer['address'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label><?php echo __('note_label'); ?></label>
                        <textarea name="note" rows="2" placeholder="<?php echo __('order_note_placeholder'); ?>"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label><?php echo __('payment_method'); ?></label>
                        <select name="payment_method">
                            <option value="cash"><?php echo __('payment_cash'); ?></option>
                            <option value="transfer"><?php echo __('payment_transfer'); ?></option>
                            <option value="card"><?php echo __('payment_card'); ?></option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block"><?php echo __('confirm_order'); ?></button>
                </form>
            </div>
            
            <div class="order-summary">
                <h3><?php echo __('your_order'); ?></h3>
                
                <div class="order-items">
                    <?php foreach ($cart_items as $item): ?>
                    <div class="order-item">
                        <span><?php echo htmlspecialchars($item['name']); ?> x<?php echo $item['quantity']; ?></span>
                        <span><?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?>đ</span>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="order-totals">
                    <div class="total-row">
                        <span><?php echo __('subtotal'); ?>:</span>
                        <span><?php echo number_format($subtotal, 0, ',', '.'); ?>đ</span>
                    </div>
                    <div class="total-row">
                        <span><?php echo __('delivery_fee'); ?>:</span>
                        <span><?php echo number_format($delivery_fee, 0, ',', '.'); ?>đ</span>
                    </div>
                    <div class="total-row final">
                        <span><?php echo __('total'); ?>:</span>
                        <span><?php echo number_format($total, 0, ',', '.'); ?>đ</span>
                    </div>
                </div>
            </div>
        </div>
        
        <?php endif; ?>
    </div>
</section>
