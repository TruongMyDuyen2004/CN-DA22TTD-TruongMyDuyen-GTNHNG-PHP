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

// Xử lý duyệt yêu cầu nạp tiền
if (isset($_POST['approve_topup'])) {
    $request_id = intval($_POST['request_id']);
    
    try {
        // Lấy thông tin yêu cầu
        $stmt = $conn->prepare("
            SELECT tr.*, mc.balance, mc.id as card_id
            FROM topup_requests tr
            JOIN member_cards mc ON tr.card_id = mc.id
            WHERE tr.id = ? AND tr.status = 'waiting'
        ");
        $stmt->execute([$request_id]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$request) {
            $error = 'Không tìm thấy yêu cầu hoặc yêu cầu đã được xử lý!';
        } else {
            $conn->beginTransaction();
            
            $balance_before = $request['balance'];
            $balance_after = $balance_before + $request['amount'];
            
            // Cập nhật số dư thẻ
            $stmt = $conn->prepare("UPDATE member_cards SET balance = balance + ?, total_deposited = total_deposited + ? WHERE id = ?");
            $stmt->execute([$request['amount'], $request['amount'], $request['card_id']]);
            
            // Ghi lịch sử giao dịch
            $method_names = ['momo' => 'Momo', 'zalopay' => 'ZaloPay', 'bank' => 'Chuyển khoản'];
            $description = 'Nạp tiền qua ' . ($method_names[$request['method']] ?? $request['method']) . ' - ' . $request['transaction_code'];
            
            $stmt = $conn->prepare("INSERT INTO card_transactions (card_id, type, amount, balance_before, balance_after, description, admin_id) VALUES (?, 'deposit', ?, ?, ?, ?, ?)");
            $stmt->execute([$request['card_id'], $request['amount'], $balance_before, $balance_after, $description, $_SESSION['admin_id']]);
            
            // Cập nhật trạng thái yêu cầu
            $stmt = $conn->prepare("UPDATE topup_requests SET status = 'completed', completed_at = NOW() WHERE id = ?");
            $stmt->execute([$request_id]);
            
            $conn->commit();
            $success = 'Đã duyệt yêu cầu nạp ' . number_format($request['amount']) . 'đ thành công!';
        }
    } catch (Exception $e) {
        $conn->rollBack();
        $error = 'Lỗi: ' . $e->getMessage();
    }
}

// Xử lý từ chối yêu cầu
if (isset($_POST['reject_topup'])) {
    $request_id = intval($_POST['request_id']);
    $reason = trim($_POST['reject_reason'] ?? 'Không xác nhận được giao dịch');
    
    $stmt = $conn->prepare("UPDATE topup_requests SET status = 'failed', payment_info = ? WHERE id = ? AND status = 'waiting'");
    $stmt->execute([$reason, $request_id]);
    
    if ($stmt->rowCount() > 0) {
        $success = 'Đã từ chối yêu cầu nạp tiền!';
    } else {
        $error = 'Không tìm thấy yêu cầu hoặc yêu cầu đã được xử lý!';
    }
}

// Lọc theo trạng thái
$status_filter = $_GET['status'] ?? 'waiting';

// Lấy danh sách yêu cầu nạp tiền
$sql = "
    SELECT tr.*, c.full_name, c.phone, c.email, mc.card_number
    FROM topup_requests tr
    JOIN customers c ON tr.customer_id = c.id
    JOIN member_cards mc ON tr.card_id = mc.id
";
if ($status_filter && $status_filter != 'all') {
    $sql .= " WHERE tr.status = :status";
}
$sql .= " ORDER BY tr.created_at DESC LIMIT 100";

$stmt = $conn->prepare($sql);
if ($status_filter && $status_filter != 'all') {
    $stmt->bindParam(':status', $status_filter);
}
$stmt->execute();
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Đếm số yêu cầu chờ duyệt
$waiting_count = $conn->query("SELECT COUNT(*) FROM topup_requests WHERE status = 'waiting'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yêu cầu nạp tiền - Admin</title>
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
    .page-header h1 i { color: #f59e0b; }
    
    .waiting-badge {
        background: #fef3c7; color: #92400e; padding: 0.3rem 0.75rem;
        border-radius: 20px; font-size: 0.85rem; font-weight: 600;
        margin-left: 0.5rem;
    }
    
    .filter-tabs {
        display: flex; gap: 0.5rem; margin-bottom: 1.5rem;
    }
    .filter-tab {
        padding: 0.6rem 1.25rem; border-radius: 10px; text-decoration: none;
        font-weight: 600; font-size: 0.9rem; transition: all 0.2s;
        border: 2px solid #e5e7eb; color: #6b7280; background: white;
    }
    .filter-tab:hover { border-color: #d1d5db; }
    .filter-tab.active { background: #f59e0b; color: white; border-color: #f59e0b; }
    .filter-tab.active.completed { background: #22c55e; border-color: #22c55e; }
    .filter-tab.active.failed { background: #ef4444; border-color: #ef4444; }
    
    .card {
        background: white; border-radius: 16px; border: 1px solid #e5e7eb;
        overflow: hidden;
    }
    
    .data-table { width: 100%; border-collapse: collapse; }
    .data-table th {
        background: #f9fafb; padding: 1rem; text-align: left;
        font-size: 0.8rem; font-weight: 700; color: #6b7280;
        text-transform: uppercase; letter-spacing: 0.5px;
    }
    .data-table td { padding: 1rem; border-bottom: 1px solid #f3f4f6; color: #374151; }
    .data-table tbody tr:hover { background: #fffbeb; }
    
    .tx-code {
        font-family: monospace; font-weight: 700; color: #8b5cf6;
        font-size: 0.9rem; letter-spacing: 0.5px;
    }
    .amount { font-weight: 700; color: #22c55e; font-size: 1.05rem; }
    
    .method-badge {
        padding: 0.3rem 0.75rem; border-radius: 8px; font-size: 0.8rem; font-weight: 600;
        display: inline-flex; align-items: center; gap: 0.3rem;
    }
    .method-momo { background: #fce7f3; color: #be185d; }
    .method-zalopay { background: #dbeafe; color: #1d4ed8; }
    .method-bank { background: #d1fae5; color: #047857; }
    
    .status-badge {
        padding: 0.3rem 0.75rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600;
    }
    .status-pending { background: #e5e7eb; color: #6b7280; }
    .status-waiting { background: #fef3c7; color: #92400e; }
    .status-completed { background: #dcfce7; color: #15803d; }
    .status-failed { background: #fee2e2; color: #dc2626; }
    
    .btn {
        padding: 0.5rem 1rem; border-radius: 8px; font-weight: 600;
        cursor: pointer; border: none; transition: all 0.2s;
        display: inline-flex; align-items: center; gap: 0.4rem; font-size: 0.85rem;
    }
    .btn-approve { background: #22c55e; color: white; }
    .btn-approve:hover { background: #16a34a; }
    .btn-reject { background: #fee2e2; color: #dc2626; }
    .btn-reject:hover { background: #fecaca; }
    
    .alert {
        padding: 1rem 1.5rem; border-radius: 10px; margin-bottom: 1rem;
        display: flex; align-items: center; gap: 0.75rem;
    }
    .alert-success { background: #dcfce7; color: #15803d; border: 1px solid #86efac; }
    .alert-error { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }
    
    .empty-state {
        text-align: center; padding: 3rem; color: #6b7280;
    }
    .empty-state i { font-size: 3rem; margin-bottom: 1rem; opacity: 0.5; }
    
    /* Modal */
    .modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 1000; }
    .modal.active { display: flex; align-items: center; justify-content: center; }
    .modal-overlay { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); }
    .modal-content {
        position: relative; background: white; border-radius: 16px;
        max-width: 450px; width: 90%; padding: 2rem; z-index: 1;
    }
    .modal-header { margin-bottom: 1.5rem; }
    .modal-header h3 { margin: 0; font-size: 1.25rem; color: #1f2937; }
    .form-group { margin-bottom: 1rem; }
    .form-group label { display: block; font-weight: 600; color: #374151; margin-bottom: 0.5rem; }
    .form-group textarea {
        width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb;
        border-radius: 10px; font-size: 0.95rem; resize: vertical; min-height: 80px;
    }
    .modal-actions { display: flex; gap: 1rem; margin-top: 1.5rem; }
    .modal-actions .btn { flex: 1; justify-content: center; padding: 0.75rem; }
    .btn-cancel { background: #e5e7eb; color: #374151; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1>
                <i class="fas fa-money-bill-wave"></i> Yêu cầu nạp tiền
                <?php if ($waiting_count > 0): ?>
                <span class="waiting-badge"><?php echo $waiting_count; ?> chờ duyệt</span>
                <?php endif; ?>
            </h1>
        </div>
        
        <?php if ($success): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>
        
        <!-- Filter Tabs -->
        <div class="filter-tabs">
            <a href="?status=waiting" class="filter-tab <?php echo $status_filter == 'waiting' ? 'active' : ''; ?>">
                <i class="fas fa-clock"></i> Chờ duyệt
            </a>
            <a href="?status=completed" class="filter-tab <?php echo $status_filter == 'completed' ? 'active completed' : ''; ?>">
                <i class="fas fa-check"></i> Đã duyệt
            </a>
            <a href="?status=failed" class="filter-tab <?php echo $status_filter == 'failed' ? 'active failed' : ''; ?>">
                <i class="fas fa-times"></i> Từ chối
            </a>
            <a href="?status=all" class="filter-tab <?php echo $status_filter == 'all' ? 'active' : ''; ?>">
                <i class="fas fa-list"></i> Tất cả
            </a>
        </div>
        
        <!-- Danh sách yêu cầu -->
        <div class="card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Mã GD</th>
                        <th>Khách hàng</th>
                        <th>Số thẻ</th>
                        <th>Số tiền</th>
                        <th>Phương thức</th>
                        <th>Trạng thái</th>
                        <th>Thời gian</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($requests)): ?>
                    <tr>
                        <td colspan="8">
                            <div class="empty-state">
                                <i class="fas fa-inbox"></i>
                                <p>Không có yêu cầu nào</p>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($requests as $req): ?>
                    <tr>
                        <td><span class="tx-code"><?php echo $req['transaction_code']; ?></span></td>
                        <td>
                            <strong><?php echo htmlspecialchars($req['full_name']); ?></strong>
                            <br><small style="color:#6b7280;"><?php echo $req['phone']; ?></small>
                        </td>
                        <td><span style="font-family:monospace;"><?php echo $req['card_number']; ?></span></td>
                        <td><span class="amount"><?php echo number_format($req['amount']); ?>đ</span></td>
                        <td>
                            <?php
                            $method_class = 'method-' . $req['method'];
                            $method_icons = ['momo' => 'fa-wallet', 'zalopay' => 'fa-mobile-alt', 'bank' => 'fa-university'];
                            $method_names = ['momo' => 'Momo', 'zalopay' => 'ZaloPay', 'bank' => 'Ngân hàng'];
                            ?>
                            <span class="method-badge <?php echo $method_class; ?>">
                                <i class="fas <?php echo $method_icons[$req['method']] ?? 'fa-credit-card'; ?>"></i>
                                <?php echo $method_names[$req['method']] ?? $req['method']; ?>
                            </span>
                        </td>
                        <td>
                            <?php
                            $status_labels = ['pending' => 'Đang tạo', 'waiting' => 'Chờ duyệt', 'completed' => 'Đã duyệt', 'failed' => 'Từ chối'];
                            ?>
                            <span class="status-badge status-<?php echo $req['status']; ?>">
                                <?php echo $status_labels[$req['status']] ?? $req['status']; ?>
                            </span>
                        </td>
                        <td>
                            <?php echo date('d/m/Y H:i', strtotime($req['created_at'])); ?>
                            <?php if ($req['completed_at']): ?>
                            <br><small style="color:#22c55e;">Duyệt: <?php echo date('d/m H:i', strtotime($req['completed_at'])); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($req['status'] == 'waiting'): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                                <button type="submit" name="approve_topup" class="btn btn-approve" onclick="return confirm('Xác nhận duyệt nạp <?php echo number_format($req['amount']); ?>đ?')">
                                    <i class="fas fa-check"></i> Duyệt
                                </button>
                            </form>
                            <button type="button" class="btn btn-reject" onclick="openRejectModal(<?php echo $req['id']; ?>, '<?php echo $req['transaction_code']; ?>')">
                                <i class="fas fa-times"></i> Từ chối
                            </button>
                            <?php else: ?>
                            <span style="color:#9ca3af;">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Modal Từ chối -->
    <div class="modal" id="rejectModal">
        <div class="modal-overlay" onclick="closeRejectModal()"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-times-circle" style="color:#ef4444;"></i> Từ chối yêu cầu nạp tiền</h3>
            </div>
            <form method="POST">
                <input type="hidden" name="request_id" id="rejectRequestId">
                <div class="form-group">
                    <label>Mã giao dịch</label>
                    <input type="text" id="rejectTxCode" readonly style="background:#f3f4f6;padding:0.75rem;border:2px solid #e5e7eb;border-radius:10px;width:100%;">
                </div>
                <div class="form-group">
                    <label>Lý do từ chối</label>
                    <textarea name="reject_reason" placeholder="Nhập lý do từ chối...">Không xác nhận được giao dịch chuyển khoản</textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" onclick="closeRejectModal()" class="btn btn-cancel">Hủy</button>
                    <button type="submit" name="reject_topup" class="btn btn-reject">
                        <i class="fas fa-times"></i> Từ chối
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    function openRejectModal(id, code) {
        document.getElementById('rejectRequestId').value = id;
        document.getElementById('rejectTxCode').value = code;
        document.getElementById('rejectModal').classList.add('active');
    }
    
    function closeRejectModal() {
        document.getElementById('rejectModal').classList.remove('active');
    }
    </script>
</body>
</html>
