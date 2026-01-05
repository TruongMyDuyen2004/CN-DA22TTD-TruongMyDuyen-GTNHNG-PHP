<?php
/**
 * Trang Voucher của khách hàng - Xem và lưu mã giảm giá
 */
if (!isset($_SESSION['customer_id'])) {
    echo '<script>window.location.href = "auth/login.php";</script>';
    exit;
}

$db = new Database();
$conn = $db->connect();
$customer_id = $_SESSION['customer_id'];

// Lấy danh sách voucher khả dụng
$stmt = $conn->prepare("
    SELECT v.*, 
           (SELECT COUNT(*) FROM voucher_usage vu WHERE vu.voucher_id = v.id AND vu.customer_id = ?) as user_used
    FROM vouchers v 
    WHERE v.is_active = 1 
    AND v.start_date <= NOW() 
    AND v.end_date >= NOW()
    AND (v.usage_limit IS NULL OR v.used_count < v.usage_limit)
    ORDER BY v.discount_value DESC
");
$stmt->execute([$customer_id]);
$vouchers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy lịch sử sử dụng voucher
$stmt = $conn->prepare("
    SELECT vu.*, v.code, v.name, o.order_number
    FROM voucher_usage vu
    JOIN vouchers v ON vu.voucher_id = v.id
    LEFT JOIN orders o ON vu.order_id = o.id
    WHERE vu.customer_id = ?
    ORDER BY vu.used_at DESC
    LIMIT 10
");
$stmt->execute([$customer_id]);
$history = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
.vouchers-page { padding: 20px; max-width: 900px; margin: 0 auto; background: #f8fafc; min-height: 100vh; }

.page-header { text-align: center; margin-bottom: 30px; }
.page-header h1 { font-size: 28px; color: #059669; margin-bottom: 8px; font-weight: 700; }
.page-header p { color: #64748b; }

/* Voucher input */
.voucher-input-card { background: #ffffff; border-radius: 16px; padding: 24px; margin-bottom: 24px; border: 2px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
.voucher-input-card h3 { color: #1e293b; font-size: 16px; margin-bottom: 16px; font-weight: 700; }
.voucher-input-form { display: flex; gap: 12px; }
.voucher-input-form input { flex: 1; padding: 14px 18px; background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 10px; color: #1e293b; font-size: 16px; text-transform: uppercase; }
.voucher-input-form input:focus { border-color: #059669; outline: none; box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.15); }
.voucher-input-form input::placeholder { text-transform: none; color: #94a3b8; }
.btn-check-voucher { padding: 14px 28px; background: #059669; color: #fff; border: none; border-radius: 10px; font-weight: 700; cursor: pointer; white-space: nowrap; transition: all 0.3s; box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3); }
.btn-check-voucher:hover { background: #047857; transform: translateY(-2px); box-shadow: 0 6px 20px rgba(5, 150, 105, 0.4); }

.voucher-result { margin-top: 16px; padding: 16px; border-radius: 10px; display: none; font-weight: 500; }
.voucher-result.success { background: #ecfdf5; border: 2px solid #34d399; color: #059669; }
.voucher-result.error { background: #fef2f2; border: 2px solid #fca5a5; color: #dc2626; }

/* Voucher list */
.vouchers-section { margin-bottom: 24px; }
.vouchers-section h3 { color: #1e293b; font-size: 18px; margin-bottom: 16px; display: flex; align-items: center; gap: 10px; font-weight: 700; }
.vouchers-section h3 span { background: #059669; color: #fff; font-size: 12px; padding: 4px 12px; border-radius: 20px; font-weight: 700; }

.voucher-grid { display: grid; gap: 16px; }

.voucher-card { background: #ffffff; border-radius: 16px; overflow: hidden; border: 2px solid #e2e8f0; display: flex; transition: all 0.3s; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
.voucher-card:hover { border-color: #059669; transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1); }
.voucher-card.used { opacity: 0.6; }

.voucher-left { width: 130px; padding: 20px; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; border-right: 2px dashed #e2e8f0; position: relative; background: #f0fdf4; }
.voucher-left::before, .voucher-left::after { content: ''; position: absolute; width: 20px; height: 20px; background: #f8fafc; border-radius: 50%; right: -10px; }
.voucher-left::before { top: -10px; }
.voucher-left::after { bottom: -10px; }

.voucher-discount { font-size: 28px; font-weight: 800; color: #059669; }
.voucher-discount.percent::after { content: '%'; font-size: 16px; }
.voucher-discount.fixed { font-size: 20px; }
.voucher-type { font-size: 11px; color: #64748b; margin-top: 4px; text-transform: uppercase; font-weight: 600; }

.voucher-right { flex: 1; padding: 20px; display: flex; flex-direction: column; }
.voucher-name { font-weight: 700; color: #1e293b; font-size: 16px; margin-bottom: 4px; }
.voucher-desc { font-size: 13px; color: #64748b; margin-bottom: 12px; line-height: 1.4; }
.voucher-conditions { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 12px; }
.voucher-tag { font-size: 11px; padding: 5px 12px; background: #f1f5f9; border-radius: 20px; color: #475569; font-weight: 500; border: 1px solid #e2e8f0; }
.voucher-tag i { margin-right: 4px; color: #059669; }

.voucher-footer { display: flex; justify-content: space-between; align-items: center; margin-top: auto; }
.voucher-code-display { font-family: monospace; font-size: 14px; font-weight: 700; color: #d97706; background: #fef3c7; padding: 8px 14px; border-radius: 8px; cursor: pointer; border: 2px solid #f59e0b; transition: all 0.3s; }
.voucher-code-display:hover { background: #fde68a; transform: scale(1.02); }
.voucher-expiry { font-size: 12px; color: #64748b; font-weight: 500; }
.voucher-expiry.soon { color: #dc2626; font-weight: 700; }

.voucher-status { font-size: 12px; padding: 4px 12px; border-radius: 20px; font-weight: 600; }
.voucher-status.available { background: #ecfdf5; color: #059669; border: 1px solid #34d399; }
.voucher-status.used { background: #f1f5f9; color: #64748b; border: 1px solid #cbd5e1; }

/* History */
.history-section { background: #ffffff; border-radius: 16px; padding: 24px; border: 2px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
.history-section h3 { color: #1e293b; font-size: 16px; margin-bottom: 16px; font-weight: 700; }
.history-item { display: flex; justify-content: space-between; align-items: center; padding: 14px 0; border-bottom: 1px solid #e2e8f0; }
.history-item:last-child { border-bottom: none; }
.history-code { font-family: monospace; font-weight: 700; color: #059669; font-size: 15px; }
.history-info { font-size: 13px; color: #64748b; margin-top: 4px; }
.history-discount { font-weight: 800; color: #059669; font-size: 16px; }

.empty-state { text-align: center; padding: 40px; color: #64748b; background: #f8fafc; border-radius: 12px; }
.empty-state i { font-size: 48px; color: #cbd5e1; margin-bottom: 16px; display: block; }

/* Copy tooltip */
.copy-tooltip { position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); background: #059669; color: #fff; padding: 12px 24px; border-radius: 8px; font-weight: 600; opacity: 0; transition: opacity 0.3s; z-index: 1000; box-shadow: 0 4px 15px rgba(5, 150, 105, 0.4); }
.copy-tooltip.show { opacity: 1; }

@media (max-width: 600px) {
    .voucher-input-form { flex-direction: column; }
    .voucher-card { flex-direction: column; }
    .voucher-left { width: 100%; border-right: none; border-bottom: 2px dashed #e2e8f0; padding: 16px; }
    .voucher-left::before, .voucher-left::after { display: none; }
}
</style>

<div class="vouchers-page">
    <div class="page-header">
        <h1><i class="fas fa-ticket-alt"></i> Voucher của tôi</h1>
        <p>Nhập mã hoặc chọn voucher để sử dụng khi thanh toán</p>
    </div>
    
    <!-- Nhập mã voucher -->
    <div class="voucher-input-card">
        <h3><i class="fas fa-keyboard"></i> Nhập mã voucher</h3>
        <div class="voucher-input-form">
            <input type="text" id="voucherCodeInput" placeholder="Nhập mã voucher..." maxlength="50">
            <button class="btn-check-voucher" onclick="checkVoucher()">
                <i class="fas fa-check"></i> Kiểm tra
            </button>
        </div>
        <div class="voucher-result" id="voucherResult"></div>
    </div>
    
    <!-- Danh sách voucher khả dụng -->
    <div class="vouchers-section">
        <h3><i class="fas fa-gift"></i> Voucher khả dụng <span><?php echo count($vouchers); ?></span></h3>
        
        <?php if (empty($vouchers)): ?>
        <div class="empty-state">
            <i class="fas fa-ticket-alt"></i>
            <p>Chưa có voucher nào khả dụng</p>
            <p style="font-size:13px;">Hãy theo dõi để nhận voucher mới!</p>
        </div>
        <?php else: ?>
        <div class="voucher-grid">
            <?php foreach ($vouchers as $v): 
                $canUse = $v['user_used'] < $v['usage_per_user'];
                $daysLeft = (strtotime($v['end_date']) - time()) / 86400;
                $expiringSoon = $daysLeft <= 3;
            ?>
            <div class="voucher-card <?php echo !$canUse ? 'used' : ''; ?>">
                <div class="voucher-left">
                    <?php if ($v['discount_type'] === 'percent'): ?>
                    <div class="voucher-discount percent"><?php echo intval($v['discount_value']); ?></div>
                    <div class="voucher-type">Giảm giá</div>
                    <?php else: ?>
                    <div class="voucher-discount fixed"><?php echo number_format($v['discount_value']); ?>đ</div>
                    <div class="voucher-type">Giảm trực tiếp</div>
                    <?php endif; ?>
                </div>
                <div class="voucher-right">
                    <div class="voucher-name"><?php echo htmlspecialchars($v['name']); ?></div>
                    <div class="voucher-desc"><?php echo htmlspecialchars($v['description']); ?></div>
                    
                    <div class="voucher-conditions">
                        <span class="voucher-tag"><i class="fas fa-shopping-cart"></i> Đơn tối thiểu <?php echo number_format($v['min_order_value']); ?>đ</span>
                        <?php if ($v['max_discount']): ?>
                        <span class="voucher-tag"><i class="fas fa-arrow-down"></i> Giảm tối đa <?php echo number_format($v['max_discount']); ?>đ</span>
                        <?php endif; ?>
                        <span class="voucher-tag"><i class="fas fa-redo"></i> Còn <?php echo $v['usage_per_user'] - $v['user_used']; ?> lượt</span>
                    </div>
                    
                    <div class="voucher-footer">
                        <span class="voucher-code-display" onclick="copyCode('<?php echo $v['code']; ?>')" title="Click để copy">
                            <i class="fas fa-copy"></i> <?php echo $v['code']; ?>
                        </span>
                        <span class="voucher-expiry <?php echo $expiringSoon ? 'soon' : ''; ?>">
                            <?php if ($expiringSoon): ?>
                            <i class="fas fa-clock"></i> Còn <?php echo ceil($daysLeft); ?> ngày
                            <?php else: ?>
                            HSD: <?php echo date('d/m/Y', strtotime($v['end_date'])); ?>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Lịch sử sử dụng -->
    <?php if (!empty($history)): ?>
    <div class="history-section">
        <h3><i class="fas fa-history"></i> Lịch sử sử dụng</h3>
        <?php foreach ($history as $h): ?>
        <div class="history-item">
            <div>
                <div class="history-code"><?php echo $h['code']; ?></div>
                <div class="history-info">
                    <?php echo $h['name']; ?>
                    <?php if ($h['order_number']): ?>
                    • Đơn <?php echo $h['order_number']; ?>
                    <?php endif; ?>
                    • <?php echo date('d/m/Y', strtotime($h['used_at'])); ?>
                </div>
            </div>
            <div class="history-discount">-<?php echo number_format($h['discount_amount']); ?>đ</div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<div class="copy-tooltip" id="copyTooltip">Đã copy mã voucher!</div>

<script>
function checkVoucher() {
    const code = document.getElementById('voucherCodeInput').value.trim();
    const resultDiv = document.getElementById('voucherResult');
    
    if (!code) {
        resultDiv.className = 'voucher-result error';
        resultDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> Vui lòng nhập mã voucher';
        resultDiv.style.display = 'block';
        return;
    }
    
    fetch('api/voucher.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=check&code=${encodeURIComponent(code)}&order_total=0`
    })
    .then(res => res.json())
    .then(data => {
        resultDiv.style.display = 'block';
        if (data.success) {
            resultDiv.className = 'voucher-result success';
            resultDiv.innerHTML = `<i class="fas fa-check-circle"></i> <strong>${data.voucher.name}</strong><br>
                ${data.voucher.description || ''}<br>
                <small>Đơn tối thiểu: ${Number(data.voucher.min_order_value).toLocaleString()}đ</small>`;
        } else {
            resultDiv.className = 'voucher-result error';
            resultDiv.innerHTML = `<i class="fas fa-times-circle"></i> ${data.message}`;
        }
    })
    .catch(err => {
        resultDiv.className = 'voucher-result error';
        resultDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Lỗi kết nối';
        resultDiv.style.display = 'block';
    });
}

function copyCode(code) {
    navigator.clipboard.writeText(code).then(() => {
        const tooltip = document.getElementById('copyTooltip');
        tooltip.classList.add('show');
        setTimeout(() => tooltip.classList.remove('show'), 2000);
    });
}

// Enter to check
document.getElementById('voucherCodeInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') checkVoucher();
});
</script>
