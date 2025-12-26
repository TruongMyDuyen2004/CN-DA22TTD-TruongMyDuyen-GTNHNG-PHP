<?php
if (!isset($_SESSION['customer_id'])) {
    echo '<script>window.location.href = "auth/login.php";</script>';
    exit;
}

$db = new Database();
$conn = $db->connect();

// Lấy thông tin thẻ thành viên
$member_card = null;
$transactions = [];
$pending_topups = [];

try {
    $stmt = $conn->prepare("SELECT * FROM member_cards WHERE customer_id = ?");
    $stmt->execute([$_SESSION['customer_id']]);
    $member_card = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($member_card) {
        // Lấy lịch sử giao dịch gần đây
        $stmt = $conn->prepare("
            SELECT ct.*, o.order_number
            FROM card_transactions ct
            LEFT JOIN orders o ON ct.order_id = o.id
            WHERE ct.card_id = ?
            ORDER BY ct.created_at DESC
            LIMIT 10
        ");
        $stmt->execute([$member_card['id']]);
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Lấy các yêu cầu nạp tiền đang chờ hoặc bị từ chối
        $stmt = $conn->prepare("
            SELECT * FROM topup_requests 
            WHERE customer_id = ? AND status IN ('waiting', 'failed', 'pending')
            ORDER BY created_at DESC
            LIMIT 10
        ");
        $stmt->execute([$_SESSION['customer_id']]);
        $pending_topups = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    // Bảng chưa tồn tại
}
?>

<section class="member-card-page">
    <div class="page-container">
        <div class="page-header">
            <h1><i class="fas fa-credit-card"></i> Thẻ Thành Viên</h1>
            <p>Quản lý thẻ và xem lịch sử giao dịch của bạn</p>
        </div>
        
        <?php if (!$member_card): ?>
        <!-- Chưa có thẻ -->
        <div class="no-card-box">
            <div class="no-card-icon">
                <i class="fas fa-credit-card"></i>
            </div>
            <h3>Bạn chưa có thẻ thành viên</h3>
            <p>Liên hệ nhà hàng để đăng ký thẻ thành viên và nhận nhiều ưu đãi hấp dẫn!</p>
            <a href="?page=contact" class="btn-contact">
                <i class="fas fa-phone"></i> Liên hệ ngay
            </a>
        </div>
        <?php else: ?>
        
        <!-- Thẻ thành viên hiện đại -->
        <div class="card-display">
            <div class="modern-card-wrapper">
                <div class="modern-card <?php echo $member_card['status'] == 'blocked' ? 'blocked' : ''; ?>">
                    <!-- Hiệu ứng nền -->
                    <div class="card-bg-effects">
                        <div class="card-glow"></div>
                        <div class="card-pattern"></div>
                        <div class="card-shine"></div>
                    </div>
                    
                    <!-- Header thẻ -->
                    <div class="card-header-row">
                        <div class="card-brand">
                            <div class="brand-icon">
                                <i class="fas fa-utensils"></i>
                            </div>
                            <div class="brand-text">
                                <span class="brand-name">Ngon Gallery</span>
                                <span class="brand-type">Premium Member</span>
                            </div>
                        </div>
                        <div class="card-status-badge">
                            <?php if ($member_card['status'] == 'active'): ?>
                            <span class="status-active"><i class="fas fa-check-circle"></i> Hoạt động</span>
                            <?php else: ?>
                            <span class="status-blocked"><i class="fas fa-lock"></i> Đã khóa</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Chip thẻ -->
                    <div class="card-chip">
                        <div class="chip-lines">
                            <span></span><span></span><span></span><span></span>
                        </div>
                    </div>
                    
                    <!-- Số thẻ -->
                    <div class="card-number-section">
                        <div class="card-number-display">
                            <?php 
                            $card_num = $member_card['card_number'];
                            $formatted = implode(' ', str_split($card_num, 4));
                            echo $formatted;
                            ?>
                        </div>
                    </div>
                    
                    <!-- Số dư -->
                    <div class="card-balance-row">
                        <div class="balance-info">
                            <span class="balance-label">SỐ DƯ HIỆN TẠI</span>
                            <span class="balance-value"><?php echo number_format($member_card['balance']); ?><small>đ</small></span>
                        </div>
                        <div class="card-contactless">
                            <i class="fas fa-wifi"></i>
                        </div>
                    </div>
                    
                    <!-- Footer thẻ -->
                    <div class="card-footer-row">
                        <div class="card-holder">
                            <span class="holder-label">CHỦ THẺ</span>
                            <span class="holder-name"><?php echo strtoupper($_SESSION['customer_name'] ?? 'MEMBER'); ?></span>
                        </div>
                        <div class="card-valid">
                            <span class="valid-label">NGÀY TẠO</span>
                            <span class="valid-date"><?php echo date('m/Y', strtotime($member_card['created_at'])); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Bóng đổ 3D -->
                <div class="card-shadow"></div>
            </div>
            
            <!-- Thống kê hiện đại -->
            <div class="modern-stats">
                <div class="stat-card stat-deposit">
                    <div class="stat-icon-wrap">
                        <div class="stat-icon">
                            <i class="fas fa-arrow-up"></i>
                        </div>
                        <div class="stat-icon-bg"></div>
                    </div>
                    <div class="stat-content">
                        <span class="stat-value"><?php echo number_format($member_card['total_deposited']); ?><small>đ</small></span>
                        <span class="stat-label">Tổng nạp</span>
                    </div>
                </div>
                <div class="stat-card stat-spent">
                    <div class="stat-icon-wrap">
                        <div class="stat-icon">
                            <i class="fas fa-shopping-bag"></i>
                        </div>
                        <div class="stat-icon-bg"></div>
                    </div>
                    <div class="stat-content">
                        <span class="stat-value"><?php echo number_format($member_card['total_spent']); ?><small>đ</small></span>
                        <span class="stat-label">Đã chi tiêu</span>
                    </div>
                </div>
                <div class="stat-card stat-date">
                    <div class="stat-icon-wrap">
                        <div class="stat-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="stat-icon-bg"></div>
                    </div>
                    <div class="stat-content">
                        <span class="stat-value"><?php echo date('d/m/Y', strtotime($member_card['created_at'])); ?></span>
                        <span class="stat-label">Ngày tạo thẻ</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Nút nạp tiền -->
        <div class="topup-section">
            <button class="btn-topup" onclick="openTopupModal()">
                <i class="fas fa-plus-circle"></i> Nạp tiền vào thẻ
            </button>
        </div>
        
        <!-- Layout 2 cột: Yêu cầu nạp tiền (trái) + Lịch sử giao dịch (phải) -->
        <div class="two-column-layout">
            <!-- Cột trái: Yêu cầu nạp tiền -->
            <div class="column-left">
                <div class="pending-topups-section">
                    <h2><i class="fas fa-clock"></i> Yêu cầu nạp tiền</h2>
                    <?php if (empty($pending_topups)): ?>
                    <div class="no-pending">
                        <i class="fas fa-check-circle"></i>
                        <p>Không có yêu cầu nào đang chờ</p>
                    </div>
                    <?php else: ?>
                    <div class="pending-topups-list">
                        <?php foreach ($pending_topups as $topup): ?>
                        <div class="pending-topup-item status-<?php echo $topup['status']; ?>">
                            <div class="topup-icon">
                                <?php if ($topup['status'] == 'waiting'): ?>
                                <i class="fas fa-hourglass-half"></i>
                                <?php elseif ($topup['status'] == 'failed'): ?>
                                <i class="fas fa-times-circle"></i>
                                <?php else: ?>
                                <i class="fas fa-clock"></i>
                                <?php endif; ?>
                            </div>
                            <div class="topup-info">
                                <div class="topup-amount"><?php echo number_format($topup['amount']); ?>đ</div>
                                <div class="topup-method">
                                    <?php 
                                    $methods = ['momo' => 'Momo', 'zalopay' => 'ZaloPay', 'bank' => 'Chuyển khoản'];
                                    echo $methods[$topup['method']] ?? $topup['method'];
                                    ?> - <?php echo $topup['transaction_code']; ?>
                                </div>
                                <div class="topup-date"><?php echo date('d/m/Y H:i', strtotime($topup['created_at'])); ?></div>
                                <?php if ($topup['status'] == 'failed' && !empty($topup['payment_info'])): ?>
                                <div class="topup-reason">
                                    <i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($topup['payment_info']); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="topup-status">
                                <?php if ($topup['status'] == 'waiting'): ?>
                                <span class="badge-waiting"><i class="fas fa-clock"></i> Chờ duyệt</span>
                                <?php elseif ($topup['status'] == 'failed'): ?>
                                <span class="badge-failed"><i class="fas fa-times"></i> Từ chối</span>
                                <?php else: ?>
                                <span class="badge-pending"><i class="fas fa-hourglass"></i> Đang xử lý</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Cột phải: Lịch sử giao dịch -->
            <div class="column-right">
                <div class="transactions-section">
                    <h2><i class="fas fa-history"></i> Lịch sử giao dịch gần đây</h2>
                    
                    <?php if (empty($transactions)): ?>
                    <div class="no-transactions">
                        <i class="fas fa-receipt"></i>
                        <p>Chưa có giao dịch nào</p>
                    </div>
                    <?php else: ?>
                    <div class="transactions-list">
                        <?php foreach ($transactions as $tx): ?>
                <div class="transaction-item <?php echo $tx['type']; ?>">
                    <div class="tx-icon">
                        <?php if ($tx['type'] == 'deposit'): ?>
                        <i class="fas fa-arrow-down"></i>
                        <?php elseif ($tx['type'] == 'payment'): ?>
                        <i class="fas fa-shopping-bag"></i>
                        <?php else: ?>
                        <i class="fas fa-undo"></i>
                        <?php endif; ?>
                    </div>
                    <div class="tx-info">
                        <span class="tx-desc"><?php echo htmlspecialchars($tx['description']); ?></span>
                        <span class="tx-date"><?php echo date('d/m/Y H:i', strtotime($tx['created_at'])); ?></span>
                    </div>
                    <div class="tx-amount <?php echo ($tx['type'] == 'deposit' || $tx['type'] == 'refund') ? 'positive' : 'negative'; ?>">
                        <?php echo ($tx['type'] == 'deposit' || $tx['type'] == 'refund') ? '+' : '-'; ?>
                        <?php echo number_format($tx['amount']); ?>đ
                    </div>
                </div>
                <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <!-- Kết thúc layout 2 cột -->
        
        <?php endif; ?>
    </div>
</section>

<!-- Modal Nạp tiền -->
<?php if ($member_card): ?>
<div class="topup-modal" id="topupModal">
    <div class="modal-overlay" onclick="closeTopupModal()"></div>
    <div class="modal-content">
        <!-- Step 1: Chọn số tiền và phương thức -->
        <div id="topupStep1">
            <div class="modal-header">
                <h3><i class="fas fa-plus-circle"></i> Nạp tiền vào thẻ</h3>
                <button class="modal-close" onclick="closeTopupModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="amount-section">
                    <label>Chọn số tiền nạp</label>
                    <div class="amount-presets">
                        <button type="button" class="amount-btn" data-amount="50000">50,000đ</button>
                        <button type="button" class="amount-btn" data-amount="100000">100,000đ</button>
                        <button type="button" class="amount-btn" data-amount="200000">200,000đ</button>
                        <button type="button" class="amount-btn" data-amount="500000">500,000đ</button>
                        <button type="button" class="amount-btn" data-amount="1000000">1,000,000đ</button>
                        <button type="button" class="amount-btn" data-amount="2000000">2,000,000đ</button>
                    </div>
                    <div class="custom-amount">
                        <input type="number" id="customAmount" placeholder="Hoặc nhập số tiền khác..." min="10000" max="10000000" step="1000">
                        <span>đ</span>
                    </div>
                </div>
                
                <div class="method-section">
                    <label>Phương thức thanh toán</label>
                    <div class="payment-methods">
                        <label class="method-option selected" data-method="momo">
                            <input type="radio" name="payment_method" value="momo" checked>
                            <div class="method-icon momo">
                                <i class="fas fa-wallet"></i>
                            </div>
                            <div class="method-info">
                                <strong>Ví Momo</strong>
                                <span>Thanh toán qua ví điện tử Momo</span>
                            </div>
                            <div class="method-check">
                                <i class="fas fa-check"></i>
                            </div>
                        </label>
                        <label class="method-option" data-method="zalopay">
                            <input type="radio" name="payment_method" value="zalopay">
                            <div class="method-icon zalopay">
                                <i class="fas fa-mobile-alt"></i>
                            </div>
                            <div class="method-info">
                                <strong>ZaloPay</strong>
                                <span>Thanh toán qua ví ZaloPay</span>
                            </div>
                            <div class="method-check">
                                <i class="fas fa-check"></i>
                            </div>
                        </label>
                        <label class="method-option" data-method="bank">
                            <input type="radio" name="payment_method" value="bank">
                            <div class="method-icon bank">
                                <i class="fas fa-university"></i>
                            </div>
                            <div class="method-info">
                                <strong>Chuyển khoản ngân hàng</strong>
                                <span>Chuyển khoản qua tài khoản ngân hàng</span>
                            </div>
                            <div class="method-check">
                                <i class="fas fa-check"></i>
                            </div>
                        </label>
                    </div>
                </div>
                
                <button type="button" class="topup-submit" id="btnCreateTopup" onclick="createTopupRequest()">
                    <i class="fas fa-arrow-right"></i> Tiếp tục
                </button>
            </div>
        </div>
        
        <!-- Step 2: Thông tin thanh toán -->
        <div id="topupStep2" style="display: none;">
            <div class="modal-header">
                <h3><i class="fas fa-qrcode"></i> Thông tin thanh toán</h3>
                <button class="modal-close" onclick="closeTopupModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="payment-info-box" id="paymentInfoBox">
                    <!-- Thông tin thanh toán sẽ được điền bằng JS -->
                </div>
                
                <div class="payment-note">
                    <p><i class="fas fa-info-circle"></i> Vui lòng chuyển khoản đúng số tiền và nội dung để hệ thống tự động cộng tiền vào thẻ của bạn.</p>
                </div>
                
                <button type="button" class="confirm-payment-btn" onclick="confirmPayment()">
                    <i class="fas fa-check-circle"></i> Tôi đã thanh toán
                </button>
                <button type="button" class="cancel-payment-btn" onclick="backToStep1()">
                    <i class="fas fa-arrow-left"></i> Quay lại
                </button>
            </div>
        </div>
        
        <!-- Step 3: Chờ duyệt -->
        <div id="topupStep3" style="display: none;">
            <div class="modal-body">
                <div class="success-animation">
                    <div class="waiting-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3>Yêu cầu đã được gửi!</h3>
                    <p>Vui lòng chờ Admin xác nhận thanh toán</p>
                    <div class="waiting-amount" id="waitingAmountDisplay">0đ</div>
                    <div class="waiting-note">
                        <i class="fas fa-info-circle"></i>
                        <span>Số tiền sẽ được cộng vào thẻ sau khi Admin xác nhận giao dịch của bạn.</span>
                    </div>
                    <button type="button" class="topup-submit" onclick="closeAndReload()">
                        <i class="fas fa-check"></i> Đã hiểu
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let selectedAmount = 0;
let selectedMethod = 'momo';
let currentTransactionCode = '';

// Mở modal nạp tiền
function openTopupModal() {
    document.getElementById('topupModal').classList.add('active');
    document.body.style.overflow = 'hidden';
    resetTopupForm();
}

// Đóng modal
function closeTopupModal() {
    document.getElementById('topupModal').classList.remove('active');
    document.body.style.overflow = '';
}

// Reset form
function resetTopupForm() {
    document.getElementById('topupStep1').style.display = 'block';
    document.getElementById('topupStep2').style.display = 'none';
    document.getElementById('topupStep3').style.display = 'none';
    selectedAmount = 0;
    document.getElementById('customAmount').value = '';
    document.querySelectorAll('.amount-btn').forEach(btn => btn.classList.remove('selected'));
}

// Quay lại step 1
function backToStep1() {
    document.getElementById('topupStep1').style.display = 'block';
    document.getElementById('topupStep2').style.display = 'none';
}

// Chọn số tiền preset
document.querySelectorAll('.amount-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.amount-btn').forEach(b => b.classList.remove('selected'));
        this.classList.add('selected');
        selectedAmount = parseInt(this.dataset.amount);
        document.getElementById('customAmount').value = '';
    });
});

// Nhập số tiền tùy chỉnh
document.getElementById('customAmount').addEventListener('input', function() {
    document.querySelectorAll('.amount-btn').forEach(b => b.classList.remove('selected'));
    selectedAmount = parseInt(this.value) || 0;
});

// Chọn phương thức thanh toán
document.querySelectorAll('.method-option').forEach(option => {
    option.addEventListener('click', function() {
        document.querySelectorAll('.method-option').forEach(o => o.classList.remove('selected'));
        this.classList.add('selected');
        this.querySelector('input').checked = true;
        selectedMethod = this.dataset.method;
    });
});

// Tạo yêu cầu nạp tiền
function createTopupRequest() {
    if (selectedAmount < 10000) {
        alert('Vui lòng chọn số tiền nạp tối thiểu 10,000đ');
        return;
    }
    
    const btn = document.getElementById('btnCreateTopup');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
    
    const formData = new FormData();
    formData.append('action', 'create_topup');
    formData.append('amount', selectedAmount);
    formData.append('method', selectedMethod);
    
    fetch('api/topup-card.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-arrow-right"></i> Tiếp tục';
        
        if (data.success) {
            currentTransactionCode = data.data.transaction_code;
            showPaymentInfo(data.data);
        } else {
            alert(data.message);
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-arrow-right"></i> Tiếp tục';
        alert('Có lỗi xảy ra, vui lòng thử lại!');
    });
}

// Hiển thị thông tin thanh toán
function showPaymentInfo(data) {
    const info = data.payment_info;
    let html = '';
    
    if (data.method === 'momo') {
        html = `
            <h4><i class="fas fa-wallet" style="color:#d82d8b;"></i> Thanh toán qua Momo</h4>
            <div class="payment-detail">
                <span class="label">Số điện thoại</span>
                <span class="value">${info.phone} <button class="copy-btn" onclick="copyText('${info.phone}')">Copy</button></span>
            </div>
            <div class="payment-detail">
                <span class="label">Tên người nhận</span>
                <span class="value">${info.name}</span>
            </div>
            <div class="payment-detail">
                <span class="label">Số tiền</span>
                <span class="value highlight">${formatMoney(data.amount)}đ</span>
            </div>
            <div class="payment-detail">
                <span class="label">Nội dung CK</span>
                <span class="value highlight">${info.content} <button class="copy-btn" onclick="copyText('${info.content}')">Copy</button></span>
            </div>
        `;
    } else if (data.method === 'zalopay') {
        html = `
            <h4><i class="fas fa-mobile-alt" style="color:#0068ff;"></i> Thanh toán qua ZaloPay</h4>
            <div class="payment-detail">
                <span class="label">Số điện thoại</span>
                <span class="value">${info.phone} <button class="copy-btn" onclick="copyText('${info.phone}')">Copy</button></span>
            </div>
            <div class="payment-detail">
                <span class="label">Tên người nhận</span>
                <span class="value">${info.name}</span>
            </div>
            <div class="payment-detail">
                <span class="label">Số tiền</span>
                <span class="value highlight">${formatMoney(data.amount)}đ</span>
            </div>
            <div class="payment-detail">
                <span class="label">Nội dung CK</span>
                <span class="value highlight">${info.content} <button class="copy-btn" onclick="copyText('${info.content}')">Copy</button></span>
            </div>
        `;
    } else {
        html = `
            <h4><i class="fas fa-university" style="color:#059669;"></i> Chuyển khoản ngân hàng</h4>
            <div class="payment-detail">
                <span class="label">Ngân hàng</span>
                <span class="value">${info.bank_name}</span>
            </div>
            <div class="payment-detail">
                <span class="label">Số tài khoản</span>
                <span class="value highlight">${info.account_number} <button class="copy-btn" onclick="copyText('${info.account_number}')">Copy</button></span>
            </div>
            <div class="payment-detail">
                <span class="label">Tên TK</span>
                <span class="value">${info.account_name}</span>
            </div>
            <div class="payment-detail">
                <span class="label">Số tiền</span>
                <span class="value highlight">${formatMoney(data.amount)}đ</span>
            </div>
            <div class="payment-detail">
                <span class="label">Nội dung CK</span>
                <span class="value highlight">${info.content} <button class="copy-btn" onclick="copyText('${info.content}')">Copy</button></span>
            </div>
        `;
    }
    
    document.getElementById('paymentInfoBox').innerHTML = html;
    document.getElementById('topupStep1').style.display = 'none';
    document.getElementById('topupStep2').style.display = 'block';
}

// Xác nhận đã thanh toán
function confirmPayment() {
    const formData = new FormData();
    formData.append('action', 'confirm_topup');
    formData.append('transaction_code', currentTransactionCode);
    
    fetch('api/topup-card.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            document.getElementById('waitingAmountDisplay').textContent = formatMoney(data.data.amount) + 'đ';
            document.getElementById('topupStep2').style.display = 'none';
            document.getElementById('topupStep3').style.display = 'block';
        } else {
            alert(data.message);
        }
    })
    .catch(err => {
        alert('Có lỗi xảy ra, vui lòng thử lại!');
    });
}

// Đóng và reload trang
function closeAndReload() {
    closeTopupModal();
    window.location.reload();
}

// Format tiền
function formatMoney(num) {
    return new Intl.NumberFormat('vi-VN').format(num);
}

// Copy text
function copyText(text) {
    navigator.clipboard.writeText(text).then(() => {
        alert('Đã copy: ' + text);
    });
}
</script>
<?php endif; ?>

<style>
.member-card-page {
    min-height: 100vh;
    padding: 2rem 0 4rem;
    background: linear-gradient(180deg, #faf5ff 0%, #f8fafc 100%);
}

.page-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1.5rem;
}

.page-header {
    text-align: center;
    margin-bottom: 2rem;
}

.page-header h1 {
    font-size: 1.75rem;
    color: #1f2937;
    margin: 0 0 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
}

.page-header h1 i {
    color: #8b5cf6;
}

.page-header p {
    color: #6b7280;
    margin: 0;
}

/* No Card */
.no-card-box {
    background: white;
    border-radius: 20px;
    padding: 3rem 2rem;
    text-align: center;
    border: 2px dashed #d1d5db;
}

.no-card-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 1.5rem;
    background: linear-gradient(135deg, #ede9fe 0%, #ddd6fe 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.no-card-icon i {
    font-size: 2rem;
    color: #8b5cf6;
}

.no-card-box h3 {
    color: #1f2937;
    margin: 0 0 0.5rem;
}

.no-card-box p {
    color: #6b7280;
    margin: 0 0 1.5rem;
}

.btn-contact {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.85rem 2rem;
    background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
    color: white;
    border-radius: 10px;
    font-weight: 600;
    text-decoration: none;
}

/* Card Display */
.card-display {
    margin-bottom: 2rem;
}

/* Modern Card Wrapper */
.modern-card-wrapper {
    position: relative;
    perspective: 1000px;
    margin-bottom: 2rem;
}

.modern-card {
    position: relative;
    width: 100%;
    max-width: 480px;
    margin: 0 auto;
    aspect-ratio: 1.586;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
    border-radius: 24px;
    padding: 1.75rem 2rem;
    color: white;
    overflow: hidden;
    box-shadow: 
        0 25px 50px -12px rgba(102, 126, 234, 0.5),
        0 0 0 1px rgba(255, 255, 255, 0.1) inset;
    transform-style: preserve-3d;
    transition: transform 0.6s cubic-bezier(0.23, 1, 0.32, 1);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.modern-card:hover {
    transform: rotateY(-5deg) rotateX(5deg) translateZ(10px);
}

.modern-card.blocked {
    background: linear-gradient(135deg, #4a5568 0%, #2d3748 50%, #1a202c 100%);
    box-shadow: 
        0 25px 50px -12px rgba(45, 55, 72, 0.5),
        0 0 0 1px rgba(255, 255, 255, 0.05) inset;
}

/* Card Background Effects */
.card-bg-effects {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    pointer-events: none;
    overflow: hidden;
    border-radius: 24px;
}

.card-glow {
    position: absolute;
    top: -50%;
    right: -50%;
    width: 100%;
    height: 100%;
    background: radial-gradient(circle, rgba(255,255,255,0.3) 0%, transparent 60%);
    animation: glowPulse 4s ease-in-out infinite;
}

@keyframes glowPulse {
    0%, 100% { opacity: 0.5; transform: scale(1); }
    50% { opacity: 0.8; transform: scale(1.1); }
}

.card-pattern {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-image: 
        radial-gradient(circle at 20% 80%, rgba(255,255,255,0.1) 0%, transparent 50%),
        radial-gradient(circle at 80% 20%, rgba(255,255,255,0.08) 0%, transparent 50%),
        radial-gradient(circle at 40% 40%, rgba(255,255,255,0.05) 0%, transparent 30%);
}

.card-shine {
    position: absolute;
    top: 0;
    left: -100%;
    width: 50%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transform: skewX(-25deg);
    animation: cardShine 6s ease-in-out infinite;
}

@keyframes cardShine {
    0%, 100% { left: -100%; }
    50% { left: 150%; }
}

/* Card Header */
.card-header-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    position: relative;
    z-index: 2;
}

.card-brand {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.brand-icon {
    width: 44px;
    height: 44px;
    background: rgba(255,255,255,0.2);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    border: 1px solid rgba(255,255,255,0.3);
}

.brand-text {
    display: flex;
    flex-direction: column;
}

.brand-name {
    font-weight: 800;
    font-size: 1.1rem;
    letter-spacing: 0.5px;
    text-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.brand-type {
    font-size: 0.7rem;
    opacity: 0.8;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.card-status-badge {
    position: relative;
    z-index: 2;
}

.card-status-badge .status-active,
.card-status-badge .status-blocked {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.4rem 0.85rem;
    border-radius: 20px;
    backdrop-filter: blur(10px);
}

.card-status-badge .status-active {
    background: rgba(34, 197, 94, 0.3);
    border: 1px solid rgba(34, 197, 94, 0.5);
}

.card-status-badge .status-blocked {
    background: rgba(239, 68, 68, 0.3);
    border: 1px solid rgba(239, 68, 68, 0.5);
}

/* Card Chip */
.card-chip {
    width: 50px;
    height: 38px;
    background: linear-gradient(135deg, #ffd700 0%, #ffb347 50%, #ffd700 100%);
    border-radius: 8px;
    position: relative;
    z-index: 2;
    box-shadow: 
        0 2px 8px rgba(0,0,0,0.2),
        inset 0 1px 0 rgba(255,255,255,0.5);
    overflow: hidden;
}

.chip-lines {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 70%;
    height: 60%;
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    grid-template-rows: repeat(2, 1fr);
    gap: 2px;
}

.chip-lines span {
    background: linear-gradient(135deg, #b8860b 0%, #daa520 100%);
    border-radius: 2px;
}

/* Card Number */
.card-number-section {
    position: relative;
    z-index: 2;
}

.card-number-display {
    font-family: 'Courier New', 'SF Mono', monospace;
    font-size: 1.6rem;
    font-weight: 600;
    letter-spacing: 4px;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
    word-spacing: 8px;
}

/* Card Balance */
.card-balance-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    position: relative;
    z-index: 2;
}

.balance-info {
    display: flex;
    flex-direction: column;
}

.balance-info .balance-label {
    font-size: 0.65rem;
    opacity: 0.7;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    margin-bottom: 0.25rem;
}

.balance-info .balance-value {
    font-size: 2.2rem;
    font-weight: 800;
    line-height: 1;
    text-shadow: 0 2px 8px rgba(0,0,0,0.3);
}

.balance-info .balance-value small {
    font-size: 1rem;
    font-weight: 600;
    margin-left: 2px;
}

.card-contactless {
    width: 36px;
    height: 36px;
    background: rgba(255,255,255,0.15);
    backdrop-filter: blur(10px);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transform: rotate(90deg);
    border: 1px solid rgba(255,255,255,0.2);
}

.card-contactless i {
    font-size: 1rem;
    opacity: 0.9;
}

/* Card Footer */
.card-footer-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    position: relative;
    z-index: 2;
}

.card-holder,
.card-valid {
    display: flex;
    flex-direction: column;
}

.holder-label,
.valid-label {
    font-size: 0.6rem;
    opacity: 0.6;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 0.2rem;
}

.holder-name {
    font-size: 0.85rem;
    font-weight: 700;
    letter-spacing: 1px;
    text-shadow: 0 1px 2px rgba(0,0,0,0.2);
}

.valid-date {
    font-size: 0.9rem;
    font-weight: 600;
    letter-spacing: 1px;
}

/* Card Shadow */
.card-shadow {
    position: absolute;
    bottom: -15px;
    left: 50%;
    transform: translateX(-50%);
    width: 80%;
    height: 20px;
    background: radial-gradient(ellipse at center, rgba(102, 126, 234, 0.35) 0%, transparent 70%);
    filter: blur(10px);
}

/* Modern Stats - Compact & Sharp */
.modern-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.75rem;
    margin-top: 0.75rem;
    max-width: 480px;
    margin-left: auto;
    margin-right: auto;
}

.stat-card {
    background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
    border-radius: 12px;
    padding: 0.875rem 1rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 0.5rem;
    border: 1px solid rgba(0,0,0,0.04);
    box-shadow: 
        0 1px 3px rgba(0, 0, 0, 0.04),
        0 1px 2px rgba(0, 0, 0, 0.02);
    transition: all 0.25s ease;
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    border-radius: 12px 12px 0 0;
}

.stat-deposit::before {
    background: linear-gradient(90deg, #10b981, #34d399);
}

.stat-spent::before {
    background: linear-gradient(90deg, #f59e0b, #fbbf24);
}

.stat-date::before {
    background: linear-gradient(90deg, #8b5cf6, #a78bfa);
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 
        0 8px 16px -4px rgba(0, 0, 0, 0.08),
        0 4px 6px -2px rgba(0, 0, 0, 0.04);
}

.stat-icon-wrap {
    position: relative;
}

.stat-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    position: relative;
    z-index: 1;
}

.stat-icon-bg {
    display: none;
}

.stat-deposit .stat-icon {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    box-shadow: 0 4px 12px -2px rgba(16, 185, 129, 0.4);
}

.stat-spent .stat-icon {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: white;
    box-shadow: 0 4px 12px -2px rgba(245, 158, 11, 0.4);
}

.stat-date .stat-icon {
    background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
    color: white;
    box-shadow: 0 4px 12px -2px rgba(139, 92, 246, 0.4);
}

.stat-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    min-width: 0;
    width: 100%;
}

.stat-card .stat-value {
    font-weight: 700;
    color: #1e293b;
    font-size: 0.95rem;
    line-height: 1.3;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100%;
}

.stat-card .stat-value small {
    font-size: 0.7rem;
    font-weight: 600;
    color: #64748b;
}

.stat-card .stat-label {
    font-size: 0.65rem;
    color: #94a3b8;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-top: 0.1rem;
}

/* Two Column Layout */
.two-column-layout {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.column-left,
.column-right {
    min-width: 0;
}

/* No pending state */
.no-pending {
    text-align: center;
    padding: 2rem;
    color: #6b7280;
}

.no-pending i {
    font-size: 2rem;
    margin-bottom: 0.5rem;
    color: #22c55e;
    opacity: 0.6;
}

.no-pending p {
    margin: 0;
}

/* Pending Topups Section */
.pending-topups-section {
    background: white;
    border-radius: 16px;
    padding: 1.5rem;
    border: 1px solid #e5e7eb;
    height: 100%;
}

.pending-topups-section h2 {
    font-size: 1.1rem;
    color: #1f2937;
    margin: 0 0 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.pending-topups-section h2 i {
    color: #f59e0b;
}

.pending-topups-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    max-height: 280px;
    overflow-y: auto;
    padding-right: 0.5rem;
}

.pending-topups-list::-webkit-scrollbar {
    width: 6px;
}

.pending-topups-list::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.pending-topups-list::-webkit-scrollbar-thumb {
    background: #fcd34d;
    border-radius: 3px;
}

.pending-topups-list::-webkit-scrollbar-thumb:hover {
    background: #f59e0b;
}

.pending-topup-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1rem;
    background: #f9fafb;
    border-radius: 12px;
    border-left: 4px solid #f59e0b;
}

.pending-topup-item.status-failed {
    border-left-color: #ef4444;
    background: #fef2f2;
}

.pending-topup-item.status-pending {
    border-left-color: #6b7280;
}

.topup-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    background: #fef3c7;
    color: #f59e0b;
}

.status-waiting .topup-icon {
    background: #fef3c7;
    color: #d97706;
}

.status-failed .topup-icon {
    background: #fee2e2;
    color: #ef4444;
}

.status-pending .topup-icon {
    background: #e5e7eb;
    color: #6b7280;
}

.topup-info {
    flex: 1;
    min-width: 0;
}

.topup-amount {
    font-weight: 700;
    color: #1f2937;
    font-size: 1.1rem;
}

.topup-method {
    font-size: 0.8rem;
    color: #6b7280;
    margin-top: 0.25rem;
    word-break: break-all;
}

.topup-date {
    font-size: 0.75rem;
    color: #9ca3af;
    margin-top: 0.25rem;
}

.topup-reason {
    font-size: 0.8rem;
    color: #ef4444;
    margin-top: 0.5rem;
    padding: 0.5rem;
    background: #fee2e2;
    border-radius: 6px;
}

.topup-status {
    flex-shrink: 0;
}

.badge-waiting {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.3rem 0.75rem;
    background: #fef3c7;
    color: #92400e;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.badge-failed {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.3rem 0.75rem;
    background: #fee2e2;
    color: #dc2626;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.badge-pending {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.3rem 0.75rem;
    background: #e5e7eb;
    color: #6b7280;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

/* Transactions */
.transactions-section {
    background: white;
    border-radius: 16px;
    padding: 1.5rem;
    border: 1px solid #e5e7eb;
    height: 100%;
}

.transactions-section h2 {
    font-size: 1.1rem;
    color: #1f2937;
    margin: 0 0 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.transactions-section h2 i {
    color: #8b5cf6;
}

.no-transactions {
    text-align: center;
    padding: 2rem;
    color: #6b7280;
}

.no-transactions i {
    font-size: 2rem;
    margin-bottom: 0.5rem;
    opacity: 0.5;
}

.transactions-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    max-height: 400px;
    overflow-y: auto;
    padding-right: 0.5rem;
}

.transactions-list::-webkit-scrollbar {
    width: 6px;
}

.transactions-list::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.transactions-list::-webkit-scrollbar-thumb {
    background: #c4b5fd;
    border-radius: 3px;
}

.transactions-list::-webkit-scrollbar-thumb:hover {
    background: #8b5cf6;
}

.transaction-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: #f9fafb;
    border-radius: 12px;
}

.tx-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.transaction-item.deposit .tx-icon {
    background: #dcfce7;
    color: #16a34a;
}

.transaction-item.payment .tx-icon {
    background: #fee2e2;
    color: #dc2626;
}

.transaction-item.refund .tx-icon {
    background: #dbeafe;
    color: #2563eb;
}

.tx-info {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.tx-desc {
    font-weight: 500;
    color: #1f2937;
    font-size: 0.9rem;
}

.tx-date {
    font-size: 0.75rem;
    color: #6b7280;
}

.tx-amount {
    font-weight: 700;
    font-size: 1rem;
}

.tx-amount.positive {
    color: #16a34a;
}

.tx-amount.negative {
    color: #dc2626;
}

/* Topup Section */
.topup-section {
    margin-bottom: 2rem;
    text-align: center;
}

.btn-topup {
    display: inline-flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 2.5rem;
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    color: white;
    border: none;
    border-radius: 14px;
    font-size: 1.1rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(34, 197, 94, 0.3);
}

.btn-topup:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(34, 197, 94, 0.4);
}

.btn-topup i {
    font-size: 1.2rem;
}

/* Modal Styles */
.topup-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 9999;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}

.topup-modal.active {
    display: flex;
}

.modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
}

.modal-content {
    position: relative;
    background: white;
    border-radius: 20px;
    max-width: 480px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    animation: modalSlideIn 0.3s ease;
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: translateY(-30px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.modal-header {
    padding: 1.5rem;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
    font-size: 1.25rem;
    color: #1f2937;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.modal-header h3 i {
    color: #22c55e;
}

.modal-close {
    width: 36px;
    height: 36px;
    border: none;
    background: #f3f4f6;
    border-radius: 10px;
    font-size: 1.25rem;
    cursor: pointer;
    color: #6b7280;
    transition: all 0.2s;
}

.modal-close:hover {
    background: #e5e7eb;
    color: #1f2937;
}

.modal-body {
    padding: 1.5rem;
}

/* Amount Selection */
.amount-section {
    margin-bottom: 1.5rem;
}

.amount-section label {
    display: block;
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.75rem;
}

.amount-presets {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.amount-btn {
    padding: 0.85rem;
    border: 2px solid #e5e7eb;
    background: white;
    border-radius: 12px;
    font-size: 0.95rem;
    font-weight: 600;
    color: #374151;
    cursor: pointer;
    transition: all 0.2s;
}

.amount-btn:hover {
    border-color: #8b5cf6;
    background: #faf5ff;
}

.amount-btn.selected {
    border-color: #8b5cf6;
    background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
    color: white;
}

.custom-amount {
    position: relative;
}

.custom-amount input {
    width: 100%;
    padding: 1rem 1.25rem;
    padding-right: 3rem;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    font-size: 1.1rem;
    font-weight: 600;
    transition: all 0.2s;
}

.custom-amount input:focus {
    outline: none;
    border-color: #8b5cf6;
    box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
}

.custom-amount span {
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #6b7280;
    font-weight: 600;
}

/* Payment Methods */
.method-section {
    margin-bottom: 1.5rem;
}

.method-section label {
    display: block;
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.75rem;
}

.payment-methods {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.method-option {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem 1.25rem;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s;
}

.method-option:hover {
    border-color: #d1d5db;
    background: #f9fafb;
}

.method-option.selected {
    border-color: #8b5cf6;
    background: #faf5ff;
}

.method-option input {
    display: none;
}

.method-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    color: white;
}

.method-icon.momo {
    background: linear-gradient(135deg, #d82d8b 0%, #a50064 100%);
}

.method-icon.zalopay {
    background: linear-gradient(135deg, #0068ff 0%, #004bb5 100%);
}

.method-icon.bank {
    background: linear-gradient(135deg, #059669 0%, #047857 100%);
}

.method-info {
    flex: 1;
}

.method-info strong {
    display: block;
    color: #1f2937;
    font-size: 1rem;
}

.method-info span {
    font-size: 0.85rem;
    color: #6b7280;
}

.method-check {
    width: 24px;
    height: 24px;
    border: 2px solid #d1d5db;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.method-option.selected .method-check {
    border-color: #8b5cf6;
    background: #8b5cf6;
    color: white;
}

/* Submit Button */
.topup-submit {
    width: 100%;
    padding: 1rem;
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 1.1rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.topup-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(34, 197, 94, 0.4);
}

.topup-submit:disabled {
    background: #d1d5db;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

/* Payment Info Modal */
.payment-info-box {
    background: #f9fafb;
    border-radius: 16px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

.payment-info-box h4 {
    margin: 0 0 1rem;
    color: #1f2937;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.payment-detail {
    display: flex;
    justify-content: space-between;
    padding: 0.75rem 0;
    border-bottom: 1px dashed #e5e7eb;
}

.payment-detail:last-child {
    border-bottom: none;
}

.payment-detail .label {
    color: #6b7280;
}

.payment-detail .value {
    font-weight: 600;
    color: #1f2937;
}

.payment-detail .value.highlight {
    color: #8b5cf6;
    font-family: monospace;
    font-size: 1.1rem;
}

.copy-btn {
    background: #8b5cf6;
    color: white;
    border: none;
    padding: 0.3rem 0.75rem;
    border-radius: 6px;
    font-size: 0.8rem;
    cursor: pointer;
    margin-left: 0.5rem;
}

.copy-btn:hover {
    background: #7c3aed;
}

.payment-note {
    background: #fef3c7;
    border: 1px solid #fcd34d;
    border-radius: 10px;
    padding: 1rem;
    margin-bottom: 1.5rem;
}

.payment-note p {
    margin: 0;
    color: #92400e;
    font-size: 0.9rem;
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
}

.payment-note i {
    color: #f59e0b;
    margin-top: 2px;
}

.confirm-payment-btn {
    width: 100%;
    padding: 1rem;
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
    margin-bottom: 0.75rem;
}

.cancel-payment-btn {
    width: 100%;
    padding: 0.85rem;
    background: #f3f4f6;
    color: #6b7280;
    border: none;
    border-radius: 12px;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
}

/* Success Animation */
.success-animation {
    text-align: center;
    padding: 2rem;
}

.success-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.5rem;
    animation: successPop 0.5s ease;
}

@keyframes successPop {
    0% { transform: scale(0); }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); }
}

.success-icon i {
    font-size: 2.5rem;
    color: white;
}

.success-animation h3 {
    color: #1f2937;
    margin: 0 0 0.5rem;
}

.success-animation p {
    color: #6b7280;
    margin: 0 0 1.5rem;
}

.new-balance {
    font-size: 2rem;
    font-weight: 800;
    color: #22c55e;
    margin-bottom: 1.5rem;
}

/* Waiting Icon */
.waiting-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.5rem;
    animation: successPop 0.5s ease;
}

.waiting-icon i {
    font-size: 2.5rem;
    color: white;
}

.waiting-amount {
    font-size: 2rem;
    font-weight: 800;
    color: #f59e0b;
    margin-bottom: 1rem;
}

.waiting-note {
    background: #fef3c7;
    border: 1px solid #fcd34d;
    border-radius: 10px;
    padding: 1rem;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    text-align: left;
}

.waiting-note i {
    color: #f59e0b;
    margin-top: 2px;
}

.waiting-note span {
    color: #92400e;
    font-size: 0.9rem;
    line-height: 1.5;
}

@media (max-width: 900px) {
    .two-column-layout {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 600px) {
    .modern-stats {
        grid-template-columns: repeat(3, 1fr);
        gap: 0.5rem;
    }
    
    .stat-card {
        padding: 0.65rem 0.5rem;
        border-radius: 10px;
    }
    
    .stat-icon {
        width: 30px;
        height: 30px;
        font-size: 0.75rem;
        border-radius: 8px;
    }
    
    .stat-card .stat-value {
        font-size: 0.8rem;
    }
    
    .stat-card .stat-label {
        font-size: 0.55rem;
    }
    
    .modern-card {
        padding: 1.25rem 1.5rem;
        border-radius: 18px;
    }
    
    .card-number-display {
        font-size: 1.2rem;
        letter-spacing: 2px;
    }
    
    .balance-info .balance-value {
        font-size: 1.75rem;
    }
    
    .brand-icon {
        width: 36px;
        height: 36px;
        font-size: 1rem;
    }
    
    .brand-name {
        font-size: 0.95rem;
    }
    
    .card-chip {
        width: 42px;
        height: 32px;
    }
    
    .holder-name {
        font-size: 0.75rem;
    }
    
    .amount-presets {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .pending-topup-item {
        flex-direction: column;
        align-items: stretch;
    }
    
    .topup-status {
        margin-top: 0.5rem;
    }
}
</style>
