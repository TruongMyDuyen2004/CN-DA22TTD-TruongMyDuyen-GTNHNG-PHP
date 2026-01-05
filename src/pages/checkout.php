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

// Lấy thông tin điểm tích lũy
$customer_points = null;
$point_settings = [];
try {
    $stmt = $conn->prepare("SELECT * FROM customer_points WHERE customer_id = ?");
    $stmt->execute([$_SESSION['customer_id']]);
    $customer_points = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $point_settings = $conn->query("SELECT setting_key, setting_value FROM point_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (Exception $e) {
    // Bảng chưa tồn tại
}
$available_points = $customer_points['available_points'] ?? 0;
$points_to_money = intval($point_settings['points_to_money'] ?? 100);
$min_redeem_points = intval($point_settings['min_redeem_points'] ?? 100);
$max_redeem_percent = intval($point_settings['max_redeem_percent'] ?? 50);

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

// Thêm giảm giá từ voucher mới
$voucher_discount = 0;
$applied_voucher = $_SESSION['applied_voucher'] ?? null;
if ($applied_voucher) {
    // Tính lại discount dựa trên subtotal hiện tại
    try {
        $stmt = $conn->prepare("SELECT * FROM vouchers WHERE code = ? AND is_active = 1");
        $stmt->execute([$applied_voucher['code']]);
        $voucher = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Trong mode buynow, subtotal = 0 nên bỏ qua check min_order_value
        // Giữ lại voucher và để JavaScript tính discount sau
        if ($voucher) {
            if ($is_buynow) {
                // Mode buynow: giữ nguyên discount_amount từ session (đã tính khi apply)
                $voucher_discount = $applied_voucher['discount_amount'] ?? 0;
            } elseif ($subtotal >= $voucher['min_order_value']) {
                // Mode cart: tính lại discount
                if ($voucher['discount_type'] === 'percent') {
                    $voucher_discount = $subtotal * ($voucher['discount_value'] / 100);
                    if ($voucher['max_discount'] && $voucher_discount > $voucher['max_discount']) {
                        $voucher_discount = $voucher['max_discount'];
                    }
                } else {
                    $voucher_discount = $voucher['discount_value'];
                }
                $_SESSION['applied_voucher']['discount_amount'] = $voucher_discount;
            } else {
                // Subtotal không đủ min_order_value - xóa voucher
                unset($_SESSION['applied_voucher']);
                $applied_voucher = null;
            }
        } else {
            // Voucher không còn hợp lệ
            unset($_SESSION['applied_voucher']);
            $applied_voucher = null;
        }
    } catch (Exception $e) {
        // Bảng vouchers chưa tồn tại
    }
}
$total = $total - $voucher_discount;

// Xử lý điểm thưởng đã áp dụng
$points_discount = 0;
$applied_points = $_SESSION['applied_points'] ?? null;
if ($applied_points) {
    $points_discount = $applied_points['discount_amount'] ?? 0;
    // Kiểm tra lại điểm còn đủ không
    if ($applied_points['points'] > $available_points) {
        unset($_SESSION['applied_points']);
        $applied_points = null;
        $points_discount = 0;
    }
}
$total = $total - $points_discount;

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
    
    // Thêm giảm giá từ voucher
    if ($voucher_discount > 0) {
        $total = $total - $voucher_discount;
        $total_discount += $voucher_discount;
    }
    
    // Thêm giảm giá từ điểm thưởng
    $points_used_in_order = 0;
    if ($applied_points && $points_discount > 0) {
        $total = $total - $points_discount;
        $total_discount += $points_discount;
        $points_used_in_order = $applied_points['points'];
    }
    
    if (empty($delivery_address) || empty($delivery_phone)) {
        $error = 'Vui lòng điền đầy đủ thông tin giao hàng';
    } else {
        try {
            $conn->beginTransaction();
            $order_number = 'DH' . date('YmdHis') . rand(100, 999);
            
            // Xác định trạng thái thanh toán
            // - Tiền mặt (COD): pending (chờ thanh toán khi nhận hàng)
            // - Chuyển khoản: pending (chờ admin xác nhận)
            // - Thẻ thành viên: paid (trừ tiền ngay)
            $payment_status = 'pending'; // Mặc định là pending
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
            
            // Trừ điểm đã sử dụng
            if ($points_used_in_order > 0 && $points_discount > 0) {
                try {
                    // Lấy điểm hiện tại
                    $stmt = $conn->prepare("SELECT available_points, used_points FROM customer_points WHERE customer_id = ?");
                    $stmt->execute([$_SESSION['customer_id']]);
                    $cp = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($cp) {
                        $balance_before = $cp['available_points'];
                        $balance_after = $balance_before - $points_used_in_order;
                        $new_used = ($cp['used_points'] ?? 0) + $points_used_in_order;
                        
                        // Cập nhật điểm
                        $stmt = $conn->prepare("UPDATE customer_points SET available_points = ?, used_points = ? WHERE customer_id = ?");
                        $stmt->execute([$balance_after, $new_used, $_SESSION['customer_id']]);
                        
                        // Ghi lịch sử trừ điểm
                        $stmt = $conn->prepare("INSERT INTO point_transactions (customer_id, type, points, balance_before, balance_after, order_id, description) VALUES (?, 'redeem', ?, ?, ?, ?, ?)");
                        $stmt->execute([
                            $_SESSION['customer_id'], 
                            $points_used_in_order, 
                            $balance_before, 
                            $balance_after, 
                            $order_id, 
                            'Đổi điểm đơn hàng ' . $order_number . ' (giảm ' . number_format($points_discount) . 'đ)'
                        ]);
                        
                        // Cập nhật điểm đã dùng vào order
                        $stmt = $conn->prepare("UPDATE orders SET points_used = ?, points_discount = ? WHERE id = ?");
                        $stmt->execute([$points_used_in_order, $points_discount, $order_id]);
                    }
                    
                    // Xóa session điểm đã áp dụng
                    unset($_SESSION['applied_points']);
                } catch (Exception $e) {
                    // Bảng điểm chưa tồn tại, bỏ qua
                }
            }
            
            // Xóa voucher đã áp dụng
            if ($applied_voucher) {
                try {
                    // Tăng số lần sử dụng voucher
                    $stmt = $conn->prepare("UPDATE vouchers SET used_count = used_count + 1 WHERE code = ?");
                    $stmt->execute([$applied_voucher['code']]);
                } catch (Exception $e) {}
                unset($_SESSION['applied_voucher']);
            }
            
            // Tích điểm cho khách hàng (sau khi commit để đảm bảo đơn hàng đã được lưu)
            $points_earned = 0;
            try {
                // Lấy cấu hình điểm
                $point_settings = $conn->query("SELECT setting_key, setting_value FROM point_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
                $points_per_order = intval($point_settings['points_per_order'] ?? 1000);
                
                if ($points_per_order > 0) {
                    // Tính điểm cơ bản
                    $base_points = floor($subtotal / $points_per_order);
                    
                    if ($base_points > 0) {
                        // Lấy tier hiện tại để tính bonus
                        $stmt = $conn->prepare("SELECT tier, available_points, total_points FROM customer_points WHERE customer_id = ?");
                        $stmt->execute([$_SESSION['customer_id']]);
                        $cp = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        $tier = $cp['tier'] ?? 'bronze';
                        $bonus_percent = intval($point_settings['bonus_' . $tier] ?? 0);
                        $bonus_points = floor($base_points * $bonus_percent / 100);
                        $points_earned = $base_points + $bonus_points;
                        
                        $balance_before = $cp['available_points'] ?? 0;
                        $balance_after = $balance_before + $points_earned;
                        $new_total = ($cp['total_points'] ?? 0) + $points_earned;
                        
                        if (!$cp) {
                            // Tạo mới record điểm
                            $stmt = $conn->prepare("INSERT INTO customer_points (customer_id, total_points, available_points, tier) VALUES (?, ?, ?, 'bronze')");
                            $stmt->execute([$_SESSION['customer_id'], $points_earned, $points_earned]);
                            $balance_before = 0;
                            $balance_after = $points_earned;
                        } else {
                            // Cập nhật điểm
                            $stmt = $conn->prepare("UPDATE customer_points SET available_points = ?, total_points = ? WHERE customer_id = ?");
                            $stmt->execute([$balance_after, $new_total, $_SESSION['customer_id']]);
                        }
                        
                        // Ghi lịch sử tích điểm
                        $desc = "Tích điểm đơn hàng " . $order_number;
                        if ($bonus_points > 0) {
                            $desc .= " (+$bonus_points bonus " . ucfirst($tier) . ")";
                        }
                        $stmt = $conn->prepare("INSERT INTO point_transactions (customer_id, type, points, balance_before, balance_after, order_id, description) VALUES (?, 'earn', ?, ?, ?, ?, ?)");
                        $stmt->execute([$_SESSION['customer_id'], $points_earned, $balance_before, $balance_after, $order_id, $desc]);
                        
                        // Cập nhật điểm earned vào order
                        $stmt = $conn->prepare("UPDATE orders SET points_earned = ? WHERE id = ?");
                        $stmt->execute([$points_earned, $order_id]);
                        
                        // Cập nhật tier nếu cần
                        $tier_thresholds = [
                            'silver' => intval($point_settings['tier_silver'] ?? 1000),
                            'gold' => intval($point_settings['tier_gold'] ?? 5000),
                            'platinum' => intval($point_settings['tier_platinum'] ?? 15000),
                            'diamond' => intval($point_settings['tier_diamond'] ?? 50000),
                        ];
                        $new_tier = 'bronze';
                        if ($new_total >= $tier_thresholds['diamond']) $new_tier = 'diamond';
                        elseif ($new_total >= $tier_thresholds['platinum']) $new_tier = 'platinum';
                        elseif ($new_total >= $tier_thresholds['gold']) $new_tier = 'gold';
                        elseif ($new_total >= $tier_thresholds['silver']) $new_tier = 'silver';
                        
                        if ($new_tier !== $tier) {
                            $stmt = $conn->prepare("UPDATE customer_points SET tier = ? WHERE customer_id = ?");
                            $stmt->execute([$new_tier, $_SESSION['customer_id']]);
                        }
                    }
                }
            } catch (Exception $e) {
                // Bảng điểm chưa tồn tại, bỏ qua
            }
            
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
                'created_at' => date('d/m/Y H:i'),
                'points_earned' => $points_earned
            ];
            
            
            echo '<script>sessionStorage.removeItem("buyNowItem");</script>';
        } catch (Exception $e) {
            $conn->rollBack();
            $error = 'Có lỗi xảy ra, vui lòng thử lại';
        }
    }
}
?>


<!-- Hero Section giống các trang khác -->
<section class="about-hero checkout-hero">
    <div class="about-hero-content">
        <span class="section-badge"><?php echo $is_buynow ? 'Mua ngay' : 'Thanh toán'; ?></span>
        <h1 class="about-hero-title">
            <?php if ($is_buynow): ?>
            <i class="fas fa-credit-card"></i> Thanh Toán
            <?php else: ?>
            <i class="fas fa-credit-card"></i> Thanh Toán
            <?php endif; ?>
        </h1>
        <p class="about-hero-subtitle">
            <?php echo $is_buynow ? 'Hoàn tất đơn hàng của bạn nhanh chóng' : 'Xác nhận và hoàn tất đơn hàng của bạn'; ?>
        </p>
    </div>
</section>

<section class="checkout-page">
    <div class="checkout-container">
        <!-- Breadcrumb Navigation -->
        <div class="checkout-breadcrumb">
            <a href="?page=menu"><i class="fas fa-utensils"></i> Thực đơn</a>
            <i class="fas fa-chevron-right"></i>
            <?php if (!$is_buynow): ?>
            <a href="?page=cart"><i class="fas fa-shopping-cart"></i> Giỏ hàng</a>
            <i class="fas fa-chevron-right"></i>
            <?php endif; ?>
            <span class="current"><?php echo $is_buynow ? 'Mua ngay' : 'Thanh toán'; ?></span>
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
                            <?php 
                            if ($bill['payment_method'] === 'cash') {
                                echo 'Tiền mặt (COD)';
                            } elseif ($bill['payment_method'] === 'card') {
                                echo 'Thẻ thành viên';
                            } else {
                                echo 'Chuyển khoản';
                            }
                            ?>
                            <?php if (isset($bill['payment_status'])): ?>
                                <?php if ($bill['payment_status'] === 'paid'): ?>
                                    <span class="payment-status paid"><i class="fas fa-check-circle"></i> Đã thanh toán</span>
                                <?php elseif ($bill['payment_status'] === 'pending'): ?>
                                    <?php if ($bill['payment_method'] === 'transfer'): ?>
                                        <span class="payment-status pending"><i class="fas fa-clock"></i> Chờ xác nhận</span>
                                    <?php elseif ($bill['payment_method'] === 'cash'): ?>
                                        <span class="payment-status pending"><i class="fas fa-money-bill-wave"></i> Thanh toán khi nhận hàng</span>
                                    <?php else: ?>
                                        <span class="payment-status pending"><i class="fas fa-clock"></i> Chờ thanh toán</span>
                                    <?php endif; ?>
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

        <?php if ($is_buynow): ?>
        <div class="checkout-layout buynow-layout">
        <?php else: ?>
        <div class="checkout-layout">
        <?php endif; ?>
            
            <!-- Order Summary - ĐƯA LÊN ĐẦU -->
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
                        <?php if ($voucher_discount > 0): ?>
                        <div class="total-row discount voucher-discount-row">
                            <span><i class="fas fa-ticket-alt"></i> Voucher (<?php echo $applied_voucher['code']; ?>)</span>
                            <span>-<?php echo number_format($voucher_discount, 0, ',', '.'); ?>đ</span>
                        </div>
                        <?php endif; ?>
                        <?php if ($points_discount > 0): ?>
                        <div class="total-row discount points-discount-row">
                            <span><i class="fas fa-coins"></i> Điểm thưởng (<?php echo number_format($applied_points['points']); ?> điểm)</span>
                            <span>-<?php echo number_format($points_discount, 0, ',', '.'); ?>đ</span>
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

            <!-- Form Section -->
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

                    <!-- CARD 1: Thông tin giao hàng (gom người nhận + địa chỉ) -->
                    <div class="form-card">
                        <div class="card-header">
                            <i class="fas fa-shipping-fast"></i>
                            <h3>Thông tin giao hàng</h3>
                        </div>
                        <div class="card-body">
                            <!-- Người nhận -->
                            <div class="form-row">
                                <div class="form-group">
                                    <label><i class="fas fa-user-circle"></i> Họ và tên</label>
                                    <input type="text" value="<?php echo htmlspecialchars($customer['full_name']); ?>" disabled class="disabled-input">
                                </div>
                                <div class="form-group">
                                    <label><i class="fas fa-phone"></i> Số điện thoại</label>
                                    <input type="tel" value="<?php echo htmlspecialchars($customer['phone'] ?? ''); ?>" disabled class="disabled-input">
                                    <input type="hidden" name="delivery_phone" value="<?php echo htmlspecialchars($customer['phone'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="edit-profile-hint">
                                <i class="fas fa-info-circle"></i> 
                                Muốn thay đổi thông tin? <a href="?page=profile">Vào trang cá nhân</a>
                            </div>
                            
                            <!-- Địa chỉ -->
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

                    <!-- CARD 2: Ưu đãi (gom voucher + điểm thưởng) -->
                    <?php 
                    // Lấy voucher đã áp dụng từ session
                    $applied_voucher = $_SESSION['applied_voucher'] ?? null;
                    $show_discount_card = true; // Luôn hiện card ưu đãi
                    ?>
                    <?php if ($show_discount_card): ?>
                    <div class="form-card discount-card">
                        <div class="card-header">
                            <i class="fas fa-percent"></i>
                            <h3>Ưu đãi</h3>
                        </div>
                        <div class="card-body">
                            <!-- Mã giảm giá -->
                            <div class="discount-section">
                                <div class="section-label"><i class="fas fa-ticket-alt"></i> Mã giảm giá</div>
                                <?php if ($applied_voucher): ?>
                                <div class="voucher-applied-box">
                                    <div class="voucher-applied-info">
                                        <div class="voucher-code-badge"><?php echo $applied_voucher['code']; ?></div>
                                        <div class="voucher-details">
                                            <span class="voucher-name"><?php echo htmlspecialchars($applied_voucher['name']); ?></span>
                                            <span class="voucher-discount-amount">-<?php echo number_format($applied_voucher['discount_amount']); ?>đ</span>
                                        </div>
                                    </div>
                                    <button type="button" class="btn-remove-voucher" onclick="removeVoucher()">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <input type="hidden" name="voucher_code" value="<?php echo $applied_voucher['code']; ?>">
                                <input type="hidden" name="voucher_discount" id="voucherDiscountValue" value="<?php echo $applied_voucher['discount_amount']; ?>">
                                <?php else: ?>
                                <div class="voucher-input-wrapper compact">
                                    <div class="voucher-input-group">
                                        <input type="text" id="voucherCodeInput" placeholder="Nhập mã giảm giá..." maxlength="50">
                                        <button type="button" class="btn-apply-voucher" onclick="applyVoucher()">
                                            <i class="fas fa-check"></i> Áp dụng
                                        </button>
                                    </div>
                                    <div class="voucher-message" id="voucherMessage"></div>
                                    <a href="?page=vouchers" class="voucher-link"><i class="fas fa-tags"></i> Xem voucher của bạn</a>
                                </div>
                                <input type="hidden" name="voucher_code" id="voucherCodeHidden" value="">
                                <input type="hidden" name="voucher_discount" id="voucherDiscountValue" value="0">
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($available_points > 0): ?>
                            <div class="section-divider"></div>
                            
                            <!-- Điểm thưởng -->
                            <div class="discount-section">
                                <div class="section-label"><i class="fas fa-coins"></i> Điểm thưởng</div>
                                <div class="points-info-compact">
                                    <span class="points-badge"><?php echo number_format($available_points); ?> điểm</span>
                                    <span class="points-equivalent">= <?php echo number_format($available_points * $points_to_money); ?>đ</span>
                                </div>
                                
                                <?php if ($applied_points): ?>
                                <div class="points-applied-box compact">
                                    <div class="points-applied-info">
                                        <i class="fas fa-check-circle"></i>
                                        <span>Đã dùng <strong><?php echo number_format($applied_points['points']); ?></strong> điểm (-<?php echo number_format($applied_points['discount_amount']); ?>đ)</span>
                                    </div>
                                    <button type="button" class="btn-remove-points" onclick="removePoints()">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <input type="hidden" name="use_points" value="<?php echo $applied_points['points']; ?>">
                                <input type="hidden" name="points_discount" id="pointsDiscountValue" value="<?php echo $applied_points['discount_amount']; ?>">
                                <?php else: ?>
                                <div class="points-form-compact">
                                    <div class="points-input-row">
                                        <input type="number" id="pointsInput" placeholder="Nhập số điểm" min="1" max="<?php echo $available_points; ?>" value="">
                                        <button type="button" class="btn-apply-points" onclick="applyPoints()">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button type="button" class="btn-use-all-points" onclick="useAllPoints()">Dùng hết</button>
                                    </div>
                                    <div class="points-message" id="pointsMessage"></div>
                                </div>
                                <input type="hidden" name="use_points" id="usePointsHidden" value="0">
                                <input type="hidden" name="points_discount" id="pointsDiscountValue" value="0">
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- CARD 3: Thanh toán (gom ghi chú + phương thức) -->
                    <div class="form-card">
                        <div class="card-header">
                            <i class="fas fa-wallet"></i>
                            <h3>Thanh toán</h3>
                        </div>
                        <div class="card-body">
                            <!-- Ghi chú -->
                            <div class="form-group">
                                <label><i class="fas fa-sticky-note"></i> Ghi chú đơn hàng</label>
                                <textarea name="note" rows="2" placeholder="Ví dụ: Giao giờ hành chính, gọi trước khi giao..."></textarea>
                            </div>
                            
                            <div class="section-divider"></div>
                            
                            <!-- Phương thức thanh toán -->
                            <div class="section-label"><i class="fas fa-credit-card"></i> Phương thức thanh toán</div>
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
                                            <img src="https://img.vietqr.io/image/MB-444418062004-print.png" alt="QR Code MB Bank">
                                        </div>
                                        <div class="bank-info">
                                            <div class="bank-details">
                                                <p><strong>Ngân hàng:</strong> MB Bank</p>
                                                <p><strong>Chi nhánh:</strong> Càng Long</p>
                                                <p><strong>Chủ TK:</strong> TRUONG MY DUYEN</p>
                                                <p><strong>Số TK:</strong> 444418062004</p>
                                                <p class="transfer-note"><i class="fas fa-info-circle"></i> Nội dung CK: <strong id="transferContent">DH + SĐT</strong></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Nút xác nhận đặt hàng -->
                            <button type="submit" class="submit-btn">
                                <i class="fas fa-check-circle"></i> Xác nhận đặt hàng
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>


<style>
/* FORCE LAYOUT 2 CỘT - Form TRÁI, Đơn hàng PHẢI - HIGHEST PRIORITY */
html body .checkout-page .checkout-layout,
html body .checkout-page .checkout-layout.buynow-layout,
.checkout-page .checkout-layout,
.checkout-page .checkout-layout.buynow-layout {
    display: flex !important;
    flex-direction: row !important;
    flex-wrap: nowrap !important;
    gap: 2rem !important;
    align-items: flex-start !important;
    max-width: 1200px !important;
    width: 100% !important;
    margin: 0 auto !important;
    padding: 0 !important;
}

/* ĐƠN HÀNG BÊN PHẢI (order: 2) */
html body .checkout-page .checkout-layout .order-summary-section,
html body .checkout-page .checkout-layout.buynow-layout .order-summary-section,
.checkout-page .order-summary-section {
    flex: 0 0 400px !important;
    width: 400px !important;
    min-width: 400px !important;
    max-width: 400px !important;
    position: sticky !important;
    top: 100px !important;
    order: 2 !important;
    align-self: flex-start !important;
}

/* FORM BÊN TRÁI (order: 1) */
html body .checkout-page .checkout-layout .checkout-form-section,
html body .checkout-page .checkout-layout.buynow-layout .checkout-form-section,
.checkout-page .checkout-form-section {
    flex: 1 1 auto !important;
    min-width: 0 !important;
    max-width: calc(100% - 420px) !important;
    order: 1 !important;
    display: flex !important;
    flex-direction: column !important;
    gap: 1.25rem !important;
}

/* Mobile: 1 cột, đơn hàng LÊN TRÊN form */
@media (max-width: 960px) {
    html body .checkout-page .checkout-layout,
    html body .checkout-page .checkout-layout.buynow-layout,
    .checkout-page .checkout-layout,
    .checkout-page .checkout-layout.buynow-layout {
        display: flex !important;
        flex-direction: column !important;
        flex-wrap: nowrap !important;
        max-width: 650px !important;
        gap: 1.5rem !important;
    }
    
    /* ĐƠN HÀNG LÊN TRÊN - order: -1 để chắc chắn lên đầu */
    html body .checkout-page .checkout-layout .order-summary-section,
    html body .checkout-page .checkout-layout.buynow-layout .order-summary-section,
    .checkout-page .order-summary-section,
    .checkout-layout .order-summary-section {
        flex: 0 0 auto !important;
        width: 100% !important;
        min-width: 100% !important;
        max-width: 100% !important;
        position: relative !important;
        top: 0 !important;
        order: -1 !important; /* -1 để chắc chắn lên đầu tiên */
    }
    
    /* FORM XUỐNG DƯỚI */
    html body .checkout-page .checkout-layout .checkout-form-section,
    html body .checkout-page .checkout-layout.buynow-layout .checkout-form-section,
    .checkout-page .checkout-form-section,
    .checkout-layout .checkout-form-section {
        flex: 0 0 auto !important;
        width: 100% !important;
        max-width: 100% !important;
        order: 1 !important; /* Form xuống dưới đơn hàng */
    }
}

/* ========== CHECKOUT HERO SECTION ========== */
.checkout-hero {
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 50%, #15803d 100%);
    padding: 2.5rem 0;
    margin-bottom: 0;
    position: relative;
    overflow: hidden;
    box-shadow: none !important;
}

.checkout-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    opacity: 0.5;
}

.checkout-hero::after {
    display: none !important;
}

/* Xóa thanh shadow dưới hero */
.about-hero.checkout-hero::after,
section.about-hero.checkout-hero::after {
    display: none !important;
    content: none !important;
}

.checkout-hero .about-hero-content {
    position: relative;
    z-index: 1;
}

.checkout-hero .about-hero-title {
    font-size: 2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
}

.checkout-hero .about-hero-title i {
    font-size: 1.75rem;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

.checkout-hero .about-hero-subtitle {
    font-size: 1rem;
    opacity: 0.95;
}

/* ========== CHECKOUT BREADCRUMB ========== */
.checkout-breadcrumb {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 0;
    margin-bottom: 1.5rem;
    font-size: 0.9rem;
    flex-wrap: wrap;
}

.checkout-breadcrumb a {
    color: #6b7280;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 0.4rem;
    transition: color 0.2s;
}

.checkout-breadcrumb a:hover {
    color: #22c55e;
}

.checkout-breadcrumb a i {
    font-size: 0.85rem;
}

.checkout-breadcrumb .fa-chevron-right {
    font-size: 0.7rem;
    color: #d1d5db;
}

.checkout-breadcrumb .current {
    color: #22c55e;
    font-weight: 600;
}

/* Modern Checkout Page - White & Green Theme */
.checkout-page {
    min-height: auto;
    padding: 0 0 4rem;
    background: #f8fafc;
    width: 100%;
}

.checkout-container {
    max-width: 1300px;
    width: 100%;
    margin: 0 auto;
    padding: 1.5rem;
    box-sizing: border-box;
}

/* Header - giữ lại cho backward compatibility */
.checkout-header {
    display: none; /* Ẩn header cũ vì đã có hero */
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


/* Alert đã được định nghĩa ở trên */

/* Đơn hàng bên phải - sticky */

/* === CARD ĐƠN HÀNG (BÊN PHẢI) - CLEAN WHITE THEME === */
.checkout-layout.buynow-layout .summary-card,
.summary-card {
    background: #fff;
    border-radius: 16px;
    border: 1px solid #e5e7eb;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.checkout-layout.buynow-layout .summary-card .card-header,
.summary-card .card-header {
    padding: 1.25rem 1.5rem;
    background: #ffffff;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.checkout-layout.buynow-layout .summary-card .card-header i,
.summary-card .card-header i {
    width: 40px;
    height: 40px;
    background: #f3f4f6;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #374151;
    font-size: 1.1rem;
}

.checkout-layout.buynow-layout .summary-card .card-header h3,
.summary-card .card-header h3 {
    font-size: 1.1rem;
    letter-spacing: 0;
    color: #1f2937;
    font-weight: 700;
    margin: 0;
    text-transform: none;
}

.checkout-layout.buynow-layout .order-items {
    max-height: 200px;
    padding: 1rem 1.25rem;
    overflow-y: auto;
}

.checkout-layout.buynow-layout .order-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem;
    background: #f8fafc;
    border-radius: 12px;
    margin-bottom: 0.5rem;
}

.checkout-layout.buynow-layout .order-item:last-child {
    margin-bottom: 0;
}

.checkout-layout.buynow-layout .item-img {
    width: 60px;
    height: 60px;
    border-radius: 10px;
    object-fit: cover;
    border: 2px solid #e5e7eb;
}

.checkout-layout.buynow-layout .order-item .item-info {
    flex: 1;
}

.checkout-layout.buynow-layout .order-item h4 {
    font-size: 0.95rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 0.25rem;
}

.checkout-layout.buynow-layout .order-item .item-qty {
    font-size: 0.85rem;
    color: #6b7280;
    background: #e5e7eb;
    padding: 0.15rem 0.5rem;
    border-radius: 4px;
    display: inline-block;
}

.checkout-layout.buynow-layout .item-price {
    font-size: 1.1rem;
    font-weight: 700;
    color: #ef4444;
}

.checkout-layout.buynow-layout .order-totals {
    padding: 1.25rem 1.5rem;
    background: #ffffff;
    border-top: 1px solid #e5e7eb;
}

.checkout-layout.buynow-layout .total-row {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    font-size: 0.95rem;
    color: #4b5563;
}

.checkout-layout.buynow-layout .total-row.final {
    border-top: 1px solid #e5e7eb;
    margin-top: 0.5rem;
    padding-top: 0.75rem;
}

.checkout-layout.buynow-layout .total-row.final span:first-child {
    font-size: 1.1rem;
    font-weight: 700;
    color: #1f2937;
}

.checkout-layout.buynow-layout .total-row.final span:last-child {
    font-size: 1.35rem;
    font-weight: 800;
    color: #dc2626;
}
}

.checkout-layout.buynow-layout .total-row.final {
    border-top: 2px solid #22c55e;
    margin-top: 0.5rem;
    padding-top: 0.75rem;
}

.checkout-layout.buynow-layout .total-row.final span:first-child {
    font-size: 1.1rem;
    font-weight: 700;
    color: #1f2937;
}

.checkout-layout.buynow-layout .total-row.final span:last-child {
    font-size: 1.35rem;
    font-weight: 800;
    color: #ef4444;
}

/* Shipping Info */
.checkout-layout.buynow-layout .shipping-info {
    margin-top: 1rem;
    padding: 1rem 1.25rem;
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
    border: 1px solid #86efac;
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.checkout-layout.buynow-layout .shipping-info i {
    font-size: 1.5rem;
    color: #22c55e;
}

.checkout-layout.buynow-layout .shipping-info strong {
    display: block;
    font-size: 0.95rem;
    color: #15803d;
}

.checkout-layout.buynow-layout .shipping-info span {
    font-size: 0.85rem;
    color: #4b5563;
}

/* === FORM CARDS (BÊN PHẢI) === */
.checkout-layout.buynow-layout .form-card {
    background: #fff;
    border-radius: 14px;
    border: 1px solid #e5e7eb;
    margin-bottom: 1rem;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
    overflow: hidden;
}

.checkout-layout.buynow-layout .form-card .card-header {
    padding: 0.85rem 1.15rem;
}

.checkout-layout.buynow-layout .form-card .card-header h3 {
    font-size: 0.9rem;
}

.checkout-layout.buynow-layout .form-card .card-header i {
    font-size: 1rem;
}

.checkout-layout.buynow-layout .form-card .card-body {
    padding: 1rem 1.15rem;
}

.checkout-layout.buynow-layout .form-card .section-label {
    font-size: 0.85rem;
    margin-bottom: 0.6rem;
    color: #374151;
}

.checkout-layout.buynow-layout .form-card .section-label i {
    font-size: 0.8rem;
}

.checkout-layout.buynow-layout .form-card .section-divider {
    margin: 1rem 0;
}

.checkout-layout.buynow-layout .form-card .form-group {
    margin-bottom: 0.85rem;
}

.checkout-layout.buynow-layout .form-card .form-group label {
    font-size: 0.8rem;
    margin-bottom: 0.35rem;
}

.checkout-layout.buynow-layout .form-card .form-group input,
.checkout-layout.buynow-layout .form-card .form-group textarea,
.checkout-layout.buynow-layout .form-card .form-group select {
    padding: 0.65rem 0.9rem;
    font-size: 0.9rem;
    border-radius: 8px;
}

.checkout-layout.buynow-layout .form-card .form-row {
    gap: 0.85rem;
}

/* Delivery Info trong form */
.checkout-layout.buynow-layout .form-card .delivery-info {
    padding: 0.65rem 0.9rem;
    margin-top: 0.75rem;
    background: transparent;
    border-radius: 0;
}

.checkout-layout.buynow-layout .form-card .info-row {
    font-size: 0.85rem;
    padding: 0.3rem 0;
}

/* Voucher & Points compact */
.checkout-layout.buynow-layout .voucher-input-wrapper.compact .voucher-input-group {
    display: flex;
    gap: 0.5rem;
}

.checkout-layout.buynow-layout .voucher-input-wrapper.compact input {
    padding: 0.6rem 0.85rem;
    font-size: 0.85rem;
}

.checkout-layout.buynow-layout .voucher-input-wrapper.compact .btn-apply-voucher {
    padding: 0.6rem 0.85rem;
    font-size: 0.85rem;
}

.checkout-layout.buynow-layout .voucher-link {
    font-size: 0.75rem;
}

.checkout-layout.buynow-layout .points-info-compact {
    margin-bottom: 0.6rem;
}

.checkout-layout.buynow-layout .points-badge {
    font-size: 0.8rem;
    padding: 0.3rem 0.65rem;
}

.checkout-layout.buynow-layout .points-equivalent {
    font-size: 0.8rem;
}

.checkout-layout.buynow-layout .points-form-compact input {
    padding: 0.6rem 0.85rem;
    font-size: 0.85rem;
    max-width: 130px;
}

.checkout-layout.buynow-layout .points-form-compact .btn-apply-points {
    padding: 0.6rem 0.75rem;
}

.checkout-layout.buynow-layout .points-form-compact .btn-use-all-points {
    padding: 0.6rem 0.85rem;
    font-size: 0.8rem;
}

/* Payment Options */
.checkout-layout.buynow-layout .payment-options {
    display: flex;
    gap: 0.6rem;
}

.checkout-layout.buynow-layout .payment-option .option-content {
    padding: 0.65rem 0.5rem;
    border-radius: 10px;
}

.checkout-layout.buynow-layout .payment-option .option-content i {
    font-size: 1.15rem;
}

.checkout-layout.buynow-layout .payment-option .option-content span {
    font-size: 0.75rem;
}

/* QR Box compact */
.checkout-layout.buynow-layout .qr-transfer-box {
    margin-top: 1rem;
    border-radius: 12px;
}

.checkout-layout.buynow-layout .qr-transfer-box .qr-header {
    padding: 0.6rem 0.9rem;
    font-size: 0.85rem;
}

.checkout-layout.buynow-layout .qr-transfer-box .qr-content {
    padding: 1rem;
    gap: 1rem;
}

.checkout-layout.buynow-layout .qr-transfer-box .qr-image {
    width: 120px;
    height: 120px;
}

.checkout-layout.buynow-layout .qr-transfer-box .bank-details p {
    font-size: 0.85rem;
    margin: 0.3rem 0;
}

/* Member Card Box compact */
.checkout-layout.buynow-layout .member-card-box {
    margin-top: 1rem;
    border-radius: 12px;
}

.checkout-layout.buynow-layout .member-card-box .card-info-header {
    padding: 0.6rem 0.9rem;
    font-size: 0.85rem;
}

.checkout-layout.buynow-layout .member-card-box .card-info-content {
    padding: 1rem;
}

.checkout-layout.buynow-layout .member-card-box .card-visual {
    padding: 1rem;
    border-radius: 10px;
}

.checkout-layout.buynow-layout .member-card-box .card-number {
    font-size: 0.95rem;
}

.checkout-layout.buynow-layout .member-card-box .balance-amount {
    font-size: 1.25rem;
}

/* Submit Button */
.checkout-layout.buynow-layout .submit-btn {
    padding: 0.9rem 1rem;
    font-size: 0.95rem;
    border-radius: 12px;
    margin-top: 0.5rem;
}

/* === RESPONSIVE === */
@media (max-width: 768px) {
    .checkout-layout.buynow-layout {
        max-width: 100%;
        padding: 0 0.5rem;
    }
}

@media (max-width: 600px) {
    .checkout-layout.buynow-layout .payment-options {
        flex-direction: column;
    }
    
    .checkout-layout.buynow-layout .qr-transfer-box .qr-content {
        flex-direction: column;
        text-align: center;
    }
}

/* Form Cards */
.form-card, .summary-card,
.checkout-page .form-card,
.checkout-page .summary-card,
body.dark-theme .checkout-page .form-card,
body.dark-theme .checkout-page .summary-card {
    background: #ffffff !important;
    border-radius: 16px;
    border: 1px solid #e5e7eb !important;
    overflow: hidden;
    margin-bottom: 0;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08) !important;
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
    transition: box-shadow 0.3s ease, transform 0.3s ease;
}

.form-card:hover,
.checkout-page .form-card:hover {
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12) !important;
}

.card-header,
.checkout-page .card-header,
body.dark-theme .checkout-page .card-header {
    display: flex !important;
    align-items: center !important;
    justify-content: flex-start !important;
    flex-direction: row !important;
    gap: 0.75rem !important;
    padding: 1rem 1.5rem;
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    border-bottom: none;
}

.card-header i,
.checkout-page .card-header i,
body.dark-theme .checkout-page .card-header i {
    color: #fff;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.card-header h3,
.checkout-page .card-header h3,
body.dark-theme .checkout-page .card-header h3 {
    margin: 0;
    color: #fff !important;
    font-size: 1rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

.card-body {
    padding: 1.25rem 1.5rem;
}

/* Section Label & Divider - NEW */
.section-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.95rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.85rem;
}
.section-label i {
    color: #22c55e;
    font-size: 0.9rem;
}
.section-divider {
    display: none !important;
}

/* Đảm bảo section-divider ẩn hoàn toàn */
.checkout-page .section-divider,
.checkout-layout .section-divider,
.form-card .section-divider {
    display: none !important;
}

/* Override any green bar from global CSS */
.checkout-page .form-card .card-body::before,
.checkout-page .form-card .card-body::after {
    display: none !important;
}

/* ========== DISCOUNT CARD - MODERN GREEN DESIGN ========== */
.discount-card {
    background: #ffffff;
    border: 2px solid #22c55e;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(34, 197, 94, 0.15);
}

.discount-card .card-header {
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%) !important;
    padding: 1rem 1.25rem;
}

.discount-card .card-header i {
    color: #ffffff !important;
    font-size: 1.1rem;
    background: rgba(255, 255, 255, 0.2);
    padding: 0.5rem;
    border-radius: 8px;
}

.discount-card .card-header h3 {
    color: #ffffff !important;
    font-size: 1rem;
    font-weight: 700;
    letter-spacing: 0.5px;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.discount-card .card-body {
    padding: 1.25rem;
    background: #ffffff;
}

/* Discount Sections Layout */
.discount-section {
    margin-bottom: 1.5rem;
    padding-bottom: 1.25rem;
    border-bottom: 1px dashed #e5e7eb;
}

.discount-section:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
}

.section-label {
    font-size: 0.85rem;
    font-weight: 700;
    color: #1f2937;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.875rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.section-label i {
    color: #22c55e;
    font-size: 0.9rem;
}

/* Voucher Input - Modern Green Style */
.voucher-input-wrapper.compact {
    margin-bottom: 0;
}

.voucher-input-wrapper.compact .voucher-input-group {
    display: flex;
    gap: 0.5rem;
}

.voucher-input-wrapper.compact input {
    flex: 1;
    padding: 0.875rem 1rem;
    background: #f0fdf4;
    border: 2px solid #bbf7d0;
    border-radius: 10px;
    font-size: 0.9rem;
    color: #1f2937;
    font-weight: 500;
    transition: all 0.2s;
}

.voucher-input-wrapper.compact input:focus {
    outline: none;
    border-color: #22c55e;
    background: #ffffff;
    box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.15);
}

.voucher-input-wrapper.compact input::placeholder {
    color: #6b7280;
}

.voucher-input-wrapper.compact .btn-apply-voucher {
    padding: 0.875rem 1.5rem;
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    color: #ffffff;
    border: none;
    border-radius: 10px;
    font-size: 0.85rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;
    box-shadow: 0 2px 8px rgba(34, 197, 94, 0.3);
}

.voucher-input-wrapper.compact .btn-apply-voucher:hover {
    background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(34, 197, 94, 0.4);
}

.voucher-link {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    margin-top: 0.75rem;
    font-size: 0.8rem;
    color: #16a34a;
    text-decoration: none;
    font-weight: 600;
    transition: color 0.2s;
}

.voucher-link:hover {
    color: #15803d;
    text-decoration: underline;
}

.voucher-link i {
    font-size: 0.75rem;
}

/* Voucher Applied Box - Green Design */
.voucher-applied-box {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
    border: 2px solid #86efac;
    border-radius: 12px;
    padding: 1rem 1.25rem;
}

.voucher-applied-info {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.voucher-code-badge {
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    color: #000000;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 800;
    letter-spacing: 0.5px;
    box-shadow: 0 2px 6px rgba(34, 197, 94, 0.3);
}

.voucher-details {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.voucher-name {
    font-size: 0.85rem;
    color: #374151;
    font-weight: 500;
}

.voucher-discount-amount {
    font-size: 1rem;
    font-weight: 800;
    color: #15803d;
}

.btn-remove-voucher {
    background: #ffffff;
    border: 2px solid #fecaca;
    color: #ef4444;
    width: 36px;
    height: 36px;
    border-radius: 10px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.btn-remove-voucher:hover {
    background: #fef2f2;
    border-color: #ef4444;
    transform: scale(1.05);
}

/* Points Section - Modern Green Style */
.points-info-compact {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1rem;
    padding: 0.75rem 1rem;
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
    border-radius: 10px;
    border: 1px solid #bbf7d0;
}

.points-badge {
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    color: #ffffff;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 800;
    box-shadow: 0 2px 6px rgba(34, 197, 94, 0.3);
}

.points-equivalent {
    color: #15803d;
    font-size: 1rem;
    font-weight: 700;
}

.points-form-compact .points-input-row {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.points-form-compact input {
    flex: 1;
    padding: 0.875rem 1rem;
    background: #f0fdf4;
    border: 2px solid #bbf7d0;
    border-radius: 10px;
    font-size: 0.9rem;
    color: #1f2937;
    font-weight: 500;
    max-width: 150px;
    transition: all 0.2s;
}

.points-form-compact input:focus {
    outline: none;
    border-color: #22c55e;
    background: #ffffff;
    box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.15);
}

.points-form-compact .btn-apply-points {
    padding: 0.875rem 1.25rem;
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    color: #ffffff;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.2s;
}

.points-form-compact .btn-apply-points:hover {
    background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
    transform: translateY(-1px);
}

.points-form-compact .btn-use-all-points {
    padding: 0.875rem 1.25rem;
    background: #ffffff;
    color: #16a34a;
    border: 2px solid #22c55e;
    border-radius: 10px;
    font-size: 0.85rem;
    font-weight: 700;
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.2s;
}

.points-form-compact .btn-use-all-points:hover {
    background: #f0fdf4;
    border-color: #16a34a;
}

/* Points Applied Box - Green Design */
.points-applied-box.compact {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
    padding: 1rem 1.25rem;
    border-radius: 12px;
    border: 2px solid #86efac;
}

.points-applied-box.compact .points-applied-info {
    display: flex;
    align-items: center;
    gap: 0.625rem;
    color: #15803d;
    font-size: 0.9rem;
    font-weight: 600;
}

.points-applied-box.compact .points-applied-info i {
    color: #22c55e;
    font-size: 1.1rem;
}

.points-applied-box.compact .btn-remove-points {
    background: #ffffff;
    border: 2px solid #fecaca;
    color: #ef4444;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.points-applied-box.compact .btn-remove-points:hover {
    background: #fef2f2;
    border-color: #ef4444;
    transform: scale(1.05);
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
    color: #374151 !important;
    font-size: 0.85rem;
    margin-bottom: 0.4rem;
    font-weight: 600;
}

.form-group label i,
.checkout-page .form-group label i,
body.dark-theme .checkout-page .form-group label i {
    color: #22c55e !important;
    margin-right: 0.35rem;
    font-size: 0.8rem;
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
    padding: 0.7rem 0.9rem;
    background: #ffffff !important;
    border: 1px solid #d1d5db !important;
    border-radius: 8px;
    color: #111827 !important;
    font-size: 0.9rem;
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
    box-shadow: 0 0 0 2px rgba(34, 197, 94, 0.1);
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

/* Edit Profile Hint */
.edit-profile-hint {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-top: 0.75rem;
    margin-bottom: 0;
    padding: 0.6rem 0.9rem;
    background: #fef3c7;
    border: 1px solid #fcd34d;
    border-radius: 8px;
    font-size: 0.8rem;
    color: #92400e;
}

.edit-profile-hint i {
    color: #f59e0b;
}

.edit-profile-hint a {
    color: #d97706;
    font-weight: 600;
    text-decoration: underline;
}

.edit-profile-hint a:hover {
    color: #b45309;
}

/* Delivery Info */
.delivery-info {
    background: transparent;
    border: none;
    border-radius: 0;
    padding: 0.75rem 1rem;
    margin-top: 0.75rem;
}

.info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.35rem 0;
    color: #4b5563;
    font-size: 0.85rem;
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
    gap: 0.75rem;
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
    gap: 0.4rem;
    padding: 0.75rem 0.5rem;
    background: #f8fafc;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    transition: all 0.2s;
}

.option-content i {
    font-size: 1.25rem;
    color: #9ca3af;
}

.option-content span {
    font-size: 0.8rem;
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
    font-weight: 600;
}

/* Member Card Box */
.member-card-box {
    margin-top: 1rem;
    background: linear-gradient(145deg, #faf5ff, #ede9fe);
    border: 1px solid #c4b5fd;
    border-radius: 12px;
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
    border: 2px solid #22c55e;
    border-radius: 16px;
    overflow: hidden;
    animation: slideDown 0.3s ease;
    box-shadow: 0 4px 15px rgba(34, 197, 94, 0.15);
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.qr-header {
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    padding: 1rem 1.25rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    color: white;
    font-weight: 700;
    font-size: 1rem;
}

.qr-header i {
    font-size: 1.2rem;
}

.qr-content {
    padding: 1.5rem;
    display: flex;
    gap: 1.5rem;
    align-items: flex-start;
    background: white;
}

.qr-image {
    width: 160px;
    height: 160px;
    background: #fff;
    border-radius: 12px;
    padding: 10px;
    flex-shrink: 0;
    border: 3px solid #22c55e;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
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
    margin-bottom: 1rem;
    padding-bottom: 0.75rem;
    border-bottom: 2px solid #e5e7eb;
}

.bank-logo img {
    height: 35px;
}

.bank-details {
    background: #f8fafc;
    border-radius: 12px;
    padding: 1rem;
    border: 1px solid #e5e7eb;
}

.bank-details p {
    color: #374151;
    font-size: 0.95rem;
    margin: 0.6rem 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.bank-details strong {
    color: #1f2937;
    font-weight: 700;
    font-size: 1rem;
}

.bank-details .amount-value {
    color: #dc2626;
    font-size: 1.15rem;
    font-weight: 800;
}

.bank-details .content-value {
    color: #22c55e;
    font-family: 'Courier New', monospace;
    font-weight: 700;
}

.transfer-note {
    margin-top: 1rem !important;
    padding: 1rem;
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    border: 2px solid #f59e0b;
    border-radius: 10px;
    color: #92400e !important;
    font-size: 0.9rem !important;
    font-weight: 500;
}

.transfer-note i {
    margin-right: 0.5rem;
    color: #f59e0b;
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
    padding: 1.15rem 1.5rem;
    background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
    border: none;
    border-radius: 14px;
    color: #fff;
    font-size: 1.1rem;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.6rem;
    transition: all 0.3s;
    margin-top: 1.5rem;
    box-shadow: 0 4px 15px rgba(220, 38, 38, 0.25);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.submit-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 35px rgba(220, 38, 38, 0.35);
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}

.submit-btn:active {
    transform: translateY(-1px);
}

.submit-btn i {
    font-size: 1.2rem;
}

.checkout-form-section .form-card,
.checkout-form-section .discount-card {
    width: 100% !important;
    max-width: 100% !important;
    box-sizing: border-box !important;
    flex-shrink: 0;
}

.summary-card {
    margin-bottom: 1rem;
    border: 1px solid #e5e7eb !important;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08) !important;
    border-radius: 16px !important;
}

.summary-card .card-header {
    padding: 1.25rem 1.5rem;
    border-radius: 16px 16px 0 0;
    background: #ffffff;
    border-bottom: 1px solid #e5e7eb;
}

/* Order Items */
.order-items {
    max-height: 350px;
    overflow-y: auto;
    padding: 1.25rem;
    border-bottom: 1px solid #e5e7eb;
    background: #ffffff;
}

.order-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: #f9fafb;
    border-radius: 12px;
    margin-bottom: 0.75rem;
    border: 1px solid #e5e7eb;
    transition: transform 0.2s, box-shadow 0.2s;
}

.order-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
    background: #f3f4f6;
}

.order-item:last-child {
    margin-bottom: 0;
}

.item-img {
    width: 65px;
    height: 65px;
    border-radius: 10px;
    object-fit: cover;
    flex-shrink: 0;
    border: 1px solid #e5e7eb;
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
    margin: 0 0 0.35rem;
    color: #1f2937;
    font-size: 0.95rem;
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.item-qty {
    color: #6b7280;
    font-size: 0.8rem;
    background: #e5e7eb;
    padding: 0.2rem 0.6rem;
    border-radius: 6px;
    display: inline-block;
    font-weight: 500;
}

.item-price {
    color: #dc2626;
    font-weight: 700;
    font-size: 1rem;
}

.buynow-item {
    background: #f9fafb;
    border-radius: 12px;
    padding: 1rem !important;
    border: 1px solid #e5e7eb !important;
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
    padding: 1.25rem 1.5rem;
    background: #ffffff;
    border-top: 1px solid #e5e7eb;
}

.total-row {
    display: flex;
    justify-content: space-between;
    padding: 0.6rem 0;
    color: #4b5563;
    font-size: 0.95rem;
}

.total-row.discount {
    color: #16a34a;
}

.total-row.discount.points-discount-row {
    color: #d97706;
}

.total-row.discount.points-discount-row i {
    color: #d97706;
}

.total-row.final {
    border-top: 1px solid #e5e7eb;
    margin-top: 0.75rem;
    padding-top: 1rem;
    background: #f9fafb;
    margin: 0.75rem -1.5rem -1.25rem;
    padding: 1rem 1.5rem;
    border-radius: 0 0 16px 16px;
}

.total-row.final span:first-child {
    color: #1f2937;
    font-weight: 700;
    font-size: 1.1rem;
}

.total-row.final span:last-child {
    color: #dc2626;
    font-weight: 800;
    font-size: 1.4rem;
}

/* Shipping Info */
.shipping-info {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.25rem;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    margin-top: 1rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.shipping-info i {
    font-size: 1.5rem;
    color: #16a34a;
    width: 44px;
    height: 44px;
    background: #f0fdf4;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.shipping-info strong {
    display: block;
    font-size: 0.95rem;
    color: #1f2937;
    margin-bottom: 0.25rem;
    font-weight: 600;
}

.shipping-info span {
    color: #6b7280;
    font-size: 0.85rem;
}


/* Responsive - General */
@media (max-width: 768px) {
    .checkout-hero {
        padding: 2rem 1rem;
    }
    
    .checkout-hero .about-hero-title {
        font-size: 1.5rem;
    }
    
    .checkout-hero .about-hero-title i {
        font-size: 1.25rem;
    }
    
    .checkout-hero .about-hero-subtitle {
        font-size: 0.9rem;
    }
    
    .checkout-breadcrumb {
        padding: 0.75rem 0;
        font-size: 0.85rem;
        gap: 0.5rem;
    }
    
    .checkout-container {
        padding: 1rem;
    }
    
    .checkout-page .checkout-layout {
        max-width: 100%;
        padding: 0;
    }

    .checkout-header h1 {
        font-size: 1.25rem;
    }

    .payment-options {
        flex-direction: column;
    }
    
    .card-header {
        padding: 0.85rem 1rem;
    }
    
    .card-header h3 {
        font-size: 0.9rem;
    }
    
    .card-body {
        padding: 1rem;
    }
    
    .submit-btn {
        padding: 1rem;
        font-size: 1rem;
    }
}

@media (max-width: 480px) {
    .checkout-hero {
        padding: 1.5rem 1rem;
    }
    
    .checkout-hero .about-hero-title {
        font-size: 1.25rem;
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .checkout-breadcrumb {
        font-size: 0.8rem;
    }
    
    .order-item {
        padding: 0.75rem;
        gap: 0.75rem;
    }
    
    .item-img {
        width: 50px;
        height: 50px;
    }
    
    .total-row.final {
        padding: 0.85rem 1rem;
        margin: 0.75rem -1rem -1rem;
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
    const voucherDiscountInit = <?php echo $voucher_discount; ?>;
    const pointsDiscountInit = <?php echo $points_discount; ?>;
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
        // Lấy giảm giá từ voucher và điểm thưởng (từ input hidden hoặc từ PHP)
        const voucherDiscount = parseInt(document.getElementById('voucherDiscountValue')?.value) || voucherDiscountInit || 0;
        const pointsDiscount = parseInt(document.getElementById('pointsDiscountValue')?.value) || pointsDiscountInit || 0;
        
        const total = currentSubtotal + deliveryFee - promoDiscount - comboDiscount - voucherDiscount - pointsDiscount;
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

// ========== VOUCHER FUNCTIONS ==========
function applyVoucher() {
    const codeInput = document.getElementById('voucherCodeInput');
    const msgDiv = document.getElementById('voucherMessage');
    
    if (!codeInput || !msgDiv) {
        alert('Lỗi: Không tìm thấy form voucher');
        return;
    }
    
    const code = codeInput.value.trim().toUpperCase();
    
    // Lấy subtotal - ưu tiên từ PHP, fallback từ sessionStorage hoặc DOM
    let subtotal = <?php echo intval($subtotal); ?>;
    
    // Mode buynow: lấy từ sessionStorage
    if (subtotal <= 0) {
        const buyNowItem = sessionStorage.getItem('buyNowItem');
        if (buyNowItem) {
            try {
                const item = JSON.parse(buyNowItem);
                subtotal = (item.price || 0) * (item.quantity || 1);
            } catch(e) {}
        }
    }
    
    // Fallback: lấy từ tổng tiền hiển thị trên trang
    if (subtotal <= 0) {
        const totalEl = document.querySelector('.summary-total .value') || 
                        document.querySelector('.total-value') ||
                        document.querySelector('#orderTotal');
        if (totalEl) {
            subtotal = parseInt(totalEl.textContent.replace(/[^\d]/g, '')) || 0;
        }
    }
    
    // Validate
    if (!code) {
        msgDiv.className = 'voucher-message error';
        msgDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> Vui lòng nhập mã voucher';
        msgDiv.style.display = 'flex';
        return;
    }
    
    if (subtotal <= 0) {
        msgDiv.className = 'voucher-message error';
        msgDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> Không thể xác định giá trị đơn hàng';
        msgDiv.style.display = 'flex';
        return;
    }
    
    // Disable button
    const btn = document.querySelector('.btn-apply-voucher');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    }
    
    // Gọi API
    fetch('api/voucher.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=apply&code=${encodeURIComponent(code)}&order_total=${subtotal}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // Hiển thị thành công rồi reload
            msgDiv.className = 'voucher-message success';
            msgDiv.innerHTML = '<i class="fas fa-check-circle"></i> ' + data.message;
            msgDiv.style.display = 'flex';
            setTimeout(() => location.reload(), 500);
        } else {
            msgDiv.className = 'voucher-message error';
            msgDiv.innerHTML = '<i class="fas fa-times-circle"></i> ' + data.message;
            msgDiv.style.display = 'flex';
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check"></i> Áp dụng';
            }
        }
    })
    .catch(err => {
        console.error('Voucher error:', err);
        msgDiv.className = 'voucher-message error';
        msgDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Lỗi kết nối server';
        msgDiv.style.display = 'flex';
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check"></i> Áp dụng';
        }
    });
}

function removeVoucher() {
    fetch('api/voucher.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=remove'
    })
    .then(() => location.reload());
}

// Enter to apply voucher
document.getElementById('voucherCodeInput')?.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        applyVoucher();
    }
});

// ========== POINTS FUNCTIONS ==========
const pointsConfig = {
    available: <?php echo intval($available_points); ?>,
    toMoney: <?php echo intval($points_to_money); ?>,
    minRedeem: <?php echo intval($min_redeem_points); ?>,
    maxRedeemPercent: <?php echo intval($max_redeem_percent); ?>
};

function applyPoints() {
    const pointsInput = document.getElementById('pointsInput');
    const msgDiv = document.getElementById('pointsMessage');
    const points = parseInt(pointsInput?.value) || 0;
    
    // Lấy subtotal
    let subtotal = <?php echo intval($subtotal); ?>;
    if (subtotal <= 0) {
        const subtotalText = document.getElementById('subtotalDisplay')?.textContent || '0';
        subtotal = parseInt(subtotalText.replace(/[^\d]/g, '')) || 0;
    }
    
    console.log('Points apply - Points:', points, 'Subtotal:', subtotal);
    
    // Validate
    if (points <= 0) {
        if (msgDiv) {
            msgDiv.className = 'points-message error';
            msgDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> Vui lòng nhập số điểm muốn sử dụng';
        }
        return;
    }
    
    if (points > pointsConfig.available) {
        if (msgDiv) {
            msgDiv.className = 'points-message error';
            msgDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> Bạn chỉ có ' + pointsConfig.available.toLocaleString() + ' điểm';
        }
        return;
    }
    
    // Disable button
    const btn = document.querySelector('.btn-apply-points');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    }
    
    fetch('api/points.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=apply&order_total=${subtotal}&points=${points}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            if (msgDiv) {
                msgDiv.className = 'points-message error';
                msgDiv.innerHTML = '<i class="fas fa-times-circle"></i> ' + data.message;
            }
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check"></i> Dùng điểm';
            }
        }
    })
    .catch(err => {
        console.error('Points error:', err);
        if (msgDiv) {
            msgDiv.className = 'points-message error';
            msgDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Lỗi kết nối';
        }
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check"></i> Dùng điểm';
        }
    });
}

function useAllPoints() {
    const pointsInput = document.getElementById('pointsInput');
    if (pointsInput) {
        // Dùng tất cả điểm khả dụng (không giới hạn % đơn hàng)
        pointsInput.value = pointsConfig.available;
        applyPoints();
    }
}

function removePoints() {
    fetch('api/points.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=remove'
    })
    .then(() => location.reload());
}

// Update points display when input changes
document.getElementById('pointsInput')?.addEventListener('input', function() {
    const points = parseInt(this.value) || 0;
    const value = points * pointsConfig.toMoney;
    const msgDiv = document.getElementById('pointsMessage');
    
    if (points > pointsConfig.available) {
        if (msgDiv) {
            msgDiv.className = 'points-message error';
            msgDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> Bạn chỉ có ' + pointsConfig.available.toLocaleString() + ' điểm';
        }
        this.value = pointsConfig.available;
    } else if (points > 0) {
        if (msgDiv) {
            msgDiv.className = 'points-message success';
            msgDiv.innerHTML = '<i class="fas fa-check-circle"></i> = ' + value.toLocaleString() + 'đ';
        }
    } else {
        if (msgDiv) {
            msgDiv.className = 'points-message';
            msgDiv.innerHTML = '';
        }
    }
});

// Enter to apply points
document.getElementById('pointsInput')?.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        applyPoints();
    }
});
</script>

<style>
/* ========== VOUCHER STYLES ========== */
.voucher-card .card-header {
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%) !important;
}

.voucher-input-wrapper {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.voucher-input-group {
    display: flex;
    gap: 10px;
}

.voucher-input-group input {
    flex: 1;
    padding: 14px 16px;
    background: #f9fafb;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    color: #1f2937;
    font-size: 15px;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.voucher-input-group input:focus {
    border-color: #22c55e;
    outline: none;
    background: #f0fdf4;
}

.voucher-input-group input::placeholder {
    text-transform: none;
    letter-spacing: normal;
    color: #9ca3af;
}

.btn-apply-voucher {
    padding: 14px 24px;
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    color: #fff;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    white-space: nowrap;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
}

.btn-apply-voucher:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(34,197,94,0.4);
}

.btn-apply-voucher:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

.voucher-message {
    padding: 10px 14px;
    border-radius: 8px;
    font-size: 14px;
    display: none;
}

.voucher-message.error {
    display: flex;
    align-items: center;
    gap: 8px;
    background: rgba(239,68,68,0.15);
    color: #f87171;
    border: 1px solid rgba(239,68,68,0.3);
}

.voucher-message.success {
    display: flex;
    align-items: center;
    gap: 8px;
    background: rgba(34,197,94,0.15);
    color: #4ade80;
    border: 1px solid rgba(34,197,94,0.3);
}

.voucher-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: #22c55e;
    font-size: 13px;
    text-decoration: none;
    transition: color 0.3s;
}

.voucher-link:hover {
    color: #4ade80;
}

/* Voucher Applied Box - Green Style */
.voucher-applied-box {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #f0fdf4;
    border: 2px solid #22c55e;
    border-radius: 10px;
    padding: 0.75rem 1rem;
}

.voucher-applied-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.voucher-code-badge {
    background: #22c55e;
    color: #000000;
    padding: 0.35rem 0.7rem;
    border-radius: 6px;
    font-family: monospace;
    font-weight: 700;
    font-size: 0.85rem;
    letter-spacing: 0.5px;
}

.voucher-details {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.voucher-name {
    font-size: 0.85rem;
    color: #166534;
    font-weight: 500;
}

.voucher-discount-amount {
    font-size: 0.9rem;
    color: #15803d;
    font-weight: 700;
}

.btn-remove-voucher {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #ef4444;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}
.btn-remove-voucher:hover {
    background: #ef4444;
    color: #fff;
}

.voucher-name {
    color: #000000;
    font-weight: 600;
    font-size: 14px;
}

.voucher-discount-amount {
    color: #4ade80;
    font-weight: 700;
    font-size: 16px;
}

.btn-remove-voucher {
    background: rgba(239,68,68,0.2);
    color: #f87171;
    border: none;
    padding: 8px 14px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: all 0.3s;
}

.btn-remove-voucher:hover {
    background: rgba(239,68,68,0.3);
}

@media (max-width: 480px) {
    .voucher-input-group {
        flex-direction: column;
    }
    
    .voucher-applied-box {
        flex-direction: column;
        gap: 12px;
        text-align: center;
    }
    
    .voucher-applied-info {
        flex-direction: column;
    }
}

/* ========== POINTS STYLES ========== */
.points-card .card-header {
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%) !important;
}

.points-info-box {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.points-available {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    background: rgba(34,197,94,0.1);
    border-radius: 10px;
    border: 1px solid rgba(34,197,94,0.2);
}

.points-label {
    color: #9ca3af;
    font-size: 14px;
}

.points-value {
    color: #22c55e;
    font-weight: 700;
    font-size: 18px;
}

.points-equivalent {
    color: #fbbf24;
    font-size: 14px;
    margin-left: auto;
}

.points-input-group {
    display: flex;
    gap: 10px;
}

.points-input-group input {
    flex: 1;
    padding: 14px 16px;
    background: #fff;
    border: 2px solid #ddd;
    border-radius: 10px;
    color: #333;
    font-size: 15px;
}

.points-input-group input:focus {
    border-color: #22c55e;
    outline: none;
}

.points-input-group input::placeholder {
    color: rgba(255,255,255,0.4);
}

.btn-apply-points {
    padding: 14px 24px;
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    color: #fff;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    white-space: nowrap;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
}

.btn-apply-points:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(34,197,94,0.4);
}

.btn-apply-points:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

.points-message {
    padding: 10px 14px;
    border-radius: 8px;
    font-size: 14px;
    display: none;
}

.points-message.error {
    display: flex;
    align-items: center;
    gap: 8px;
    background: rgba(239,68,68,0.15);
    color: #f87171;
    border: 1px solid rgba(239,68,68,0.3);
}

.points-message.warning {
    display: flex;
    align-items: center;
    gap: 8px;
    background: rgba(251,191,36,0.15);
    color: #fbbf24;
    border: 1px solid rgba(251,191,36,0.3);
}

.points-message.success {
    display: flex;
    align-items: center;
    gap: 8px;
    background: rgba(34,197,94,0.15);
    color: #4ade80;
    border: 1px solid rgba(34,197,94,0.3);
}

.points-hint {
    font-size: 13px;
    color: #666;
    display: flex;
    align-items: center;
    gap: 6px;
    margin-top: 8px;
}

.points-hint i {
    color: #22c55e;
}

.btn-use-all-points {
    padding: 10px 16px;
    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
    color: #1a1a2e;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
    font-size: 14px;
}

.btn-use-all-points:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(251,191,36,0.3);
}

/* Points Form Simple */
.points-form-simple {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.points-form-simple .points-input-row {
    display: flex;
    gap: 10px;
}

.points-form-simple .points-input-row input {
    flex: 1;
    padding: 12px 16px;
    border: 2px solid #ddd;
    border-radius: 8px;
    font-size: 15px;
    color: #333;
    background: #fff;
}

.points-form-simple .points-input-row input:focus {
    border-color: #22c55e;
    outline: none;
}

.points-form-simple .points-input-row input::placeholder {
    color: #999;
}

.points-actions {
    display: flex;
    gap: 10px;
}

/* Points Applied Box */
.points-applied-box {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
    border: 2px solid #86efac;
    border-radius: 12px;
    padding: 16px;
    margin-top: 12px;
}

.points-applied-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.points-applied-info i {
    color: #16a34a;
    font-size: 24px;
}

.points-applied-text {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.points-used-label {
    color: #166534;
    font-size: 15px;
}

.points-used-label strong {
    color: #15803d;
}

.points-discount-value {
    color: #16a34a;
    font-weight: 700;
    font-size: 18px;
}

.btn-remove-points {
    background: #fecaca;
    color: #dc2626;
    border: none;
    padding: 10px 16px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: all 0.3s;
}

.btn-remove-points:hover {
    background: #fca5a5;
}

@media (max-width: 480px) {
    .points-form-simple .points-input-row {
        flex-direction: column;
    }
    
    .points-applied-box {
        flex-direction: column;
        gap: 12px;
        text-align: center;
    }
    
    .points-applied-info {
        flex-direction: column;
    }
    
    .points-equivalent {
        margin-left: 0;
        width: 100%;
    }
}

/* === MOBILE: ĐƠN HÀNG LÊN TRÊN - FINAL OVERRIDE === */
@media (max-width: 960px) {
    .checkout-layout {
        display: flex !important;
        flex-direction: column !important;
    }
    
    .checkout-layout .order-summary-section {
        order: -1 !important;
    }
    
    .checkout-layout .checkout-form-section {
        order: 1 !important;
    }
}
</style>
