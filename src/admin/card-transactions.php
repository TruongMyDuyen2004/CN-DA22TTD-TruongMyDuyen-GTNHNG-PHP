<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    echo '<script>window.location.href = "login.php";</script>';
    exit;
}

$db = new Database();
$conn = $db->connect();

$card_id = isset($_GET['card_id']) ? intval($_GET['card_id']) : 0;

// Lấy thông tin thẻ
$card = null;
$customer = null;
if ($card_id > 0) {
    $stmt = $conn->prepare("
        SELECT mc.*, c.full_name, c.email, c.phone
        FROM member_cards mc
        JOIN customers c ON mc.customer_id = c.id
        WHERE mc.id = ?
    ");
    $stmt->execute([$card_id]);
    $card = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$card) {
    echo '<script>window.location.href = "member-cards.php";</script>';
    exit;
}

// Lấy lịch sử giao dịch
$transactions = $conn->prepare("
    SELECT ct.*, o.order_number
    FROM card_transactions ct
    LEFT JOIN orders o ON ct.order_id = o.id
    WHERE ct.card_id = ?
    ORDER BY ct.created_at DESC
");
$transactions->execute([$card_id]);
$transactions = $transactions->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch sử giao dịch - <?php echo $card['card_number']; ?></title>
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
        color: #1f2937 !important; font-size: 1.5rem; font-weight: 800;
        display: flex; align-items: center; gap: 0.75rem; margin: 0;
    }
    .page-header h1 i { color: #8b5cf6; }
    
    .btn-back {
        padding: 0.6rem 1.25rem; background: white; color: #374151;
        border: 2px solid #e5e7eb; border-radius: 10px; font-weight: 600;
        text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem;
    }
    .btn-back:hover { border-color: #8b5cf6; color: #8b5cf6; }
    
    .card-info-box {
        background: linear-gradient(135deg, #7c3aed 0%, #5b21b6 100%);
        border-radius: 16px; padding: 1.5rem; color: white; margin-bottom: 1.5rem;
        display: flex; justify-content: space-between; align-items: center;
    }
    .card-info-box .card-number {
        font-family: 'Courier New', monospace; font-size: 1.25rem;
        letter-spacing: 2px; margin-bottom: 0.5rem;
    }
    .card-info-box .customer-name { opacity: 0.9; font-size: 0.95rem; }
    .card-info-box .balance-section { text-align: right; }
    .card-info-box .balance-label { font-size: 0.8rem; opacity: 0.8; }
    .card-info-box .balance-amount { font-size: 2rem; font-weight: 700; }
    
    .card {
        background: white; border-radius: 16px; border: 1px solid #e5e7eb;
        overflow: hidden;
    }
    .card-header {
        padding: 1.25rem 1.5rem; border-bottom: 1px solid #f3f4f6;
        display: flex; align-items: center; gap: 0.75rem;
    }
    .card-header h2 { margin: 0; font-size: 1.1rem; font-weight: 700; color: #1f2937; }
    .card-header i { color: #8b5cf6; }
    
    .data-table { width: 100%; border-collapse: collapse; }
    .data-table th {
        background: #f9fafb; padding: 1rem; text-align: left;
        font-size: 0.8rem; font-weight: 700; color: #6b7280;
        text-transform: uppercase;
    }
    .data-table td { padding: 1rem; border-bottom: 1px solid #f3f4f6; color: #374151; }
    .data-table tbody tr:hover { background: #faf5ff; }
    
    .type-badge {
        padding: 0.3rem 0.75rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600;
    }
    .type-deposit { background: #dcfce7; color: #15803d; }
    .type-payment { background: #fee2e2; color: #dc2626; }
    .type-refund { background: #dbeafe; color: #1d4ed8; }
    
    .amount-positive { color: #16a34a; font-weight: 700; }
    .amount-negative { color: #dc2626; font-weight: 700; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-history"></i> Lịch sử giao dịch</h1>
            <a href="member-cards.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Quay lại
            </a>
        </div>
        
        <!-- Thông tin thẻ -->
        <div class="card-info-box">
            <div>
                <div class="card-number"><?php echo $card['card_number']; ?></div>
                <div class="customer-name">
                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($card['full_name']); ?> - <?php echo $card['phone']; ?>
                </div>
            </div>
            <div class="balance-section">
                <div class="balance-label">Số dư hiện tại</div>
                <div class="balance-amount"><?php echo number_format($card['balance']); ?>đ</div>
            </div>
        </div>
        
        <!-- Lịch sử giao dịch -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-list"></i>
                <h2>Lịch sử giao dịch (<?php echo count($transactions); ?>)</h2>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Thời gian</th>
                        <th>Loại</th>
                        <th>Số tiền</th>
                        <th>Số dư trước</th>
                        <th>Số dư sau</th>
                        <th>Mô tả</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                    <tr><td colspan="6" style="text-align: center; padding: 2rem; color: #6b7280;">Chưa có giao dịch nào</td></tr>
                    <?php else: ?>
                    <?php foreach ($transactions as $tx): ?>
                    <tr>
                        <td><?php echo date('d/m/Y H:i', strtotime($tx['created_at'])); ?></td>
                        <td>
                            <?php
                            $type_labels = [
                                'deposit' => ['Nạp tiền', 'type-deposit'],
                                'payment' => ['Thanh toán', 'type-payment'],
                                'refund' => ['Hoàn tiền', 'type-refund']
                            ];
                            $type = $type_labels[$tx['type']] ?? ['Khác', ''];
                            ?>
                            <span class="type-badge <?php echo $type[1]; ?>"><?php echo $type[0]; ?></span>
                        </td>
                        <td>
                            <?php if ($tx['type'] == 'deposit' || $tx['type'] == 'refund'): ?>
                            <span class="amount-positive">+<?php echo number_format($tx['amount']); ?>đ</span>
                            <?php else: ?>
                            <span class="amount-negative">-<?php echo number_format($tx['amount']); ?>đ</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo number_format($tx['balance_before']); ?>đ</td>
                        <td><?php echo number_format($tx['balance_after']); ?>đ</td>
                        <td>
                            <?php echo htmlspecialchars($tx['description']); ?>
                            <?php if ($tx['order_number']): ?>
                            <br><small style="color:#6b7280;">Đơn: <?php echo $tx['order_number']; ?></small>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
