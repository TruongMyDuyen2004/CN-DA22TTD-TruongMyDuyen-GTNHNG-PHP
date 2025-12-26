<?php
if (!isset($_SESSION['customer_id'])) {
    echo '<script>window.location.href = "auth/login.php";</script>';
    exit;
}

$db = new Database();
$conn = $db->connect();
$current_lang = getCurrentLanguage();

// Kiểm tra mode: buynow (mua ngay) hoặc cart (giỏ hàng)
$checkout_mode = $_GET['mode'] ?? 'cart';
$is_buynow = ($checkout_mode === 'buynow');

// Kiểm tra nếu có order_id trong URL - hiển thị đơn hàng đã đặt
$view_order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$view_order = null;

// Nếu không có order_id trong URL, kiểm tra last_order_id trong session
if ($view_order_id == 0 && isset($_SESSION['last_order_id'])) {
    $view_order_id = $_SESSION['last_order_id'];
}

if ($view_order_id > 0) {
    // Lấy thông tin đơn hàng từ database (chỉ cho phép xem đơn của chính mình)
    $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND customer_id = ?");
    $stmt->execute([$view_order_id, $_SESSION['customer_id']]);
    $view_order = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Lấy thông tin khách hàng
$stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$_SESSION['customer_id']]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

// Lấy thông tin thẻ thành viên (nếu có)
$member_card = null;
try {
    $stmt = $conn->prepare("SELECT * FROM member_cards WHERE customer_id = ? AND status = 'active'");
    $stmt->execute([$_SESSION['customer_id']]);
    $member_card = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Bảng chưa tồn tại, bỏ qua
}

$cart_items = [];

if ($is_buynow) {
    $cart_items = [];
} else {
    $stmt = $conn->prepare("
        SELECT c.*, m.name, m.price, m.image, m.is_available 
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
}

$subtotal = 0;
if (!$is_buynow) {
    foreach ($cart_items as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }
}

$delivery_fee = 0;
$promo_code = $_SESSION['promo_code'] ?? '';
$promo_discount = 0;
$promo_info = null;

// Lấy giảm giá combo từ session
$combo_discount = 0;
$applied_combos = $_SESSION['applied_combos'] ?? [];
$applied_combo = $_SESSION['applied_promo'] ?? null;

if (!empty($applied_combos)) {
    // Tạo map số lượng món trong giỏ hàng
    $cart_quantities = [];
    foreach ($cart_items as $item) {
        $cart_quantities[$item['menu_item_id']] = ($cart_quantities[$item['menu_item_id']] ?? 0) + $item['quantity'];
    }
    
    // Kiểm tra từng combo còn hợp lệ không
    foreach ($applied_combos as $combo) {
        $promo_id = $combo['promo_id'] ?? 0;
        $combo_count = $combo['count'] ?? 1;
        
        if ($promo_id > 0) {
            $stmt = $conn->prepare("SELECT menu_item_id, quantity FROM promotion_items WHERE promotion_id = ?");
            $stmt->execute([$promo_id]);
            $combo_items_needed = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $combo_valid = true;
            foreach ($combo_items_needed as $needed) {
                $menu_id = $needed['menu_item_id'];
                $qty_needed = $needed['quantity'] * $combo_count;
                $qty_in_cart = $cart_quantities[$menu_id] ?? 0;
                
                if ($qty_in_cart < $qty_needed) {
                    $combo_valid = false;
                    break;
                }
            }
            
            if ($combo_valid) {
                $combo_discount += $combo['discount_amount'] ?? 0;
            }
        }
    }
} elseif ($applied_combo && $applied_combo['type'] === 'combo') {
    $combo_discount = $applied_combo['discount_amount'] ?? 0;
}

if ($promo_code) {
    try {
        $stmt = $conn->prepare("SELECT * FROM promotions WHERE code = ? AND is_active = 1 AND start_date <= NOW() AND end_date >= NOW()");
        $stmt->execute([$promo_code]);
        $promo_info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($promo_info && $subtotal >= $promo_info['min_order_value']) {
            if ($promo_info['usage_limit'] === null || $promo_info['used_count'] < $promo_info['usage_limit']) {
                if ($promo_info['discount_type'] === 'percent') {
                    $promo_discount = $subtotal * ($promo_info['discount_value'] / 100);
                    if ($promo_info['max_discount'] && $promo_discount > $promo_info['max_discount']) {
                        $promo_discount = $promo_info['max_discount'];
                    }
                } else {
                    $promo_discount = $promo_info['discount_value'];
                }
            }
        }
    } catch (PDOException $e) {}
}

$total = $subtotal + $delivery_fee - $promo_discount - $combo_discount;
$error = '';
$success = '';
$promo_error = '';

// Xử lý áp dụng mã khuyến mãi
if (isset($_POST['apply_promo'])) {
    $code = strtoupper(trim($_POST['promo_code'] ?? ''));
    if (empty($code)) {
        $promo_error = 'Vui lòng nhập mã khuyến mãi';
    } else {
        try {
            $stmt = $conn->prepare("SELECT * FROM promotions WHERE code = ? AND is_active = 1 AND start_date <= NOW() AND end_date >= NOW()");
            $stmt->execute([$code]);
            $promo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$promo) {
                $promo_error = 'Mã khuyến mãi không hợp lệ hoặc đã hết hạn';
            } elseif ($subtotal < $promo['min_order_value']) {
                $promo_error = 'Đơn hàng tối thiểu: ' . number_format($promo['min_order_value'], 0, ',', '.') . 'đ';
            } else {
                $_SESSION['promo_code'] = $code;
                echo '<script>location.reload();</script>';
                exit;
            }
        } catch (PDOException $e) {
            $promo_error = 'Hệ thống khuyến mãi chưa sẵn sàng';
        }
    }
}

if (isset($_POST['remove_promo'])) {
    unset($_SESSION['promo_code']);
    echo '<script>location.reload();</script>';
    exit;
}


// Xử lý đặt hàng
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['apply_promo']) && !isset($_POST['remove_promo'])) {
    $delivery_address = trim($_POST['delivery_address'] ?? '');
    $delivery_phone = trim($_POST['delivery_phone'] ?? '');
    $note = trim($_POST['note'] ?? '');
    $payment_method = $_POST['payment_method'] ?? 'cash';
    $is_buynow_order = isset($_POST['is_buynow']) && $_POST['is_buynow'] === '1';
    $delivery_fee = intval($_POST['delivery_fee'] ?? 0);
    
    if ($is_buynow_order) {
        $buynow_item_id = intval($_POST['buynow_item_id'] ?? 0);
        $buynow_quantity = intval($_POST['buynow_quantity'] ?? 1);
        $buynow_price = intval($_POST['buynow_price'] ?? 0);
        $buynow_note = trim($_POST['buynow_note'] ?? '');
        $subtotal = $buynow_price * $buynow_quantity;
    }
    
    $total = $subtotal + $delivery_fee - $promo_discount - $combo_discount;
    $total_discount = $promo_discount + $combo_discount;
    
    if (empty($delivery_address) || empty($delivery_phone)) {
        $error = 'Vui lòng điền đầy đủ thông tin giao hàng';
    } else {
        try {
            $conn->beginTransaction();
            $order_number = 'DH' . date('YmdHis') . rand(100, 999);
            
            // Xác định trạng thái thanh toán
            // - Tiền mặt (COD): paid (sẽ thanh toán khi nhận hàng)
            // - Chuyển khoản: pending (chờ admin xác nhận)
            // - Thẻ thành viên: paid (trừ tiền ngay)
            $payment_status = ($payment_method === 'transfer') ? 'pending' : 'paid';
            $card_id_used = null;
            
            // Xử lý thanh toán bằng thẻ thành viên
            if ($payment_method === 'card') {
                // Kiểm tra thẻ thành viên
                $stmt = $conn->prepare("SELECT * FROM member_cards WHERE customer_id = ? AND status = 'active' FOR UPDATE");
                $stmt->execute([$_SESSION['customer_id']]);
                $card = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$card) {
                    throw new Exception('Bạn chưa có thẻ thành viên hoặc thẻ đã bị khóa!');
                }
                
                if ($card['balance'] < $total) {
                    throw new Exception('Số dư thẻ không đủ! Số dư hiện tại: ' . number_format($card['balance']) . 'đ');
                }
                
                $card_id_used = $card['id'];
                $balance_before = $card['balance'];
                $balance_after = $balance_before - $total;
                
                // Trừ tiền từ thẻ
                $stmt = $conn->prepare("UPDATE member_cards SET balance = balance - ?, total_spent = total_spent + ? WHERE id = ?");
                $stmt->execute([$total, $total, $card['id']]);
                
                $payment_status = 'paid';
            }
            
            // Kiểm tra xem cột payment_status có tồn tại không
            $hasPaymentStatus = false;
            $hasCardId = false;
            try {
                $checkCol = $conn->query("SHOW COLUMNS FROM orders LIKE 'payment_status'");
                $hasPaymentStatus = $checkCol->rowCount() > 0;
                $checkCol2 = $conn->query("SHOW COLUMNS FROM orders LIKE 'card_id'");
                $hasCardId = $checkCol2->rowCount() > 0;
            } catch (Exception $e) {}
            
            if ($hasPaymentStatus && $hasCardId) {
                $stmt = $conn->prepare("
                    INSERT INTO orders (customer_id, order_number, delivery_address, delivery_phone, 
                                       total_amount, delivery_fee, discount_amount, promo_code, note, payment_method, payment_status, card_id) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $_SESSION['customer_id'], $order_number, $delivery_address, $delivery_phone,
                    $total, $delivery_fee, $total_discount, $promo_code ?: null, $note, $payment_method, $payment_status, $card_id_used
                ]);
            } elseif ($hasPaymentStatus) {
                $stmt = $conn->prepare("
                    INSERT INTO orders (customer_id, order_number, delivery_address, delivery_phone, 
                                       total_amount, delivery_fee, discount_amount, promo_code, note, payment_method, payment_status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $_SESSION['customer_id'], $order_number, $delivery_address, $delivery_phone,
                    $total, $delivery_fee, $total_discount, $promo_code ?: null, $note, $payment_method, $payment_status
                ]);
            } else {
                $stmt = $conn->prepare("
                    INSERT INTO orders (customer_id, order_number, delivery_address, delivery_phone, 
                                       total_amount, delivery_fee, discount_amount, promo_code, note, payment_method) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $_SESSION['customer_id'], $order_number, $delivery_address, $delivery_phone,
                    $total, $delivery_fee, $total_discount, $promo_code ?: null, $note, $payment_method
                ]);
            }
            
            $order_id = $conn->lastInsertId();
            
            // Ghi lịch sử giao dịch thẻ
            if ($payment_method === 'card' && $card_id_used) {
                $stmt = $conn->prepare("INSERT INTO card_transactions (card_id, type, amount, balance_before, balance_after, order_id, description) VALUES (?, 'payment', ?, ?, ?, ?, ?)");
                $stmt->execute([$card_id_used, $total, $balance_before, $balance_after, $order_id, 'Thanh toán đơn hàng ' . $order_number]);
            }
            
            if ($promo_code && $promo_discount > 0) {
                try {
                    $stmt = $conn->prepare("UPDATE promotions SET used_count = used_count + 1 WHERE code = ?");
                    $stmt->execute([$promo_code]);
                } catch (PDOException $e) {}
                unset($_SESSION['promo_code']);
            }
            
            // Xóa combo discount sau khi đặt hàng
            if ($combo_discount > 0) {
                unset($_SESSION['applied_combos']);
                unset($_SESSION['applied_promo']);
            }
            
            if ($is_buynow_order) {
                $stmt = $conn->prepare("INSERT INTO order_items (order_id, menu_item_id, quantity, price, note) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$order_id, $buynow_item_id, $buynow_quantity, $buynow_price, $buynow_note]);
            } else {
                $stmt = $conn->prepare("
                    INSERT INTO order_items (order_id, menu_item_id, quantity, price, note)
                    SELECT ?, menu_item_id, quantity, (SELECT price FROM menu_items WHERE id = menu_item_id), note
                    FROM cart WHERE customer_id = ?
                ");
                $stmt->execute([$order_id, $_SESSION['customer_id']]);
                
                $stmt = $conn->prepare("DELETE FROM cart WHERE customer_id = ?");
                $stmt->execute([$_SESSION['customer_id']]);
            }
            
            $conn->commit();
            $success = 'Đặt hàng thành công! Mã đơn: ' . $order_number;
            
            // Lưu thông tin bill vào session để hiển thị
            $_SESSION['order_bill'] = [
                'order_id' => $order_id,
                'order_number' => $order_number,
                'delivery_address' => $delivery_address,
                'delivery_phone' => $delivery_phone,
                'total' => $total,
                'subtotal' => $subtotal,
                'delivery_fee' => $delivery_fee,
                'discount' => $total_discount,
                'payment_method' => $payment_method,
                'payment_status' => $payment_status,
                'note' => $note,
                'created_at' => date('d/m/Y H:i')
            ];
            
            
            echo '<script>sessionStorage.removeItem("buyNowItem");</script>';
        } catch (Exception $e) {
            $conn->rollBack();
            $error = 'Có lỗi xảy ra, vui lòng thử lại';
        }
    }
}
?>


<section class="checkout-page">
    <div class="checkout-container">
        <!-- Header -->
        <div class="checkout-header">
            <a href="?page=cart" class="back-link"><i class="fas fa-arrow-left"></i></a>
            <div class="header-content">
                <?php if ($is_buynow): ?>
                <h1><i class="fas fa-bolt"></i> Đặt hàng nhanh</h1>
                <span class="mode-badge buynow"><i class="fas fa-bolt"></i> Mua ngay</span>
                <?php else: ?>
                <h1><i class="fas fa-credit-card"></i> Thanh toán</h1>
                <span class="mode-badge cart"><i class="fas fa-shopping-cart"></i> <?php echo count($cart_items); ?> món</span>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($error): ?>
        <div class="alert error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success && isset($_SESSION['order_bill'])): ?>
        <?php 
            $bill = $_SESSION['order_bill'];
            $order_id_for_bill = $bill['order_id'];
            
            // Query lại TOÀN BỘ thông tin đơn hàng từ database để lấy giá trị mới nhất
            try {
                $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
                $stmt->execute([$order_id_for_bill]);
                $fresh_order = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($fresh_order) {
                    // Cập nhật TẤT CẢ thông tin từ database
                    $bill['payment_status'] = $fresh_order['payment_status'] ?? 'pending';
                    $bill['payment_method'] = $fresh_order['payment_method'] ?? 'cash';
                    $bill['status'] = $fresh_order['status'] ?? 'pending';
                }
            } catch (Exception $e) {}
            
            // Lấy danh sách món trong đơn hàng
            $stmt = $conn->prepare("
                SELECT oi.*, m.name, m.image 
                FROM order_items oi 
                JOIN menu_items m ON oi.menu_item_id = m.id 
                WHERE oi.order_id = ?
            ");
            $stmt->execute([$order_id_for_bill]);
            $bill_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Lưu order_id vào session riêng để có thể xem lại
            $_SESSION['last_order_id'] = $order_id_for_bill;
            
            // Xóa bill khỏi session sau khi lấy
            unset($_SESSION['order_bill']);
        ?>
        
        <!-- Bill/Hóa đơn -->
        <div class="order-bill">
            <div class="bill-header">
                <div class="bill-success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h2>Đặt hàng thành công!</h2>
                <p>Cảm ơn bạn đã đặt hàng tại Ngon Gallery</p>
                
                <?php if (isset($bill['payment_status']) && $bill['payment_status'] === 'pending' && $bill['payment_method'] === 'transfer'): ?>
                <!-- Thông báo chờ xác nhận thanh toán -->
                <div class="payment-pending-notice">
                    <div class="notice-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="notice-content">
                        <strong><i class="fas fa-exclamation-triangle"></i> Chờ xác nhận thanh toán</strong>
                        <p>Vui lòng chuyển khoản theo thông tin bên dưới. Đơn hàng sẽ được xử lý sau khi admin xác nhận thanh toán.</p>
                        <div class="transfer-info-box">
                            <p><strong>Ngân hàng:</strong> Vietcombank</p>
                            <p><strong>Chủ TK:</strong> TRUONG MY DUYEN</p>
                            <p><strong>Số TK:</strong> 9384848127</p>
                            <p><strong>Số tiền:</strong> <span class="amount"><?php echo number_format($bill['total'], 0, ',', '.'); ?>đ</span></p>
                            <p><strong>Nội dung CK:</strong> <span class="transfer-code"><?php echo $bill['order_number']; ?></span></p>
                        </div>
                        <p class="notice-tip"><i class="fas fa-info-circle"></i> Sau khi chuyển khoản, bạn có thể chụp màn hình gửi qua chat để được xác nhận nhanh hơn.</p>
                    </div>
                </div>
                <?php else: ?>
                <div class="delivery-estimate">
                    <i class="fas fa-motorcycle"></i>
                    <span>Dự kiến giao hàng trong <strong>30-45 phút</strong></span>
                </div>
                <p class="contact-note"><i class="fas fa-phone-alt"></i> Chúng tôi sẽ sớm liên hệ với bạn để xác nhận đơn hàng</p>
                <?php endif; ?>
            </div>
            
            <div class="bill-content">
                <div class="bill-info">
                    <div class="bill-row">
                        <span class="bill-label"><i class="fas fa-receipt"></i> Mã đơn hàng</span>
                        <span class="bill-value order-number"><?php echo $bill['order_number']; ?></span>
                    </div>
                    <div class="bill-row">
                        <span class="bill-label"><i class="fas fa-calendar"></i> Thời gian</span>
                        <span class="bill-value"><?php echo $bill['created_at']; ?></span>
                    </div>
                    <div class="bill-row">
                        <span class="bill-label"><i class="fas fa-map-marker-alt"></i> Địa chỉ giao</span>
                        <span class="bill-value"><?php echo htmlspecialchars($bill['delivery_address']); ?></span>
                    </div>
                    <div class="bill-row">
                        <span class="bill-label"><i class="fas fa-phone"></i> Số điện thoại</span>
                        <span class="bill-value"><?php echo htmlspecialchars($bill['delivery_phone']); ?></span>
                    </div>
                    <div class="bill-row">
                        <span class="bill-label"><i class="fas fa-wallet"></i> Thanh toán</span>
                        <span class="bill-value">
                            <?php echo $bill['payment_method'] === 'cash' ? 'Tiền mặt (COD)' : 'Chuyển khoản'; ?>
                            <?php if (isset($bill['payment_status'])): ?>
                                <?php if ($bill['payment_status'] === 'pending' && $bill['payment_method'] === 'transfer'): ?>
                                    <span class="payment-status pending"><i class="fas fa-clock"></i> Chờ xác nhận</span>
                                <?php elseif ($bill['payment_status'] === 'paid'): ?>
                                    <span class="payment-status paid"><i class="fas fa-check-circle"></i> Đã thanh toán</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </span>
                    </div>
                    <?php if ($bill['note']): ?>
                    <div class="bill-row">
                        <span class="bill-label"><i class="fas fa-sticky-note"></i> Ghi chú</span>
                        <span class="bill-value"><?php echo htmlspecialchars($bill['note']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="bill-items">
                    <h4><i class="fas fa-utensils"></i> Chi tiết đơn hàng</h4>
                    <?php foreach ($bill_items as $item): ?>
                    <div class="bill-item">
                        <?php if (!empty($item['image'])): ?>
                        <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="" class="bill-item-img">
                        <?php else: ?>
                        <div class="bill-item-img no-img"><i class="fas fa-utensils"></i></div>
                        <?php endif; ?>
                        <div class="bill-item-info">
                            <span class="bill-item-name"><?php echo htmlspecialchars($item['name']); ?></span>
                            <span class="bill-item-qty">x<?php echo $item['quantity']; ?></span>
                        </div>
                        <span class="bill-item-price"><?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?>đ</span>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="bill-totals">
                    <div class="bill-total-row">
                        <span>Tạm tính</span>
                        <span><?php echo number_format($bill['subtotal'], 0, ',', '.'); ?>đ</span>
                    </div>
                    <div class="bill-total-row">
                        <span>Phí giao hàng</span>
                        <span><?php echo $bill['delivery_fee'] > 0 ? number_format($bill['delivery_fee'], 0, ',', '.') . 'đ' : 'Miễn phí'; ?></span>
                    </div>
                    <?php if ($bill['discount'] > 0): ?>
                    <div class="bill-total-row discount">
                        <span>Giảm giá</span>
                        <span>-<?php echo number_format($bill['discount'], 0, ',', '.'); ?>đ</span>
                    </div>
                    <?php endif; ?>
                    <div class="bill-total-row final">
                        <span>Tổng cộng</span>
                        <span><?php echo number_format($bill['total'], 0, ',', '.'); ?>đ</span>
                    </div>
                </div>
            </div>
            
            <div class="bill-actions">
                <a href="?page=invoice&id=<?php echo $bill['order_id']; ?>" target="_blank" class="btn-print-bill">
                    <i class="fas fa-print"></i> In hóa đơn
                </a>
                <a href="?page=orders" class="btn-view-orders">
                    <i class="fas fa-list-alt"></i> Xem đơn hàng
                </a>
                <a href="?page=menu" class="btn-continue-shopping">
                    <i class="fas fa-utensils"></i> Đặt tiếp
                </a>
            </div>
        </div>
        
        <?php elseif ($success): ?>
        <div class="alert success">
            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            <div class="success-animation"><i class="fas fa-truck"></i></div>
        </div>
        <?php else: ?>

        <div class="checkout-layout">
            <!-- Left: Form -->
            <div class="checkout-form-section">
                <form method="POST" id="checkoutForm">
                    <?php if ($is_buynow): ?>
                    <input type="hidden" name="is_buynow" value="1">
                    <input type="hidden" name="buynow_item_id" id="buynowItemId">
                    <input type="hidden" name="buynow_quantity" id="buynowQuantity">
                    <input type="hidden" name="buynow_price" id="buynowPrice">
                    <input type="hidden" name="buynow_note" id="buynowNote">
                    <?php endif; ?>
                    <input type="hidden" name="delivery_fee" id="deliveryFeeInput" value="0">

                    <!-- Thông tin người nhận -->
                    <div class="form-card">
                        <div class="card-header">
                            <i class="fas fa-user"></i>
                            <h3>Thông tin người nhận</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="form-group">
                                    <label><i class="fas fa-user-circle"></i> Họ và tên</label>
                                    <input type="text" value="<?php echo htmlspecialchars($customer['full_name']); ?>" disabled class="disabled-input">
                                </div>
                                <div class="form-group">
                                    <label><i class="fas fa-phone"></i> Số điện thoại *</label>
                                    <input type="tel" name="delivery_phone" required value="<?php echo htmlspecialchars($customer['phone'] ?? ''); ?>" placeholder="0xxx xxx xxx">
                                </div>
                            </div>
                        </div>
                    </div>


                    <!-- Địa chỉ giao hàng -->
                    <div class="form-card">
                        <div class="card-header">
                            <i class="fas fa-map-marker-alt"></i>
                            <h3>Địa chỉ giao hàng</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label><i class="fas fa-home"></i> Địa chỉ chi tiết *</label>
                                <textarea name="delivery_address" id="deliveryAddressInput" rows="2" required placeholder="Số nhà, đường, phường/xã, quận/huyện..."><?php echo htmlspecialchars($customer['address'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="delivery-info" id="deliveryInfo">
                                <div class="info-row">
                                    <span><i class="fas fa-route"></i> Khoảng cách</span>
                                    <span id="distanceDisplay">--</span>
                                </div>
                                <div class="info-row">
                                    <span><i class="fas fa-truck"></i> Phí giao hàng</span>
                                    <span id="feeDisplay" class="fee-value">--</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Ghi chú & Thanh toán -->
                    <div class="form-card">
                        <div class="card-header">
                            <i class="fas fa-clipboard-list"></i>
                            <h3>Thông tin thêm</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label><i class="fas fa-sticky-note"></i> Ghi chú đơn hàng</label>
                                <textarea name="note" rows="2" placeholder="Ví dụ: Giao giờ hành chính, gọi trước khi giao..."></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label><i class="fas fa-wallet"></i> Phương thức thanh toán</label>
                                <div class="payment-options">
                                    <label class="payment-option active">
                                        <input type="radio" name="payment_method" value="cash" checked>
                                        <div class="option-content">
                                            <i class="fas fa-money-bill-wave"></i>
                                            <span>Tiền mặt</span>
                                        </div>
                                    </label>
                                    <label class="payment-option">
                                        <input type="radio" name="payment_method" value="transfer">
                                        <div class="option-content">
                                            <i class="fas fa-university"></i>
                                            <span>Chuyển khoản</span>
                                        </div>
                                    </label>
                                    <?php if ($member_card && $member_card['balance'] > 0): ?>
                                    <label class="payment-option">
                                        <input type="radio" name="payment_method" value="card">
                                        <div class="option-content">
                                            <i class="fas fa-credit-card"></i>
                                            <span>Thẻ thành viên</span>
                                        </div>
                                    </label>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Thông tin thẻ thành viên -->
                                <?php if ($member_card): ?>
                                <div class="member-card-box" id="memberCardBox" style="display: none;">
                                    <div class="card-info-header">
                                        <i class="fas fa-credit-card"></i>
                                        <span>Thẻ thành viên của bạn</span>
                                    </div>
                                    <div class="card-info-content">
                                        <div class="card-visual">
                                            <div class="card-number"><?php echo $member_card['card_number']; ?></div>
                                            <div class="card-balance">
                                                <span class="balance-label">Số dư</span>
                                                <span class="balance-amount" id="cardBalance"><?php echo number_format($member_card['balance']); ?>đ</span>
                                            </div>
                                        </div>
                                        <div class="card-status" id="cardPaymentStatus">
                                            <?php if ($member_card['balance'] >= $total): ?>
                                            <span class="status-ok"><i class="fas fa-check-circle"></i> Đủ số dư để thanh toán</span>
                                            <?php else: ?>
                                            <span class="status-warning"><i class="fas fa-exclamation-triangle"></i> Số dư không đủ. Vui lòng nạp thêm hoặc chọn phương thức khác.</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <!-- QR Code Box (hiển thị khi chọn chuyển khoản) -->
                                <div class="qr-transfer-box" id="qrTransferBox" style="display: none;">
                                    <div class="qr-header">
                                        <i class="fas fa-qrcode"></i>
                                        <span>Quét mã QR để thanh toán</span>
                                    </div>
                                    <div class="qr-content">
                                        <div class="qr-image">
                                            <img src="assets/images/qr-vietcombank.png" alt="QR Code" onerror="this.src='https://img.vietqr.io/image/VCB-9384848127-compact2.png'">
                                        </div>
                                        <div class="bank-info">
                                            <div class="bank-details">
                                                <p><strong>Ngân hàng:</strong> Vietcombank</p>
                                                <p><strong>Chủ TK:</strong> TRUONG MY DUYEN</p>
                                                <p><strong>Số TK:</strong> 9384848127</p>
                                                <p class="transfer-note"><i class="fas fa-info-circle"></i> Nội dung CK: <strong id="transferContent">DH + SĐT</strong></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="submit-btn">
                        <i class="fas fa-check-circle"></i> Xác nhận đặt hàng
                    </button>
                </form>
            </div>


            <!-- Right: Order Summary -->
            <div class="order-summary-section">
                <div class="summary-card">
                    <div class="card-header">
                        <i class="fas fa-receipt"></i>
                        <h3>Đơn hàng của bạn</h3>
                    </div>
                    
                    <!-- Order Items -->
                    <div class="order-items" id="orderItemsList">
                        <?php if ($is_buynow): ?>
                        <div class="order-item buynow-item" id="buynowOrderItem">
                            <img src="" alt="" id="buynowItemImage" class="item-img">
                            <div class="item-info">
                                <h4 id="buynowItemName">Đang tải...</h4>
                                <span class="item-qty" id="buynowItemQty">x1</span>
                            </div>
                            <span class="item-price" id="buynowItemTotal">0đ</span>
                        </div>
                        <?php else: ?>
                        <?php foreach ($cart_items as $item): ?>
                        <div class="order-item">
                            <?php if (!empty($item['image'])): ?>
                            <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="" class="item-img">
                            <?php else: ?>
                            <div class="item-img no-img"><i class="fas fa-utensils"></i></div>
                            <?php endif; ?>
                            <div class="item-info">
                                <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                <span class="item-qty">x<?php echo $item['quantity']; ?></span>
                            </div>
                            <span class="item-price"><?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?>đ</span>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>


                    <!-- Totals -->
                    <div class="order-totals">
                        <div class="total-row">
                            <span>Tạm tính</span>
                            <span id="subtotalDisplay"><?php echo number_format($subtotal, 0, ',', '.'); ?>đ</span>
                        </div>
                        <div class="total-row">
                            <span>Phí giao hàng</span>
                            <span id="deliveryFeeDisplay">0đ</span>
                        </div>
                        <?php if ($combo_discount > 0): ?>
                        <div class="total-row discount combo-discount">
                            <span><i class="fas fa-gift"></i> Giảm giá Combo</span>
                            <span>-<?php echo number_format($combo_discount, 0, ',', '.'); ?>đ</span>
                        </div>
                        <?php endif; ?>
                        <?php if ($promo_discount > 0): ?>
                        <div class="total-row discount">
                            <span><i class="fas fa-tag"></i> Mã giảm giá</span>
                            <span>-<?php echo number_format($promo_discount, 0, ',', '.'); ?>đ</span>
                        </div>
                        <?php endif; ?>
                        <div class="total-row final">
                            <span>Tổng cộng</span>
                            <span id="totalDisplay"><?php echo number_format($total, 0, ',', '.'); ?>đ</span>
                        </div>
                    </div>
                </div>

                <!-- Free Shipping Info -->
                <div class="shipping-info">
                    <i class="fas fa-truck"></i>
                    <div>
                        <strong>Miễn phí giao hàng</strong>
                        <span>Trong bán kính 3km</span>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>


<style>
/* Modern Checkout Page - White & Green Theme */
.checkout-page {
    min-height: 100vh;
    padding: 1.5rem 0 4rem;
    background: #f8fafc;
}

.checkout-container {
    max-width: 1100px;
    margin: 0 auto;
    padding: 0 1.5rem;
}

/* Header */
.checkout-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #e5e7eb;
}

.back-link {
    width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f3f4f6;
    border-radius: 12px;
    color: #6b7280;
    text-decoration: none;
    transition: all 0.2s;
}

.back-link:hover {
    background: #dcfce7;
    color: #22c55e;
}

.header-content {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex: 1;
}

.checkout-header h1 {
    font-size: 1.5rem;
    color: #1f2937;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.checkout-header h1 i {
    color: #22c55e;
}

.mode-badge {
    padding: 0.4rem 1rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

.mode-badge.buynow {
    background: #dcfce7;
    color: #15803d;
}

.mode-badge.cart {
    background: #dcfce7;
    color: #15803d;
}

/* Alert */
.alert {
    padding: 1rem 1.25rem;
    border-radius: 12px;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.alert.error {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #ef4444;
}

.alert.success {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    color: #22c55e;
}


/* Layout */
.checkout-layout {
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: 2rem;
    align-items: start;
}

/* Form Cards */
.form-card, .summary-card,
.checkout-page .form-card,
.checkout-page .summary-card,
body.dark-theme .checkout-page .form-card,
body.dark-theme .checkout-page .summary-card {
    background: #ffffff !important;
    border-radius: 16px;
    border: 2px solid #86efac !important;
    overflow: hidden;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 15px rgba(34, 197, 94, 0.1), 0 1px 3px rgba(0, 0, 0, 0.05) !important;
}

.card-header,
.checkout-page .card-header,
body.dark-theme .checkout-page .card-header {
    display: flex !important;
    align-items: center !important;
    justify-content: flex-start !important;
    flex-direction: row !important;
    gap: 0.75rem !important;
    padding: 1rem 1.25rem;
    background: #f8fafc;
    border-bottom: 1px solid #e5e7eb;
}

.card-header i,
.checkout-page .card-header i,
body.dark-theme .checkout-page .card-header i {
    color: #22c55e;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.card-header h3,
.checkout-page .card-header h3,
body.dark-theme .checkout-page .card-header h3 {
    margin: 0;
    color: #111827 !important;
    font-size: 1.05rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.02em;
}

.card-body {
    padding: 1.25rem;
}

/* Form Elements */
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.form-group {
    margin-bottom: 1rem;
}

.form-group:last-child {
    margin-bottom: 0;
}

.form-group label,
.checkout-page .form-group label,
body.dark-theme .checkout-page .form-group label {
    display: block;
    color: #111827 !important;
    font-size: 0.9rem;
    margin-bottom: 0.6rem;
    font-weight: 700;
}

.form-group label i,
.checkout-page .form-group label i,
body.dark-theme .checkout-page .form-group label i {
    color: #22c55e !important;
    margin-right: 0.4rem;
}

.form-group input,
.form-group textarea,
.form-group select,
.checkout-page input,
.checkout-page textarea,
.checkout-page select,
body.dark-theme .checkout-page input,
body.dark-theme .checkout-page textarea,
body.dark-theme .checkout-page select,
body.dark-theme .checkout-page .form-group input,
body.dark-theme .checkout-page .form-group textarea,
body.dark-theme .checkout-page .form-group select {
    width: 100%;
    padding: 0.9rem 1.1rem;
    background: #ffffff !important;
    border: 1px solid #d1d5db !important;
    border-radius: 12px;
    color: #111827 !important;
    font-size: 1rem;
    font-weight: 500;
    transition: all 0.2s;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus,
.checkout-page input:focus,
.checkout-page textarea:focus,
.checkout-page select:focus,
body.dark-theme .checkout-page input:focus,
body.dark-theme .checkout-page textarea:focus,
body.dark-theme .checkout-page select:focus {
    outline: none;
    background: #ffffff !important;
    border-color: #22c55e !important;
    box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.1);
}

.form-group input::placeholder,
.form-group textarea::placeholder,
.checkout-page input::placeholder,
.checkout-page textarea::placeholder,
body.dark-theme .checkout-page input::placeholder,
body.dark-theme .checkout-page textarea::placeholder {
    color: #9ca3af !important;
    font-weight: 400;
}

.disabled-input,
.checkout-page .disabled-input,
body.dark-theme .checkout-page .disabled-input {
    opacity: 1;
    cursor: not-allowed;
    background: #f9fafb !important;
    color: #374151 !important;
    border-color: #e5e7eb !important;
}

/* Delivery Info */
.delivery-info {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    border-radius: 10px;
    padding: 1rem;
    margin-top: 1rem;
}

.info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    color: #4b5563;
    font-size: 0.9rem;
}

.info-row i {
    color: #22c55e;
    margin-right: 0.4rem;
}

.fee-value {
    font-weight: 700;
    color: #22c55e;
}


/* Payment Options */
.payment-options {
    display: flex;
    gap: 1rem;
}

.payment-option {
    flex: 1;
    cursor: pointer;
}

.payment-option input {
    display: none;
}

.option-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem;
    background: #f8fafc;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    transition: all 0.2s;
}

.option-content i {
    font-size: 1.5rem;
    color: #9ca3af;
}

.option-content span {
    font-size: 0.85rem;
    color: #6b7280;
}

.payment-option input:checked + .option-content {
    border-color: #22c55e;
    background: #f0fdf4;
}

.payment-option input:checked + .option-content i {
    color: #22c55e;
}

.payment-option input:checked + .option-content span {
    color: #1f2937;
}

/* Member Card Box */
.member-card-box {
    margin-top: 1.25rem;
    background: linear-gradient(145deg, #faf5ff, #ede9fe);
    border: 2px solid #c4b5fd;
    border-radius: 16px;
    overflow: hidden;
    animation: slideDown 0.3s ease;
}

.card-info-header {
    background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
    padding: 0.75rem 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: white;
    font-weight: 600;
    font-size: 0.9rem;
}

.card-info-content {
    padding: 1.25rem;
}

.card-visual {
    background: linear-gradient(135deg, #7c3aed 0%, #5b21b6 100%);
    border-radius: 12px;
    padding: 1.25rem;
    color: white;
    margin-bottom: 1rem;
}

.card-visual .card-number {
    font-family: 'Courier New', monospace;
    font-size: 1.1rem;
    letter-spacing: 2px;
    margin-bottom: 1rem;
    opacity: 0.9;
}

.card-visual .card-balance {
    display: flex;
    flex-direction: column;
}

.card-visual .balance-label {
    font-size: 0.75rem;
    opacity: 0.8;
    text-transform: uppercase;
}

.card-visual .balance-amount {
    font-size: 1.5rem;
    font-weight: 700;
}

.card-status .status-ok {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #16a34a;
    font-weight: 600;
    font-size: 0.9rem;
}

.card-status .status-warning {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #dc2626;
    font-weight: 600;
    font-size: 0.85rem;
    background: #fef2f2;
    padding: 0.75rem;
    border-radius: 8px;
    border: 1px solid #fecaca;
}

/* QR Transfer Box */
.qr-transfer-box {
    margin-top: 1.25rem;
    background: linear-gradient(145deg, #f0fdf4, #dcfce7);
    border: 1px solid #bbf7d0;
    border-radius: 16px;
    overflow: hidden;
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.qr-header {
    background: #dcfce7;
    padding: 0.75rem 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #15803d;
    font-weight: 600;
    font-size: 0.9rem;
    border-bottom: 1px solid #bbf7d0;
}

.qr-content {
    padding: 1.25rem;
    display: flex;
    gap: 1.25rem;
    align-items: center;
}

.qr-image {
    width: 150px;
    height: 150px;
    background: #fff;
    border-radius: 12px;
    padding: 8px;
    flex-shrink: 0;
    border: 1px solid #e5e7eb;
}

.qr-image img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.bank-info {
    flex: 1;
}

.bank-logo {
    margin-bottom: 0.75rem;
}

.bank-logo img {
    height: 30px;
}

.bank-details p {
    color: #4b5563;
    font-size: 0.9rem;
    margin: 0.4rem 0;
}

.bank-details strong {
    color: #1f2937;
}

.transfer-note {
    margin-top: 0.75rem !important;
    padding: 0.6rem 0.75rem;
    background: #fef3c7;
    border: 1px solid #fcd34d;
    border-radius: 8px;
    color: #b45309 !important;
    font-size: 0.85rem !important;
}

.transfer-note i {
    margin-right: 0.3rem;
}

.transfer-note strong {
    color: #b45309;
}

@media (max-width: 500px) {
    .qr-content {
        flex-direction: column;
        text-align: center;
    }
    
    .qr-image {
        width: 140px;
        height: 140px;
    }
}

/* Submit Button */
.submit-btn {
    width: 100%;
    padding: 1.1rem;
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    border: none;
    border-radius: 12px;
    color: #fff;
    font-size: 1.05rem;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    transition: all 0.3s;
}

.submit-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(34, 197, 94, 0.3);
}

/* Order Summary */
.order-summary-section {
    position: sticky;
    top: 100px;
}

.summary-card {
    margin-bottom: 1rem;
}

/* Order Items */
.order-items {
    max-height: 250px;
    overflow-y: auto;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #e5e7eb;
}

.order-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 0;
    border-bottom: 1px solid #f3f4f6;
}

.order-item:last-child {
    border-bottom: none;
}

.item-img {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    object-fit: cover;
    flex-shrink: 0;
}

.item-img.no-img {
    background: #f3f4f6;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #9ca3af;
}

.order-item .item-info {
    flex: 1;
    min-width: 0;
}

.order-item h4 {
    margin: 0 0 0.2rem;
    color: #1f2937;
    font-size: 0.9rem;
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.item-qty {
    color: #6b7280;
    font-size: 0.8rem;
}

.item-price {
    color: #ef4444;
    font-weight: 700;
    font-size: 0.95rem;
}

.buynow-item {
    background: #f0fdf4;
    border-radius: 10px;
    padding: 0.75rem !important;
    border: 1px solid #bbf7d0 !important;
}


/* Promo Section */
.promo-section {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #e5e7eb;
}

.promo-header {
    color: #f59e0b;
    font-weight: 600;
    font-size: 0.9rem;
    margin-bottom: 0.75rem;
}

.promo-header i {
    margin-right: 0.4rem;
}

.promo-form {
    display: flex;
    gap: 0.5rem;
}

.promo-form input,
body.dark-theme .checkout-page .promo-form input {
    flex: 1;
    padding: 0.85rem 1rem;
    background: #ffffff !important;
    border: 2px solid #d1d5db !important;
    border-radius: 10px;
    color: #1f2937 !important;
    font-size: 0.95rem;
    font-weight: 500;
}

.promo-form input:focus,
body.dark-theme .checkout-page .promo-form input:focus {
    outline: none;
    background: #fffbeb !important;
    border-color: #f59e0b !important;
    box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.15);
}

.promo-form input::placeholder,
body.dark-theme .checkout-page .promo-form input::placeholder {
    color: #6b7280 !important;
}

.promo-form button {
    padding: 0.75rem 1rem;
    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
    border: none;
    border-radius: 8px;
    color: #1a1a1a;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
}

.promo-form button:hover {
    transform: scale(1.02);
}

.promo-error {
    background: #fef2f2;
    color: #ef4444;
    padding: 0.5rem 0.75rem;
    border-radius: 6px;
    font-size: 0.85rem;
    margin-bottom: 0.75rem;
}

.promo-applied {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #f0fdf4;
    padding: 0.75rem;
    border-radius: 8px;
    border: 1px solid #bbf7d0;
}

.promo-badge {
    background: #22c55e;
    color: #fff;
    padding: 0.3rem 0.75rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
}

.remove-promo {
    background: #fef2f2;
    border: none;
    color: #ef4444;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    cursor: pointer;
    transition: all 0.2s;
}

.remove-promo:hover {
    background: #ef4444;
    color: #fff;
}

/* Order Totals */
.order-totals {
    padding: 1rem 1.25rem;
}

.total-row {
    display: flex;
    justify-content: space-between;
    padding: 0.6rem 0;
    color: #6b7280;
    font-size: 0.95rem;
}

.total-row.discount {
    color: #22c55e;
}

.total-row.final {
    border-top: 2px solid #22c55e;
    margin-top: 0.5rem;
    padding-top: 1rem;
}

.total-row.final span:first-child {
    color: #1f2937;
    font-weight: 700;
    font-size: 1.05rem;
}

.total-row.final span:last-child {
    color: #ef4444;
    font-weight: 800;
    font-size: 1.4rem;
}

/* Shipping Info */
.shipping-info {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem 1.25rem;
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    border-radius: 12px;
}

.shipping-info i {
    font-size: 1.5rem;
    color: #22c55e;
}

.shipping-info strong {
    display: block;
    color: #15803d;
    font-size: 0.95rem;
}

.shipping-info span {
    color: #6b7280;
    font-size: 0.85rem;
}


/* Responsive */
@media (max-width: 900px) {
    .checkout-layout {
        grid-template-columns: 1fr;
    }
    
    .order-summary-section {
        position: static;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 600px) {
    .checkout-header h1 {
        font-size: 1.25rem;
    }
    
    .payment-options {
        flex-direction: column;
    }
}

/* Order Bill Styles */
.order-bill {
    max-width: 600px;
    margin: 0 auto;
    background: #ffffff;
    border-radius: 20px;
    border: 2px solid #86efac;
    box-shadow: 0 10px 40px rgba(34, 197, 94, 0.15);
    overflow: hidden;
}

.bill-header {
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    padding: 2rem;
    text-align: center;
    color: #fff;
}

.bill-success-icon {
    width: 80px;
    height: 80px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    font-size: 2.5rem;
    animation: scaleIn 0.5s ease;
}

@keyframes scaleIn {
    from { transform: scale(0); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}

.bill-header h2 {
    margin: 0 0 0.5rem;
    font-size: 1.5rem;
    font-weight: 700;
}

.bill-header p {
    margin: 0;
    opacity: 0.9;
    font-size: 0.95rem;
}

.delivery-estimate {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: rgba(255, 255, 255, 0.2);
    padding: 0.75rem 1.25rem;
    border-radius: 25px;
    margin-top: 1rem;
    font-size: 0.95rem;
    backdrop-filter: blur(10px);
}

.delivery-estimate i {
    font-size: 1.1rem;
    animation: moveMotorcycle 1.5s ease-in-out infinite;
}

@keyframes moveMotorcycle {
    0%, 100% { transform: translateX(0); }
    50% { transform: translateX(5px); }
}

/* Payment Pending Notice */
.payment-pending-notice {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    border: 2px solid #f59e0b;
    border-radius: 16px;
    padding: 1.5rem;
    margin-top: 1.5rem;
    text-align: left;
    color: #92400e;
}

.payment-pending-notice .notice-icon {
    width: 50px;
    height: 50px;
    background: #f59e0b;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    color: #fff;
    font-size: 1.5rem;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

.payment-pending-notice .notice-content strong {
    display: block;
    font-size: 1.1rem;
    color: #b45309;
    margin-bottom: 0.5rem;
    text-align: center;
}

.payment-pending-notice .notice-content > p {
    color: #92400e;
    font-size: 0.9rem;
    margin-bottom: 1rem;
    text-align: center;
}

.transfer-info-box {
    background: #fff;
    border-radius: 12px;
    padding: 1rem 1.25rem;
    margin: 1rem 0;
    border: 1px solid #fcd34d;
}

.transfer-info-box p {
    margin: 0.4rem 0;
    font-size: 0.9rem;
    color: #78350f;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.transfer-info-box .amount {
    font-size: 1.1rem;
    font-weight: 700;
    color: #dc2626;
}

.transfer-info-box .transfer-code {
    font-family: monospace;
    font-weight: 700;
    color: #2563eb;
    background: #dbeafe;
    padding: 0.2rem 0.5rem;
    border-radius: 4px;
}

.payment-pending-notice .notice-tip {
    font-size: 0.8rem;
    color: #a16207;
    text-align: center;
    margin: 0;
    padding-top: 0.5rem;
    border-top: 1px dashed #fcd34d;
}

.payment-pending-notice .notice-tip i {
    margin-right: 0.3rem;
}

/* Payment Status Badge */
.payment-status {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.25rem 0.6rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    margin-left: 0.5rem;
}

.payment-status.pending {
    background: #fef3c7;
    color: #b45309;
    border: 1px solid #fcd34d;
}

.payment-status.paid {
    background: #dcfce7;
    color: #15803d;
    border: 1px solid #86efac;
}

.delivery-estimate strong {
    color: #fff;
    font-weight: 700;
}

.contact-note {
    margin-top: 1rem !important;
    font-size: 0.85rem !important;
    opacity: 0.95 !important;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.contact-note i {
    font-size: 0.9rem;
}

.bill-content {
    padding: 1.5rem;
}

.bill-info {
    background: #f8fafc;
    border-radius: 12px;
    padding: 1rem;
    margin-bottom: 1.5rem;
}

.bill-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 0.6rem 0;
    border-bottom: 1px dashed #e5e7eb;
}

.bill-row:last-child {
    border-bottom: none;
}

.bill-label {
    color: #6b7280;
    font-size: 0.9rem;
}

.bill-label i {
    color: #22c55e;
    margin-right: 0.5rem;
    width: 16px;
}

.bill-value {
    color: #111827;
    font-weight: 600;
    font-size: 0.9rem;
    text-align: right;
    max-width: 60%;
}

.bill-value.order-number {
    color: #22c55e;
    font-size: 1rem;
    font-weight: 700;
}

.bill-items {
    margin-bottom: 1.5rem;
}

.bill-items h4 {
    color: #111827;
    font-size: 1rem;
    font-weight: 700;
    margin: 0 0 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #22c55e;
}

.bill-items h4 i {
    color: #22c55e;
    margin-right: 0.5rem;
}

.bill-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 0;
    border-bottom: 1px solid #f3f4f6;
}

.bill-item:last-child {
    border-bottom: none;
}

.bill-item-img {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    object-fit: cover;
    flex-shrink: 0;
}

.bill-item-img.no-img {
    background: #f3f4f6;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #9ca3af;
}

.bill-item-info {
    flex: 1;
    min-width: 0;
}

.bill-item-name {
    display: block;
    color: #111827;
    font-weight: 600;
    font-size: 0.9rem;
    margin-bottom: 0.2rem;
}

.bill-item-qty {
    color: #6b7280;
    font-size: 0.8rem;
}

.bill-item-price {
    color: #ef4444;
    font-weight: 700;
    font-size: 0.95rem;
}

.bill-totals {
    background: #f0fdf4;
    border-radius: 12px;
    padding: 1rem;
    border: 1px solid #bbf7d0;
}

.bill-total-row {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    color: #4b5563;
    font-size: 0.95rem;
}

.bill-total-row.discount {
    color: #22c55e;
}

.bill-total-row.final {
    border-top: 2px solid #22c55e;
    margin-top: 0.5rem;
    padding-top: 0.75rem;
}

.bill-total-row.final span:first-child {
    color: #111827;
    font-weight: 700;
    font-size: 1.05rem;
}

.bill-total-row.final span:last-child {
    color: #ef4444;
    font-weight: 800;
    font-size: 1.3rem;
}

.bill-actions {
    display: flex;
    gap: 0.75rem;
    padding: 1.5rem;
    background: #f8fafc;
    border-top: 1px solid #e5e7eb;
}

.btn-print-bill,
.btn-view-orders,
.btn-continue-shopping {
    flex: 1;
    padding: 0.9rem 0.5rem;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.85rem;
    text-decoration: none;
    text-align: center;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    transition: all 0.3s;
    cursor: pointer;
    border: none;
}

.btn-print-bill {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: #fff;
}

.btn-print-bill:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(59, 130, 246, 0.3);
}

.btn-view-orders {
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    color: #fff;
}

.btn-view-orders:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(34, 197, 94, 0.3);
}

.btn-continue-shopping {
    background: #fff;
    color: #22c55e;
    border: 2px solid #22c55e;
}

.btn-continue-shopping:hover {
    background: #f0fdf4;
}

/* Print Styles */
@media print {
    body * {
        visibility: hidden;
    }
    
    .order-bill, .order-bill * {
        visibility: visible;
    }
    
    .order-bill {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        max-width: 100%;
        border: none;
        box-shadow: none;
    }
    
    .bill-actions {
        display: none !important;
    }
    
    .bill-header {
        background: #22c55e !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}

@media (max-width: 500px) {
    .bill-actions {
        flex-direction: column;
    }
    
    .bill-row {
        flex-direction: column;
        gap: 0.3rem;
    }
    
    .bill-value {
        text-align: left;
        max-width: 100%;
    }
}
</style>


<script>
// Function to print bill
function printBill() {
    window.print();
}

// Format number with dots
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

document.addEventListener('DOMContentLoaded', function() {
    const isBuynow = <?php echo $is_buynow ? 'true' : 'false'; ?>;
    let currentSubtotal = <?php echo $subtotal; ?>;
    const promoDiscount = <?php echo $promo_discount; ?>;
    const comboDiscount = <?php echo $combo_discount; ?>;
    let deliveryFee = 0;
    
    // Payment option toggle + QR display + Member Card display
    const qrBox = document.getElementById('qrTransferBox');
    const memberCardBox = document.getElementById('memberCardBox');
    
    document.querySelectorAll('.payment-option').forEach(opt => {
        opt.addEventListener('click', function() {
            document.querySelectorAll('.payment-option').forEach(o => o.classList.remove('active'));
            this.classList.add('active');
            
            const paymentValue = this.querySelector('input').value;
            
            // Ẩn tất cả các box
            if (qrBox) qrBox.style.display = 'none';
            if (memberCardBox) memberCardBox.style.display = 'none';
            
            // Hiển thị box tương ứng
            if (paymentValue === 'transfer' && qrBox) {
                qrBox.style.display = 'block';
            } else if (paymentValue === 'card' && memberCardBox) {
                memberCardBox.style.display = 'block';
            }
        });
    });
    
    // Kiểm tra nếu đã đặt hàng thành công thì không cần kiểm tra sessionStorage
    const hasSuccess = document.querySelector('.alert.success') || document.querySelector('.order-bill');
    
    // Nếu đã thành công thì xóa sessionStorage và không cần xử lý gì thêm
    if (hasSuccess) {
        sessionStorage.removeItem('buyNowItem');
        return;
    }
    
    // Xử lý mode mua ngay
    if (isBuynow) {
        const buyNowData = sessionStorage.getItem('buyNowItem');
        if (!buyNowData) {
            // Chỉ redirect nếu không phải trang success
            if (!window.location.search.includes('success')) {
                window.location.href = '?page=menu';
            }
            return;
        }
        
        const item = JSON.parse(buyNowData);
        currentSubtotal = item.price * item.quantity;
        
        document.getElementById('buynowItemId').value = item.item_id;
        document.getElementById('buynowQuantity').value = item.quantity;
        document.getElementById('buynowPrice').value = item.price;
        document.getElementById('buynowNote').value = item.note || '';
        
        document.getElementById('buynowItemName').textContent = item.item_name;
        document.getElementById('buynowItemQty').textContent = 'x' + item.quantity;
        document.getElementById('buynowItemTotal').textContent = formatNumber(item.price * item.quantity) + 'đ';
        
        if (item.item_image) {
            document.getElementById('buynowItemImage').src = item.item_image;
        }
        
        document.getElementById('subtotalDisplay').textContent = formatNumber(currentSubtotal) + 'đ';
        updateTotal();
    }
    
    // Lấy thông tin từ sessionStorage
    const savedAddress = sessionStorage.getItem('deliveryAddress');
    const savedFee = sessionStorage.getItem('deliveryFee');
    const savedDistance = sessionStorage.getItem('deliveryDistance');
    
    const addressInput = document.getElementById('deliveryAddressInput');
    if (!addressInput) return;
    
    if (savedAddress && !addressInput.value.trim()) {
        addressInput.value = savedAddress;
    }
    
    if (savedFee !== null && savedDistance) {
        deliveryFee = parseInt(savedFee);
        document.getElementById('deliveryFeeInput').value = deliveryFee;
        document.getElementById('distanceDisplay').textContent = parseFloat(savedDistance).toFixed(1) + ' km';
        
        if (deliveryFee === 0) {
            document.getElementById('feeDisplay').innerHTML = '<span style="color:#10b981;">Miễn phí</span>';
        } else {
            document.getElementById('feeDisplay').textContent = formatNumber(deliveryFee) + 'đ';
            document.getElementById('feeDisplay').style.color = '#d4a574';
        }
        
        document.getElementById('deliveryFeeDisplay').textContent = deliveryFee === 0 ? 'Miễn phí' : formatNumber(deliveryFee) + 'đ';
        updateTotal();
    } else if (addressInput.value.trim().length >= 5) {
        // Tự động tính phí nếu đã có địa chỉ sẵn (từ profile)
        calculateFee(addressInput.value.trim());
    }

    
    // Tự động tính phí khi thay đổi địa chỉ
    let calcTimeout;
    addressInput.addEventListener('input', function() {
        clearTimeout(calcTimeout);
        calcTimeout = setTimeout(() => {
            if (this.value.trim().length >= 5) {
                calculateFee(this.value.trim());
            }
        }, 800);
    });
    
    function calculateFee(address) {
        document.getElementById('feeDisplay').innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        
        let searchAddress = address;
        if (!address.toLowerCase().includes('trà vinh')) {
            searchAddress = address + ', Trà Vinh, Việt Nam';
        }
        
        fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(searchAddress)}&limit=1&countrycodes=vn`)
            .then(r => r.json())
            .then(data => {
                if (data && data.length > 0) {
                    const lat = parseFloat(data[0].lat);
                    const lng = parseFloat(data[0].lon);
                    const dist = calcDistance(9.9347, 106.3456, lat, lng);
                    applyFee(dist);
                } else {
                    estimateFee(address);
                }
            })
            .catch(() => estimateFee(address));
    }
    
    function calcDistance(lat1, lon1, lat2, lon2) {
        const R = 6371;
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;
        const a = Math.sin(dLat/2)**2 + Math.cos(lat1*Math.PI/180) * Math.cos(lat2*Math.PI/180) * Math.sin(dLon/2)**2;
        return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    }
    
    function estimateFee(address) {
        const addr = address.toLowerCase();
        const areas = [
            // Các phường TP Trà Vinh
            {kw: ['phường 1', 'p1', 'p.1'], d: 1.5}, 
            {kw: ['phường 2', 'p2', 'p.2'], d: 2},
            {kw: ['phường 3', 'p3', 'p.3'], d: 2.5}, 
            {kw: ['phường 4', 'p4', 'p.4'], d: 1},
            {kw: ['phường 5', 'p5', 'p.5'], d: 0.5}, 
            {kw: ['phường 6', 'p6', 'p.6'], d: 2},
            {kw: ['phường 7', 'p7', 'p.7'], d: 3}, 
            {kw: ['phường 8', 'p8', 'p.8'], d: 2.5},
            {kw: ['phường 9', 'p9', 'p.9'], d: 3.5}, 
            {kw: ['long đức'], d: 5},
            // Các huyện Trà Vinh
            {kw: ['châu thành'], d: 8}, 
            {kw: ['càng long'], d: 15},
            {kw: ['cầu kè'], d: 20}, 
            {kw: ['tiểu cần'], d: 25},
            {kw: ['cầu ngang'], d: 18}, 
            {kw: ['trà cú'], d: 30},
            {kw: ['duyên hải', 'duyen hai'], d: 35},
            // Thị xã
            {kw: ['thị xã', 'tx.', 'tx '], d: 10}
        ];
        
        let dist = 5; // Mặc định 5km nếu không tìm thấy
        for (const a of areas) {
            if (a.kw.some(k => addr.includes(k))) { 
                dist = a.d; 
                break; 
            }
        }
        applyFee(dist);
    }
    
    function applyFee(distance) {
        const FREE_KM = 3;
        const FEE_PER_KM = 5000;
        
        deliveryFee = distance <= FREE_KM ? 0 : Math.ceil(distance - FREE_KM) * FEE_PER_KM;
        
        document.getElementById('deliveryFeeInput').value = deliveryFee;
        document.getElementById('distanceDisplay').textContent = distance.toFixed(1) + ' km';
        
        if (deliveryFee === 0) {
            document.getElementById('feeDisplay').innerHTML = '<span style="color:#10b981;">Miễn phí</span>';
            document.getElementById('deliveryFeeDisplay').textContent = 'Miễn phí';
        } else {
            document.getElementById('feeDisplay').textContent = formatNumber(deliveryFee) + 'đ';
            document.getElementById('feeDisplay').style.color = '#d4a574';
            document.getElementById('deliveryFeeDisplay').textContent = formatNumber(deliveryFee) + 'đ';
        }
        
        updateTotal();
    }
    
    function updateTotal() {
        const total = currentSubtotal + deliveryFee - promoDiscount - comboDiscount;
        document.getElementById('totalDisplay').textContent = formatNumber(total) + 'đ';
    }
});

// Auto-check payment status for transfer orders
(function() {
    const pendingNotice = document.querySelector('.payment-pending-notice');
    if (!pendingNotice) return;
    
    // Lấy order_id từ URL hoặc từ link in hóa đơn
    const printLink = document.querySelector('.btn-print-bill');
    if (!printLink) return;
    
    const urlParams = new URLSearchParams(printLink.href.split('?')[1]);
    const orderId = urlParams.get('id');
    if (!orderId) return;
    
    console.log('Checking payment status for order:', orderId);
    
    // Kiểm tra mỗi 5 giây
    const checkInterval = setInterval(function() {
        fetch('api/check-payment-status.php?order_id=' + orderId)
            .then(response => response.json())
            .then(data => {
                console.log('Payment status:', data);
                if (data.success && data.payment_status === 'paid') {
                    // Thanh toán đã được xác nhận - cập nhật UI
                    clearInterval(checkInterval);
                    
                    // Thay thế thông báo chờ xác nhận bằng thông báo đã thanh toán
                    pendingNotice.innerHTML = `
                        <div class="notice-icon" style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="notice-content">
                            <strong style="color: #16a34a;"><i class="fas fa-check-circle"></i> Thanh toán đã được xác nhận!</strong>
                            <p>Cảm ơn bạn đã thanh toán. Đơn hàng của bạn đang được xử lý.</p>
                        </div>
                    `;
                    pendingNotice.style.background = 'linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%)';
                    pendingNotice.style.borderColor = '#86efac';
                    
                    // Cập nhật badge trạng thái thanh toán
                    const paymentStatusBadge = document.querySelector('.payment-status.pending');
                    if (paymentStatusBadge) {
                        paymentStatusBadge.className = 'payment-status paid';
                        paymentStatusBadge.innerHTML = '<i class="fas fa-check-circle"></i> Đã thanh toán';
                    }
                }
            })
            .catch(err => console.log('Error checking payment:', err));
    }, 5000); // Kiểm tra mỗi 5 giây
    
    // Dừng kiểm tra sau 10 phút
    setTimeout(function() {
        clearInterval(checkInterval);
    }, 600000);
})();
</script>
