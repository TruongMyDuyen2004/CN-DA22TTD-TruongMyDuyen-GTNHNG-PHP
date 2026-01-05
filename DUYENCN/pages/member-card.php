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
                <div class="pending-topups-section collapsible-section">
                    <h2 class="section-toggle" onclick="toggleSection('pendingList')">
                        <i class="fas fa-clock"></i> Yêu cầu nạp tiền
                        <i class="fas fa-chevron-down toggle-icon"></i>
                    </h2>
                    <div id="pendingList" class="section-content" style="display: none;">
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
            </div>
            
            <!-- Cột phải: Lịch sử giao dịch -->
            <div class="column-right">
                <div class="transactions-section collapsible-section">
                    <h2 class="section-toggle" onclick="toggleSection('transactionsList')">
                        <i class="fas fa-history"></i> Lịch sử giao dịch gần đây
                        <i class="fas fa-chevron-down toggle-icon"></i>
                    </h2>
                    <div id="transactionsList" class="section-content" style="display: none;">
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
                        <label class="method-option selected" data-method="bank">
                            <input type="radio" name="payment_method" value="bank" checked>
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
                    <p><i class="fas fa-info-circle"></i> Vui lòng chuyển khoản đúng số tiền và nội dung. Hệ thống sẽ <strong>tự động xác nhận</strong> sau khi nhận được tiền.</p>
                </div>
                
                <div class="auto-check-status" id="autoCheckStatus" style="display:none;">
                    <div class="checking-animation">
                        <i class="fas fa-sync fa-spin"></i>
                        <span>Đang chờ giao dịch từ ngân hàng...</span>
                    </div>
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
                    <div class="step3-buttons">
                        <button type="button" class="check-history-btn" onclick="checkTopupHistory()">
                            <i class="fas fa-history"></i> Kiểm tra lịch sử nạp tiền
                        </button>
                        <button type="button" class="topup-submit" onclick="closeAndReload()">
                            <i class="fas fa-check"></i> Đã hiểu
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Step 4: Lịch sử nạp tiền -->
        <div id="topupStep4" style="display: none;">
            <div class="modal-header">
                <h3><i class="fas fa-history"></i> Lịch sử nạp tiền</h3>
                <button class="modal-close" onclick="closeTopupModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div id="topupHistoryList" class="topup-history-list">
                    <div class="loading-spinner">
                        <i class="fas fa-spinner fa-spin"></i> Đang tải...
                    </div>
                </div>
                <button type="button" class="cancel-payment-btn" onclick="backToStep3()">
                    <i class="fas fa-arrow-left"></i> Quay lại
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let selectedAmount = 0;
let selectedMethod = 'bank';
let currentTransactionCode = '';

// Toggle section (ẩn/hiện yêu cầu nạp tiền và lịch sử giao dịch)
function toggleSection(sectionId) {
    const content = document.getElementById(sectionId);
    const toggle = content.previousElementSibling;
    
    if (content.style.display === 'none') {
        content.style.display = 'block';
        toggle.classList.add('active');
    } else {
        content.style.display = 'none';
        toggle.classList.remove('active');
    }
}

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
    stopAutoCheckTransaction(); // Dừng auto-check khi đóng modal
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
    stopAutoCheckTransaction(); // Dừng auto-check khi quay lại
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
    .then(res => {
        console.log('Response status:', res.status);
        return res.json();
    })
    .then(data => {
        console.log('API Response:', data);
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-arrow-right"></i> Tiếp tục';
        
        if (data.success) {
            currentTransactionCode = data.data.transaction_code;
            console.log('Payment info:', data.data.payment_info);
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
    
    html = `
        <div class="qr-section">
            <img src="${info.qr_url}" alt="QR Code" class="qr-code-img">
            <p class="qr-hint">Quét mã QR để thanh toán</p>
        </div>
        <div class="bank-info-section">
            <h4><i class="fas fa-university" style="color:#1a4d8f;"></i> ${info.bank_name}</h4>
            <div class="payment-detail">
                <span class="label">Số tài khoản</span>
                <span class="value highlight">${info.account_number} <button class="copy-btn" onclick="copyText('${info.account_number}')">Copy</button></span>
            </div>
            <div class="payment-detail">
                <span class="label">Chủ tài khoản</span>
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
        </div>
    `;
    
    document.getElementById('paymentInfoBox').innerHTML = html;
    document.getElementById('topupStep1').style.display = 'none';
    document.getElementById('topupStep2').style.display = 'block';
    
    // Không cần auto-check SePay vì sẽ tự động cộng tiền khi user bấm xác nhận
    // startAutoCheckTransaction();
}

// Biến để quản lý auto-check
let autoCheckInterval = null;
let autoCheckCount = 0;
const MAX_AUTO_CHECK = 60; // Kiểm tra tối đa 60 lần (5 phút)

// Bắt đầu tự động kiểm tra giao dịch
function startAutoCheckTransaction() {
    document.getElementById('autoCheckStatus').style.display = 'block';
    autoCheckCount = 0;
    
    // Kiểm tra mỗi 5 giây
    autoCheckInterval = setInterval(function() {
        autoCheckCount++;
        
        if (autoCheckCount > MAX_AUTO_CHECK) {
            stopAutoCheckTransaction();
            return;
        }
        
        // Gọi API kiểm tra giao dịch từ SePay và tự động cộng tiền
        fetch('api/check-sepay-transaction.php?transaction_code=' + currentTransactionCode + '&amount=' + selectedAmount + '&auto_confirm=1')
        .then(res => res.json())
        .then(data => {
            if (data.success && data.confirmed) {
                // Tìm thấy và đã cộng tiền thành công
                stopAutoCheckTransaction();
                showTopupSuccess(data.amount, data.new_balance);
            } else if (data.already_completed) {
                // Đã xác nhận trước đó
                stopAutoCheckTransaction();
                showTopupSuccess(data.amount, data.amount);
            }
        })
        .catch(err => {
            console.log('Auto check error:', err);
        });
        
    }, 5000); // 5 giây
}

// Dừng auto-check
function stopAutoCheckTransaction() {
    if (autoCheckInterval) {
        clearInterval(autoCheckInterval);
        autoCheckInterval = null;
    }
    document.getElementById('autoCheckStatus').style.display = 'none';
}

// Tự động xác nhận khi tìm thấy giao dịch
function autoConfirmTopup() {
    // Gọi API check-sepay để kiểm tra và tự động cộng tiền
    fetch('api/check-sepay-transaction.php?transaction_code=' + currentTransactionCode + '&amount=' + selectedAmount + '&auto_confirm=1')
    .then(res => res.json())
    .then(data => {
        if (data.success && data.confirmed) {
            stopAutoCheckTransaction();
            showTopupSuccess(data.amount, data.new_balance);
        }
    });
}

// Hiển thị thông báo nạp tiền thành công
function showTopupSuccess(amount, newBalance) {
    document.getElementById('waitingAmountDisplay').textContent = formatMoney(amount) + 'đ';
    document.getElementById('topupStep2').style.display = 'none';
    document.getElementById('topupStep3').style.display = 'block';
    
    // Hiển thị thông báo thành công
    document.querySelector('#topupStep3 .waiting-icon').innerHTML = '<i class="fas fa-check-circle" style="color: #22c55e;"></i>';
    document.querySelector('#topupStep3 h3').textContent = 'Nạp tiền thành công!';
    document.querySelector('#topupStep3 p').textContent = 'Số dư mới: ' + formatMoney(newBalance) + 'đ';
    document.querySelector('#topupStep3 .waiting-note span').textContent = 'Bạn có thể sử dụng số dư để thanh toán ngay bây giờ.';
}

// Xác nhận đã thanh toán - Gửi yêu cầu chờ Admin duyệt
function confirmPayment() {
    const btn = document.querySelector('.confirm-payment-btn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang gửi yêu cầu...';
    
    // Gọi API confirm_topup để chuyển sang trạng thái chờ duyệt
    const formData = new FormData();
    formData.append('action', 'confirm_topup');
    formData.append('transaction_code', currentTransactionCode);
    
    fetch('api/topup-card.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check-circle"></i> Tôi đã thanh toán';
        
        if (data.success) {
            stopAutoCheckTransaction();
            
            if (data.data?.status === 'completed') {
                // Đã được duyệt trước đó
                showTopupSuccess(data.data.amount, data.data.new_balance);
            } else {
                // Chuyển sang trạng thái chờ duyệt
                showWaitingForApproval(data.data?.amount || selectedAmount);
            }
        } else {
            alert(data.message || 'Có lỗi xảy ra, vui lòng thử lại!');
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check-circle"></i> Tôi đã thanh toán';
        console.error('Error:', err);
        alert('Không thể kết nối server. Vui lòng thử lại!');
    });
}

// Hiển thị màn hình chờ Admin duyệt
function showWaitingForApproval(amount) {
    document.getElementById('waitingAmountDisplay').textContent = formatMoney(amount) + 'đ';
    document.getElementById('topupStep2').style.display = 'none';
    document.getElementById('topupStep3').style.display = 'block';
    
    // Hiển thị thông báo chờ duyệt
    document.querySelector('#topupStep3 .waiting-icon').innerHTML = '<i class="fas fa-clock" style="color: #f59e0b;"></i>';
    document.querySelector('#topupStep3 h3').textContent = 'Yêu cầu đã được gửi!';
    document.querySelector('#topupStep3 p').textContent = 'Vui lòng chờ Admin xác nhận giao dịch';
    document.querySelector('#topupStep3 .waiting-note span').textContent = 'Số tiền sẽ được cộng vào thẻ sau khi Admin xác nhận giao dịch chuyển khoản của bạn.';
}

// Đóng và reload trang
function closeAndReload() {
    closeTopupModal();
    window.location.reload();
}

// Quay lại step 3
function backToStep3() {
    document.getElementById('topupStep4').style.display = 'none';
    document.getElementById('topupStep3').style.display = 'block';
}

// Kiểm tra lịch sử nạp tiền
function checkTopupHistory() {
    document.getElementById('topupStep3').style.display = 'none';
    document.getElementById('topupStep4').style.display = 'block';
    
    // Gọi API lấy lịch sử
    fetch('api/topup-card.php?action=get_history')
    .then(res => res.json())
    .then(data => {
        if (data.success && data.data.length > 0) {
            let html = '';
            data.data.forEach(item => {
                const statusClass = getStatusClass(item.status);
                const statusText = getStatusText(item.status);
                const date = new Date(item.created_at);
                const formattedDate = date.toLocaleDateString('vi-VN') + ' ' + date.toLocaleTimeString('vi-VN', {hour: '2-digit', minute: '2-digit'});
                
                html += `
                    <div class="history-item ${statusClass}">
                        <div class="history-icon">
                            ${getStatusIcon(item.status)}
                        </div>
                        <div class="history-info">
                            <div class="history-amount">${formatMoney(item.amount)}đ</div>
                            <div class="history-code">${item.transaction_code}</div>
                            <div class="history-date">${formattedDate}</div>
                        </div>
                        <div class="history-status">
                            <span class="status-badge ${statusClass}">${statusText}</span>
                        </div>
                    </div>
                `;
            });
            document.getElementById('topupHistoryList').innerHTML = html;
        } else {
            document.getElementById('topupHistoryList').innerHTML = `
                <div class="no-history">
                    <i class="fas fa-inbox"></i>
                    <p>Chưa có lịch sử nạp tiền</p>
                </div>
            `;
        }
    })
    .catch(err => {
        document.getElementById('topupHistoryList').innerHTML = `
            <div class="no-history">
                <i class="fas fa-exclamation-circle"></i>
                <p>Không thể tải lịch sử</p>
            </div>
        `;
    });
}

function getStatusClass(status) {
    switch(status) {
        case 'completed': return 'status-completed';
        case 'waiting': return 'status-waiting';
        case 'pending': return 'status-pending';
        case 'failed': return 'status-failed';
        case 'expired': return 'status-expired';
        default: return '';
    }
}

function getStatusText(status) {
    switch(status) {
        case 'completed': return 'Thành công';
        case 'waiting': return 'Chờ duyệt';
        case 'pending': return 'Đang xử lý';
        case 'failed': return 'Từ chối';
        case 'expired': return 'Hết hạn';
        default: return status;
    }
}

function getStatusIcon(status) {
    switch(status) {
        case 'completed': return '<i class="fas fa-check-circle"></i>';
        case 'waiting': return '<i class="fas fa-clock"></i>';
        case 'pending': return '<i class="fas fa-hourglass-half"></i>';
        case 'failed': return '<i class="fas fa-times-circle"></i>';
        case 'expired': return '<i class="fas fa-calendar-times"></i>';
        default: return '<i class="fas fa-question-circle"></i>';
    }
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
    align-items: start;
}

.column-left,
.column-right {
    min-width: 0;
}

.column-left .collapsible-section,
.column-right .collapsible-section {
    height: 100%;
    display: flex;
    flex-direction: column;
}

.column-left .section-content,
.column-right .section-content {
    flex: 1;
    max-height: 350px;
    overflow-y: auto;
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

/* Collapsible Section Styles - Modern Design */
.collapsible-section {
    background: white;
    border-radius: 20px;
    border: none;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
}

.collapsible-section:hover {
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
    transform: translateY(-2px);
}

.section-toggle {
    font-size: 1rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0;
    padding: 1.25rem 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    cursor: pointer;
    user-select: none;
    transition: all 0.3s ease;
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    border-bottom: none;
    position: relative;
}

.section-toggle::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    background: linear-gradient(180deg, #f59e0b 0%, #f97316 100%);
    border-radius: 0 4px 4px 0;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.pending-topups-section .section-toggle::before {
    background: linear-gradient(180deg, #f59e0b 0%, #f97316 100%);
}

.transactions-section .section-toggle::before {
    background: linear-gradient(180deg, #6366f1 0%, #8b5cf6 100%);
}

.section-toggle:hover::before,
.section-toggle.active::before {
    opacity: 1;
}

.section-toggle:hover {
    background: linear-gradient(135deg, #fefefe 0%, #f1f5f9 100%);
    padding-left: 1.75rem;
}

.section-toggle > i:first-child {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    font-size: 1.1rem;
    transition: all 0.3s ease;
}

.pending-topups-section .section-toggle > i:first-child {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    color: #d97706;
}

.transactions-section .section-toggle > i:first-child {
    background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
    color: #6366f1;
}

.section-toggle .toggle-icon {
    margin-left: auto;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: #f3f4f6;
    transition: all 0.3s ease;
    color: #9ca3af;
    font-size: 0.8rem;
}

.section-toggle:hover .toggle-icon {
    background: #e5e7eb;
    color: #6b7280;
}

.section-toggle.active .toggle-icon {
    transform: rotate(180deg);
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    color: white;
}

.section-content {
    padding: 1.5rem;
    background: #fafbfc;
    animation: slideDown 0.3s ease;
    border-top: 1px solid #f1f5f9;
    min-height: 300px;
    max-height: 350px;
    overflow-y: auto;
}

@keyframes slideDown {
    from {
        opacity: 0;
        max-height: 0;
    }
    to {
        opacity: 1;
        max-height: 500px;
    }
}

/* Badge count for pending items */
.section-toggle .badge-count {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
    font-size: 0.7rem;
    font-weight: 700;
    padding: 0.2rem 0.5rem;
    border-radius: 20px;
    min-width: 20px;
    text-align: center;
    margin-left: 0.5rem;
    box-shadow: 0 2px 8px rgba(239, 68, 68, 0.4);
}

/* Pending Topups Section */
.pending-topups-section {
    background: white;
    border-radius: 20px;
    border: none;
    height: fit-content;
}

.pending-topups-section h2 {
    font-size: 1rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.pending-topups-section h2 i:first-child {
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
    border-radius: 20px;
    border: none;
    height: fit-content;
}

.transactions-section h2 {
    font-size: 1rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0;
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

/* QR Code Section */
.qr-section {
    text-align: center;
    padding: 1.5rem;
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    border-radius: 16px;
    margin-bottom: 1.5rem;
    border: 2px solid #e2e8f0;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
}

.qr-code-img {
    max-width: 200px;
    width: 100%;
    height: auto;
    border-radius: 12px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    border: 3px solid #22c55e;
    padding: 8px;
    background: white;
}

.qr-hint {
    margin: 1rem 0 0;
    color: #374151;
    font-size: 0.95rem;
    font-weight: 600;
}

.bank-info-section {
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
    border-radius: 16px;
    padding: 1.5rem;
    border: 2px solid #22c55e;
    box-shadow: 0 4px 15px rgba(34, 197, 94, 0.15);
}

.bank-info-section h4 {
    margin: 0 0 1.25rem;
    color: #166534;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 1.2rem;
    font-weight: 700;
    padding-bottom: 0.75rem;
    border-bottom: 2px solid #bbf7d0;
}

.bank-info-section h4 i {
    font-size: 1.3rem;
    color: #1a4d8f;
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
    align-items: center;
    padding: 1rem;
    margin-bottom: 0.5rem;
    background: white;
    border-radius: 10px;
    border: 1px solid #e5e7eb;
}

.payment-detail:last-child {
    margin-bottom: 0;
}

.payment-detail .label {
    color: #6b7280;
    font-size: 0.9rem;
    font-weight: 500;
}

.payment-detail .value {
    font-weight: 700;
    color: #1f2937;
    font-size: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.payment-detail .value.highlight {
    color: #dc2626;
    font-family: 'Courier New', monospace;
    font-size: 1.15rem;
    font-weight: 800;
}

.copy-btn {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: white;
    border: none;
    padding: 0.4rem 0.85rem;
    border-radius: 8px;
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    box-shadow: 0 2px 6px rgba(59, 130, 246, 0.3);
}

.copy-btn:hover {
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
}

.payment-note {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    border: 2px solid #f59e0b;
    border-radius: 12px;
    padding: 1.25rem;
    margin-bottom: 1.5rem;
}

.payment-note p {
    margin: 0;
    color: #92400e;
    font-size: 0.95rem;
    font-weight: 500;
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    line-height: 1.5;
}

.payment-note i {
    color: #f59e0b;
    margin-top: 3px;
    font-size: 1.1rem;
}

.auto-check-status {
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
    border: 2px solid #3b82f6;
    border-radius: 12px;
    padding: 1.25rem;
    margin-bottom: 1.25rem;
    text-align: center;
}

.checking-animation {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    color: #1d4ed8;
    font-weight: 700;
    font-size: 1rem;
}

.checking-animation i {
    font-size: 1.5rem;
    color: #3b82f6;
}

.confirm-payment-btn {
    width: 100%;
    padding: 1.25rem;
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 1.1rem;
    font-weight: 700;
    cursor: pointer;
    margin-bottom: 0.75rem;
    box-shadow: 0 4px 15px rgba(34, 197, 94, 0.3);
    transition: all 0.2s;
}

.confirm-payment-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(34, 197, 94, 0.4);
}
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

/* Step 3 Buttons */
.step3-buttons {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.check-history-btn {
    width: 100%;
    padding: 0.85rem;
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    transition: all 0.3s;
}

.check-history-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
}

/* Topup History List */
.topup-history-list {
    max-height: 400px;
    overflow-y: auto;
    margin-bottom: 1rem;
}

.history-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: #f9fafb;
    border-radius: 12px;
    margin-bottom: 0.75rem;
    border-left: 4px solid #d1d5db;
}

.history-item.status-completed {
    border-left-color: #22c55e;
    background: #f0fdf4;
}

.history-item.status-waiting {
    border-left-color: #f59e0b;
    background: #fffbeb;
}

.history-item.status-pending {
    border-left-color: #6b7280;
    background: #f9fafb;
}

.history-item.status-failed {
    border-left-color: #ef4444;
    background: #fef2f2;
}

.history-item.status-expired {
    border-left-color: #9ca3af;
    background: #f3f4f6;
}

.history-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.status-completed .history-icon {
    background: #dcfce7;
    color: #16a34a;
}

.status-waiting .history-icon {
    background: #fef3c7;
    color: #d97706;
}

.status-pending .history-icon {
    background: #e5e7eb;
    color: #6b7280;
}

.status-failed .history-icon {
    background: #fee2e2;
    color: #dc2626;
}

.status-expired .history-icon {
    background: #e5e7eb;
    color: #9ca3af;
}

.history-info {
    flex: 1;
    min-width: 0;
}

.history-amount {
    font-weight: 700;
    color: #1f2937;
    font-size: 1.1rem;
}

.history-code {
    font-size: 0.8rem;
    color: #6b7280;
    font-family: monospace;
    margin-top: 0.2rem;
}

.history-date {
    font-size: 0.75rem;
    color: #9ca3af;
    margin-top: 0.2rem;
}

.history-status {
    flex-shrink: 0;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.3rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.status-badge.status-completed {
    background: #dcfce7;
    color: #16a34a;
}

.status-badge.status-waiting {
    background: #fef3c7;
    color: #92400e;
}

.status-badge.status-pending {
    background: #e5e7eb;
    color: #6b7280;
}

.status-badge.status-failed {
    background: #fee2e2;
    color: #dc2626;
}

.status-badge.status-expired {
    background: #e5e7eb;
    color: #9ca3af;
}

.no-history {
    text-align: center;
    padding: 2rem;
    color: #6b7280;
}

.no-history i {
    font-size: 2.5rem;
    margin-bottom: 0.75rem;
    opacity: 0.5;
}

.no-history p {
    margin: 0;
}

.loading-spinner {
    text-align: center;
    padding: 2rem;
    color: #6b7280;
}

.loading-spinner i {
    font-size: 1.5rem;
    margin-right: 0.5rem;
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
