<?php
/**
 * Component Voucher & Points cho trang Checkout
 * Include file này vào checkout.php
 */

// Lấy thông tin điểm của khách hàng
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
$min_redeem = intval($point_settings['min_redeem_points'] ?? 100);
$max_redeem_percent = intval($point_settings['max_redeem_percent'] ?? 50);

// Lấy voucher đã áp dụng từ session
$applied_voucher = $_SESSION['applied_voucher'] ?? null;
$applied_points = $_SESSION['applied_points'] ?? null;
?>

<!-- Voucher & Points Section -->
<div class="form-card voucher-points-card">
    <div class="card-header">
        <i class="fas fa-gift"></i>
        <h3>Ưu đãi & Điểm thưởng</h3>
    </div>
    <div class="card-body">
        
        <!-- Voucher Section -->
        <div class="discount-section">
            <div class="section-title">
                <i class="fas fa-ticket-alt"></i> Mã giảm giá
                <a href="?page=vouchers" class="view-all-link">Xem tất cả</a>
            </div>
            
            <?php if ($applied_voucher): ?>
            <div class="applied-discount voucher-applied">
                <div class="discount-info">
                    <span class="discount-code"><?php echo $applied_voucher['code']; ?></span>
                    <span class="discount-name"><?php echo htmlspecialchars($applied_voucher['name']); ?></span>
                    <span class="discount-amount">-<?php echo number_format($applied_voucher['discount_amount']); ?>đ</span>
                </div>
                <button type="button" class="btn-remove-discount" onclick="removeVoucher()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <input type="hidden" name="voucher_code" value="<?php echo $applied_voucher['code']; ?>">
            <input type="hidden" name="voucher_discount" id="voucherDiscountInput" value="<?php echo $applied_voucher['discount_amount']; ?>">
            <?php else: ?>
            <div class="voucher-input-row">
                <input type="text" id="voucherCodeInput" placeholder="Nhập mã voucher..." maxlength="50">
                <button type="button" class="btn-apply-voucher" onclick="applyVoucher()">Áp dụng</button>
            </div>
            <div class="voucher-message" id="voucherMessage"></div>
            <input type="hidden" name="voucher_code" id="voucherCodeHidden" value="">
            <input type="hidden" name="voucher_discount" id="voucherDiscountInput" value="0">
            <?php endif; ?>
        </div>
        
        <!-- Points Section -->
        <?php if ($available_points > 0): ?>
        <div class="discount-section points-section">
            <div class="section-title">
                <i class="fas fa-star"></i> Điểm tích lũy
                <span class="points-balance"><?php echo number_format($available_points); ?> điểm khả dụng</span>
            </div>
            
            <?php if ($applied_points): ?>
            <div class="applied-discount points-applied">
                <div class="discount-info">
                    <span class="discount-code"><?php echo number_format($applied_points['points']); ?> điểm</span>
                    <span class="discount-amount">-<?php echo number_format($applied_points['discount_amount']); ?>đ</span>
                </div>
                <button type="button" class="btn-remove-discount" onclick="removePoints()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <input type="hidden" name="points_used" value="<?php echo $applied_points['points']; ?>">
            <input type="hidden" name="points_discount" id="pointsDiscountInput" value="<?php echo $applied_points['discount_amount']; ?>">
            <?php else: ?>
            <div class="points-slider-container">
                <div class="points-input-row">
                    <input type="range" id="pointsSlider" min="0" max="<?php echo $available_points; ?>" value="0" step="10">
                    <input type="number" id="pointsInput" min="0" max="<?php echo $available_points; ?>" value="0" class="points-number-input">
                </div>
                <div class="points-info-row">
                    <span class="points-value-display">0 điểm = 0đ</span>
                    <button type="button" class="btn-apply-points" onclick="applyPoints()" disabled>Dùng điểm</button>
                </div>
                <div class="points-note">
                    <i class="fas fa-info-circle"></i> 
                    Tối đa <?php echo $max_redeem_percent; ?>% đơn hàng
                </div>
            </div>
            <input type="hidden" name="points_used" id="pointsUsedHidden" value="0">
            <input type="hidden" name="points_discount" id="pointsDiscountInput" value="0">
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
    </div>
</div>

<style>
.voucher-points-card { margin-bottom: 20px; }
.voucher-points-card .card-header { background: #059669; }

.discount-section { padding: 16px 0; border-bottom: 1px solid #e2e8f0; }
.discount-section:last-child { border-bottom: none; padding-bottom: 0; }

.section-title { display: flex; align-items: center; gap: 8px; margin-bottom: 12px; color: #1e293b; font-weight: 700; }
.section-title i { color: #059669; }
.view-all-link { margin-left: auto; font-size: 13px; color: #059669; text-decoration: none; font-weight: 500; }
.view-all-link:hover { text-decoration: underline; }
.points-balance { margin-left: auto; font-size: 13px; color: #059669; font-weight: 600; }

.voucher-input-row { display: flex; gap: 10px; }
.voucher-input-row input { flex: 1; padding: 12px 16px; background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 8px; color: #1e293b; text-transform: uppercase; font-weight: 600; }
.voucher-input-row input:focus { border-color: #059669; outline: none; box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.15); }
.voucher-input-row input::placeholder { text-transform: none; color: #94a3b8; font-weight: 400; }
.btn-apply-voucher { padding: 12px 20px; background: #059669; color: #fff; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; white-space: nowrap; transition: all 0.3s; }
.btn-apply-voucher:hover { background: #047857; }

.voucher-message { margin-top: 10px; font-size: 14px; padding: 12px 16px; border-radius: 8px; display: none; font-weight: 500; }
.voucher-message.success { display: block; background: #ecfdf5; color: #059669; border: 2px solid #34d399; }
.voucher-message.error { display: block; background: #fef2f2; color: #dc2626; border: 2px solid #fca5a5; }

.applied-discount { display: flex; align-items: center; justify-content: space-between; background: #ecfdf5; border: 2px solid #34d399; border-radius: 10px; padding: 12px 16px; }
.discount-info { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
.discount-code { font-family: monospace; font-weight: 700; color: #000000 !important; background: #fef3c7; padding: 6px 12px; border-radius: 6px; border: 1px solid #f59e0b; }
.discount-name { color: #000000; font-size: 13px; font-weight: 600; }
.discount-amount { color: #059669; font-weight: 800; font-size: 16px; }
.btn-remove-discount { background: none; border: none; color: #dc2626; cursor: pointer; padding: 8px; font-size: 16px; }
.btn-remove-discount:hover { color: #b91c1c; }

/* Points slider */
.points-slider-container { margin-top: 8px; }
.points-input-row { display: flex; align-items: center; gap: 12px; }
.points-input-row input[type="range"] { flex: 1; height: 8px; -webkit-appearance: none; background: #e2e8f0; border-radius: 4px; }
.points-input-row input[type="range"]::-webkit-slider-thumb { -webkit-appearance: none; width: 20px; height: 20px; background: #059669; border-radius: 50%; cursor: pointer; }
.points-number-input { width: 80px; padding: 8px 12px; background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 6px; color: #1e293b; text-align: center; font-weight: 600; }

.points-info-row { display: flex; justify-content: space-between; align-items: center; margin-top: 12px; }
.points-value-display { color: #059669; font-weight: 700; }
.btn-apply-points { padding: 8px 16px; background: #059669; color: #fff; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; }
.btn-apply-points:disabled { opacity: 0.5; cursor: not-allowed; }
.btn-apply-points:hover:not(:disabled) { background: #047857; }

.points-note { margin-top: 8px; font-size: 12px; color: #64748b; }
.points-not-enough { font-size: 13px; color: #64748b; padding: 12px; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0; }
</style>

<script>
// Voucher functions
function applyVoucher() {
    const code = document.getElementById('voucherCodeInput').value.trim();
    const msgDiv = document.getElementById('voucherMessage');
    const subtotal = <?php echo $subtotal; ?>;
    
    if (!code) {
        msgDiv.className = 'voucher-message error';
        msgDiv.textContent = 'Vui lòng nhập mã voucher';
        return;
    }
    
    // Hiển thị loading
    msgDiv.className = 'voucher-message';
    msgDiv.style.display = 'block';
    msgDiv.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang kiểm tra...';
    
    fetch('api/voucher.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=apply&code=${encodeURIComponent(code)}&order_total=${subtotal}`
    })
    .then(res => res.json())
    .then(data => {
        console.log('Voucher response:', data);
        if (data.success) {
            msgDiv.className = 'voucher-message success';
            msgDiv.textContent = data.message || 'Áp dụng thành công!';
            setTimeout(() => location.reload(), 500);
        } else {
            msgDiv.className = 'voucher-message error';
            msgDiv.textContent = data.message || 'Mã voucher không hợp lệ';
        }
    })
    .catch(err => {
        console.error('Voucher error:', err);
        msgDiv.className = 'voucher-message error';
        msgDiv.textContent = 'Lỗi kết nối, vui lòng thử lại';
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

// Points functions
const pointsSlider = document.getElementById('pointsSlider');
const pointsInput = document.getElementById('pointsInput');
const pointsDisplay = document.querySelector('.points-value-display');
const applyPointsBtn = document.querySelector('.btn-apply-points');
const pointsToMoney = <?php echo $points_to_money; ?>;
const minRedeem = <?php echo $min_redeem; ?>;
const maxRedeemPercent = <?php echo $max_redeem_percent; ?>;
const subtotal = <?php echo $subtotal; ?>;

if (pointsSlider) {
    function updatePointsDisplay() {
        const points = parseInt(pointsSlider.value) || 0;
        pointsInput.value = points;
        const value = points * pointsToMoney;
        pointsDisplay.textContent = `${points.toLocaleString()} điểm = ${value.toLocaleString()}đ`;
        applyPointsBtn.disabled = points <= 0;
    }
    
    pointsSlider.addEventListener('input', updatePointsDisplay);
    pointsInput.addEventListener('input', function() {
        pointsSlider.value = this.value;
        updatePointsDisplay();
    });
}

function applyPoints() {
    const points = parseInt(pointsInput.value) || 0;
    
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
            alert(data.message);
        }
    });
}

function removePoints() {
    fetch('api/points.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=remove'
    })
    .then(() => location.reload());
}
</script>
