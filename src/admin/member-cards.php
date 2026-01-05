<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    echo '<script>window.location.href = "login.php";</script>';
    exit;
}

$db = new Database();
$conn = $db->connect();

$success = '';
$error = '';

// Xử lý tạo thẻ mới
if (isset($_POST['create_card'])) {
    $customer_id = intval($_POST['customer_id']);
    $initial_balance = floatval($_POST['initial_balance'] ?? 0);
    
    // Kiểm tra khách hàng đã có thẻ chưa
    $stmt = $conn->prepare("SELECT id FROM member_cards WHERE customer_id = ?");
    $stmt->execute([$customer_id]);
    if ($stmt->fetch()) {
        $error = 'Khách hàng này đã có thẻ thành viên!';
    } else {
        // Tạo số thẻ ngẫu nhiên
        $card_number = 'NG' . date('y') . str_pad(rand(0, 99999999), 8, '0', STR_PAD_LEFT);
        $card_pin = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        
        $stmt = $conn->prepare("INSERT INTO member_cards (customer_id, card_number, card_pin, balance, total_deposited) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$customer_id, $card_number, $card_pin, $initial_balance, $initial_balance]);
        $card_id = $conn->lastInsertId();
        
        // Ghi lịch sử nạp tiền ban đầu
        if ($initial_balance > 0) {
            $stmt = $conn->prepare("INSERT INTO card_transactions (card_id, type, amount, balance_before, balance_after, description, admin_id) VALUES (?, 'deposit', ?, 0, ?, 'Nạp tiền khi tạo thẻ', ?)");
            $stmt->execute([$card_id, $initial_balance, $initial_balance, $_SESSION['admin_id']]);
        }
        
        $success = "Tạo thẻ thành công! Số thẻ: <strong>$card_number</strong> - PIN: <strong>$card_pin</strong>";
    }
}

// Xử lý nạp tiền
if (isset($_POST['deposit'])) {
    $card_id = intval($_POST['card_id']);
    $amount = floatval($_POST['amount']);
    
    if ($amount <= 0) {
        $error = 'Số tiền nạp phải lớn hơn 0!';
    } else {
        // Lấy số dư hiện tại
        $stmt = $conn->prepare("SELECT balance FROM member_cards WHERE id = ?");
        $stmt->execute([$card_id]);
        $card = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($card) {
            $balance_before = $card['balance'];
            $balance_after = $balance_before + $amount;
            
            // Cập nhật số dư
            $stmt = $conn->prepare("UPDATE member_cards SET balance = balance + ?, total_deposited = total_deposited + ? WHERE id = ?");
            $stmt->execute([$amount, $amount, $card_id]);
            
            // Ghi lịch sử
            $stmt = $conn->prepare("INSERT INTO card_transactions (card_id, type, amount, balance_before, balance_after, description, admin_id) VALUES (?, 'deposit', ?, ?, ?, 'Nạp tiền bởi Admin', ?)");
            $stmt->execute([$card_id, $amount, $balance_before, $balance_after, $_SESSION['admin_id']]);
            
            $success = 'Nạp tiền thành công! Số dư mới: ' . number_format($balance_after) . 'đ';
        }
    }
}

// Xử lý khóa/mở khóa thẻ
if (isset($_GET['action']) && isset($_GET['id'])) {
    $card_id = intval($_GET['id']);
    if ($_GET['action'] == 'block') {
        $conn->prepare("UPDATE member_cards SET status = 'blocked' WHERE id = ?")->execute([$card_id]);
        $success = 'Đã khóa thẻ!';
    } elseif ($_GET['action'] == 'activate') {
        $conn->prepare("UPDATE member_cards SET status = 'active' WHERE id = ?")->execute([$card_id]);
        $success = 'Đã kích hoạt thẻ!';
    }
}

// Lấy danh sách thẻ
$cards = $conn->query("
    SELECT mc.*, c.full_name, c.email, c.phone,
           (SELECT COUNT(*) FROM card_transactions WHERE card_id = mc.id) as transaction_count
    FROM member_cards mc
    JOIN customers c ON mc.customer_id = c.id
    ORDER BY mc.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Lấy danh sách khách hàng chưa có thẻ
$customers_without_card = $conn->query("
    SELECT c.id, c.full_name, c.email, c.phone
    FROM customers c
    LEFT JOIN member_cards mc ON c.id = mc.customer_id
    WHERE mc.id IS NULL
    ORDER BY c.full_name
")->fetchAll(PDO::FETCH_ASSOC);

// Thống kê
$stats = $conn->query("
    SELECT 
        COUNT(*) as total_cards,
        SUM(balance) as total_balance,
        SUM(total_deposited) as total_deposited,
        SUM(total_spent) as total_spent,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_cards
    FROM member_cards
")->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Thẻ Thành Viên - Admin</title>
    <link rel="icon" type="image/jpeg" href="../assets/images/logo.jpg">
    <link rel="shortcut icon" type="image/jpeg" href="../assets/images/logo.jpg">
    <link rel="stylesheet" href="../assets/css/admin-dark-modern.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    body { background: #f8fafc !important; }
    .main-content { background: #f8fafc !important; padding: 2rem; }
    
    .page-header {
        display: flex; justify-content: space-between; align-items: center;
        margin-bottom: 2rem; padding-bottom: 1.5rem; border-bottom: 2px solid #e2e8f0;
    }
    .page-header h1 {
        color: #1f2937 !important; font-size: 1.75rem; font-weight: 800;
        display: flex; align-items: center; gap: 0.75rem; margin: 0;
    }
    .page-header h1 i { color: #8b5cf6; }
    
    .stats-grid {
        display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1.5rem;
    }
    .stat-card {
        background: white; border-radius: 14px; padding: 1.25rem 1.5rem;
        display: flex; align-items: center; gap: 1rem;
        border: 1px solid #e5e7eb; box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    }
    .stat-icon {
        width: 52px; height: 52px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.3rem; color: white;
    }
    .stat-icon.purple { background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); }
    .stat-icon.green { background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); }
    .stat-icon.blue { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); }
    .stat-icon.orange { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
    .stat-content h3 { font-size: 1.5rem; font-weight: 800; color: #1f2937; margin: 0; }
    .stat-content p { color: #6b7280; font-size: 0.85rem; margin: 0.2rem 0 0; }
    
    .card {
        background: white; border-radius: 16px; border: 1px solid #e5e7eb;
        margin-bottom: 1.5rem; overflow: hidden;
    }
    .card-header {
        padding: 1.25rem 1.5rem; border-bottom: 1px solid #f3f4f6;
        display: flex; align-items: center; gap: 0.75rem;
    }
    .card-header h2 { margin: 0; font-size: 1.1rem; font-weight: 700; color: #1f2937; }
    .card-header i { color: #8b5cf6; }
    .card-body { padding: 1.5rem; }
    
    .form-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; }
    .form-group { margin-bottom: 1rem; }
    .form-group label { display: block; font-weight: 600; color: #374151; margin-bottom: 0.5rem; font-size: 0.9rem; }
    .form-group input, .form-group select {
        width: 100%; padding: 0.75rem 1rem; border: 2px solid #e5e7eb;
        border-radius: 10px; font-size: 0.95rem; transition: all 0.2s;
    }
    .form-group input:focus, .form-group select:focus {
        outline: none; border-color: #8b5cf6; box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
    }
    
    .btn {
        padding: 0.75rem 1.5rem; border-radius: 10px; font-weight: 600;
        cursor: pointer; border: none; transition: all 0.2s;
        display: inline-flex; align-items: center; gap: 0.5rem;
    }
    .btn-primary { background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); color: white; }
    .btn-success { background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); color: white; }
    .btn-danger { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; }
    .btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
    
    .data-table { width: 100%; border-collapse: collapse; }
    .data-table th {
        background: #f9fafb; padding: 1rem; text-align: left;
        font-size: 0.8rem; font-weight: 700; color: #6b7280;
        text-transform: uppercase; letter-spacing: 0.5px;
    }
    .data-table td { padding: 1rem; border-bottom: 1px solid #f3f4f6; color: #374151; }
    .data-table tbody tr:hover { background: #faf5ff; }
    
    .card-number {
        font-family: 'Courier New', monospace; font-weight: 700;
        color: #8b5cf6; font-size: 0.95rem; letter-spacing: 1px;
    }
    .balance { font-weight: 700; color: #22c55e; font-size: 1rem; }
    
    .status-badge {
        padding: 0.3rem 0.75rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600;
    }
    .status-active { background: #dcfce7; color: #15803d; }
    .status-blocked { background: #fee2e2; color: #dc2626; }
    
    .action-btn {
        width: 32px; height: 32px; border-radius: 8px; border: none;
        cursor: pointer; display: inline-flex; align-items: center; justify-content: center;
        font-size: 0.85rem; transition: all 0.2s; margin-right: 4px;
    }
    .action-btn:hover { transform: scale(1.1); }
    .btn-deposit { background: #dcfce7; color: #16a34a; }
    .btn-view { background: #dbeafe; color: #2563eb; }
    .btn-block { background: #fee2e2; color: #dc2626; }
    .btn-activate { background: #d1fae5; color: #059669; }
    
    .alert {
        padding: 1rem 1.5rem; border-radius: 10px; margin-bottom: 1rem;
        display: flex; align-items: center; gap: 0.75rem;
    }
    .alert-success { background: #dcfce7; color: #15803d; border: 1px solid #86efac; }
    .alert-error { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }
    
    /* Modal */
    .modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 1000; }
    .modal.active { display: flex; align-items: center; justify-content: center; }
    .modal-overlay { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); }
    .modal-content {
        position: relative; background: white; border-radius: 16px;
        max-width: 500px; width: 90%; padding: 2rem; z-index: 1;
    }
    .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
    .modal-header h3 { margin: 0; font-size: 1.25rem; color: #1f2937; }
    .modal-close { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #6b7280; }
    
    @media (max-width: 1200px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 768px) { .stats-grid, .form-row { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-credit-card"></i> Quản lý Thẻ Thành Viên</h1>
        </div>
        
        <?php if ($success): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>
        
        <!-- Thống kê -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon purple"><i class="fas fa-credit-card"></i></div>
                <div class="stat-content">
                    <h3><?php echo $stats['total_cards'] ?? 0; ?></h3>
                    <p>Tổng số thẻ</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-wallet"></i></div>
                <div class="stat-content">
                    <h3><?php echo number_format($stats['total_balance'] ?? 0); ?>đ</h3>
                    <p>Tổng số dư</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-arrow-up"></i></div>
                <div class="stat-content">
                    <h3><?php echo number_format($stats['total_deposited'] ?? 0); ?>đ</h3>
                    <p>Tổng nạp</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon orange"><i class="fas fa-shopping-cart"></i></div>
                <div class="stat-content">
                    <h3><?php echo number_format($stats['total_spent'] ?? 0); ?>đ</h3>
                    <p>Tổng chi tiêu</p>
                </div>
            </div>
        </div>
        
        <!-- Form tạo thẻ mới -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-plus-circle"></i>
                <h2>Tạo thẻ mới</h2>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-user"></i> Khách hàng</label>
                            <select name="customer_id" required>
                                <option value="">-- Chọn khách hàng --</option>
                                <?php foreach ($customers_without_card as $c): ?>
                                <option value="<?php echo $c['id']; ?>">
                                    <?php echo htmlspecialchars($c['full_name']); ?> - <?php echo $c['phone']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-money-bill"></i> Số tiền nạp ban đầu</label>
                            <input type="number" name="initial_balance" value="0" min="0" step="1000">
                        </div>
                        <div class="form-group" style="display: flex; align-items: flex-end;">
                            <button type="submit" name="create_card" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Tạo thẻ
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Danh sách thẻ -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-list"></i>
                <h2>Danh sách thẻ (<?php echo count($cards); ?>)</h2>
            </div>
            <div class="card-body" style="padding: 0;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Số thẻ</th>
                            <th>Khách hàng</th>
                            <th>Số dư</th>
                            <th>Đã nạp</th>
                            <th>Đã chi</th>
                            <th>Trạng thái</th>
                            <th>Ngày tạo</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($cards)): ?>
                        <tr><td colspan="8" style="text-align: center; padding: 2rem; color: #6b7280;">Chưa có thẻ nào</td></tr>
                        <?php else: ?>
                        <?php foreach ($cards as $card): ?>
                        <tr>
                            <td>
                                <span class="card-number"><?php echo $card['card_number']; ?></span>
                                <br><small style="color:#6b7280;">PIN: <?php echo $card['card_pin']; ?></small>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($card['full_name']); ?></strong>
                                <br><small style="color:#6b7280;"><?php echo $card['phone']; ?></small>
                            </td>
                            <td><span class="balance"><?php echo number_format($card['balance']); ?>đ</span></td>
                            <td><?php echo number_format($card['total_deposited']); ?>đ</td>
                            <td><?php echo number_format($card['total_spent']); ?>đ</td>
                            <td>
                                <span class="status-badge status-<?php echo $card['status']; ?>">
                                    <?php echo $card['status'] == 'active' ? 'Hoạt động' : 'Đã khóa'; ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($card['created_at'])); ?></td>
                            <td>
                                <button class="action-btn btn-deposit" onclick="openDepositModal(<?php echo $card['id']; ?>, '<?php echo $card['card_number']; ?>')" title="Nạp tiền">
                                    <i class="fas fa-plus"></i>
                                </button>
                                <a href="card-transactions.php?card_id=<?php echo $card['id']; ?>" class="action-btn btn-view" title="Lịch sử">
                                    <i class="fas fa-history"></i>
                                </a>
                                <?php if ($card['status'] == 'active'): ?>
                                <a href="?action=block&id=<?php echo $card['id']; ?>" class="action-btn btn-block" title="Khóa thẻ" onclick="return confirm('Khóa thẻ này?')">
                                    <i class="fas fa-lock"></i>
                                </a>
                                <?php else: ?>
                                <a href="?action=activate&id=<?php echo $card['id']; ?>" class="action-btn btn-activate" title="Kích hoạt">
                                    <i class="fas fa-unlock"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Modal Nạp tiền -->
    <div class="modal" id="depositModal">
        <div class="modal-overlay" onclick="closeDepositModal()"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-plus-circle" style="color:#22c55e;"></i> Nạp tiền vào thẻ</h3>
                <button class="modal-close" onclick="closeDepositModal()">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="card_id" id="depositCardId">
                <div class="form-group">
                    <label>Số thẻ</label>
                    <input type="text" id="depositCardNumber" readonly style="background:#f3f4f6;">
                </div>
                <div class="form-group">
                    <label>Số tiền nạp (VNĐ)</label>
                    <input type="number" name="amount" required min="1000" step="1000" placeholder="Nhập số tiền...">
                </div>
                <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                    <button type="button" onclick="closeDepositModal()" class="btn" style="background:#e5e7eb;color:#374151;flex:1;">Hủy</button>
                    <button type="submit" name="deposit" class="btn btn-success" style="flex:1;">
                        <i class="fas fa-check"></i> Nạp tiền
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    function openDepositModal(cardId, cardNumber) {
        document.getElementById('depositCardId').value = cardId;
        document.getElementById('depositCardNumber').value = cardNumber;
        document.getElementById('depositModal').classList.add('active');
    }
    
    function closeDepositModal() {
        document.getElementById('depositModal').classList.remove('active');
    }
    </script>
</body>
</html>
