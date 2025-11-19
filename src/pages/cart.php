<?php
if (!isset($_SESSION['customer_id'])) {
    echo '<script>window.location.href = "auth/login.php";</script>';
    exit;
}

$db = new Database();
$conn = $db->connect();

// Xử lý thêm vào giỏ hàng
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    $menu_item_id = $_POST['menu_item_id'];
    $quantity = $_POST['quantity'] ?? 1;
    $note = $_POST['note'] ?? '';
    
    $stmt = $conn->prepare("INSERT INTO cart (customer_id, menu_item_id, quantity, note) VALUES (?, ?, ?, ?) 
                           ON DUPLICATE KEY UPDATE quantity = quantity + ?, note = ?");
    $stmt->execute([$_SESSION['customer_id'], $menu_item_id, $quantity, $note, $quantity, $note]);
    
    echo '<script>window.location.href = "?page=cart";</script>';
    exit;
}

// Xử lý cập nhật số lượng
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_quantity'])) {
    $cart_id = $_POST['cart_id'];
    $quantity = max(1, intval($_POST['quantity']));
    
    $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND customer_id = ?");
    $stmt->execute([$quantity, $cart_id, $_SESSION['customer_id']]);
    
    echo '<script>window.location.href = "?page=cart";</script>';
    exit;
}

// Xử lý xóa khỏi giỏ hàng
if (isset($_GET['remove'])) {
    $cart_id = $_GET['remove'];
    $stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND customer_id = ?");
    $stmt->execute([$cart_id, $_SESSION['customer_id']]);
    
    echo '<script>window.location.href = "?page=cart";</script>';
    exit;
}

// Lấy giỏ hàng
$stmt = $conn->prepare("
    SELECT c.*, m.name, m.price, m.is_available 
    FROM cart c 
    JOIN menu_items m ON c.menu_item_id = m.id 
    WHERE c.customer_id = ?
");
$stmt->execute([$_SESSION['customer_id']]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = 0;
foreach ($cart_items as $item) {
    $total += $item['price'] * $item['quantity'];
}
?>

<section class="cart-section">
    <div class="container">
        <h2><?php echo __('cart_title'); ?></h2>
        
        <?php if (empty($cart_items)): ?>
            <div class="empty-cart">
                <p><?php echo __('empty_cart'); ?></p>
                <a href="?page=menu" class="btn btn-primary"><?php echo __('view_menu'); ?></a>
            </div>
        <?php else: ?>
            <div class="cart-content">
                <div class="cart-items">
                    <?php foreach ($cart_items as $item): ?>
                    <div class="cart-item">
                        <div class="cart-item-info">
                            <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                            <p class="price"><?php echo number_format($item['price'], 0, ',', '.'); ?>đ</p>
                            <?php if ($item['note']): ?>
                                <p class="note"><?php echo __('note_label'); ?>: <?php echo htmlspecialchars($item['note']); ?></p>
                            <?php endif; ?>
                            <?php if (!$item['is_available']): ?>
                                <span class="unavailable"><?php echo __('item_unavailable'); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="cart-item-actions">
                            <div class="quantity-form">
                                <input type="number" 
                                       class="quantity-input" 
                                       value="<?php echo $item['quantity']; ?>" 
                                       min="1" 
                                       max="99"
                                       data-cart-id="<?php echo $item['id']; ?>"
                                       onchange="updateCartQuantity(<?php echo $item['id']; ?>, this.value)">
                            </div>
                            
                            <p class="subtotal"><?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?>đ</p>
                            
                            <button class="btn-remove" onclick="removeFromCart(<?php echo $item['id']; ?>)">
                                <i class="fas fa-trash-alt"></i> <?php echo __('remove'); ?>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="cart-summary">
                    <h3><?php echo __('order_summary'); ?></h3>
                    <div class="summary-row">
                        <span><?php echo __('subtotal'); ?>:</span>
                        <span><?php echo number_format($total, 0, ',', '.'); ?>đ</span>
                    </div>
                    <div class="summary-row">
                        <span><?php echo __('delivery_fee'); ?>:</span>
                        <span>20,000đ</span>
                    </div>
                    <div class="summary-row total">
                        <span><?php echo __('total'); ?>:</span>
                        <span><?php echo number_format($total + 20000, 0, ',', '.'); ?>đ</span>
                    </div>
                    
                    <a href="?page=checkout" class="btn btn-primary btn-block"><?php echo __('place_order'); ?></a>
                    <a href="?page=menu" class="btn btn-secondary btn-block"><?php echo __('continue_shopping'); ?></a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>
