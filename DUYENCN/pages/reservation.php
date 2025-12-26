<?php
$message = '';
$messageType = '';
$user_name = '';
$user_email = '';
$user_phone = '';
$selected_promo = null;

// Lấy khuyến mãi từ URL nếu có
$promo_id = isset($_GET['promo_id']) ? intval($_GET['promo_id']) : 0;
if ($promo_id > 0) {
    try {
        $db = new Database();
        $conn = $db->connect();
        $stmt = $conn->prepare("SELECT * FROM restaurant_promotions WHERE id = ? AND is_active = 1");
        $stmt->execute([$promo_id]);
        $selected_promo = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
}

if (isset($_SESSION['customer_id'])) {
    try {
        $db = new Database();
        $conn = $db->connect();
        if ($conn) {
            $stmt = $conn->prepare("SELECT full_name, email, phone FROM customers WHERE id = ?");
            $stmt->execute([$_SESSION['customer_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user) {
                $user_name = $user['full_name'] ?? '';
                $user_email = $user['email'] ?? '';
                $user_phone = $user['phone'] ?? '';
            }
        }
    } catch (Exception $e) {}
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $guests = intval($_POST['guests'] ?? 0);
    $table_preference = $_POST['table_preference'] ?? 'any';
    $request = trim($_POST['request'] ?? '');
    $promotion_id = intval($_POST['promotion_id'] ?? 0);
    
    // Thêm thông tin khuyến mãi vào ghi chú nếu có
    if ($promotion_id > 0 && $selected_promo) {
        $promo_note = "[Ưu đãi: " . $selected_promo['title'] . " - " . $selected_promo['discount_text'] . "]";
        $request = $promo_note . ($request ? "\n" . $request : '');
    }
    
    if ($name && $email && $phone && $date && $time && $guests > 0) {
        try {
            $db = new Database();
            $conn = $db->connect();
            if ($conn) {
                // Kiểm tra xem bảng có cột customer_id và table_preference không
                $hasCustomerId = false;
                $hasTablePref = false;
                try {
                    $check = $conn->query("SHOW COLUMNS FROM reservations LIKE 'customer_id'");
                    $hasCustomerId = $check->rowCount() > 0;
                    $check2 = $conn->query("SHOW COLUMNS FROM reservations LIKE 'table_preference'");
                    $hasTablePref = $check2->rowCount() > 0;
                } catch (Exception $e) {}
                
                // Thêm cột table_preference nếu chưa có
                if (!$hasTablePref) {
                    try {
                        $conn->exec("ALTER TABLE reservations ADD COLUMN table_preference VARCHAR(50) DEFAULT 'any'");
                        $hasTablePref = true;
                    } catch (Exception $e) {}
                }
                
                if ($hasCustomerId && $hasTablePref) {
                    $customer_id = $_SESSION['customer_id'] ?? null;
                    $stmt = $conn->prepare("INSERT INTO reservations (customer_id, customer_name, email, phone, reservation_date, reservation_time, number_of_guests, table_preference, special_request) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$customer_id, $name, $email, $phone, $date, $time, $guests, $table_preference, $request]);
                } elseif ($hasTablePref) {
                    $stmt = $conn->prepare("INSERT INTO reservations (customer_name, email, phone, reservation_date, reservation_time, number_of_guests, table_preference, special_request) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$name, $email, $phone, $date, $time, $guests, $table_preference, $request]);
                } elseif ($hasCustomerId) {
                    $customer_id = $_SESSION['customer_id'] ?? null;
                    $stmt = $conn->prepare("INSERT INTO reservations (customer_id, customer_name, email, phone, reservation_date, reservation_time, number_of_guests, special_request) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$customer_id, $name, $email, $phone, $date, $time, $guests, $request]);
                } else {
                    $stmt = $conn->prepare("INSERT INTO reservations (customer_name, email, phone, reservation_date, reservation_time, number_of_guests, special_request) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$name, $email, $phone, $date, $time, $guests, $request]);
                }
                
                $promo_msg = $promotion_id > 0 ? ' Ưu đãi của bạn đã được ghi nhận.' : '';
                $message = 'Đặt bàn thành công!' . $promo_msg . ' Chúng tôi sẽ liên hệ xác nhận sớm.';
                $messageType = 'success';
            }
        } catch(PDOException $e) {
            $message = 'Có lỗi xảy ra: ' . $e->getMessage();
            $messageType = 'error';
        }
    } else {
        $message = 'Vui lòng điền đầy đủ thông tin!';
        $messageType = 'error';
    }
}
?>

<!-- Hero Section giống trang About -->
<section class="about-hero">
    <div class="about-hero-content">
        <span class="section-badge"><i class="fas fa-concierge-bell"></i> Đặt Bàn</span>
        <h1 class="about-hero-title">Đặt Bàn Trực Tuyến</h1>
        <p class="about-hero-subtitle">Đặt bàn trước để có trải nghiệm tốt nhất tại Ngon Gallery</p>
    </div>
</section>

<!-- Main Content -->
<section class="about-section">
    <div class="container">
        
        <?php // Hiển thị khuyến mãi đã chọn từ trang promotions ?>
        <?php if ($selected_promo): ?>
        <div class="rsv-promo-banner">
            <div class="rsv-promo-banner-icon">
                <i class="fas fa-gift"></i>
            </div>
            <div class="rsv-promo-banner-content">
                <strong>🎉 Ưu đãi đã được áp dụng!</strong>
                <span><?php echo htmlspecialchars($selected_promo['title']); ?> - <?php echo htmlspecialchars($selected_promo['discount_text']); ?></span>
            </div>
            <input type="hidden" id="preselectedPromoId" value="<?php echo $selected_promo['id']; ?>">
        </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['customer_id'])): ?>
        <div class="rsv-history-link">
            <a href="?page=my-reservations">
                <i class="fas fa-history"></i> Xem lịch sử đặt bàn của bạn
                <i class="fas fa-chevron-right"></i>
            </a>
        </div>
        <?php endif; ?>
        
        <?php if ($message): ?>
        <div class="rsv-alert rsv-alert-<?php echo $messageType; ?>">
            <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
            <span><?php echo $message; ?></span>
            <?php if ($messageType === 'success' && isset($_SESSION['customer_id'])): ?>
            <a href="?page=my-reservations" class="rsv-view-history">Xem trạng thái đặt bàn →</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="rsv-layout rsv-single-column">
            <!-- Form -->
            <div class="rsv-form-section">
                <div class="rsv-form-card">
                    <div class="rsv-form-header">
                        <div class="rsv-header-icon">
                            <i class="fas fa-utensils"></i>
                        </div>
                        <h2>Đặt Bàn</h2>
                        <p>Vui lòng điền thông tin để đặt bàn</p>
                        <div class="rsv-hotline-box">
                            <i class="fas fa-phone-alt"></i>
                            <span>Hotline: <strong>(028) 1234 5678</strong></span>
                        </div>
                    </div>
                    
                    <form method="POST" action="index.php?page=reservation" class="rsv-form">
                        
                        <?php if (isset($_SESSION['customer_id'])): ?>
                        <!-- Thông tin cá nhân - READONLY -->
                        <div class="rsv-user-info-box">
                            <div class="rsv-user-info-header">
                                <span><i class="fas fa-user-circle"></i> Thông tin của bạn</span>
                                <a href="index.php?page=profile" class="rsv-edit-btn">
                                    <i class="fas fa-edit"></i> Chỉnh sửa
                                </a>
                            </div>
                            <div class="rsv-user-info-grid">
                                <div class="rsv-user-item">
                                    <label><i class="fas fa-user"></i> Họ và tên</label>
                                    <div class="rsv-user-value"><?php echo htmlspecialchars($user_name); ?></div>
                                    <input type="hidden" name="name" value="<?php echo htmlspecialchars($user_name); ?>">
                                </div>
                                <div class="rsv-user-item">
                                    <label><i class="fas fa-phone"></i> Số điện thoại</label>
                                    <div class="rsv-user-value"><?php echo htmlspecialchars($user_phone); ?></div>
                                    <input type="hidden" name="phone" value="<?php echo htmlspecialchars($user_phone); ?>">
                                </div>
                                <div class="rsv-user-item full">
                                    <label><i class="fas fa-envelope"></i> Email</label>
                                    <div class="rsv-user-value"><?php echo htmlspecialchars($user_email); ?></div>
                                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($user_email); ?>">
                                </div>
                            </div>
                        </div>
                        <?php else: ?>
                        <!-- Chưa đăng nhập - cho nhập thông tin -->
                        <div class="rsv-form-grid">
                            <div class="rsv-input-group">
                                <label><i class="fas fa-user"></i> Họ và tên <span>*</span></label>
                                <input type="text" name="name" required placeholder="Nhập họ tên">
                            </div>
                            <div class="rsv-input-group">
                                <label><i class="fas fa-phone"></i> Số điện thoại <span>*</span></label>
                                <input type="tel" name="phone" required placeholder="Số điện thoại">
                            </div>
                        </div>
                        <div class="rsv-input-group">
                            <label><i class="fas fa-envelope"></i> Email <span>*</span></label>
                            <input type="email" name="email" required placeholder="Email xác nhận">
                        </div>
                        <?php endif; ?>
                        
                        <div class="rsv-form-grid">
                            <div class="rsv-input-group">
                                <label><i class="fas fa-calendar"></i> Ngày đặt <span>*</span></label>
                                <input type="date" name="date" required min="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="rsv-input-group">
                                <label><i class="fas fa-clock"></i> Giờ đến <span>*</span></label>
                                <input type="time" name="time" required>
                            </div>
                        </div>
                        
                        <div class="rsv-input-group">
                            <label><i class="fas fa-users"></i> Số khách <span>*</span></label>
                            <select name="guests" required>
                                <option value="">-- Chọn số khách --</option>
                                <?php for($i = 1; $i <= 20; $i++): ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?> người</option>
                                <?php endfor; ?>
                                <option value="25">Trên 20 người</option>
                            </select>
                        </div>
                        
                        <div class="rsv-input-group">
                            <label><i class="fas fa-chair"></i> Vị trí bàn <span>*</span></label>
                            <select name="table_preference" required>
                                <option value="">-- Chọn vị trí --</option>
                                <option value="indoor">🏠 Trong nhà</option>
                                <option value="outdoor">🌳 Sân vườn</option>
                                <option value="vip">👑 Phòng VIP</option>
                                <option value="private_room">🚪 Phòng riêng</option>
                                <option value="any">❓ Bất kỳ (để nhà hàng sắp xếp)</option>
                            </select>
                        </div>
                        
                        <div class="rsv-input-group">
                            <label><i class="fas fa-comment"></i> Ghi chú</label>
                            <textarea name="request" rows="3" placeholder="Yêu cầu đặc biệt (ghế trẻ em, sinh nhật...)"></textarea>
                        </div>
                        
                        <button type="submit" class="rsv-submit-btn">
                            <i class="fas fa-paper-plane"></i> Xác Nhận Đặt Bàn
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
    </div>
</section>

<script>
// Load khuyến mãi đặt bàn khi trang load
document.addEventListener('DOMContentLoaded', function() {
    // Kiểm tra nếu có khuyến mãi được chọn từ trang promotions
    const preselectedPromo = document.getElementById('preselectedPromoId');
    if (preselectedPromo && preselectedPromo.value) {
        // Đã có khuyến mãi được chọn sẵn, ẩn danh sách và hiển thị thông tin
        document.getElementById('selectedPromoId').value = preselectedPromo.value;
        document.getElementById('reservationPromos').style.display = 'none';
    } else {
        loadReservationPromotions();
    }
});

function loadReservationPromotions() {
    const listEl = document.getElementById('reservationPromos');
    
    fetch('api/apply-promotion.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            action: 'get_promotions',
            type: 'reservation',
            order_total: 0
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success && data.promotions.length > 0) {
            let html = '';
            // Lọc khuyến mãi phù hợp cho đặt bàn
            const reservationPromos = data.promotions.filter(p => 
                ['member', 'event', 'discount'].includes(p.promo_type)
            );
            
            if (reservationPromos.length === 0) {
                listEl.innerHTML = '<div class="rsv-no-promo"><i class="fas fa-info-circle"></i> Chưa có ưu đãi đặt bàn</div>';
                return;
            }
            
            reservationPromos.forEach(promo => {
                html += `
                <div class="rsv-promo-item" onclick="selectReservationPromo(${promo.id}, '${promo.title}', '${promo.discount_text}')">
                    <div class="rsv-promo-icon ${promo.promo_type}">
                        <i class="fas fa-${getPromoIcon(promo.promo_type)}"></i>
                    </div>
                    <div class="rsv-promo-info">
                        <strong>${promo.title}</strong>
                        <span>${promo.discount_text}</span>
                    </div>
                    <div class="rsv-promo-check">
                        <i class="far fa-circle"></i>
                    </div>
                </div>`;
            });
            listEl.innerHTML = html;
        } else {
            listEl.innerHTML = '<div class="rsv-no-promo"><i class="fas fa-info-circle"></i> Chưa có ưu đãi đặt bàn</div>';
        }
    })
    .catch(err => {
        listEl.innerHTML = '<div class="rsv-no-promo"><i class="fas fa-exclamation-circle"></i> Không thể tải ưu đãi</div>';
    });
}

function selectReservationPromo(promoId, title, discountText) {
    // Bỏ chọn tất cả
    document.querySelectorAll('.rsv-promo-item').forEach(item => {
        item.classList.remove('selected');
        item.querySelector('.rsv-promo-check i').className = 'far fa-circle';
    });
    
    // Chọn item này
    event.currentTarget.classList.add('selected');
    event.currentTarget.querySelector('.rsv-promo-check i').className = 'fas fa-check-circle';
    
    // Lưu vào hidden input
    document.getElementById('selectedPromoId').value = promoId;
    
    // Hiển thị thông tin đã chọn
    document.getElementById('reservationPromos').style.display = 'none';
    document.getElementById('selectedPromoInfo').style.display = 'flex';
    document.getElementById('selectedPromoText').innerHTML = `<strong>${title}</strong> - ${discountText}`;
}

function clearReservationPromo() {
    document.getElementById('selectedPromoId').value = '';
    document.getElementById('selectedPromoInfo').style.display = 'none';
    document.getElementById('reservationPromos').style.display = 'block';
    
    document.querySelectorAll('.rsv-promo-item').forEach(item => {
        item.classList.remove('selected');
        item.querySelector('.rsv-promo-check i').className = 'far fa-circle';
    });
}

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
</script>

<style>
/* ========================================
   RESERVATION PAGE - MODERN WHITE THEME
   ======================================== */

/* Promo Banner - Hiển thị khi có khuyến mãi được chọn từ trang promotions */
.rsv-promo-banner {
    display: flex;
    align-items: center;
    gap: 1rem;
    background: linear-gradient(135deg, rgba(34, 197, 94, 0.1), rgba(34, 197, 94, 0.05));
    border: 2px solid #22c55e;
    border-radius: 14px;
    padding: 1rem 1.25rem;
    margin-bottom: 1.5rem;
}

.rsv-promo-banner-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #22c55e, #16a34a);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.rsv-promo-banner-icon i {
    color: white;
    font-size: 1.25rem;
}

.rsv-promo-banner-content {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.rsv-promo-banner-content strong {
    color: #15803d;
    font-size: 0.95rem;
}

.rsv-promo-banner-content span {
    color: #374151;
    font-size: 0.9rem;
}

/* Reservation Promo Section */
.rsv-promo-section {
    margin-bottom: 20px;
}

.rsv-promo-section > label {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #374151;
    font-weight: 600;
    font-size: 0.9rem;
    margin-bottom: 12px;
}

.rsv-promo-section > label i {
    color: #22c55e;
}

.rsv-promo-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
    max-height: 200px;
    overflow-y: auto;
}

.rsv-promo-loading, .rsv-no-promo {
    text-align: center;
    padding: 1rem;
    color: #6b7280;
    font-size: 0.9rem;
}

.rsv-promo-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 14px;
    background: #f9fafb;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s;
}

.rsv-promo-item:hover {
    border-color: #22c55e;
    background: rgba(34, 197, 94, 0.05);
}

.rsv-promo-item.selected {
    border-color: #22c55e;
    background: rgba(34, 197, 94, 0.1);
}

.rsv-promo-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #22c55e, #16a34a);
    flex-shrink: 0;
}

.rsv-promo-icon.member { background: linear-gradient(135deg, #ec4899, #be185d); }
.rsv-promo-icon.event { background: linear-gradient(135deg, #f59e0b, #d97706); }
.rsv-promo-icon.discount { background: linear-gradient(135deg, #22c55e, #16a34a); }

.rsv-promo-icon i {
    color: white;
    font-size: 1rem;
}

.rsv-promo-info {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.rsv-promo-info strong {
    color: #1f2937;
    font-size: 0.9rem;
    margin-bottom: 2px;
}

.rsv-promo-info span {
    color: #22c55e;
    font-size: 0.8rem;
    font-weight: 600;
}

.rsv-promo-check {
    color: #d1d5db;
    font-size: 1.25rem;
}

.rsv-promo-item.selected .rsv-promo-check {
    color: #22c55e;
}

.rsv-selected-promo {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 14px;
    background: rgba(34, 197, 94, 0.1);
    border: 2px solid #22c55e;
    border-radius: 12px;
}

.rsv-selected-promo > i {
    color: #22c55e;
    font-size: 1.25rem;
}

.rsv-selected-promo span {
    flex: 1;
    color: #1f2937;
    font-size: 0.9rem;
}

.rsv-selected-promo strong {
    color: #22c55e;
}

.rsv-clear-promo {
    background: rgba(239, 68, 68, 0.1);
    border: none;
    color: #ef4444;
    width: 28px;
    height: 28px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
}

.rsv-clear-promo:hover {
    background: rgba(239, 68, 68, 0.2);
}

/* User Info Box - Readonly */
.rsv-user-info-box {
    background: linear-gradient(135deg, rgba(34, 197, 94, 0.06), rgba(34, 197, 94, 0.02));
    border: 1px solid rgba(34, 197, 94, 0.2);
    border-radius: 14px;
    padding: 18px 20px;
    margin-bottom: 24px;
}

.rsv-user-info-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 14px;
    padding-bottom: 10px;
    border-bottom: 1px solid rgba(34, 197, 94, 0.15);
}

.rsv-user-info-header span {
    color: #22c55e;
    font-weight: 600;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 8px;
}

.rsv-user-info-header span i {
    font-size: 1rem;
}

.rsv-edit-btn {
    color: #22c55e;
    font-size: 0.8rem;
    font-weight: 600;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 5px 10px;
    background: rgba(34, 197, 94, 0.1);
    border: 1px solid rgba(34, 197, 94, 0.3);
    border-radius: 6px;
    transition: all 0.3s;
}

.rsv-edit-btn:hover {
    background: #22c55e;
    color: #fff;
    transform: translateY(-1px);
}

.rsv-user-info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
}

.rsv-user-item {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.rsv-user-item.full {
    grid-column: span 2;
}

.rsv-user-item label {
    color: #6b7280;
    font-size: 0.75rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 5px;
}

.rsv-user-item label i {
    color: #22c55e;
    font-size: 0.7rem;
}

.rsv-user-value {
    background: #ffffff;
    padding: 10px 14px;
    border-radius: 8px;
    color: #1f2937;
    font-weight: 500;
    font-size: 0.9rem;
    border: 1px solid #e5e7eb;
}

/* Reservation Page Styles */
.rsv-history-link {
    max-width: 700px;
    margin: 0 auto 1.5rem;
    text-align: center;
}

.rsv-alert {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-wrap: wrap;
    gap: 12px;
    padding: 16px 20px;
    border-radius: 12px;
    margin-bottom: 24px;
    font-weight: 500;
    max-width: 700px;
    margin-left: auto;
    margin-right: auto;
}
.rsv-alert-success {
    background: #f0fdf4;
    border: 2px solid #22c55e;
    color: #16a34a;
}
.rsv-alert-error {
    background: #fef2f2;
    border: 2px solid #ef4444;
    color: #dc2626;
}

.rsv-layout {
    display: grid;
    grid-template-columns: 1fr;
    gap: 40px;
    align-items: start;
    max-width: 700px;
    margin: 0 auto;
}

.rsv-layout.rsv-single-column {
    grid-template-columns: 1fr;
    max-width: 700px;
    margin: 0 auto;
}

/* Form Section */
.rsv-form-card {
    background: #ffffff;
    border: 2px solid #22c55e;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 8px 30px rgba(34, 197, 94, 0.1);
}

.rsv-form-header {
    background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
    padding: 20px 24px;
    text-align: center;
    border-bottom: 2px solid #22c55e;
}

.rsv-header-icon {
    width: 50px;
    height: 50px;
    background: #22c55e;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 10px;
}

.rsv-header-icon i {
    font-size: 1.4rem;
    color: #fff;
}

.rsv-form-header h2 {
    color: #166534;
    font-size: 1.3rem;
    font-weight: 700;
    margin: 0 0 4px;
}

.rsv-form-header p {
    color: #6b7280;
    margin: 0 0 12px;
    font-size: 0.85rem;
}

/* Hotline Box */
.rsv-hotline-box {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    background: #22c55e;
    border-radius: 50px;
    color: #fff;
    font-size: 0.85rem;
}

.rsv-hotline-box i {
    font-size: 0.85rem;
}

.rsv-hotline-box strong {
    font-size: 0.95rem;
    letter-spacing: 0.5px;
}

.rsv-form {
    padding: 30px;
    background: #ffffff;
}

.rsv-form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 4px;
}

.rsv-input-group {
    margin-bottom: 18px;
}
.rsv-input-group label {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #374151;
    font-size: 0.9rem;
    font-weight: 600;
    margin-bottom: 8px;
}
.rsv-input-group label i {
    color: #22c55e;
    width: 18px;
    font-size: 0.9rem;
}
.rsv-input-group label span {
    color: #ef4444;
    font-size: 0.85rem;
}

.rsv-input-group input,
.rsv-input-group select,
.rsv-input-group textarea {
    width: 100%;
    padding: 14px 16px;
    background: #f8fafc;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    color: #1f2937;
    font-size: 0.95rem;
    transition: all 0.3s;
}
.rsv-input-group input::placeholder,
.rsv-input-group textarea::placeholder {
    color: #9ca3af;
}
.rsv-input-group input:hover,
.rsv-input-group select:hover,
.rsv-input-group textarea:hover {
    border-color: #22c55e;
    background: #ffffff;
}
.rsv-input-group input:focus,
.rsv-input-group select:focus,
.rsv-input-group textarea:focus {
    outline: none;
    border-color: #22c55e;
    background: #ffffff;
    box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.1);
}
.rsv-input-group select {
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%2322c55e' viewBox='0 0 16 16'%3E%3Cpath d='M8 11L3 6h10l-5 5z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 16px center;
    background-color: #f8fafc;
}
.rsv-input-group select option {
    background: #ffffff;
    color: #1f2937;
    padding: 12px;
}
.rsv-input-group textarea {
    min-height: 90px;
    resize: vertical;
}

.rsv-submit-btn {
    width: 100%;
    padding: 16px 28px;
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
    gap: 10px;
    box-shadow: 0 6px 25px rgba(34, 197, 94, 0.35);
    position: relative;
    overflow: hidden;
    transition: all 0.3s;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-top: 10px;
}
.rsv-submit-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    transition: left 0.5s ease;
}
.rsv-submit-btn:hover::before {
    left: 100%;
}
.rsv-submit-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 30px rgba(34, 197, 94, 0.4);
}
.rsv-submit-btn:active {
    transform: translateY(-1px);
}
.rsv-submit-btn i {
    font-size: 0.95rem;
}

/* History Link */
.rsv-history-link a {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 12px 24px;
    background: rgba(34, 197, 94, 0.1);
    border: 2px solid #22c55e;
    border-radius: 50px;
    color: #22c55e;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s;
}
.rsv-history-link a:hover {
    background: #22c55e;
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(34, 197, 94, 0.3);
}

.rsv-view-history {
    color: #16a34a;
    text-decoration: underline;
    font-weight: 600;
}

@media (max-width: 900px) {
    .rsv-layout {
        grid-template-columns: 1fr;
        max-width: 100%;
    }
}

@media (max-width: 600px) {
    .rsv-form { padding: 20px; }
    .rsv-form-grid { grid-template-columns: 1fr; }
    .rsv-user-info-grid { grid-template-columns: 1fr; }
    .rsv-user-item.full { grid-column: span 1; }
    .rsv-hotline-box {
        flex-direction: column;
        text-align: center;
        gap: 8px;
    }
}

/* Force override dark theme styles */
body.dark-theme .rsv-form-card,
body.dark-theme .rsv-form {
    background: #ffffff !important;
}

body.dark-theme .rsv-form-header {
    background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%) !important;
}

body.dark-theme .rsv-form-header h2 {
    color: #166534 !important;
    background: none !important;
    -webkit-text-fill-color: #166534 !important;
}

body.dark-theme .rsv-form-header p {
    color: #6b7280 !important;
}

body.dark-theme .rsv-input-group label {
    color: #374151 !important;
}

body.dark-theme .rsv-input-group label i {
    color: #22c55e !important;
}

body.dark-theme .rsv-input-group input,
body.dark-theme .rsv-input-group select,
body.dark-theme .rsv-input-group textarea {
    background: #f8fafc !important;
    border: 2px solid #e2e8f0 !important;
    color: #1f2937 !important;
    box-sizing: border-box !important;
    max-width: 100% !important;
}

body.dark-theme .rsv-input-group input:focus,
body.dark-theme .rsv-input-group select:focus,
body.dark-theme .rsv-input-group textarea:focus {
    background: #ffffff !important;
    border-color: #22c55e !important;
    box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.1) !important;
}

body.dark-theme .rsv-input-group input::placeholder,
body.dark-theme .rsv-input-group textarea::placeholder {
    color: #9ca3af !important;
}

body.dark-theme .rsv-user-info-box {
    background: linear-gradient(135deg, rgba(34, 197, 94, 0.06), rgba(34, 197, 94, 0.02)) !important;
    border: 1px solid rgba(34, 197, 94, 0.2) !important;
}

body.dark-theme .rsv-user-info-header span {
    color: #22c55e !important;
}

body.dark-theme .rsv-user-value {
    background: #ffffff !important;
    color: #1f2937 !important;
    border: 1px solid #e5e7eb !important;
}

/* Fix input width overflow */
.rsv-form-card,
.rsv-form {
    overflow: hidden !important;
}

.rsv-input-group input,
.rsv-input-group select,
.rsv-input-group textarea {
    box-sizing: border-box !important;
    max-width: 100% !important;
}
</style>
