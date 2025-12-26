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
    
    // Kiểm tra nếu đang áp dụng combo, xóa giảm giá combo
    if (isset($_SESSION['applied_promo']) && $_SESSION['applied_promo']['type'] === 'combo') {
        // Lấy promo_id của combo đang áp dụng
        $promo_id = $_SESSION['applied_promo']['promo_id'] ?? 0;
        
        if ($promo_id > 0) {
            // Lấy danh sách món trong combo
            $stmt = $conn->prepare("SELECT menu_item_id FROM promotion_items WHERE promotion_id = ?");
            $stmt->execute([$promo_id]);
            $combo_menu_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Lấy danh sách món trong giỏ hàng hiện tại
            $stmt = $conn->prepare("SELECT menu_item_id FROM cart WHERE customer_id = ?");
            $stmt->execute([$_SESSION['customer_id']]);
            $cart_menu_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Kiểm tra xem tất cả món trong combo có còn trong giỏ không
            $combo_complete = true;
            foreach ($combo_menu_ids as $menu_id) {
                if (!in_array($menu_id, $cart_menu_ids)) {
                    $combo_complete = false;
                    break;
                }
            }
            
            // Nếu combo không còn đủ món, xóa giảm giá
            if (!$combo_complete) {
                unset($_SESSION['applied_promo']);
            }
        }
    }
    
    echo '<script>window.location.href = "?page=cart";</script>';
    exit;
}

// Xử lý xóa toàn bộ giỏ hàng
if (isset($_GET['clear_all'])) {
    $stmt = $conn->prepare("DELETE FROM cart WHERE customer_id = ?");
    $stmt->execute([$_SESSION['customer_id']]);
    // Xóa luôn khuyến mãi đã áp dụng
    unset($_SESSION['applied_promo']);
    echo '<script>window.location.href = "?page=cart";</script>';
    exit;
}

// Lấy giỏ hàng
$stmt = $conn->prepare("
    SELECT c.*, m.name, m.price, m.image, m.is_available 
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
$item_count = count($cart_items);

// Kiểm tra khuyến mãi combo đã áp dụng (hỗ trợ nhiều combo)
$applied_combos = $_SESSION['applied_combos'] ?? [];
$applied_combo = $_SESSION['applied_promo'] ?? null;
$combo_discount = 0;
$final_total = $total;

// Tạo map số lượng món trong giỏ hàng
$cart_quantities = [];
foreach ($cart_items as $item) {
    $cart_quantities[$item['menu_item_id']] = ($cart_quantities[$item['menu_item_id']] ?? 0) + $item['quantity'];
}

$valid_combos = [];

if (!empty($applied_combos)) {
    foreach ($applied_combos as $combo) {
        $promo_id = $combo['promo_id'] ?? 0;
        $combo_count = $combo['count'] ?? 1; // Số lần đặt combo này
        
        if ($promo_id > 0) {
            // Lấy danh sách món và số lượng cần cho combo
            $stmt = $conn->prepare("SELECT menu_item_id, quantity FROM promotion_items WHERE promotion_id = ?");
            $stmt->execute([$promo_id]);
            $combo_items_needed = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Kiểm tra xem giỏ hàng có đủ số lượng cho combo không
            $combo_valid = true;
            foreach ($combo_items_needed as $needed) {
                $menu_id = $needed['menu_item_id'];
                $qty_needed = $needed['quantity'] * $combo_count; // Số lượng cần = số lượng trong combo x số lần đặt
                $qty_in_cart = $cart_quantities[$menu_id] ?? 0;
                
                if ($qty_in_cart < $qty_needed) {
                    $combo_valid = false;
                    break;
                }
            }
            
            if ($combo_valid) {
                $valid_combos[] = $combo;
                $combo_discount += $combo['discount_amount'] ?? 0;
            }
        }
    }
    
    // Cập nhật lại session với các combo còn hợp lệ
    if (count($valid_combos) != count($applied_combos)) {
        $_SESSION['applied_combos'] = $valid_combos;
        if (empty($valid_combos)) {
            unset($_SESSION['applied_promo']);
            unset($_SESSION['applied_combos']);
            $applied_combo = null;
        } else {
            $combo_titles = array_column($valid_combos, 'title');
            $_SESSION['applied_promo'] = [
                'promo_id' => $valid_combos[0]['promo_id'],
                'title' => implode(' + ', $combo_titles),
                'type' => 'combo',
                'discount_amount' => $combo_discount,
                'combo_count' => count($valid_combos)
            ];
            $applied_combo = $_SESSION['applied_promo'];
        }
    }
    
    $final_total = $total - $combo_discount;
    if ($final_total < 0) $final_total = 0;
} elseif ($applied_combo && $applied_combo['type'] === 'combo') {
    // Tương thích với code cũ (1 combo)
    $promo_id = $applied_combo['promo_id'] ?? 0;
    
    if ($promo_id > 0) {
        $stmt = $conn->prepare("SELECT menu_item_id, quantity FROM promotion_items WHERE promotion_id = ?");
        $stmt->execute([$promo_id]);
        $combo_items_needed = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $combo_valid = true;
        foreach ($combo_items_needed as $needed) {
            $menu_id = $needed['menu_item_id'];
            $qty_needed = $needed['quantity'];
            $qty_in_cart = $cart_quantities[$menu_id] ?? 0;
            
            if ($qty_in_cart < $qty_needed) {
                $combo_valid = false;
                break;
            }
        }
        
        if ($combo_valid) {
            $combo_discount = $applied_combo['discount_amount'] ?? 0;
            $final_total = $total - $combo_discount;
            if ($final_total < 0) $final_total = 0;
        } else {
            // Combo không còn đủ món, xóa khỏi session
            unset($_SESSION['applied_promo']);
            $applied_combo = null;
        }
    }
}
?>

<section class="cart-page">
    <div class="cart-container">
        <!-- Header -->
        <div class="cart-header">
            <div class="cart-title">
                <i class="fas fa-shopping-cart"></i>
                <h1>Giỏ hàng của bạn</h1>
                <span class="item-count"><?php echo $item_count; ?> món</span>
            </div>
            <?php if (!empty($cart_items)): ?>
            <div class="cart-header-actions">
                <a href="?page=menu" class="continue-btn">
                    <i class="fas fa-plus"></i> Thêm món
                </a>
                <a href="?page=cart&clear_all=1" class="clear-all-btn" onclick="return confirm('Bạn có chắc muốn xóa toàn bộ giỏ hàng?')">
                    <i class="fas fa-trash-alt"></i> Xóa tất cả
                </a>
            </div>
            <?php endif; ?>
        </div>

        <?php if (empty($cart_items)): ?>
        <!-- Empty Cart -->
        <div class="empty-cart">
            <div class="empty-icon">
                <i class="fas fa-shopping-basket"></i>
            </div>
            <h2>Giỏ hàng trống</h2>
            <p>Bạn chưa có món nào trong giỏ hàng</p>
            <a href="?page=menu" class="shop-btn">
                <i class="fas fa-utensils"></i> Xem thực đơn
            </a>
        </div>
        <?php else: ?>
        <!-- Cart Content -->
        <div class="cart-layout">
            <!-- Left: Cart Items -->
            <div class="cart-items-section">
                <?php foreach ($cart_items as $index => $item): ?>
                <div class="cart-item" data-id="<?php echo $item['id']; ?>">
                    <div class="item-number"><?php echo $index + 1; ?></div>
                    
                    <div class="item-image">
                        <?php if($item['image']): ?>
                            <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="">
                        <?php else: ?>
                            <div class="no-img"><i class="fas fa-utensils"></i></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="item-details">
                        <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                        <?php if ($item['note']): ?>
                            <p class="item-note"><i class="fas fa-sticky-note"></i> <?php echo htmlspecialchars($item['note']); ?></p>
                        <?php endif; ?>
                        <?php if (!$item['is_available']): ?>
                            <span class="unavailable-badge"><i class="fas fa-exclamation-circle"></i> Hết món</span>
                        <?php endif; ?>
                    </div>

                    <div class="item-pricing">
                        <!-- Hiển thị: giá x số lượng -->
                        <div class="price-calc">
                            <span class="unit-price"><?php echo number_format($item['price'], 0, ',', '.'); ?>đ</span>
                            <span class="multiply">×</span>
                            <div class="qty-control">
                                <button class="qty-btn minus" onclick="updateQty(<?php echo $item['id']; ?>, -1)">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <span class="qty-value" id="qty-<?php echo $item['id']; ?>"><?php echo $item['quantity']; ?></span>
                                <button class="qty-btn plus" onclick="updateQty(<?php echo $item['id']; ?>, 1)">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="subtotal">
                            = <span id="subtotal-<?php echo $item['id']; ?>"><?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?></span>đ
                        </div>
                    </div>
                    
                    <button class="remove-btn" onclick="removeItem(<?php echo $item['id']; ?>)">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Right: Order Summary -->
            <div class="order-summary">
                <div class="summary-card">
                    <h2><i class="fas fa-receipt"></i> Tổng đơn hàng</h2>
                    
                    <!-- Địa chỉ giao hàng -->
                    <div class="address-section">
                        <label><i class="fas fa-map-marker-alt"></i> Địa chỉ giao hàng</label>
                        <div class="address-input-wrap">
                            <input type="text" id="deliveryAddress" placeholder="Nhập địa chỉ của bạn...">
                            <button onclick="calculateDeliveryFee()" class="calc-btn">
                                <i class="fas fa-calculator"></i>
                            </button>
                        </div>
                        <div id="distanceInfo" class="distance-info">
                            <i class="fas fa-route"></i> <span id="distanceText"></span>
                        </div>
                    </div>

                    <!-- Chi tiết đơn hàng -->
                    <div class="summary-details">
                        <div class="summary-row">
                            <span>Tạm tính (<?php echo $item_count; ?> món)</span>
                            <span id="subtotalAmount"><?php echo number_format($total, 0, ',', '.'); ?>đ</span>
                        </div>
                        <div class="summary-row">
                            <span>Phí giao hàng</span>
                            <span id="deliveryFeeAmount" class="pending">Nhập địa chỉ để tính</span>
                        </div>
                        <?php 
                        // Tính tổng số combo (bao gồm cả số lần đặt)
                        $total_combo_count = 0;
                        foreach ($valid_combos as $vc) {
                            $total_combo_count += $vc['count'] ?? 1;
                        }
                        ?>
                        <div class="summary-row discount" style="display: none;">
                            <span>Giảm giá</span>
                            <span id="discountAmount">-<?php echo number_format($combo_discount, 0, ',', '.'); ?>đ</span>
                        </div>
                    </div>
                    
                    <?php if (!empty($valid_combos)): ?>
                    <!-- Hiển thị từng combo đã áp dụng -->
                    <div class="applied-combos-list">
                        <?php foreach ($valid_combos as $combo): 
                            $combo_count = $combo['count'] ?? 1;
                        ?>
                        <div class="combo-applied-badge" data-promo-id="<?php echo $combo['promo_id']; ?>">
                            <i class="fas fa-check-circle"></i>
                            <span>
                                <?php echo htmlspecialchars($combo['title']); ?>
                                <?php if ($combo_count > 1): ?>
                                    <strong>x<?php echo $combo_count; ?></strong>
                                <?php endif; ?>
                            </span>
                            <small class="combo-discount-amount">-<?php echo number_format($combo['discount_amount'], 0, ',', '.'); ?>đ</small>
                            <button onclick="removeComboDiscount(<?php echo $combo['promo_id']; ?>)" class="remove-combo-btn" title="Xóa combo">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php elseif ($applied_combo): ?>
                    <div class="combo-applied-badge">
                        <i class="fas fa-check-circle"></i>
                        <span><?php echo htmlspecialchars($applied_combo['title']); ?></span>
                        <button onclick="removeComboDiscount()" class="remove-combo-btn" title="Xóa combo">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <?php endif; ?>
                    
                    <div class="summary-total">
                        <span>Tổng cộng</span>
                        <span id="totalAmount"><?php echo number_format($final_total, 0, ',', '.'); ?>đ</span>
                    </div>
                    
                    <button onclick="proceedToCheckout()" class="checkout-btn">
                        <i class="fas fa-credit-card"></i> Đặt hàng
                    </button>
                    
                    <a href="?page=menu" class="continue-link">
                        <i class="fas fa-arrow-left"></i> Tiếp tục mua sắm
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<script>
// Biến lưu tổng tiền và khuyến mãi
let cartTotal = <?php echo $total; ?>;
let comboDiscount = <?php echo $combo_discount; ?>;
let appliedPromotion = null;

// Load khuyến mãi khi trang load
document.addEventListener('DOMContentLoaded', function() {
    loadPromotions();
});

// Xóa giảm giá combo (hỗ trợ xóa từng combo hoặc tất cả)
function removeComboDiscount(promoId = 0) {
    const message = promoId > 0 ? 'Bạn có chắc muốn xóa combo này?' : 'Bạn có chắc muốn xóa tất cả giảm giá combo?';
    if (!confirm(message)) return;
    
    let body = 'action=remove_combo_discount';
    if (promoId > 0) {
        body += '&promo_id=' + promoId;
    }
    
    fetch('api/cart.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: body
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        }
    });
}

// Tải danh sách khuyến mãi
function loadPromotions() {
    const listEl = document.getElementById('promotionsList');
    listEl.innerHTML = '<div class="loading-promos"><i class="fas fa-spinner fa-spin"></i> Đang tải...</div>';
    
    fetch('api/apply-promotion.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            action: 'get_promotions',
            type: 'cart',
            order_total: cartTotal
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success && data.promotions.length > 0) {
            let html = '';
            data.promotions.forEach(promo => {
                const canApply = promo.can_apply;
                const discountText = promo.discount_amount > 0 
                    ? '-' + formatMoney(promo.discount_amount) 
                    : promo.discount_text;
                
                html += `
                <div class="promo-item ${canApply ? '' : 'disabled'}" onclick="${canApply ? `selectPromotion(${promo.id}, ${promo.discount_amount})` : ''}">
                    <div class="promo-item-left">
                        <div class="promo-item-icon ${promo.promo_type}">
                            <i class="fas fa-${getPromoIcon(promo.promo_type)}"></i>
                        </div>
                        <div class="promo-item-info">
                            <strong>${promo.title}</strong>
                            <span>${promo.discount_text}</span>
                            ${!canApply ? `<small class="promo-reason">${promo.reason}</small>` : ''}
                        </div>
                    </div>
                    <div class="promo-item-right">
                        ${canApply 
                            ? `<span class="promo-discount">${discountText}</span>
                               <i class="fas fa-chevron-right"></i>`
                            : `<span class="promo-locked"><i class="fas fa-lock"></i></span>`
                        }
                    </div>
                </div>`;
            });
            listEl.innerHTML = html;
        } else {
            listEl.innerHTML = '<div class="no-promos"><i class="fas fa-info-circle"></i> Chưa có khuyến mãi phù hợp</div>';
        }
    })
    .catch(err => {
        listEl.innerHTML = '<div class="no-promos"><i class="fas fa-exclamation-circle"></i> Không thể tải khuyến mãi</div>';
    });
}

// Chọn khuyến mãi
function selectPromotion(promoId, discountAmount) {
    fetch('api/apply-promotion.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            action: 'apply',
            promo_id: promoId,
            order_total: cartTotal,
            type: 'cart'
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            appliedPromotion = data.promo;
            showAppliedPromo(data.promo);
            updateTotals(data.promo.discount_amount);
            showToast('success', data.message);
        } else {
            showToast('error', data.message);
        }
    });
}

// Hiển thị khuyến mãi đã áp dụng
function showAppliedPromo(promo) {
    document.getElementById('promotionsList').style.display = 'none';
    const appliedEl = document.getElementById('appliedPromo');
    appliedEl.style.display = 'flex';
    document.getElementById('appliedPromoText').innerHTML = 
        `<strong>${promo.title}</strong> - Giảm ${formatMoney(promo.discount_amount)}`;
}

// Xóa khuyến mãi
function removePromotion() {
    fetch('api/apply-promotion.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({action: 'remove'})
    })
    .then(res => res.json())
    .then(data => {
        appliedPromotion = null;
        document.getElementById('appliedPromo').style.display = 'none';
        document.getElementById('promotionsList').style.display = 'block';
        updateTotals(0);
        loadPromotions();
        showToast('info', 'Đã xóa khuyến mãi');
    });
}

// Áp dụng mã khuyến mãi
function applyPromoCode() {
    const code = document.getElementById('promoCodeInput').value.trim();
    if (!code) {
        showToast('error', 'Vui lòng nhập mã khuyến mãi');
        return;
    }
    
    fetch('api/apply-promotion.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            action: 'validate_code',
            code: code,
            order_total: cartTotal
        })
    })
    .then(res => res.json())
    .then(data => {
        const resultEl = document.getElementById('promoResult');
        if (data.success) {
            appliedPromotion = data.promo;
            showAppliedPromo(data.promo);
            updateTotals(data.promo.discount_amount);
            resultEl.innerHTML = `<span class="success"><i class="fas fa-check"></i> ${data.message}</span>`;
            document.getElementById('promoCodeInput').value = '';
        } else {
            resultEl.innerHTML = `<span class="error"><i class="fas fa-times"></i> ${data.message}</span>`;
        }
    });
}

// Cập nhật tổng tiền
function updateTotals(discountAmount) {
    const discountRow = document.querySelector('.summary-row.discount');
    if (discountAmount > 0) {
        discountRow.style.display = 'flex';
        document.getElementById('discountAmount').textContent = '-' + formatMoney(discountAmount);
    } else {
        discountRow.style.display = 'none';
    }
    
    const finalTotal = cartTotal - discountAmount;
    document.getElementById('totalAmount').textContent = formatMoney(finalTotal);
}

// Format tiền
function formatMoney(amount) {
    return new Intl.NumberFormat('vi-VN').format(amount) + 'đ';
}

// Lấy icon theo loại khuyến mãi
function getPromoIcon(type) {
    const icons = {
        'combo': 'utensils',
        'discount': 'percent',
        'event': 'calendar-star',
        'seasonal': 'snowflake',
        'member': 'user-tag',
        'coupon': 'ticket',
        'flash_sale': 'bolt'
    };
    return icons[type] || 'tag';
}

// Toast notification
function showToast(type, message) {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'times-circle' : 'info-circle'}"></i> ${message}`;
    document.body.appendChild(toast);
    setTimeout(() => toast.classList.add('show'), 100);
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}
</script>

<style>
/* Modern Cart Page - Green Theme */
.cart-page {
    min-height: 100vh;
    padding: 2rem 0 4rem;
    background: linear-gradient(180deg, #f0fdf4 0%, #ffffff 100%);
}

.cart-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1.5rem;
}

/* Header */
.cart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #e5e7eb;
}

.cart-title {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.cart-title i {
    font-size: 1.5rem;
    color: #22c55e;
}

.cart-title h1 {
    font-size: 1.75rem;
    color: #1f2937;
    margin: 0;
    font-weight: 700;
}

.item-count {
    background: #dcfce7;
    color: #15803d;
    padding: 0.4rem 1rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
}

.cart-header-actions {
    display: flex;
    gap: 0.75rem;
    align-items: center;
}

.continue-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    background: #f0fdf4;
    border: 2px solid #22c55e;
    color: #15803d;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.2s;
}

.continue-btn:hover {
    background: #22c55e;
    color: white;
}

.clear-all-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    background: #fef2f2;
    border: 2px solid #ef4444;
    color: #dc2626;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.2s;
}

.clear-all-btn:hover {
    background: #ef4444;
    color: white;
}


/* Empty Cart */
.empty-cart {
    text-align: center;
    padding: 4rem 2rem;
    background: white;
    border-radius: 20px;
    border: 2px dashed #d1d5db;
}

.empty-icon {
    width: 100px;
    height: 100px;
    margin: 0 auto 1.5rem;
    background: #f0fdf4;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.empty-icon i {
    font-size: 2.5rem;
    color: #22c55e;
}

.empty-cart h2 {
    color: #1f2937;
    margin: 0 0 0.5rem;
    font-size: 1.5rem;
}

.empty-cart p {
    color: #6b7280;
    margin: 0 0 2rem;
}

.shop-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem 2rem;
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    color: white;
    text-decoration: none;
    border-radius: 12px;
    font-weight: 700;
    transition: all 0.3s;
}

.shop-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(34, 197, 94, 0.3);
}

/* Cart Layout - Equal width columns */
.cart-layout {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    align-items: start;
}

/* Cart Items */
.cart-items-section {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}


.cart-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.25rem;
    background: white;
    border-radius: 16px;
    border: 2px solid #e5e7eb;
    transition: all 0.3s;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.cart-item:hover {
    border-color: #22c55e;
    transform: translateX(5px);
    box-shadow: 0 4px 15px rgba(34, 197, 94, 0.15);
}

.item-number {
    width: 28px;
    height: 28px;
    background: #dcfce7;
    color: #15803d;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.85rem;
    flex-shrink: 0;
}

.item-image {
    width: 70px;
    height: 70px;
    border-radius: 12px;
    overflow: hidden;
    flex-shrink: 0;
    background: #f3f4f6;
}

.item-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.item-image .no-img {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #d1d5db;
    font-size: 1.5rem;
}

.item-details {
    flex: 1;
    min-width: 0;
}

.item-details h3 {
    color: #1f2937;
    font-size: 1rem;
    margin: 0 0 0.25rem;
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.item-note {
    color: #6b7280;
    font-size: 0.8rem;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.4rem;
}

.item-note i {
    color: #22c55e;
}

.unavailable-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.2rem 0.6rem;
    background: #fee2e2;
    color: #ef4444;
    border-radius: 6px;
    font-size: 0.75rem;
    margin-top: 0.25rem;
}


/* Pricing Section */
.item-pricing {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.5rem;
}

.price-calc {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.unit-price {
    color: #22c55e;
    font-weight: 600;
    font-size: 0.95rem;
}

.multiply {
    color: #9ca3af;
    font-size: 0.9rem;
}

.qty-control {
    display: flex;
    align-items: center;
    background: #f3f4f6;
    border-radius: 10px;
    overflow: hidden;
    border: 1px solid #e5e7eb;
}

.qty-btn {
    width: 32px;
    height: 32px;
    background: transparent;
    border: none;
    color: #22c55e;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.qty-btn:hover {
    background: #dcfce7;
}

.qty-btn i {
    font-size: 0.7rem;
}

.qty-value {
    width: 36px;
    text-align: center;
    color: #1f2937;
    font-weight: 700;
    font-size: 1rem;
}

.subtotal {
    color: #22c55e;
    font-weight: 700;
    font-size: 1.1rem;
}

.remove-btn {
    width: 40px;
    height: 40px;
    background: #fee2e2;
    border: none;
    border-radius: 10px;
    color: #ef4444;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    flex-shrink: 0;
}

.remove-btn:hover {
    background: #fecaca;
    transform: scale(1.1);
}


/* Order Summary */
.order-summary {
    position: sticky;
    top: 100px;
}

.summary-card {
    background: linear-gradient(145deg, #ffffff 0%, #f0fdf4 100%);
    border-radius: 24px;
    padding: 2rem;
    border: 3px solid #22c55e;
    margin-bottom: 1.5rem;
    box-shadow: 0 8px 30px rgba(34, 197, 94, 0.15);
}

.summary-card h2 {
    color: #15803d;
    font-size: 1.35rem;
    margin: 0 0 1.75rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding-bottom: 1.25rem;
    border-bottom: 2px solid #dcfce7;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.summary-card h2 i {
    color: #22c55e;
    font-size: 1.5rem;
}

/* Address Section */
.address-section {
    margin-bottom: 1.75rem;
    background: #f0fdf4;
    padding: 1.25rem;
    border-radius: 14px;
    border: 2px solid #dcfce7;
}

.address-section label {
    display: block;
    color: #15803d;
    font-size: 0.95rem;
    margin-bottom: 0.75rem;
    font-weight: 700;
}

.address-section label i {
    color: #ef4444;
}

.address-input-wrap {
    display: flex;
    gap: 0.75rem;
}

.address-input-wrap input {
    flex: 1;
    padding: 1rem 1.25rem;
    background: white;
    border: 2px solid #86efac;
    border-radius: 12px;
    color: #1f2937;
    font-size: 1rem;
    font-weight: 500;
}

.address-input-wrap input:focus {
    outline: none;
    border-color: #22c55e;
    box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.2);
}

.address-input-wrap input::placeholder {
    color: #9ca3af;
}

.calc-btn {
    width: 54px;
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    border: none;
    border-radius: 12px;
    color: white;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 1.1rem;
}

.calc-btn:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 15px rgba(34, 197, 94, 0.4);
}

.distance-info {
    margin-top: 0.75rem;
    font-size: 0.9rem;
    color: #15803d;
    display: none;
    font-weight: 500;
}

.distance-info.show {
    display: block;
}


/* Summary Details */
.summary-details {
    margin-bottom: 1.25rem;
    background: white;
    padding: 1rem 1.25rem;
    border-radius: 14px;
    border: 2px solid #e5e7eb;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 0;
    border-bottom: 1px solid #f3f4f6;
    color: #4b5563;
    font-size: 1.05rem;
    font-weight: 500;
}

.summary-row:last-child {
    border-bottom: none;
}

.summary-row .pending {
    color: #9ca3af;
    font-size: 0.9rem;
}

.summary-row.discount span:last-child {
    color: #22c55e;
}

.summary-total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.25rem;
    margin: 1rem 0 1rem;
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    border-radius: 12px;
}

.summary-total span:first-child {
    color: white;
    font-weight: 700;
    font-size: 1.1rem;
}

.summary-total span:last-child {
    color: white;
    font-weight: 800;
    font-size: 1.4rem;
}

.checkout-btn {
    width: 100%;
    padding: 1rem;
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
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
    margin-bottom: 1rem;
}

.checkout-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(245, 158, 11, 0.35);
}

.continue-link {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    color: #15803d;
    text-decoration: none;
    font-size: 0.95rem;
    font-weight: 600;
    transition: color 0.2s;
    padding: 0.6rem;
    background: #f0fdf4;
    border-radius: 8px;
}

.continue-link:hover {
    color: #22c55e;
    background: #dcfce7;
}


/* Responsive */
@media (max-width: 1024px) {
    .cart-layout {
        grid-template-columns: 1fr;
    }
    
    .order-summary {
        position: static;
    }
    
    .cart-item {
        flex-wrap: wrap;
    }
    
    .item-pricing {
        width: 100%;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        margin-top: 0.75rem;
        padding-top: 0.75rem;
        border-top: 1px solid rgba(255,255,255,0.05);
    }
}

@media (max-width: 600px) {
    .cart-header {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }
    
    .price-calc {
        flex-wrap: wrap;
        gap: 0.5rem;
    }
}

/* Promotion Select Card */
.promo-select-card {
    background: rgba(255,255,255,0.03);
    border-radius: 16px;
    padding: 1.25rem;
    margin-bottom: 1rem;
    border: 1px solid rgba(255,255,255,0.08);
}

.promo-select-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid rgba(255,255,255,0.08);
}

.promo-select-header i {
    color: #22c55e;
    font-size: 1.1rem;
}

.promo-select-header span {
    flex: 1;
    color: #fff;
    font-weight: 600;
}

.promo-select-header .refresh-btn {
    background: none;
    border: none;
    color: rgba(255,255,255,0.5);
    cursor: pointer;
    padding: 0.5rem;
    transition: all 0.2s;
}

.promo-select-header .refresh-btn:hover {
    color: #22c55e;
    transform: rotate(180deg);
}

/* Promotions List */
.promotions-list {
    max-height: 300px;
    overflow-y: auto;
}

.loading-promos, .no-promos {
    text-align: center;
    padding: 1.5rem;
    color: rgba(255,255,255,0.5);
}

.promo-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.875rem;
    background: rgba(255,255,255,0.03);
    border-radius: 10px;
    margin-bottom: 0.5rem;
    cursor: pointer;
    transition: all 0.2s;
    border: 1px solid transparent;
}

.promo-item:hover:not(.disabled) {
    background: rgba(34, 197, 94, 0.1);
    border-color: rgba(34, 197, 94, 0.3);
}

.promo-item.disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.promo-item-left {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.promo-item-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #22c55e, #16a34a);
}

.promo-item-icon.combo { background: linear-gradient(135deg, #3b82f6, #1d4ed8); }
.promo-item-icon.discount { background: linear-gradient(135deg, #22c55e, #16a34a); }
.promo-item-icon.event { background: linear-gradient(135deg, #f59e0b, #d97706); }
.promo-item-icon.member { background: linear-gradient(135deg, #ec4899, #be185d); }
.promo-item-icon.flash_sale { background: linear-gradient(135deg, #ef4444, #dc2626); }

.promo-item-icon i {
    color: white;
    font-size: 1rem;
}

.promo-item-info {
    display: flex;
    flex-direction: column;
}

.promo-item-info strong {
    color: #fff;
    font-size: 0.9rem;
    margin-bottom: 0.15rem;
}

.promo-item-info span {
    color: #22c55e;
    font-size: 0.8rem;
    font-weight: 600;
}

.promo-item-info .promo-reason {
    color: #f59e0b;
    font-size: 0.75rem;
    margin-top: 0.25rem;
}

.promo-item-right {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.promo-discount {
    color: #22c55e;
    font-weight: 700;
    font-size: 0.9rem;
}

.promo-item-right i {
    color: rgba(255,255,255,0.3);
}

.promo-locked i {
    color: rgba(255,255,255,0.3);
}

/* Applied Promo */
.applied-promo {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem;
    background: rgba(34, 197, 94, 0.15);
    border: 1px solid rgba(34, 197, 94, 0.3);
    border-radius: 10px;
}

.applied-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    color: #22c55e;
}

.applied-info i {
    font-size: 1.25rem;
}

.applied-info span {
    font-size: 0.9rem;
}

.applied-info strong {
    color: #fff;
}

.remove-promo-btn {
    background: rgba(239, 68, 68, 0.2);
    border: none;
    color: #ef4444;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
}

.remove-promo-btn:hover {
    background: rgba(239, 68, 68, 0.3);
}

/* Toast Notification */
.toast {
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    padding: 1rem 1.5rem;
    background: #1e293b;
    color: #fff;
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
    transform: translateY(100px);
    opacity: 0;
    transition: all 0.3s ease;
    z-index: 9999;
}

.toast.show {
    transform: translateY(0);
    opacity: 1;
}

.toast-success { border-left: 4px solid #22c55e; }
.toast-success i { color: #22c55e; }
.toast-error { border-left: 4px solid #ef4444; }
.toast-error i { color: #ef4444; }
.toast-info { border-left: 4px solid #3b82f6; }
.toast-info i { color: #3b82f6; }

/* Promo Result */
.promo-result {
    margin-top: 0.75rem;
}

.promo-result .success {
    color: #22c55e;
    font-size: 0.85rem;
}

.promo-result .error {
    color: #ef4444;
    font-size: 0.85rem;
}
</style>


<script>
// Dữ liệu giỏ hàng
const cartData = <?php echo json_encode(array_map(function($item) {
    return [
        'id' => $item['id'],
        'price' => $item['price'],
        'quantity' => $item['quantity']
    ];
}, $cart_items)); ?>;

let subtotal = <?php echo $total; ?>;
let deliveryFee = 0;
let calculatedDistance = 0;
let appliedPromo = null;
let discountAmount = <?php echo $combo_discount; ?>;

// Kiểm tra mã từ sessionStorage
document.addEventListener('DOMContentLoaded', function() {
    const savedPromo = sessionStorage.getItem('promo_code');
    if (savedPromo) {
        document.getElementById('promoCodeInput').value = savedPromo;
        applyPromoCode();
        sessionStorage.removeItem('promo_code');
    }
});

// Áp dụng mã giảm giá
function applyPromoCode() {
    const code = document.getElementById('promoCodeInput').value.trim().toUpperCase();
    if (!code) {
        showPromoResult('error', 'Vui lòng nhập mã giảm giá');
        return;
    }
    
    fetch('api/apply-promo.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ code: code, subtotal: subtotal })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            appliedPromo = data.promo;
            discountAmount = data.discount;
            showPromoResult('success', `<i class="fas fa-check-circle"></i> ${data.message} <strong>-${formatNumber(discountAmount)}đ</strong> <button class="remove-promo" onclick="removePromo()"><i class="fas fa-times"></i> Bỏ</button>`);
            updateTotalWithDiscount();
        } else {
            showPromoResult('error', '<i class="fas fa-exclamation-circle"></i> ' + data.message);
        }
    })
    .catch(err => {
        showPromoResult('error', 'Có lỗi xảy ra');
    });
}

function showPromoResult(type, message) {
    const result = document.getElementById('promoResult');
    result.className = 'promo-result ' + type;
    result.innerHTML = message;
}

function removePromo() {
    appliedPromo = null;
    discountAmount = 0;
    document.getElementById('promoCodeInput').value = '';
    document.getElementById('promoResult').className = 'promo-result';
    document.getElementById('promoResult').innerHTML = '';
    document.querySelector('.summary-row.discount').style.display = 'none';
    recalculateTotal();
}

function updateTotalWithDiscount() {
    const discountRow = document.querySelector('.summary-row.discount');
    discountRow.style.display = 'flex';
    document.getElementById('discountAmount').textContent = '-' + formatNumber(discountAmount) + 'đ';
    const total = Math.max(0, subtotal + deliveryFee - discountAmount);
    document.getElementById('totalAmount').textContent = formatNumber(total) + 'đ';
    console.log('Update total:', {subtotal, deliveryFee, discountAmount, total});
}

// Cập nhật số lượng
function updateQty(cartId, delta) {
    const item = cartData.find(i => i.id == cartId);
    if (!item) return;
    
    const newQty = Math.max(1, Math.min(99, item.quantity + delta));
    if (newQty === item.quantity) return;
    
    item.quantity = newQty;
    
    // Cập nhật UI
    document.getElementById('qty-' + cartId).textContent = newQty;
    document.getElementById('subtotal-' + cartId).textContent = formatNumber(item.price * newQty);
    
    // Cập nhật tổng
    recalculateTotal();
    
    // Gửi request cập nhật
    const formData = new FormData();
    formData.append('update_quantity', '1');
    formData.append('cart_id', cartId);
    formData.append('quantity', newQty);
    
    fetch('?page=cart', {
        method: 'POST',
        body: formData
    });
}

// Tính lại tổng
function recalculateTotal() {
    subtotal = cartData.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    document.getElementById('subtotalAmount').textContent = formatNumber(subtotal) + 'đ';
    const total = subtotal + deliveryFee - discountAmount;
    document.getElementById('totalAmount').textContent = formatNumber(total) + 'đ';
}

// Xóa món
function removeItem(cartId) {
    if (confirm('Bạn có chắc muốn xóa món này?')) {
        window.location.href = '?page=cart&remove=' + cartId;
    }
}

// Format số
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}


// Tính phí giao hàng - Địa chỉ quán: 126 Nguyễn Thiện Thành, Phường 5, TP. Trà Vinh
const RESTAURANT_LAT = 9.9347;
const RESTAURANT_LNG = 106.3456;
const FREE_DELIVERY_DISTANCE = 3; // Miễn phí trong 3km
const DELIVERY_FEE_PER_KM = 5000; // 5k/km

function calculateDistance(lat1, lon1, lat2, lon2) {
    const R = 6371;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
              Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
              Math.sin(dLon/2) * Math.sin(dLon/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    return R * c;
}

// Debounce function
let searchTimeout;
function debounce(func, wait) {
    return function(...args) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => func.apply(this, args), wait);
    };
}

// Tự động tính phí khi nhập địa chỉ
document.getElementById('deliveryAddress').addEventListener('input', debounce(function() {
    const address = this.value.trim();
    if (address.length >= 10) {
        calculateDeliveryFee();
    }
}, 800));

// Tính phí khi blur
document.getElementById('deliveryAddress').addEventListener('blur', function() {
    const address = this.value.trim();
    if (address.length >= 5 && calculatedDistance === 0) {
        calculateDeliveryFee();
    }
});

function calculateDeliveryFee() {
    const address = document.getElementById('deliveryAddress').value.trim();
    if (!address) {
        alert('Vui lòng nhập địa chỉ giao hàng!');
        return;
    }
    
    document.getElementById('deliveryFeeAmount').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang tính...';
    
    // Thêm "Trà Vinh" nếu chưa có để tăng độ chính xác
    let searchAddress = address;
    if (!address.toLowerCase().includes('trà vinh')) {
        searchAddress = address + ', Trà Vinh, Việt Nam';
    } else {
        searchAddress = address + ', Việt Nam';
    }
    
    const encodedAddress = encodeURIComponent(searchAddress);
    
    fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodedAddress}&limit=1&countrycodes=vn`)
        .then(response => response.json())
        .then(data => {
            if (data && data.length > 0) {
                const customerLat = parseFloat(data[0].lat);
                const customerLng = parseFloat(data[0].lon);
                
                calculatedDistance = calculateDistance(RESTAURANT_LAT, RESTAURANT_LNG, customerLat, customerLng);
                
                // Tính phí ship
                if (calculatedDistance <= FREE_DELIVERY_DISTANCE) {
                    deliveryFee = 0;
                } else {
                    const extraKm = Math.ceil(calculatedDistance - FREE_DELIVERY_DISTANCE);
                    deliveryFee = extraKm * DELIVERY_FEE_PER_KM;
                }
                
                updateDeliveryUI();
            } else {
                // Không tìm thấy - ước tính dựa trên địa chỉ
                estimateDeliveryFee(address);
            }
        })
        .catch(error => {
            estimateDeliveryFee(address);
        });
}

// Ước tính phí ship khi không tìm được địa chỉ chính xác
function estimateDeliveryFee(address) {
    const addressLower = address.toLowerCase();
    
    // Các khu vực trong TP Trà Vinh và khoảng cách ước tính
    const areas = [
        { keywords: ['phường 1', 'p1', 'p.1'], distance: 1.5 },
        { keywords: ['phường 2', 'p2', 'p.2'], distance: 2 },
        { keywords: ['phường 3', 'p3', 'p.3'], distance: 2.5 },
        { keywords: ['phường 4', 'p4', 'p.4'], distance: 1 },
        { keywords: ['phường 5', 'p5', 'p.5'], distance: 0.5 },
        { keywords: ['phường 6', 'p6', 'p.6'], distance: 2 },
        { keywords: ['phường 7', 'p7', 'p.7'], distance: 3 },
        { keywords: ['phường 8', 'p8', 'p.8'], distance: 2.5 },
        { keywords: ['phường 9', 'p9', 'p.9'], distance: 3.5 },
        { keywords: ['long đức'], distance: 5 },
        { keywords: ['châu thành'], distance: 8 },
        { keywords: ['càng long'], distance: 15 },
        { keywords: ['cầu kè'], distance: 20 },
        { keywords: ['tiểu cần'], distance: 25 },
        { keywords: ['cầu ngang'], distance: 18 },
        { keywords: ['trà cú'], distance: 30 },
        { keywords: ['duyên hải'], distance: 35 },
        { keywords: ['tp trà vinh', 'tp. trà vinh', 'thành phố trà vinh'], distance: 2 },
    ];
    
    let estimatedDistance = 5; // Mặc định 5km nếu không xác định được
    
    for (const area of areas) {
        if (area.keywords.some(kw => addressLower.includes(kw))) {
            estimatedDistance = area.distance;
            break;
        }
    }
    
    calculatedDistance = estimatedDistance;
    
    if (calculatedDistance <= FREE_DELIVERY_DISTANCE) {
        deliveryFee = 0;
    } else {
        const extraKm = Math.ceil(calculatedDistance - FREE_DELIVERY_DISTANCE);
        deliveryFee = extraKm * DELIVERY_FEE_PER_KM;
    }
    
    updateDeliveryUI(true);
}


function updateDeliveryUI(isEstimate = false) {
    const distanceInfo = document.getElementById('distanceInfo');
    const distanceText = document.getElementById('distanceText');
    const deliveryFeeAmount = document.getElementById('deliveryFeeAmount');
    
    distanceInfo.classList.add('show');
    
    if (isEstimate) {
        distanceText.innerHTML = `<i class="fas fa-info-circle" style="color: #fbbf24;"></i> Ước tính: ~${calculatedDistance.toFixed(1)} km`;
    } else {
        distanceText.innerHTML = `<i class="fas fa-check-circle" style="color: #10b981;"></i> Khoảng cách: ${calculatedDistance.toFixed(1)} km`;
    }
    
    if (deliveryFee === 0) {
        deliveryFeeAmount.innerHTML = '<span style="color: #10b981; font-weight: 600;"><i class="fas fa-gift"></i> Miễn phí</span>';
    } else {
        deliveryFeeAmount.innerHTML = `<span style="color: #d4a574; font-weight: 600;">${formatNumber(deliveryFee)}đ</span>`;
    }
    
    // Tính tổng có tính cả discount nếu đã áp mã
    const total = Math.max(0, subtotal + deliveryFee - discountAmount);
    document.getElementById('totalAmount').textContent = formatNumber(total) + 'đ';
    console.log('Delivery UI:', {subtotal, deliveryFee, discountAmount, total});
}

function proceedToCheckout() {
    const address = document.getElementById('deliveryAddress').value.trim();
    
    if (!address) {
        alert('Vui lòng nhập địa chỉ giao hàng!');
        document.getElementById('deliveryAddress').focus();
        return;
    }
    
    if (calculatedDistance === 0) {
        alert('Vui lòng nhấn nút tính phí giao hàng!');
        return;
    }
    
    sessionStorage.setItem('deliveryAddress', address);
    sessionStorage.setItem('deliveryFee', deliveryFee);
    sessionStorage.setItem('deliveryDistance', calculatedDistance);
    
    window.location.href = '?page=checkout';
}
</script>

<style>
/* Applied Combos List */
.applied-combos-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

/* Combo Applied Badge */
.combo-applied-badge {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.875rem 1rem;
    background: linear-gradient(135deg, rgba(34, 197, 94, 0.15) 0%, rgba(34, 197, 94, 0.08) 100%);
    border: 1px solid rgba(34, 197, 94, 0.4);
    border-radius: 10px;
    margin-bottom: 0;
}

.applied-combos-list .combo-applied-badge {
    margin-bottom: 0;
}

.combo-applied-badge i.fa-check-circle {
    color: #22c55e;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.combo-applied-badge span {
    flex: 1;
    color: #22c55e;
    font-weight: 600;
    font-size: 0.9rem;
}

.combo-applied-badge .combo-discount-amount {
    color: #ef4444;
    font-weight: 700;
    font-size: 0.85rem;
    flex-shrink: 0;
}

.remove-combo-btn {
    width: 28px;
    height: 28px;
    background: rgba(239, 68, 68, 0.2);
    border: none;
    border-radius: 6px;
    color: #ef4444;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    flex-shrink: 0;
}

.remove-combo-btn:hover {
    background: rgba(239, 68, 68, 0.3);
}

/* Combo Discount Row */
.combo-discount-row {
    display: flex !important;
    background: rgba(34, 197, 94, 0.1);
    margin: 0.5rem -1rem;
    padding: 0.75rem 1rem !important;
    border-radius: 8px;
}

.combo-discount-row span:first-child {
    color: #22c55e;
    font-weight: 600;
}

.combo-discount-row span:first-child i {
    margin-right: 0.4rem;
}

.combo-discount-row span:last-child {
    color: #22c55e !important;
    font-weight: 700;
}
</style>
